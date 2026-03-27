@extends('layouts.admin')

@section('title', __('messages.blackjack'))

@section('content')
@include('partials.notify')
<script>
window.__bjLobby = {
    tables:  {!! json_encode($tables->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},
    routes: {
        create:        '{{ route('entertainment.blackjack.create') }}',
        joinBase:      '{{ url('entertainment/blackjack') }}',
        entertainment: '{{ route('entertainment.index') }}',
    },
    msg: {!! json_encode([
        'entertainmentHall' => __('messages.entertainment_hall'),
        'bjLobby'           => __('messages.bj_lobby'),
        'bjCreateTable'     => __('messages.bj_create_table'),
        'tableName'         => __('messages.table_name'),
        'bjBetRange'        => __('messages.bj_bet_range'),
        'players'           => __('messages.players'),
        'status'            => __('messages.status'),
        'waiting'           => __('messages.waiting'),
        'full'              => __('messages.full'),
        'playing'           => __('messages.playing'),
        'bjJoin'            => __('messages.bj_join'),
        'cancel'            => __('messages.cancel'),
        'minBuyIn'          => __('messages.min_buy_in'),
        'maxBuyIn'          => __('messages.max_buy_in'),
        'maxPlayers'        => __('messages.max_players'),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},
};
</script>
<blackjack-lobby
    :init-tables="window.__bjLobby.tables"
    :routes="window.__bjLobby.routes"
    csrf="{{ csrf_token() }}"
    :msg="window.__bjLobby.msg"
></blackjack-lobby>
@endsection
