<?php

// SECURITY: admin only (auth:admin + AuthorizeAdminArea, area: admins); every mutating action also
// checks mayAccess('admins'); only a Super Admin (owner) may send a password link to another owner.

namespace App\Http\Controllers\Admin\Team;

use App\Http\Controllers\Admin\AdminController;
use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/** Owner-only: add admins, choose their role, switch them off. */
class AdminUserController extends AdminController
{
    public function index(): View
    {
        return view('admin.team.index', ['admins' => Admin::query()->orderByRaw("role = 'owner' desc")->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.team.form', ['member' => new Admin(['role' => AdminRole::Manager->value])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeArea('admins');
        $data = $this->validated($request);

        // A random password nobody knows; the new admin sets their own from the emailed link.
        $member = Admin::create($data + ['password' => \App\Support\PasswordGenerator::secure((int) config('store.admin_invite_password_length', 32)), 'locale' => app()->getLocale()]);
        $sent = $this->sendSetPasswordLink($member);

        return redirect()->route('admin.admins.index')->with($sent ? 'success' : 'error', __($sent ? 'admin.admins.invited' : 'admin.admins.invite_failed', ['email' => $member->email]));
    }

    public function edit(Admin $admin): View
    {
        return view('admin.team.form', ['member' => $admin]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->authorizeArea('admins');
        $data = $this->validated($request, $admin);

        if ($admin->is($request->user('admin')) && (! $data['is_active'] || $data['role'] !== AdminRole::Owner->value)) {
            throw ValidationException::withMessages(['role' => __('admin.admins.not_yourself')]);
        }

        $this->keepAnOwner($admin, $data['role'] === AdminRole::Owner->value && $data['is_active']);
        $admin->update($data);

        return redirect()->route('admin.admins.index')->with('success', __('admin.admins.saved'));
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        $this->authorizeArea('admins');
        if ($admin->is($request->user('admin'))) {
            return back()->with('error', __('admin.admins.not_yourself'));
        }

        $this->keepAnOwner($admin, false);
        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', __('admin.admins.deleted'));
    }

    public function resend(Admin $admin): RedirectResponse
    {
        $this->authorizeArea('admins');
        $sent = $this->sendSetPasswordLink($admin);

        return back()->with($sent ? 'success' : 'error', __($sent ? 'admin.admins.invited' : 'admin.admins.invite_failed', ['email' => $admin->email]));
    }

    private function validated(Request $request, ?Admin $admin = null): array
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'is_active' => $admin ? $request->boolean('is_active') : true,
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique(Admin::class)->ignore($admin)],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'is_active' => ['boolean'],
        ], [], ['role' => __('admin.admins.fields.role')]);
    }

    /** The store must always keep at least one active owner. */
    private function keepAnOwner(Admin $changing, bool $stillOwner): void
    {
        if ($changing->isOwner() && $changing->is_active && ! $stillOwner) {
            $others = Admin::query()->whereKeyNot($changing->id)->where('role', AdminRole::Owner)->where('is_active', true)->exists();

            if (! $others) {
                throw ValidationException::withMessages(['role' => __('admin.admins.last_owner')]);
            }
        }
    }

    private function sendSetPasswordLink(Admin $admin): bool
    {
        // Only a Super Admin (owner) may send a password link to another owner.
        abort_if($admin->role === \App\Enums\AdminRole::Owner && request()->user('admin')?->role !== \App\Enums\AdminRole::Owner, 403);
        // Only an admin allowed into the admins (team) area may send another admin a password link.
        if (! request()->user('admin')?->mayAccess('admins')) {   // the Team (admins) area
            abort(403);
        }

        try {
            return Password::broker('admins')->sendResetLink(['email' => $admin->email]) === Password::RESET_LINK_SENT;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
