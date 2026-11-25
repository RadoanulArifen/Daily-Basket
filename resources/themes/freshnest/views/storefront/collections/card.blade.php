{{-- Listing card from the design (Shop, category and search pages; with compact = true, the
     product page's "You may also like", which has no heart or stars). $c = HomeData::card($product). --}}
<article style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 6px 18px rgba(25,75,48,.05);">
              <div style="position:relative; aspect-ratio:1/0.92; background:var(--fn-primary-tint);">
                <a href="{{ $c['url'] }}" tabindex="-1" aria-hidden="true" style="position:absolute; inset:0; display:block;">@include('storefront.home.partials.img', ['media' => $c['image'], 'alt' => $c['name'], 'sizes' => '(min-width: 1300px) 20vw, (min-width: 700px) 30vw, 50vw'])</a>
                @if ($c['pct'])<span style="position:absolute; top:10px; left:10px; background:var(--fn-accent-light); color:var(--fn-ink); font-size:11.5px; font-weight:700; padding:6px 10px; border-radius:999px;">{{ $c['pct'] }}{{ __('% OFF') }}</span>@endif
                @unless ($compact ?? false)
                <form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $c['product']->id]) }}" x-data="wishlistButton({{ $c['wished'] ? 'true' : 'false' }})" @submit.prevent="toggle($event)" style="margin:0;">@csrf
                <button type="submit" :aria-pressed="saved" aria-label="Add {{ $c['name'] }} to wishlist" style="position:absolute; top:8px; right:8px; width:34px; height:34px; border-radius:50%; border:1px solid var(--fn-line); background:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $c['wished'] ? 'var(--fn-accent)' : 'none' }}" :fill="saved ? 'var(--fn-accent)' : 'none'" aria-hidden="true"><path d="M12 20s-7.5-4.6-7.5-9.4A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z" stroke="{{ $c['wished'] ? 'var(--fn-accent)' : 'var(--fn-muted)' }}" :stroke="saved ? 'var(--fn-accent)' : 'var(--fn-muted)'" stroke-width="1.7" stroke-linejoin="round"></path></svg>
                </button>
                </form>
                @endunless
                <span style="position:absolute; bottom:10px; left:10px; background:rgba(232,245,233,.95); color:var(--fn-primary-dark); font-size:11px; font-weight:700; padding:5px 10px; border-radius:999px; border:1px solid var(--fn-primary-pale);">{{ __('Organic') }}</span>
              </div>
              <div class="fn-product-card-body">
                <h3 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15.5px; line-height:1.3; margin:0;"><a class="fn-product-card-title" href="{{ $c['url'] }}">{{ $c['name'] }}</a></h3>
                <span style="font-size:12.5px;">{{ $c['weight'] }}</span>
                @if ($c['reviews'] && ! ($compact ?? false))
                <span style="display:flex; align-items:center; gap:6px; font-size:12.5px;">
                  <span style="color:var(--fn-accent-light); letter-spacing:1px;">★★★★★</span>
                  <span>{{ $c['rating'] }} ({{ $c['reviews'] }})</span>
                </span>
                @endif
                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:2px;">
                  <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:18px; color:var(--fn-ink); background:var(--fn-canvas); border-radius:8px; padding:2px 8px;">{{ \App\Themes\Freshnest\Price::show($c['price']) }}</span>
                  @if ($c['old'])<span style="font-size:13px; text-decoration:line-through;">{{ \App\Themes\Freshnest\Price::show($c['old']) }}</span>@endif
                </div>
                <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:auto 0 0;">@csrf<input type="hidden" name="variant_id" value="{{ $c['variant_id'] }}"><input type="hidden" name="quantity" value="1">
                <button type="submit" class="atc fh-b496c7" :disabled="busy" style="margin-top:auto; width:100%; display:flex; align-items:center; justify-content:space-between; gap:10px; height:46px; padding:0 5px 0 14px; border-radius:999px; border:1.5px solid var(--fn-primary-light); background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14px; cursor:pointer; transition:background .15s ease, border-color .15s ease;">
              <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2.2l2.3 10.4A2 2 0 0 0 9.45 16H18a2 2 0 0 0 1.95-1.55L21.5 7.5H6"></path><circle cx="10" cy="20" r="1.2"></circle><circle cx="18" cy="20" r="1.2"></circle></svg>
                {{ __('Add to Cart') }}
              </span>
              <span class="atc-plus" style="display:inline-flex; width:34px; height:34px; flex:0 0 auto; border-radius:50%; align-items:center; justify-content:center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></span>
            </button>
                </form>
              </div>
            </article>
