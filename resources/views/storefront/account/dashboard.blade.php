@extends('storefront.layouts.app')

@section('title', __('store.account.title'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-shop px-4 py-10 sm:px-6 sm:py-14 lg:px-10">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-8">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ __('store.account.hello', ['name' => \Illuminate\Support\Str::before($customer->name, ' ')]) }}</h1>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.account.intro') }}</p>
            </div>
            <form method="POST" action="{{ store_route('logout') }}">
                @csrf
                <x-store.button variant="link" class="inline-flex items-center gap-2">
                    <x-icon name="log-out" class="size-4 rtl:rotate-180" /> {{ __('store.auth.logout') }}
                </x-store.button>
            </form>
        </div>

        @if (session('status'))
            <x-store.alert class="mt-8">{{ session('status') }}</x-store.alert>
        @endif

        <div class="mt-10 grid gap-10 lg:grid-cols-3">
            <section class="lg:col-span-2" aria-labelledby="orders-heading">
                <h2 id="orders-heading" class="text-lg font-semibold">{{ __('store.account.orders') }}</h2>
                @php $recent = $customer->orders()->with('items')->limit(5)->get(); @endphp
                @if ($recent->isEmpty())
                <div class="mt-4 rounded-md border border-dashed border-line px-6 py-12 text-center">
                    <x-icon name="shopping-bag" class="mx-auto size-8 text-stone" />
                    <p class="mt-4 font-medium">{{ __('store.account.no_orders') }}</p>
                    <p class="mt-1 text-[15px] text-ink-soft">{{ __('store.account.no_orders_text') }}</p>
                    <x-store.button :href="store_route('collections.show', ['slug' => 'new-in'])" class="mt-6">{{ __('store.home.shop_new') }}</x-store.button>
                </div>
                @else
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($recent as $order)
                        @include('storefront.account.partials.order-row')
                    @endforeach
                </ul>
                <a href="{{ store_route('account.orders') }}" class="mt-4 inline-block text-[15px] underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.account.all_orders') }}</a>
                @endif
            </section>

            <aside class="space-y-10">
                <section aria-labelledby="details-heading">
                    <h2 id="details-heading" class="text-lg font-semibold">{{ __('store.account.details') }}</h2>
                    <dl class="mt-4 space-y-3 text-[15px]">
                        <div><dt class="text-sm text-stone">{{ __('store.auth.full_name') }}</dt><dd>{{ $customer->name }}</dd></div>
                        <div><dt class="text-sm text-stone">{{ __('store.auth.email') }}</dt><dd class="break-all">{{ $customer->email }}</dd></div>
                        <div><dt class="text-sm text-stone">{{ __('store.auth.phone') }}</dt><dd dir="ltr" class="text-start">{{ $customer->phone ?: '—' }}</dd></div>
                    </dl>
                </section>

                @php $savedCount = app(\App\Services\Wishlist\WishlistService::class)->count(); @endphp
                <section aria-labelledby="wishlist-heading">
                    <h2 id="wishlist-heading" class="text-lg font-semibold">{{ __('store.wishlist.title') }}</h2>
                    <p class="mt-4 text-[15px] text-ink-soft">{{ trans_choice('store.wishlist.count', $savedCount, ['count' => $savedCount]) }}</p>
                    <a href="{{ store_route('wishlist') }}" class="mt-2 inline-block text-[15px] underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.wishlist.view') }}</a>
                </section>

                <section aria-labelledby="addresses-heading">
                    <h2 id="addresses-heading" class="text-lg font-semibold">{{ __('store.account.addresses') }}</h2>
                    @if ($address = $customer->addresses()->first())
                        <address class="mt-4 text-[15px] not-italic leading-relaxed text-ink-soft">{{ $address->name }}<br>{{ $address->address_line }}@if ($address->building), {{ $address->building }}@endif<br>{{ $address->regionLine() }}</address>
                    @else
                        <p class="mt-4 text-[15px] text-ink-soft">{{ __('store.account.no_addresses') }}</p>
                    @endif
                    <a href="{{ store_route('account.addresses') }}" class="mt-2 inline-block text-[15px] underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.addresses.manage') }}</a>
                </section>
            </aside>
        </div>
    </div>
@endsection
