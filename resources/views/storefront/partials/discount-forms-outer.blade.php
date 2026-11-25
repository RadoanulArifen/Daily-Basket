{{-- The real forms for the discount box (kept outside any other form). --}}
@php $formId ??= 'discount-form'; @endphp
<form id="{{ $formId }}" method="POST" action="{{ store_route('cart.discount') }}" class="hidden">@csrf</form>
<form id="{{ $formId }}-remove" method="POST" action="{{ store_route('cart.discount.remove') }}" class="hidden">@csrf @method('DELETE')</form>
