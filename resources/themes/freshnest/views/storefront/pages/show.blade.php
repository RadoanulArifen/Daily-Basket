@extends('storefront.layouts.app')

{{--
    Content pages from the design (contact.html, faq.html, delivery-info.html …), filled from
    Content → Pages: title and intro make the green band; the page text is laid out like the
    design:
      - a first list of "<strong>figure</strong> label" items → the three figure tiles
      - each heading with the text under it → one row (FAQs: one question that opens)
      - Our Growers also shows the growers (Catalog → Brands)
    The contact page adds the design's four cards (Settings → Store details), the map picture
    and the message form (it arrives in the store's inbox).
--}}
@section('freshnest', true)
@section('title', $page->seo_title ?: $page->title)
@if ($page->seo_description)@section('meta_description', $page->seo_description)@endif

@php
    $body = (string) $page->body;
    $stats = [];
    if (preg_match('#^\s*<ul>(.*?)</ul>#is', $body, $m) && str_contains($m[1], '<strong>')) {
        preg_match_all('#<li[^>]*>\s*<strong>(.*?)</strong>\s*(.*?)</li>#is', $m[1], $li, PREG_SET_ORDER);
        $stats = array_map(fn ($x) => [html_entity_decode(strip_tags($x[1])), html_entity_decode(strip_tags($x[2]))], $li);
        $body = substr($body, strlen($m[0]));
    }
    // Heading + everything up to the next heading = one section.
    $parts = preg_split('#<h[23][^>]*>(.*?)</h[23]>#is', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
    $lead = trim($parts[0] ?? '');
    $sections = [];
    for ($i = 1; $i < count($parts); $i += 2) {
        $sections[] = [html_entity_decode(strip_tags($parts[$i])), trim($parts[$i + 1] ?? '')];
    }
    $isFaq = $page->slug === 'faq';
    $isOffers = $page->slug === 'offers';
    $isContact = $page->show_contact_form;
    $growers = $page->slug === 'our-growers'
        ? \App\Models\Brand::query()->active()->orderBy('position')->get()
        : collect();
    $growerImage = fn ($brand) => \App\Models\Product::query()->where('brand_id', $brand->id)->with('categories.image')->first()?->categories->first()?->image;
    $s = settings();
@endphp

@section('content')
    @include('storefront.partials.page-band', ['title' => $page->heading ?: $page->title, 'crumb' => $page->title, 'blurb' => $page->intro])

    @if ($isContact)
    <div class="wrap contact-grid" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px);">
      <div>
        @php
            $cards = array_values(array_filter([
                $s->get('general.store_phone') ? ['TEL', __('Call the store'), trim($s->get('general.store_phone')."\n".$s->trans('general.opening_hours'))] : null,
                $s->get('general.store_email') ? ['@', __('Email us'), $s->get('general.store_email')] : null,
                filled($s->trans('general.address')) ? ['MAP', __('Visit the depot'), $s->trans('general.address')] : null,
                $s->get('general.whatsapp') ? ['WA', __('WhatsApp'), $s->get('general.whatsapp')] : ['45m', __('Delivery support'), "Live tracking on your order page\nMissed slot? Call and we rebook free."],
            ]));
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode(str_replace("\n", ', ', (string) $s->trans('general.address')));
        @endphp
        <div class="info-grid">
          @foreach ($cards as [$mark, $title, $text])
            <div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:22px; display:flex; flex-direction:column; gap:8px;">
              <span style="display:inline-flex; width:42px; height:42px; border-radius:13px; background:var(--fn-primary-tint); align-items:center; justify-content:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:14px; color:var(--fn-primary);">{{ $mark }}</span>
              <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:15.5px; color:var(--fn-ink);">{{ $title }}</strong>
              <span style="font-size:14px; color:var(--fn-muted); line-height:1.6; white-space:pre-line;">{{ $text }}</span>
            </div>
          @endforeach
        </div>
        <div style="margin-top:20px; border-radius:18px; overflow:hidden; border:1px solid var(--fn-line); height:clamp(280px,32vw,380px); position:relative; background:var(--fn-primary-tint);">
          <img src="{{ asset('themes/freshnest/contact-map.png') }}" alt="{{ __('Map of the depot area') }}" loading="lazy" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;">
          <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 16px; position:absolute; left:14px; bottom:14px; background:#FFFFFF; color:var(--fn-primary-dark); font-weight:700; font-size:13.5px; border-radius:999px; box-shadow:0 6px 18px rgba(25,75,48,.16);"><span style="white-space:nowrap;">{{ __('Get directions') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:var(--fn-primary-dark); align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
        </div>
      </div>

      @php $inp = 'border:1.5px solid var(--fn-primary-tint); border-radius:12px; padding:13px 15px; font-size:15px; font-weight:400; outline:none; background:var(--fn-canvas);'; $lab = 'display:flex; flex-direction:column; gap:7px; font-size:13.5px; font-weight:600; color:var(--fn-ink);'; @endphp
      <form method="POST" action="{{ store_route('pages.contact', ['slug' => $page->slug]) }}" x-data="{ topic: 'Order or delivery' }" @submit="$refs.msg.value = '[' + topic + '] ' + $refs.msg.value.replace(/^\[[^\]]*\]\s*/, '')" style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:20px; padding:clamp(24px,3vw,34px); display:flex; flex-direction:column; gap:16px;">
        @csrf
        <div>
          <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:23px; letter-spacing:-.02em; margin:0; color:var(--fn-ink);">{{ __('Send us a message') }}</h2>
          <p style="margin:8px 0 0; font-size:14.5px; color:var(--fn-muted);">{{ __('We reply to every message within one working day.') }}</p>
        </div>
        @if (session('contact_sent'))
          <p role="status" style="margin:0; padding:12px 14px; border-radius:12px; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-weight:600; font-size:14px;">{{ __('Thanks — your message is on its way. We\'ll reply by email.') }}</p>
        @endif
        <label style="{{ $lab }}">{{ __('Full name') }}<input type="text" name="name" value="{{ old('name', auth('web')->user()?->name) }}" required placeholder="{{ __('Jamie Rivera') }}" style="{{ $inp }}">@error('name')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        <label style="{{ $lab }}">{{ __('Email') }}<input type="email" name="email" value="{{ old('email', auth('web')->user()?->email) }}" required placeholder="{{ __('you@email.com') }}" style="{{ $inp }}">@error('email')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        <label style="{{ $lab }}">{{ __('Topic') }}
          <select x-model="topic" style="{{ $inp }} cursor:pointer;">
            @foreach (['Order or delivery', 'A product', 'Becoming a grower', 'Something else'] as $topic)<option value="{{ $topic }}">{{ __($topic) }}</option>@endforeach
          </select>
        </label>
        <label style="{{ $lab }}">{{ __('Message') }}<textarea name="message" x-ref="msg" rows="5" required minlength="10" placeholder="{{ __('How can we help?') }}" style="{{ $inp }} resize:vertical;">{{ old('message') }}</textarea>@error('message')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        <button class="fh-92c14d" type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 22px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15.5px; border-radius:999px; cursor:pointer;"><span>{{ __('Send message') }}</span><span style="display:inline-flex; width:34px; height:34px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
      </form>
    </div>

    @elseif ($isOffers)
    @php
        $home = app(\App\Themes\Freshnest\HomeData::class);
        $weekend = $home->banner('weekend');
        [$figure, $lead] = array_pad(explode('|', (string) $weekend?->text, 2), 2, '');
        if ($lead === '') { [$figure, $lead] = ['', $figure]; }
        $codes = \App\Models\Discount::query()->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->orderBy('id')->get();
        $tags = [['var(--fn-accent-tint)', 'var(--fn-accent-dark)'], ['var(--fn-primary-tint)', 'var(--fn-primary-dark)']];
        $cards = collect();
        foreach ($home->promotions() as $promo) {
            $href = $promo->applies_to === 'category' && $promo->category ? store_route('collections.show', ['slug' => $promo->category->slug])
                : (($pid = ((array) $promo->product_ids)[0] ?? null) && ($prod = $home->products()->firstWhere('id', (int) $pid)) ? store_route('products.show', ['slug' => $prod->slug]) : store_route('shop'));
            $cards->push(['tag' => $promo->applies_to === 'category' ? ($promo->category?->name ?? __('Deal')) : __('Deal'), 'title' => $promo->label(), 'body' => $promo->summary(), 'code' => __('AUTOMATIC'),
                'expiry' => $promo->ends_at ? __('Ends ').$promo->ends_at->format('M j') : __('Ongoing'), 'href' => $href, 'apply' => null]);
        }
        foreach ($codes as $d) {
            $cards->push(['tag' => $d->usage_limit_per_customer === 1 ? __('New customer') : __('Code'), 'title' => match ($d->type) {
                    'percentage' => ((string) (float) $d->value).'% '.__('off').($d->usage_limit_per_customer === 1 ? ' '.__('your first order') : ''),
                    'fixed' => price_whole(\App\Support\Money::of((string) $d->value, config('store.base_currency'))).' '.__('off').($d->min_subtotal ? ' '.__('orders over :amount', ['amount' => price_whole($d->min_subtotal)]) : ''),
                    default => __('Free delivery'),
                },
                'body' => $d->min_subtotal ? __('On orders over :amount. Enter the code at checkout, or tap below.', ['amount' => price_whole($d->min_subtotal)]) : __('On your whole order. Enter the code at checkout, or tap below.'),
                'code' => $d->code, 'expiry' => $d->ends_at ? __('Ends ').$d->ends_at->format('M j') : __('Always on'), 'href' => null, 'apply' => $d->code]);
        }
    @endphp
    <div class="wrap" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px);">
      @if ($weekend)
      <div style="position:relative; overflow:hidden; border-radius:22px; background:linear-gradient(120deg,var(--fn-primary-dark) 0%, var(--fn-primary) 100%); color:#FFFFFF; padding:clamp(26px,3.6vw,48px); display:flex; flex-wrap:wrap; gap:28px; align-items:center; justify-content:space-between;">
        <div style="flex:1 1 300px; min-width:min(100%,280px);">
          <span style="display:inline-block; background:rgba(255,255,255,.16); font-size:12.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; padding:7px 13px; border-radius:999px;">{{ $weekend->eyebrow }}</span>
          <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(25px,3.4vw,38px); letter-spacing:-.02em; margin:16px 0 0;">{{ $weekend->title }}</h2>
          @if (filled($figure))<div style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(46px,8vw,76px); line-height:1; color:var(--fn-accent); margin-top:10px; letter-spacing:-.04em;">{{ $figure }}</div>@endif
          <p style="margin:12px 0 0; font-size:16px; color:rgba(232,245,233,.92);">{{ $lead }}</p>
        </div>
        @if ($weekend->ends_at)
        <div x-data="fnCountdown(@js($weekend->ends_at->toIso8601String()), true, @js([__('Days'), __('Hours'), __('Mins'), __('Secs')]))">
          <div style="font-size:12.5px; letter-spacing:.1em; text-transform:uppercase; color:rgba(232,245,233,.8); margin-bottom:12px;">{{ __('Ends in') }}</div>
          <div style="display:flex; gap:10px;">
            <template x-for="unit in units" :key="unit.label">
              <div style="min-width:72px; background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.18); border-radius:14px; padding:14px 10px; text-align:center;">
                <div style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; line-height:1;" x-text="unit.value"></div>
                <div style="font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:rgba(232,245,233,.8); margin-top:7px;" x-text="unit.label"></div>
              </div>
            </template>
          </div>
        </div>
        @endif
      </div>
      @endif

      <div class="offer-grid" style="margin-top:28px;">
        @foreach ($cards as $i => $o)
          <div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:24px; display:flex; flex-direction:column; gap:10px;">
            <span style="align-self:flex-start; background:{{ $tags[$i % 2][0] }}; color:{{ $tags[$i % 2][1] }}; font-size:11.5px; font-weight:700; padding:6px 11px; border-radius:999px;">{{ $o['tag'] }}</span>
            <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:19px; color:var(--fn-ink); line-height:1.3;">{{ $o['title'] }}</strong>
            <span style="font-size:14px; color:var(--fn-muted); line-height:1.6;">{{ $o['body'] }}</span>
            <span style="display:flex; align-items:center; gap:10px; margin-top:6px;">
              <span style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:13px; font-weight:600; color:var(--fn-primary-dark); background:var(--fn-primary-tint); border:1px dashed #9CCBA4; padding:8px 12px; border-radius:10px;">{{ $o['code'] }}</span>
              <span style="font-size:12.5px; color:var(--fn-muted);">{{ $o['expiry'] }}</span>
            </span>
            @if ($o['apply'])
              <form method="POST" action="{{ store_route('cart.discount') }}" style="margin:8px 0 0; align-self:flex-start;">@csrf<input type="hidden" name="code" value="{{ $o['apply'] }}">
                <button type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 20px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Grab the Deal') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
              </form>
            @else
              <a href="{{ $o['href'] }}" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 20px; margin-top:8px; align-self:flex-start; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px;"><span style="white-space:nowrap;">{{ __('Grab the Deal') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    @elseif ($isFaq)
    <div class="wrap" x-data="{ open: null }" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px); display:flex; flex-direction:column; gap:12px; max-width:920px;">
      @foreach ($sections as $i => [$q, $a])
        <div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:16px;">
          <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}" style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:16px; padding:18px 22px; border:0; background:transparent; text-align:left; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:16px; color:var(--fn-ink);">{{ $q }}<span style="font-size:20px; color:var(--fn-primary);" x-text="open === {{ $i }} ? '–' : '+'">+</span></button>
          <div x-show="open === {{ $i }}" x-cloak>
            <div class="fn-page-text" style="margin:0; padding:0 22px 20px; font-size:15px; line-height:1.7; color:var(--fn-muted); text-wrap:pretty;">{!! \App\Support\RichText::render($a) !!}</div>
          </div>
        </div>
      @endforeach
    </div>

    @else
    <div class="wrap" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px); display:flex; flex-direction:column; gap:24px;">
      @if ($stats)
      <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:14px;">
        @foreach ($stats as [$v, $l])
          <div style="background:var(--fn-primary-tint); border-radius:16px; padding:20px 22px; display:flex; flex-direction:column; gap:4px;"><strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:28px; color:var(--fn-primary-dark);">{{ $v }}</strong><span style="font-size:14px; color:#3E4A3E;">{{ $l }}</span></div>
        @endforeach
      </div>
      @endif
      @if (filled(strip_tags($lead)))
        <div class="fn-page-text" style="font-size:15.5px; line-height:1.75; color:#3E4A3E; max-width:820px;">{!! \App\Support\RichText::render($lead) !!}</div>
      @endif
      @if ($sections)
      <div style="display:flex; flex-direction:column; gap:12px;">
        @foreach ($sections as [$h, $text])
          <div style="display:flex; flex-wrap:wrap; gap:12px 32px; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:16px; padding:22px 24px;">
            <strong style="flex:1 1 220px; font-family:'Plus Jakarta Sans',sans-serif; font-size:17px; color:var(--fn-ink);">{{ $h }}</strong>
            <div class="fn-page-text" style="flex:2 1 380px; margin:0; font-size:15px; line-height:1.7; color:var(--fn-muted); text-wrap:pretty;">{!! \App\Support\RichText::render($text) !!}</div>
          </div>
        @endforeach
      </div>
      @endif
      @if ($growers->isNotEmpty())
      <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr)); gap:16px;">
        @foreach ($growers as $brand)
          @php $img = $growerImage($brand); @endphp
          <a href="{{ store_route('brands.show', ['slug' => $brand->slug]) }}" style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; overflow:hidden; display:flex; flex-direction:column; color:inherit;">
            <span style="display:block; width:100%; aspect-ratio:16/10; background:var(--fn-primary-tint){{ $img ? ' url('.$img->url('medium').')' : '' }} center/cover no-repeat;"></span>
            <div style="padding:18px 20px; display:flex; flex-direction:column; gap:6px;"><span style="font-size:12.5px; font-weight:700; color:var(--fn-accent); text-transform:uppercase; letter-spacing:.06em;">{{ $brand->origin }}</span><strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:17px; color:var(--fn-ink);">{{ $brand->name }}</strong><span style="font-size:14px; line-height:1.6; color:var(--fn-muted);">{{ \App\Support\RichText::text($brand->description) }}</span></div>
          </a>
        @endforeach
      </div>
      @endif
      @if (filled($page->footnote))
        <p style="margin:0; font-size:13.5px; color:var(--fn-muted);">{{ $page->footnote }}</p>
      @endif
    </div>
    @endif
@endsection
