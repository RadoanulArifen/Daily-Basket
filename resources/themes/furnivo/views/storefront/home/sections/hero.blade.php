{{-- Home section: hero — site.zip index.html, <!-- HERO --> (HOME view). Slides: Admin → Content → Banners (hero). --}}
@php
    $slides ??= app(\App\Themes\Furnivo\HomeData::class)->hero();
@endphp
@if ($slides->isNotEmpty())
    <section data-nav-theme="dark" x-data="heroSlider({ count: {{ $slides->count() }} })" aria-roledescription="carousel" aria-label="{{ settings()->trans('general.store_name') }}"
             style="position: relative; height: min(84vh, 920px); min-height: 600px; overflow: hidden; background: #0e0e0e;">
        {{-- photography --}}
        <div style="position: absolute; inset: 0;">
            @foreach ($slides as $i => $slide)
                @if ($slide->image)
                    <img alt="" src="{{ $slide->image->url('large') }}" srcset="{{ $slide->image->srcset() }}" sizes="100vw" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                         style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: opacity 1200ms ease, transform 8000ms ease;"
                         :style="{ opacity: index === {{ $i }} ? 1 : 0, transform: index === {{ $i }} ? 'scale(1.08)' : 'scale(1)' }" @if ($i > 0) x-cloak @endif>
                @endif
            @endforeach
        </div>
        {{-- scrims --}}
        <div style="position: absolute; inset: 0; background: linear-gradient(100deg, rgba(12,12,12,.82) 0%, rgba(12,12,12,.6) 26%, rgba(12,12,12,.18) 52%, rgba(12,12,12,0) 74%); pointer-events: none;"></div>
        <div style="position: absolute; inset: 0; background: linear-gradient(0deg, rgba(12,12,12,.5) 0%, rgba(12,12,12,0) 30%); pointer-events: none;"></div>
        {{-- content (x-show sits on a wrapper: Alpine would otherwise drop the design's display:flex) --}}
        @foreach ($slides as $i => $slide)
            <div x-show="index === {{ $i }}" @if ($i > 0) x-cloak @endif role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $slides->count() }}">
            <div style="position: absolute; inset: 0; display: flex; align-items: center; pointer-events: none; padding-top: 120px;">
                <div style="width: 100%; padding: 0 clamp(24px,4.5vw,80px);">
                    <div style="max-width: 600px; color: #fff; pointer-events: auto;">
                        @if ($slide->eyebrow)
                            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 26px;">
                                <span style="font-size: 12px; letter-spacing: .3em; text-transform: uppercase; color: rgba(255,255,255,.82);">{{ $slide->eyebrow }}</span>
                            </div>
                        @endif
                        @if ($i === 0)
                            <h1 style="margin: 0 0 24px; font-size: clamp(38px,4.8vw,68px); line-height: 1.02; font-weight: 800; letter-spacing: -.03em; white-space: nowrap;">{{ $slide->title }}</h1>
                        @else
                            <h2 style="margin: 0 0 24px; font-size: clamp(38px,4.8vw,68px); line-height: 1.02; font-weight: 800; letter-spacing: -.03em; white-space: nowrap;">{{ $slide->title }}</h2>
                        @endif
                        @if ($slide->text)
                            <p style="margin: 0 0 38px; font-size: 18px; line-height: 28px; color: rgba(255,255,255,.78); max-width: 40ch;">{{ $slide->text }}</p>
                        @endif
                        @if ($slide->button_label && $slide->href())
                            <a href="{{ $slide->href() }}" class="scp4" style="display: inline-block; border: 1px solid #fff; background: #fff; color: #1D1D1D; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; padding: 17px 38px; cursor: pointer; transition: background 400ms ease, color 400ms ease; width: max-content;">{{ $slide->button_label }}</a>
                        @endif
                    </div>
                </div>
            </div>
            </div>
        @endforeach
        {{-- controls --}}
        @if ($slides->count() > 1)
            <div style="position: absolute; left: 0; right: 0; bottom: 0; pointer-events: none;">
                <div style="width: 100%; padding: 0 clamp(24px,4.5vw,80px) clamp(28px,3vw,40px); display: flex; align-items: center; gap: 22px; pointer-events: auto;">
                    <div style="font-size: 13px; letter-spacing: .14em; color: rgba(255,255,255,.62); font-variant-numeric: tabular-nums;"><span x-text="String(index + 1).padStart(2, '0') + ' / ' + String(count).padStart(2, '0')">01 / {{ str_pad((string) $slides->count(), 2, '0', STR_PAD_LEFT) }}</span></div>
                    <div style="width: clamp(120px,20vw,220px); display: flex; gap: 10px;">
                        @foreach ($slides as $i => $slide)
                            <button type="button" @click="go({{ $i }})" aria-label="Slide {{ $i + 1 }}" :aria-current="index === {{ $i }}"
                                    style="flex: 1; height: 2px; border: none; padding: 0; cursor: pointer; background: {{ $i === 0 ? '#ffffff' : 'rgba(255,255,255,.25)' }}; transition: background 400ms ease;"
                                    :style="{ background: index === {{ $i }} ? '#ffffff' : 'rgba(255,255,255,.25)' }"></button>
                        @endforeach
                    </div>
                    <button type="button" @click="toggle()" class="scp5" title="Pause / play" :aria-label="playing ? 'Pause' : 'Play'" style="width: 34px; height: 34px; border: 1px solid rgba(255,255,255,.4); background: rgba(20,20,20,.25); backdrop-filter: blur(4px); color: #fff; font-size: 10px; cursor: pointer; transition: background 300ms;"><span x-text="playing ? '❚❚' : '▶'">❚❚</span></button>
                </div>
            </div>
        @endif
    </section>
@endif
