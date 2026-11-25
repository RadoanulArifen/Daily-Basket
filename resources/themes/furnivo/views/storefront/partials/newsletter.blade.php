{{-- "Join the List" — markup and styles from the design; subscribes without leaving the page. --}}
<div class="footer-news fx-newsletter" style="margin-top: clamp(44px, 5vw, 64px); padding-top: clamp(40px, 5vw, 56px); border-top: 1px solid rgb(38, 38, 38); display: flex; flex-direction: column; align-items: center; text-align: center;"
     x-data="{ email: '', done: false, error: '', busy: false,
               async send() {
                   this.busy = true; this.error = '';
                   try {
                       const r = await fetch($refs.form.action, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ email: this.email, website: $refs.trap.value }) });
                       const data = await r.json();
                       if (r.ok) { this.done = true; } else { this.error = data.errors?.email?.[0] ?? data.message; }
                   } catch (e) { this.error = @js(__('store.errors.generic')); }
                   this.busy = false;
               } }">
    <div style="font-size: 12px; letter-spacing: 0.24em; text-transform: uppercase; color: rgb(168, 164, 158); margin-bottom: 14px;">Join the List</div>
    <p style="margin: 0px 0px 24px; font-size: 16px; line-height: 24px; color: rgb(207, 204, 199); max-width: none; white-space: nowrap;" x-text="done ? @js(__('store.newsletter.thanks')) : 'First looks at new collections and private previews, straight to your inbox.'">First looks at new collections and private previews, straight to your inbox.</p>
    {{-- x-show sits on a wrapper: Alpine would otherwise remove the form's display:flex --}}
    <div x-show="! done" style="width: min(480px, 100%);">
    <form x-ref="form" method="POST" action="{{ store_route('newsletter.subscribe') }}" @submit.prevent="send()" style="display: flex; border: 1px solid rgb(58, 58, 58); width: min(480px, 100%);">
        @csrf
        <label for="fv-news-email" class="fv-sr">{{ __('store.auth.email') }}</label>
        <input id="fv-news-email" name="email" type="email" required x-model="email" autocomplete="email" placeholder="{{ __('store.theme.your_email') }}" style="flex: 1 1 0%; border: none; background: transparent; color: rgb(255, 255, 255); font-size: 14px; padding: 14px 16px; outline: none;">
        <input x-ref="trap" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position: absolute; left: -9999px;">
        <button style="border: none; background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; padding: 0px 22px; cursor: pointer; white-space: nowrap;" type="submit" :disabled="busy"><span>Subscribe</span></button>
    </form>
    </div>
    <p x-show="error" x-cloak x-text="error" role="alert" style="margin: 12px 0px 0px; font-size: 13px; color: rgb(232, 138, 128);"></p>
</div>
