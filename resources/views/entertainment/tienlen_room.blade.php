@extends('layouts.admin')

@section('title', $table->name . ' – Tiến Lên')

@section('content')
<script>
window.__tienlen = {
    table: {!! json_encode([
        'id'         => $table->id,
        'name'       => $table->name,
        'variant'    => $table->variant,
        'entry_fee'  => $table->entry_fee,
        'status'     => $table->status,
        'is_ai_mode' => $table->is_ai_mode,
        'owner_id'   => $table->owner_id,
        'players'    => $table->players->map(fn($p) => [
            'user_id'  => $p->id,
            'name'     => $p->name,
            'is_ready' => (bool) $p->pivot->is_ready,
            'seat'     => $p->pivot->seat,
        ])->values(),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},

    game: @if($game)
    {!! json_encode((function() use ($game, $table) {
        $state = $game->state;
        // Strip hands of other players
        $userId = auth()->id();
        foreach ($state['players'] as $idx => $p) {
            if (($p['user_id'] ?? null) !== $userId) {
                $state['players'][$idx]['hand'] = count($p['hand']);
            }
        }
        return $state;
    })(), JSON_HEX_TAG | JSON_HEX_APOS) !!}
    @else null @endif,

    myHand: {!! json_encode($myHand, JSON_HEX_TAG | JSON_HEX_APOS) !!},

    messages: {!! json_encode($messages->map(fn($m) => [
        'id'         => $m->id,
        'user_name'  => $m->user->name,
        'body'       => $m->body,
        'created_at' => $m->created_at->toIso8601String(),
    ])->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},

    amReady: {{ $playerRecord->is_ready ? 'true' : 'false' }},

    userId: {{ auth()->id() }},

    routes: {
        lobby:  '{{ route('entertainment.tienlen.index') }}',
        ready:  '{{ route('entertainment.tienlen.ready', $table->id) }}',
        start:  '{{ route('entertainment.tienlen.start', $table->id) }}',
        play:   '{{ route('entertainment.tienlen.play', $table->id) }}',
        state:  '{{ route('entertainment.tienlen.state', $table->id) }}',
        chat:   '{{ route('entertainment.tienlen.chat', $table->id) }}',
        leave:  '{{ route('entertainment.tienlen.leave', $table->id) }}',
    },

    csrf: '{{ csrf_token() }}',
};
</script>

<tienlen-room
    :init-table="window.__tienlen.table"
    :init-game="window.__tienlen.game"
    :init-my-hand="window.__tienlen.myHand"
    :init-messages="window.__tienlen.messages"
    :init-am-ready="window.__tienlen.amReady"
    :routes="window.__tienlen.routes"
    :csrf="window.__tienlen.csrf"
    :user-id="window.__tienlen.userId"
></tienlen-room>
@endsection
