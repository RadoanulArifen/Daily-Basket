@extends('admin.settings.layout')

@section('title', __('admin.currencies.add'))

@section('settings')
    <x-admin.page-header :title="__('admin.currencies.add')" :back="route('admin.settings.currencies.index')" />

    <form method="POST" action="{{ route('admin.settings.currencies.store') }}">
        @csrf
        @include('admin.settings.currencies._form')
    </form>
@endsection
