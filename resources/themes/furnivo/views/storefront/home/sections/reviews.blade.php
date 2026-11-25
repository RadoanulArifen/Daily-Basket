{{-- Customer reviews: the most recent published ones, with the piece they are about. --}}
@php
    $recentReviews = \App\Models\ProductReview::query()
        ->where('status', 'approved')->whereNotNull('body')
        ->with('product')->latest('created_at')->limit(3)->get()
        ->filter(fn ($r) => $r->product);
@endphp
@if ($recentReviews->isNotEmpty())
    <section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(56px, 7vw, 96px) 0px;" aria-labelledby="fv-reviews">
        <div style="{{ $pad }}">
            <h2 id="fv-reviews" style="font-size: clamp(22px, 2.4vw, 32px); letter-spacing: -0.02em; color: rgb(29, 29, 29);">{{ __('store.theme.what_clients_say') }}</h2>
            <div style="margin-top: 28px; display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                @foreach ($recentReviews as $review)
                    <figure style="margin: 0px; border: 1px solid rgb(236, 236, 236); padding: 26px;">
                        <div aria-label="{{ $review->rating }}/5" style="font-size: 13px; letter-spacing: 2px; color: rgb(29, 29, 29);">
                            {{ str_repeat('★', (int) $review->rating) }}<span style="color: rgb(201, 197, 191);">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                        </div>
                        <blockquote style="margin: 14px 0px 0px; font-size: 15px; line-height: 1.7; color: rgb(85, 85, 85);">{{ \Illuminate\Support\Str::limit($review->body, 180) }}</blockquote>
                        <figcaption style="margin-top: 16px; font-size: 13px; color: rgb(139, 135, 127);">
                            {{ $review->name }} · <a href="{{ store_route('products.show', ['slug' => $review->product->slug]) }}" style="color: inherit;">{{ $review->product->name }}</a>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
