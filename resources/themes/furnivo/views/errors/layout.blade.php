{{-- Error pages: the Furnivo design on the storefront, the plain page in the admin.
     @extends cannot sit inside an @if (Blade applies it either way), so each is its own
     view and only one is included. --}}
@include(request()->is(config('store.admin_path'), config('store.admin_path').'/*') ? 'errors.plain' : 'errors.furnivo', ['code' => $code])
