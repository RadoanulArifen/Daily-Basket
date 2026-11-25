@extends('storefront.layouts.app')

{{-- Sign in, in the design's account panel. --}}
@section('freshnest', true)
@section('title', __('Sign in'))
@section('noindex', true)

@section('content')
    @include('storefront.auth.card', ['mode' => 'login', 'title' => __('Welcome back'), 'sub' => __('Sign in to track orders and save favourites.')])
@endsection
