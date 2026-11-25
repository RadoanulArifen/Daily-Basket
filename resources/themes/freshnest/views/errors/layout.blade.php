{{--
    Error pages for the FreshNest theme: the admin keeps the plain page, the storefront gets the
    FreshNest design (errors/freshnest). @extends cannot sit inside an @if, so this includes one.
--}}
@include(request()->is(config('store.admin_path'), config('store.admin_path').'/*') ? 'errors.plain' : 'errors.freshnest', ['code' => $code])
