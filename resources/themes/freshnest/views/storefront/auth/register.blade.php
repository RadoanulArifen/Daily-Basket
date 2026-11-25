@extends('storefront.layouts.app')

{{-- Create account, in the design's account panel. --}}
@section('freshnest', true)
@section('title', __('Create account'))
@section('noindex', true)

@section('content')
    @include('storefront.auth.card', ['mode' => 'register', 'title' => __('Create your account'), 'sub' => __('Save addresses, reorder in one tap and keep a wishlist.')])
@endsection
