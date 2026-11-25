{{-- "Preview" opens the email exactly as the customer sees it; "Send me a copy" mails it. --}}
<div class="flex flex-wrap items-center gap-3 border-t border-line pt-4">
    <a href="{{ route('admin.settings.emails.preview', $event) }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink underline underline-offset-2">
        <x-icon name="eye" class="size-4" />{{ __('admin.settings.email.preview') }}
    </a>
    <button type="submit" form="email-send-{{ $event }}" class="text-[13px] text-muted underline underline-offset-2">{{ __('admin.settings.email.send_me') }}</button>
</div>
