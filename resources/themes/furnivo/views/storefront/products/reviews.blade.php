{{-- Ratings & reviews — markup and styles from the design, from the store's approved reviews. --}}
@php $customer = auth('web')->user(); $shown = 6; @endphp
<section id="reviews" style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(48px, 6vw, 80px) 0px;" x-data="{ limit: {{ $shown }}, writing: {{ $errors->hasAny(['rating', 'body', 'title']) ? 'true' : 'false' }}, rating: {{ (int) old('rating', 5) }} }">
    <div style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px);">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 30px;">
            <h2 style="margin: 0px; font-size: clamp(22px, 2.6vw, 32px); font-weight: 700; letter-spacing: -0.02em;">Ratings &amp; reviews</h2>
            @if ($customer)
                <button type="button" @click="writing = ! writing" :aria-expanded="writing" class="scpc" style="border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; padding: 13px 26px; cursor: pointer; transition: background 250ms, color 250ms;"><span>{{ __('store.theme.write_review') }}</span></button>
            @else
                <a href="{{ store_route('login') }}?redirect={{ urlencode(request()->path()) }}" class="scpc" style="border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; padding: 13px 26px; cursor: pointer; transition: background 250ms, color 250ms;"><span>{{ __('store.theme.write_review') }}</span></a>
            @endif
        </div>

        @if (session('review_status'))
            <p role="status" style="margin: 0px 0px 24px; padding: 14px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 14px;">{{ session('review_status') }}</p>
        @endif

        @if ($customer)
            <div x-show="writing" x-cloak>
                <form method="POST" action="{{ store_route('reviews.store', ['slug' => $product->slug]) }}" enctype="multipart/form-data" style="border: 1px solid rgb(236, 236, 236); padding: clamp(22px, 2.5vw, 28px); margin-bottom: clamp(32px, 4vw, 48px); display: grid; gap: 16px; max-width: 720px;">
                    @csrf
                    <input type="hidden" name="rating" :value="rating">
                    <div>
                        <div style="font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 8px;">{{ __('store.theme.your_rating') }}</div>
                        <div style="display: flex; gap: 4px; font-size: 24px;" role="radiogroup" aria-label="{{ __('store.theme.your_rating') }}">
                            @for ($s = 1; $s <= 5; $s++)
                                <button type="button" role="radio" :aria-checked="rating === {{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}" @click="rating = {{ $s }}" style="border: none; background: transparent; padding: 0px; cursor: pointer; color: rgb(29, 29, 29);" x-text="rating >= {{ $s }} ? '★' : '☆'">★</button>
                            @endfor
                        </div>
                    </div>
                    <label style="display: grid; gap: 6px; font-size: 13px; color: rgb(102, 102, 102);">Title (optional)
                        <input name="title" value="{{ old('title') }}" maxlength="120" style="border: 1px solid rgb(217, 213, 207); padding: 12px 14px; font-size: 15px; color: rgb(32, 32, 32);">
                    </label>
                    <label style="display: grid; gap: 6px; font-size: 13px; color: rgb(102, 102, 102);"><span>{{ __('store.theme.your_review') }}<x-store.required /></span>
                        <textarea name="body" required minlength="10" maxlength="2000" rows="4" style="border: 1px solid rgb(217, 213, 207); padding: 12px 14px; font-size: 15px; color: rgb(32, 32, 32); resize: vertical;">{{ old('body') }}</textarea>
                    </label>
                    @if ($errors->hasAny(['rating', 'body', 'title']))<p role="alert" style="margin: 0px; font-size: 14px; color: rgb(199, 64, 52);">{{ $errors->first('body') ?: $errors->first('rating') ?: $errors->first('title') }}</p>@endif
                    <button class="scpb" style="justify-self: start; height: 48px; padding: 0px 30px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; transition: opacity 250ms;">{{ __('store.theme.submit_review') }}</button>
                </form>
            </div>
        @endif

        @if ($reviews->isNotEmpty())
            <div style="display: flex; align-items: center; gap: clamp(28px, 5vw, 64px); flex-wrap: wrap; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); padding: clamp(24px, 3vw, 36px); margin-bottom: clamp(32px, 4vw, 48px);">
                <div style="text-align: center; padding-right: clamp(28px, 5vw, 64px); border-right: 1px solid rgb(228, 225, 218); min-width: 130px;">
                    <div style="font-size: clamp(48px, 6vw, 68px); line-height: 1; font-weight: 800; letter-spacing: -0.03em; color: rgb(32, 32, 32);"><span>{{ number_format($avg, 1) }}</span></div>
                    <div style="font-size: 16px; letter-spacing: 0.1em; color: rgb(29, 29, 29); margin: 10px 0px 6px;" aria-hidden="true"><span>{{ $starText($avg) }}</span></div>
                    <div style="font-size: 13px; color: rgb(139, 135, 127);"><span>{{ $reviews->count() }}</span> reviews</div>
                </div>
                <div style="flex: 1 1 0%; min-width: 240px; display: flex; flex-direction: column; gap: 9px;">
                    @foreach ($bars as $bar)
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 12px; color: rgb(102, 102, 102); width: 34px;"><span>{{ $bar['star'] }}</span> ★</span>
                            <span style="flex: 1 1 0%; height: 7px; background: rgb(236, 234, 228); position: relative; overflow: hidden;"><span style="position: absolute; left: 0px; top: 0px; bottom: 0px; width: {{ $bar['pct'] }}%; background: rgb(29, 29, 29); transition: width 400ms;"></span></span>
                            <span style="font-size: 12px; color: rgb(139, 135, 127); width: 24px; text-align: right;"><span>{{ $bar['count'] }}</span></span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="review-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2px; border: 1px solid rgb(236, 236, 236); background: rgb(236, 236, 236);">
                @foreach ($reviews as $i => $review)
                    <div @if ($i >= $shown) x-show="limit > {{ $i }}" x-cloak @endif>
                    <div style="background: rgb(255, 255, 255); padding: clamp(22px, 2.5vw, 28px); display: flex; flex-direction: column; gap: 14px; height: 100%;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div aria-hidden="true" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: rgb(29, 29, 29); color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600; flex-shrink: 0;">{{ mb_substr($review->name, 0, 1) }}</div>
                                <div>
                                    <div style="font-size: 15px; font-weight: 600; color: rgb(32, 32, 32);"><span>{{ $review->name }}</span></div>
                                    <div style="font-size: 12px; color: rgb(139, 135, 127);"><span>{{ $review->created_at->diffForHumans(['parts' => 1]) }}</span>@if ($review->verified_purchase)<span style="color: rgb(76, 175, 125);"> · Verified</span>@endif</div>
                                </div>
                            </div>
                            <div style="font-size: 13px; letter-spacing: 0.06em; color: rgb(29, 29, 29);" role="img" aria-label="{{ $review->rating }} / 5"><span>{{ $starText($review->rating) }}</span></div>
                        </div>
                        @if ($review->title)<p style="margin: 0px; font-size: 15px; font-weight: 600; color: rgb(32, 32, 32);">{{ $review->title }}</p>@endif
                        <p style="margin: 0px; font-size: 15px; line-height: 24px; color: rgb(85, 85, 85);"><span>{{ $review->body }}</span></p>
                    </div>
                    </div>
                @endforeach
            </div>
            @if ($reviews->count() > $shown)
                <div x-show="limit < {{ $reviews->count() }}">
                <div style="display: flex; justify-content: center; margin-top: 32px;">
                    <button type="button" @click="limit += {{ $shown }}" class="scpc" style="border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; padding: 15px 40px; cursor: pointer; transition: background 250ms, color 250ms;"><span x-text="'Load more reviews (' + Math.min({{ $shown }}, {{ $reviews->count() }} - limit) + ')'">Load more reviews ({{ min($shown, $reviews->count() - $shown) }})</span></button>
                </div>
                </div>
            @endif
        @endif
    </div>
</section>
