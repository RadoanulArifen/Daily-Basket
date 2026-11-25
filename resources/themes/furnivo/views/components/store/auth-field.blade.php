@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false, 'dir' => null])

<label style="display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 16px;">
    <span>{{ $label }}@if ($required)<x-store.required />@endif</span>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @required($required) @if ($dir) dir="{{ $dir }}" @endif
           style="border: 1px solid rgb({{ $errors->has($name) ? '199, 64, 52' : '217, 213, 207' }}); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);">
    @error($name)<span role="alert" style="font-size: 13px; letter-spacing: 0; text-transform: none; color: rgb(199, 64, 52);">{{ $message }}</span>@enderror
</label>
