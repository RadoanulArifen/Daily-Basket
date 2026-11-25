<?php

// SECURITY: admin only — routes/admin.php group ['auth:admin', AuthorizeAdminArea] (area: catalog),
// plus middleware() in this class and requireAdmin() in every action. Import tokens are random UUIDs
// kept in the admin's own session flow; no route here is public.

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Catalog\ProductCsv;
use App\Services\Catalog\ProductCsvExporter;
use App\Services\Catalog\ProductCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Products to and from a spreadsheet (CSV). */
class ProductTransferController extends AdminController
{

    /** Second lock inside each action: no admin session, no import/export. */
    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user('admin')?->is_active, 403);
        $this->authorizeArea('catalog');   // import/export belong to the catalog area
    }


    public function export(ProductCsvExporter $exporter): StreamedResponse
    {
        $this->requireAdmin(request());
        return $exporter->download();
    }

    /** An empty file with the right columns and one example row. */
    public function template(): StreamedResponse
    {
        $this->requireAdmin(request());
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ProductCsv::headers());
            $example = ProductCsv::exampleRow();
            fputcsv($out, array_values($example));
            fclose($out);
        }, 'products-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        $this->requireAdmin(request());
        return view('admin.catalog.products.import', ['report' => null, 'token' => null]);
    }

    /** Step 1: upload and check. Nothing is saved yet. */
    public function preview(Request $request, ProductCsvImporter $importer): View|RedirectResponse
    {
        $this->requireAdmin($request);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:'.(int) config('store.imports.max_size_kb', 5120)]], [], ['file' => __('admin.import.file')]);

        $token = app(\App\Services\Catalog\ImportTokenManager::class)->store($request->file('file'));

        try {
            $report = $importer->preview($this->path($token), $request->user('admin'));
        } catch (ValidationException $e) {
            app(\App\Services\Catalog\ImportTokenManager::class)->delete($token);

            return back()->withErrors($e->errors());
        } catch (RuntimeException) {
            return back()->withErrors(['file' => __('admin.import.unreadable')]);
        }

        return view('admin.catalog.products.import', ['report' => $report, 'token' => $report['errors'] ? null : $token]);
    }

    /** Step 2: import the checked file. */
    public function store(Request $request, ProductCsvImporter $importer): RedirectResponse
    {
        $this->requireAdmin($request);
        try {
            $result = app(\App\Services\Catalog\ImportTokenManager::class)->consume(
                (string) $request->input('token'),
                fn (string $path) => $importer->import($path, $request->user('admin')),
            );
        } catch (ValidationException $e) {
            return redirect()->route('admin.products.import')->withErrors($e->errors());
        }

        return redirect()->route('admin.products.index')->with('success', __('admin.import.done', $result));
    }

    private function path(string $token): string
    {
        return app(\App\Services\Catalog\ImportTokenManager::class)->path($token);
    }
}
