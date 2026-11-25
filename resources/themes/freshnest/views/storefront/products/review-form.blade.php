{{-- "Write a review" (the design's button links here). Signed-in customers only; one review each. --}}
@auth('web')
<form id="write" method="POST" action="{{ store_route('reviews.store', ['slug' => $product->slug]) }}" x-data="{ rating: {{ (int) old('rating', 5) }} }" style="margin-top:20px; background:#FFFFFF; border:1px solid #E6F0E7; border-radius:20px; padding:22px; display:flex; flex-direction:column; gap:14px; max-width:720px;">
  @csrf
  <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:17px; color:var(--fn-ink);">{{ __('Write a review') }}</strong>
  <input type="hidden" name="rating" :value="rating" value="{{ (int) old('rating', 5) }}">
  <div role="radiogroup" aria-label="{{ __('Rating') }}" style="display:flex; gap:4px; font-size:24px;">
    @foreach ([1, 2, 3, 4, 5] as $star)
      <button type="button" role="radio" :aria-checked="rating === {{ $star }}" aria-label="{{ $star }} {{ $star === 1 ? __('star') : __('stars') }}" @click="rating = {{ $star }}" :style="{ color: rating >= {{ $star }} ? 'var(--fn-accent)' : 'var(--fn-primary-pale)' }" style="border:0; background:transparent; padding:0; cursor:pointer; color:var(--fn-accent);">★</button>
    @endforeach
  </div>
  <input name="title" value="{{ old('title') }}" maxlength="120" placeholder="{{ __('Title (optional)') }}" aria-label="{{ __('Title') }}" style="border:1.5px solid var(--fn-line); border-radius:12px; padding:12px 14px; font-size:14.5px; outline:none;">
  <textarea name="body" required minlength="10" maxlength="2000" rows="4" placeholder="{{ __('What did you think?') }}" aria-label="{{ __('Your review') }}" style="border:1.5px solid var(--fn-line); border-radius:12px; padding:12px 14px; font-size:14.5px; outline:none; resize:vertical;">{{ old('body') }}</textarea>
  @if ($errors->has('body') || $errors->has('rating'))<span role="alert" style="font-size:13px; color:#D4380D;">{{ $errors->first('body') ?: $errors->first('rating') }}</span>@endif
  <button type="submit" style="align-self:flex-start; display:inline-flex; align-items:center; gap:12px; padding:6px 6px 6px 20px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14.5px; border-radius:999px; cursor:pointer;"><span>{{ __('Post review') }}</span><span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
</form>
@endauth
