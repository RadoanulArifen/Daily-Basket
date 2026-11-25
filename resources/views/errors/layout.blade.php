{{-- The plain error page. A theme may provide its own errors/layout (see the Furnivo
     theme), which then falls back to errors.plain for admin pages. --}}
@include('errors.plain', ['code' => $code])
