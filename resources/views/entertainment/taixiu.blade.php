@extends('layouts.admin')

@section('title', __('messages.taixiu_lobby'))

@section('content')
@include('partials.notify')
<script>
window.__txLobby = {
    tables: {!! json_encode($tables->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},
    routes: {
        create:        '{{ route('entertainment.taixiu.create') }}',
        joinBase:      '{{ url('entertainment/taixiu') }}',
        list:          '{{ route('entertainment.taixiu') }}',
        entertainment: '{{ route('entertainment.index') }}',
    },
    msg: {!! json_encode([
        'entertainmentHall' => __('messages.entertainment_hall'),
        'txLobby'           => __('messages.taixiu_lobby'),
        'txCreateTable'     => __('messages.tx_create_table'),
        'tableName'         => __('messages.table_name'),
        'minBet'            => __('messages.tx_min_bet'),
        'maxBet'            => __('messages.tx_max_bet'),
        'maxPlayers'        => __('messages.max_players'),
        'players'           => __('messages.players'),
        'status'            => __('messages.status'),
        'waiting'           => __('messages.waiting'),
        'playing'           => __('messages.playing'),
        'full'              => __('messages.full'),
        'txJoin'            => __('messages.tx_join'),
        'cancel'            => __('messages.cancel'),
        'failJoin'          => __('messages.tx_fail_join'),
        'failCreate'        => __('messages.tx_fail_create'),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},
};
</script>
<taixiu-lobby
    :init-tables="window.__txLobby.tables"
    :routes="window.__txLobby.routes"
    csrf="{{ csrf_token() }}"
    :msg="window.__txLobby.msg"
></taixiu-lobby>
@endsection
