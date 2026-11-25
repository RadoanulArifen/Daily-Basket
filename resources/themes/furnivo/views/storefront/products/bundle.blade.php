{{-- "Often bought together" — markup and styles from the design. Adds the ticked pieces to the bag. --}}
@php
    $items = $bundle->map(function ($p) {
        $v = $p->variants->sortBy(fn ($v) => $v->price->minor)->first()?->setRelation('product', $p);
        if (! $v) {
            return ['ok' => false];
        }
        $shown = app(\App\Services\Currency\CurrencyService::class)->convert($v->price);
        return ['product' => $p, 'variant' => $v, 'minor' => $shown->minor, 'ok' => $v->isPurchasable()];
    })->filter(fn ($i) => $i['ok'])->values();
    $symbol = app(\App\Services\Currency\CurrencyService::class)->current()->symbol;
@endphp
@if ($items->count() > 1)
    <section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(40px, 5vw, 64px) 0px;"
             x-data="{ on: @js($items->pluck('variant.id')->mapWithKeys(fn ($id) => [$id => true])->all()), prices: @js($items->mapWithKeys(fn ($i) => [$i['variant']->id => $i['minor']])->all()), busy: false,
                       get count() { return Object.values(this.on).filter(Boolean).length },
                       get total() { return Object.keys(this.on).filter(k => this.on[k]).reduce((s, k) => s + this.prices[k], 0) },
                       fmt(m) { const v = m / 100; return (Number.isInteger(v) ? v.toLocaleString('en-US') : v.toLocaleString('en-US', { minimumFractionDigits: 2 })) },
                       async addAll() {
                           this.busy = true; let last = null;
                           for (const id of Object.keys(this.on).filter(k => this.on[k])) {
                               const body = new FormData(); body.append('variant_id', id); body.append('quantity', 1);
                               const r = await fetch(@js(store_route('cart.add')), { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body });
                               last = await r.json().catch(() => null);
                           }
                           // As in the design: the selected pieces go in the bag, then the bag page opens.
                           if (last && last.count !== undefined) { window.location.href = @js(store_route('cart')); return; }
                           window.dispatchEvent(new CustomEvent('store-toast', { detail: { message: (last && last.message) || @js(__('store.cart.unavailable')), type: 'error' } }));
                           this.busy = false;
                       } }">
        <div style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px);">
            <h2 style="margin: 0px 0px 26px; font-size: clamp(20px, 2.2vw, 28px); font-weight: 700; letter-spacing: -0.02em;">Often bought together</h2>
            <div class="obt-wrap" style="display: grid; grid-template-columns: 1fr 300px; gap: clamp(24px, 3vw, 44px); align-items: center;">
                <div class="obt-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                    @foreach ($items as $item)
                        @php $id = $item['variant']->id; $media = $item['product']->media->first(); @endphp
                        <div style="position: relative;">
                            <button type="button" @click="on[{{ $id }}] = ! on[{{ $id }}]" :aria-pressed="on[{{ $id }}]" aria-label="Include {{ $item['product']->name }}" style="position: relative; width: 100%; aspect-ratio: 1 / 1; overflow: hidden; background: rgb(239, 236, 231); cursor: pointer; border: none; padding: 0px; display: block;">
                                @if ($media)<img alt="{{ $item['product']->name }}" src="{{ $media->url('medium') }}" loading="lazy" style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: cover;" :style="{ opacity: on[{{ $id }}] ? 1 : 0.45 }">@endif
                                <span style="position: absolute; top: 8px; left: 8px; width: 20px; height: 20px; color: rgb(255, 255, 255); font-size: 11px; display: flex; align-items: center; justify-content: center;"
                                      :style="on[{{ $id }}] ? { boxShadow: 'rgb(29, 29, 29) 0px 0px 0px 2px inset', background: 'rgb(29, 29, 29)' } : { boxShadow: 'rgb(29, 29, 29) 0px 0px 0px 2px inset', background: 'rgb(255, 255, 255)' }"><span x-show="on[{{ $id }}]">✓</span></span>
                            </button>
                            <div style="margin-top: 10px; font-size: 13px; font-weight: 600; color: rgb(32, 32, 32); line-height: 1.3;"><a href="{{ store_route('products.show', ['slug' => $item['product']->slug]) }}" style="color: inherit;"><span>{{ $item['product']->name }}</span></a></div>
                            <div style="margin-top: 3px; font-size: 13px; color: rgb(102, 102, 102);"><x-store.fv-price :money="$item['variant']->price" style="display: inline-flex; align-items: center;" /></div>
                        </div>
                    @endforeach
                </div>
                <div style="border: 1px solid rgb(236, 236, 236); padding: 22px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; align-items: baseline; justify-content: space-between;">
                        <span style="font-size: 14px; color: rgb(102, 102, 102);">Subtotal (<span x-text="count">{{ $items->count() }}</span> items)</span>
                        <span style="font-size: 19px; font-weight: 600; color: rgb(32, 32, 32);"><span style="display: inline-flex; align-items: center;"><span style="font-weight: 600; letter-spacing: 0.02em;">{{ $symbol }}</span><span x-text="fmt(total)"></span></span></span>
                    </div>
                    <button type="button" @click="addAll()" :disabled="busy || ! count" style="height: 48px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">Add selected to cart</button>
                </div>
            </div>
        </div>
    </section>
@endif
