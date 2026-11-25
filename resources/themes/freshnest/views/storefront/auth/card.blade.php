{{-- The design's sign-in panel (the account pop-up), as a page: green header, Sign in /
     Create account switch, fields. $mode = 'login' | 'register'. --}}
@php
    $inp = 'border:1.5px solid var(--fn-line); border-radius:12px; padding:13px 15px; font-size:15px; font-weight:400; outline:none; background:var(--fn-canvas); color:var(--fn-ink);';
    $lab = 'display:flex; flex-direction:column; gap:7px; font-size:13.5px; font-weight:600;';
    $modes = [['login', __('Sign in'), store_route('login')], ['register', __('Create account'), store_route('register')]];
@endphp
<div class="wrap" style="padding-top:clamp(32px,5vw,64px); padding-bottom:clamp(40px,6vw,80px); display:flex; justify-content:center;">
  <div style="width:min(440px, 100%); background:#FFFFFF; border-radius:24px; box-shadow:0 30px 70px rgba(25,75,48,.14); overflow:hidden; color:var(--fn-ink);">
    <div style="background:linear-gradient(135deg,var(--fn-primary-dark),var(--fn-primary)); color:#FFFFFF; padding:26px 26px 22px;">
      <h1 style="margin:0; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:22px;">{{ $title }}</h1>
      <span style="display:block; font-size:14px; color:var(--fn-primary-pale); margin-top:6px;">{{ $sub }}</span>
    </div>
    <div style="padding:20px 26px 26px;">
      <nav style="display:grid; grid-template-columns:1fr 1fr; gap:6px; background:#F1F7F2; padding:5px; border-radius:13px;">
        @foreach ($modes as [$key, $label, $href])
          <a href="{{ $href }}" @if ($key === $mode) aria-current="page" @endif style="text-align:center; background:{{ $key === $mode ? '#FFFFFF' : 'transparent' }}; color:{{ $key === $mode ? 'var(--fn-primary-dark)' : 'var(--fn-muted)' }}; font-weight:700; font-size:14px; padding:11px; border-radius:10px; box-shadow:{{ $key === $mode ? '0 2px 8px rgba(25,75,48,.12)' : 'none' }};">{{ $label }}</a>
        @endforeach
      </nav>
      @if (session('status'))<p role="status" style="margin:16px 0 0; padding:10px 12px; border-radius:10px; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-size:13.5px;">{{ session('status') }}</p>@endif
      <form method="POST" action="{{ $mode === 'login' ? store_route('login.store') : store_route('register.store') }}" style="display:flex; flex-direction:column; gap:14px; margin-top:18px;">
        @csrf
        @if ($mode === 'register')
          <label style="{{ $lab }}">{{ __('Full name') }}<input name="name" type="text" value="{{ old('name') }}" required autocomplete="name" placeholder="{{ __('Jamie Rivera') }}" style="{{ $inp }}">@error('name')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        @endif
        <label style="{{ $lab }}">{{ __('Email') }}<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="{{ __('you@email.com') }}" style="{{ $inp }}">@error('email')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        @if ($mode === 'register')
          <label style="{{ $lab }}">{{ __('Phone') }}<input name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" dir="ltr" placeholder="+1 (555) 000-0000" style="{{ $inp }}">@error('phone')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        @endif
        <label style="{{ $lab }}">{{ __('Password') }}<input name="password" type="password" required autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" placeholder="{{ __('At least 8 characters') }}" style="{{ $inp }}">@error('password')<span style="font-size:13px; font-weight:500; color:#D4380D;">{{ $message }}</span>@enderror</label>
        @if ($mode === 'register')
          <label style="{{ $lab }}">{{ __('Confirm password') }}<input name="password_confirmation" type="password" required autocomplete="new-password" style="{{ $inp }}"></label>
        @else
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:13.5px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="remember" value="1" style="accent-color:var(--fn-primary); width:16px; height:16px;">{{ __('Remember me') }}</label>
            <a href="{{ store_route('password.request') }}" style="font-weight:600;">{{ __('Forgot password?') }}</a>
          </div>
        @endif
        <button class="fh-92c14d" type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 22px; margin-top:4px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15.5px; border-radius:999px; cursor:pointer;"><span>{{ $mode === 'login' ? __('Sign in') : __('Create account') }}</span><span style="display:inline-flex; width:34px; height:34px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
      </form>
      @if ($mode === 'login' && ($demoAvailable ?? false))
        <form method="POST" action="{{ store_route('login.demo') }}" style="margin-top:14px;">
          @csrf
          <button class="fh-74ffb8" type="submit" style="width:100%; min-height:46px; border:1px solid var(--fn-primary-pale); border-radius:999px; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-weight:700;">{{ __('store.auth.demo_login') }}</button>
          <p style="margin:8px 0 0; text-align:center; font-size:13px; color:var(--fn-muted);">{{ __('store.auth.demo_read_only') }}</p>
        </form>
      @endif
    </div>
  </div>
</div>
