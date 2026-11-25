{{--
    Newsletter — Content → Banners → "Newsletter block": eyebrow, headline, text, button,
    picture. Sign-ups go to Marketing → Subscribers. The ticket shows the first-order code:
    a live percentage discount limited to one use per customer (Marketing → Discounts).
    The design's app-store buttons are left out (there is no app).
--}}
@php
    $news = $home->banner('newsletter');
    $welcome = \App\Models\Discount::query()->where('is_active', true)->where('type', 'percentage')->where('usage_limit_per_customer', 1)
        ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->orderBy('id')->first();
@endphp
@if ($news)
  <section id="bakery" class="wrap" style="padding-top:clamp(40px,5vw,68px); padding-bottom:clamp(40px,5vw,68px);">
    <div class="news-grid" x-data="{ note: '', busy: false, async send(e) { this.busy = true; try { const r = await fetch(e.target.action, { method: 'POST', body: new FormData(e.target), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }); const d = await r.json().catch(() => ({})); this.note = r.ok ? d.message : (d.errors ? Object.values(d.errors).flat()[0] : (d.message || 'Something went wrong.')); if (r.ok) e.target.reset(); } finally { this.busy = false; } } }" style="position:relative; overflow:hidden; gap:0; align-items:stretch; border-radius:28px; background:radial-gradient(120% 140% at 0% 0%, var(--fn-primary) 0%, var(--fn-primary-dark) 55%, var(--fn-primary-deep) 100%); color:#FFFFFF; box-shadow:0 30px 60px rgba(25,75,48,.22);">
      <span style="position:absolute; left:-90px; top:-90px; width:260px; height:260px; border-radius:50%; border:1px solid rgba(255,255,255,.12);"></span>
      <span style="position:absolute; left:-40px; top:-40px; width:160px; height:160px; border-radius:50%; border:1px solid rgba(255,255,255,.1);"></span>
      <svg width="220" height="220" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; left:38%; bottom:-70px; opacity:.07; transform:rotate(-20deg);"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="#FFFFFF"></path></svg>

      <div style="position:relative; padding:clamp(30px,4.5vw,60px); display:flex; flex-direction:column; gap:22px;">
        <span style="align-self:flex-start; display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:999px;">
          <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--fn-accent);"></span>
          {{ $news->eyebrow }}
        </span>
        <div>
          <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(30px,4vw,48px); line-height:1.08; letter-spacing:-.03em; margin:0; text-wrap:pretty;">{{ $news->title }}</h2>
          <p style="margin:14px 0 0; font-size:16px; line-height:1.65; color:rgba(232,245,233,.88); max-width:480px;">{{ $news->text }}</p>
        </div>
        <form method="POST" action="{{ store_route('newsletter.subscribe') }}" @submit.prevent="send($event)" style="display:flex; align-items:center; gap:6px; background:#FFFFFF; border-radius:18px; padding:6px 6px 6px 16px; max-width:540px; box-shadow:0 14px 30px rgba(0,0,0,.18);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="flex:0 0 auto;"><rect x="3" y="5" width="18" height="14" rx="3" stroke="var(--fn-muted)" stroke-width="1.7"></rect><path d="m4 7 8 6 8-6" stroke="var(--fn-muted)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>
          @csrf<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px;"><input type="email" name="email" required="required" aria-label="{{ __('Email address') }}" placeholder="{{ __('Enter your email address') }}" style="flex:1 1 auto; min-width:0; border:0; outline:none; font-size:15px; padding:13px 8px; color:var(--fn-ink); background:transparent;" />
          <button class="fh-f80239" type="submit" :disabled="busy" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 22px; flex:0 0 auto; border:0; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:15px; border-radius:999px; cursor:pointer; white-space:nowrap;"><span style="white-space:nowrap;">{{ $news->button_label ?: __('Subscribe') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
        </form>
        <span style="font-size:13px; color:var(--fn-primary-light); min-height:18px; margin-top:-10px;" role="status" x-text="note"></span>
        <div style="display:flex; flex-wrap:wrap; gap:10px 20px; margin-top:-8px;">
          <span style="display:inline-flex; align-items:center; gap:8px; font-size:13.5px; color:var(--fn-primary-tint);"><span style="display:inline-flex; width:20px; height:20px; border-radius:50%; background:rgba(143,215,155,.22); align-items:center; justify-content:center;"><svg width="10" height="10" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m2.5 6.2 2.2 2.2 4.8-5" stroke="var(--fn-primary-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>{{ __('Weekly harvest notes') }}</span>
          <span style="display:inline-flex; align-items:center; gap:8px; font-size:13.5px; color:var(--fn-primary-tint);"><span style="display:inline-flex; width:20px; height:20px; border-radius:50%; background:rgba(143,215,155,.22); align-items:center; justify-content:center;"><svg width="10" height="10" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m2.5 6.2 2.2 2.2 4.8-5" stroke="var(--fn-primary-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>{{ __('Seasonal recipes') }}</span>
          <span style="display:inline-flex; align-items:center; gap:8px; font-size:13.5px; color:var(--fn-primary-tint);"><span style="display:inline-flex; width:20px; height:20px; border-radius:50%; background:rgba(143,215,155,.22); align-items:center; justify-content:center;"><svg width="10" height="10" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m2.5 6.2 2.2 2.2 4.8-5" stroke="var(--fn-primary-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>{{ __('Member-only pricing') }}</span>
        </div>
      </div>

      <div style="position:relative; min-height:clamp(320px,38vw,480px); padding:clamp(16px,2vw,24px);">
        <div style="position:absolute; inset:clamp(16px,2vw,24px); border-radius:22px; overflow:hidden;">
          @include('storefront.home.partials.img', ['media' => $news->image, 'alt' => '', 'sizes' => '(min-width: 900px) 45vw, 100vw'])
        </div>
        @if ($welcome)
        <div style="position:absolute; left:clamp(0px,1vw,8px); top:50%; transform:translateY(-50%) rotate(-4deg); display:flex; width:max-content; white-space:nowrap; background:#FFFFFF; color:var(--fn-ink); border-radius:18px; box-shadow:0 24px 48px rgba(0,0,0,.25); overflow:hidden;">
          <div style="padding:18px 20px; display:flex; flex-direction:column; gap:4px; flex:0 0 auto;">
            <span style="font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--fn-primary);">{{ __('Welcome gift') }}</span>
            <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:36px; line-height:1; letter-spacing:-.03em; color:var(--fn-accent);">{{ (string) (float) $welcome->value }}{{ __('% OFF') }}</span>
            <span style="font-size:12.5px; color:var(--fn-muted);">{{ __('On your first order') }}</span>
          </div>
          <div style="position:relative; border-left:2px dashed var(--fn-primary-pale); background:var(--fn-primary-tint); padding:18px 16px; display:flex; flex-direction:column; justify-content:center; gap:4px;">
            <span style="position:absolute; left:-9px; top:-9px; width:16px; height:16px; border-radius:50%; background:var(--fn-primary-dark);"></span>
            <span style="position:absolute; left:-9px; bottom:-9px; width:16px; height:16px; border-radius:50%; background:var(--fn-primary-dark);"></span>
            <span style="font-size:10.5px; font-weight:600; color:var(--fn-muted); text-transform:uppercase; letter-spacing:.08em;">{{ __('Code') }}</span>
            <span style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:700; font-size:15px; color:var(--fn-primary-dark);">{{ $welcome->code }}</span>
          </div>
        </div>
        @endif
      </div>
    </div>
  </section>
@endif
