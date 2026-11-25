{{-- Product listing from the design (Shop, category pages, search): departments, price,
     "On offer" / "In stock" filters, sort, 4 × 4 grid and pages. The design's "Certified
     organic only" and "Rated 4.5 and up" filters and "Member price" card have no store data
     and are left out. Expects $products (paginator), $filters, $sort, $action, $current. --}}
@php
    $home = app(\App\Themes\Freshnest\HomeData::class);
    $products->getCollection()->loadMissing(['variants.optionValues', 'categories', 'media']);
    $cats = $home->categories()->filter(fn ($c) => $c['count'] > 0);
    $depts = collect([['label' => __('Shop'), 'href' => store_route('shop'), 'count' => $home->products()->count(), 'on' => $current === null]])
        ->concat($cats->map(fn ($c) => ['label' => $c['category']->name, 'href' => store_route('collections.show', ['slug' => $c['category']->slug]), 'count' => $c['count'], 'on' => $current === $c['category']->slug]));
@endphp
    <div class="wrap shop-grid fn-listing" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px);">
      <aside style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:22px;">
        <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15.5px; color:var(--fn-ink);">{{ __('Departments') }}</strong>
        <div style="display:flex; flex-direction:column; gap:2px; margin-top:14px;">
          @foreach ($depts as $d)
            <a href="{{ $d['href'] }}" @if ($d['on']) aria-current="page" @endif style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:11px; background:{{ $d['on'] ? 'var(--fn-primary-tint)' : 'transparent' }}; color:{{ $d['on'] ? 'var(--fn-primary-dark)' : 'var(--fn-ink)' }}; font-size:14px; font-weight:{{ $d['on'] ? '700' : '500' }};">
              <span>{{ $d['label'] }}</span>
              <span style="font-size:12px; color:var(--fn-muted);">{{ $d['count'] }} {{ $d['count'] === 1 ? __('item') : __('items') }}</span>
            </a>
          @endforeach
        </div>

        <form method="GET" action="{{ $action }}" style="margin:0;">
        @if (filled($filters['q'] ?? null))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
        @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
        <div style="height:1px; background:var(--fn-line); margin:22px 0;"></div>
        <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15.5px; color:var(--fn-ink);">{{ __('Price') }}</strong>
        <div style="display:flex; align-items:center; gap:10px; margin-top:14px;">
          <input type="number" name="min" min="0" step="0.01" value="{{ $filters['min'] ?? '' }}" aria-label="{{ __('Minimum price') }}" placeholder="{{ __('Min') }}" style="width:100%; min-width:0; border:1.5px solid var(--fn-primary-tint); border-radius:11px; padding:10px 12px; font-size:14px; outline:none;" />
          <span style="color:var(--fn-muted);">–</span>
          <input type="number" name="max" min="0" step="0.01" value="{{ $filters['max'] ?? '' }}" aria-label="{{ __('Maximum price') }}" placeholder="{{ __('Max') }}" style="width:100%; min-width:0; border:1.5px solid var(--fn-primary-tint); border-radius:11px; padding:10px 12px; font-size:14px; outline:none;" />
        </div>
        <button type="submit" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:6px 6px 6px 18px; margin-top:12px; width:100%; border:0; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-weight:700; font-size:14px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Apply') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:var(--fn-primary-dark); align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>

        <div style="height:1px; background:var(--fn-line); margin:22px 0;"></div>
        <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15.5px; color:var(--fn-ink);">{{ __('Refine') }}</strong>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
          @foreach (['sale' => 'On offer', 'in_stock' => 'In stock today'] as $name => $label)
            <label style="display:flex; align-items:center; gap:10px; font-size:14px; color:var(--fn-ink); cursor:pointer;">
              <input type="checkbox" name="{{ $name }}" value="1" @checked(! empty($filters[$name])) x-data @change="$el.form.submit()" style="width:17px; height:17px; accent-color:var(--fn-primary);" />
              <span>{{ __($label) }}</span>
            </label>
          @endforeach
        </div>
        </form>

      </aside>

      <div>
        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:16px; padding:14px 18px;">
          <span style="font-size:14px; color:var(--fn-muted);">{{ __('Showing') }} <strong style="color:var(--fn-ink);">{{ $products->count() }}</strong> {{ __('of') }} {{ $products->total() }} {{ $products->total() === 1 ? __('product') : __('products') }}</span>
          <form method="GET" action="{{ $action }}" style="display:flex; align-items:center; gap:10px; margin:0;">
            @foreach (request()->except(['sort', 'page']) as $key => $value)
              @foreach ((array) $value as $v)<input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">@endforeach
            @endforeach
            <label style="font-size:14px; color:var(--fn-muted);" for="sortby">{{ __('Sort by') }}</label>
            <select id="sortby" name="sort" x-data @change="$el.form.submit()" style="border:1.5px solid var(--fn-primary-tint); background:var(--fn-canvas); border-radius:11px; padding:9px 12px; font-size:14px; font-weight:600; color:var(--fn-ink); outline:none; cursor:pointer;">
              @foreach (['featured' => 'Most popular', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Top rated'] as $value => $label)
              <option value="{{ $value }}" @selected($sort === $value)>{{ __($label) }}</option>
              @endforeach
            </select>
          </form>
        </div>

        <div class="pgrid" style="margin-top:20px;">
          @forelse ($products as $product)
              @include('storefront.collections.card', ['c' => $home->card($product)])
          @empty
            <p style="grid-column:1 / -1; margin:0; padding:48px 20px; text-align:center; font-size:15px; color:var(--fn-muted); background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px;">{{ $emptyText ?? 'No products match these filters yet.' }}</p>
          @endforelse
        </div>

        @if ($products->hasPages())
        <nav aria-label="{{ __('Pages') }}" style="display:flex; gap:8px; justify-content:center; margin-top:32px;">
          @if (! $products->onFirstPage())
            <a href="{{ $products->previousPageUrl() }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:42px; height:42px; padding:0 14px; border-radius:12px; border:1.5px solid var(--fn-primary-tint); background:#FFFFFF; color:var(--fn-ink); font-weight:600; font-size:14px;">{{ __('← Prev') }}</a>
          @endif
          @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
            @php $on = $page === $products->currentPage(); @endphp
            <a href="{{ $url }}" @if ($on) aria-current="page" @endif style="display:inline-flex; align-items:center; justify-content:center; min-width:42px; height:42px; padding:0 14px; border-radius:12px; border:1.5px solid {{ $on ? 'var(--fn-primary)' : 'var(--fn-primary-tint)' }}; background:{{ $on ? 'var(--fn-primary)' : '#FFFFFF' }}; color:{{ $on ? '#FFFFFF' : 'var(--fn-ink)' }}; font-weight:600; font-size:14px;">{{ $page }}</a>
          @endforeach
          @if ($products->hasMorePages())
            <a href="{{ $products->nextPageUrl() }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:42px; height:42px; padding:0 14px; border-radius:12px; border:1.5px solid var(--fn-primary-tint); background:#FFFFFF; color:var(--fn-ink); font-weight:600; font-size:14px;">{{ __('Next →') }}</a>
          @endif
        </nav>
        @endif
      </div>
    </div>
