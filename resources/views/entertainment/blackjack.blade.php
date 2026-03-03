@extends('layouts.admin')

@section('title', __('messages.blackjack'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.blackjack') }}</h3>
    </div>
    <div class="card-body">
        <p>{{ __('messages.coming_soon') }}</p>
    </div>
</div>
@endsection
