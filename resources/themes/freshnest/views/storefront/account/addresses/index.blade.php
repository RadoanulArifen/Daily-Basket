@extends('storefront.account.layout')

{{-- Saved Addresses in the design's cards: Default badge, Edit, Set as default, Remove. --}}
@section('title', __('Saved Addresses'))
@section('account_tab', 'addresses')

@section('account')
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px;">
              @foreach ($addresses as $address)
                <div style="display:flex; flex-direction:column; gap:10px; padding:20px; background:#FFFFFF; border:{{ $address->is_default ? '1.5px solid var(--fn-primary)' : '1px solid var(--fn-line)' }}; border-radius:18px;">
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:16px;">{{ $address->label ?: __('Address') }}</strong>
                    @if ($address->is_default)<span style="padding:4px 10px; border-radius:999px; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-size:11.5px; font-weight:700;">{{ __('Default') }}</span>@endif
                  </div>
                  <p style="margin:0; font-size:14px; line-height:1.6; color:#4F5B4F;">
                    {{ $address->name }}<br>{{ $address->address_line }}@if ($address->building), {{ $address->building }}@endif<br>
                    {{ $address->regionLine() }}<br><span dir="ltr">{{ $address->phone }}</span>
                  </p>
                  <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:auto;">
                    <a class="fh-74ffb8" href="{{ store_route('account.addresses.edit', ['address' => $address->id]) }}" style="height:36px; padding:0 14px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); color:var(--fn-primary-dark); font-weight:700; font-size:13px; display:inline-flex; align-items:center;">{{ __('Edit') }}</a>
                    @unless ($address->is_default)
                      <form method="POST" action="{{ store_route('account.addresses.default', ['address' => $address->id]) }}" style="margin:0;">@csrf<button class="fh-bb6aec" type="submit" style="height:36px; padding:0 14px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); background:#FFFFFF; color:var(--fn-primary-dark); font-weight:700; font-size:13px; cursor:pointer;">{{ __('Set as default') }}</button></form>
                    @endunless
                    <form method="POST" action="{{ store_route('account.addresses.destroy', ['address' => $address->id]) }}" x-data @submit="if (! confirm(@js(__('store.addresses.delete_confirm')))) $event.preventDefault()" style="margin:0;">@csrf @method('DELETE')<button type="submit" style="height:36px; padding:0 14px; border:0; border-radius:999px; background:#FDF0EE; color:#D93A2B; font-weight:700; font-size:13px; cursor:pointer;">{{ __('Remove') }}</button></form>
                  </div>
                </div>
              @endforeach
              <a class="fh-74ffb8" href="{{ store_route('account.addresses.create') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; min-height:180px; padding:20px; background:var(--fn-canvas); border:1.5px dashed var(--fn-primary-pale); border-radius:18px; color:var(--fn-primary-dark); font-weight:700; font-size:15px;">
                <span style="display:inline-flex; width:44px; height:44px; border-radius:50%; background:var(--fn-primary); color:#FFFFFF; font-size:22px; align-items:center; justify-content:center;">+</span>
                {{ __('Add a new address') }}
              </a>
            </div>
@endsection
