@extends('admin.settings.layout')

@section('title', $currency->code)

@section('settings')
    <x-admin.page-header :title="$currency->code.' · '.$currency->name" :back="route('admin.settings.currencies.index')">
        @unless ($currency->is_base)
            <x-slot:actions>
                <x-admin.confirm-delete
                    :action="route('admin.settings.currencies.destroy', $currency)"
                    :title="__('admin.currencies.delete_title', ['code' => $currency->code])"
                    :message="__('admin.currencies.delete_text')" />
            </x-slot:actions>
        @endunless
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.settings.currencies.update', $currency) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.currencies._form')
    </form>
@endsection
