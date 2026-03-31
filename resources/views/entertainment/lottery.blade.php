@extends('layouts.admin')

@section('title', 'Xổ Số Zoo')

@section('content')
@include('partials.notify')
<script>
window.__lottery = {
    daily: {!! json_encode([
        'id'              => $daily->id,
        'type'            => $daily->type,
        'status'          => $daily->status,
        'draw_at'            => $daily->draw_at->toIso8601String(),
        'opens_at'           => $daily->opens_at?->toIso8601String(),
        'seconds_left'       => $daily->secondsUntilClose(),
        'seconds_until_draw' => $daily->secondsUntilDraw(),
        'seconds_until_open' => $daily->secondsUntilOpen(),
        'winning_numbers' => $daily->winning_numbers,
        'multiplier'      => $daily->multiplier,
        'pick_count'      => $daily->pick_count,
        'total_tickets'   => $daily->total_tickets,
        'total_pot'       => $daily->total_pot,
        'my_tickets'      => $dailyTickets->map(fn($t) => [
            'id'            => $t->id,
            'picked_number' => $t->picked_number,
            'bet_amount'    => $t->bet_amount,
            'is_winner'     => $t->is_winner,
            'payout'        => $t->payout,
        ])->values(),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},
    weekly: {!! json_encode([
        'id'              => $weekly->id,
        'type'            => $weekly->type,
        'status'          => $weekly->status,
        'draw_at'            => $weekly->draw_at->toIso8601String(),
        'opens_at'           => $weekly->opens_at?->toIso8601String(),
        'seconds_left'       => $weekly->secondsUntilClose(),
        'seconds_until_draw' => $weekly->secondsUntilDraw(),
        'seconds_until_open' => $weekly->secondsUntilOpen(),
        'winning_numbers' => $weekly->winning_numbers,
        'multiplier'      => $weekly->multiplier,
        'pick_count'      => $weekly->pick_count,
        'total_tickets'   => $weekly->total_tickets,
        'total_pot'       => $weekly->total_pot,
        'my_tickets'      => $weeklyTickets->map(fn($t) => [
            'id'            => $t->id,
            'picked_number' => $t->picked_number,
            'bet_amount'    => $t->bet_amount,
            'is_winner'     => $t->is_winner,
            'payout'        => $t->payout,
        ])->values(),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},
    jackpot: {!! json_encode([
        'id'                 => $jackpot->id,
        'status'             => $jackpot->status,
        'draw_at'            => $jackpot->draw_at?->toIso8601String(),
        'seconds_until_draw' => $jackpot->secondsUntilJackpotDraw(),
        'total_tickets'      => $jackpot->total_tickets,
        'total_pot'          => $jackpot->total_pot,
        'ticket_price'       => $jackpot->ticket_price,
        'pick_count'         => $jackpot->pick_count,
        'my_tickets'         => $myJackpotTickets->map(fn($t) => [
            'id'             => $t->id,
            'picked_number'  => $t->picked_number,
            'picked_numbers' => $t->picked_numbers,
            'bet_amount'     => $t->bet_amount,
            'is_winner'      => $t->is_winner,
            'payout'         => $t->payout,
        ])->values(),
    ], JSON_HEX_TAG | JSON_HEX_APOS) !!},
    recentJackpot: {!! json_encode($recentJackpot->map(fn($d) => [
        'id'              => $d->id,
        'drawn_at'        => $d->drawn_at?->format('d/m H:i'),
        'winning_numbers' => $d->winning_numbers,
        'total_tickets'   => $d->total_tickets,
        'total_pot'       => $d->total_pot,
    ])->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},
    recentDaily:  {!! json_encode($recentDaily->map(fn($d) => [
        'id'              => $d->id,
        'draw_at'         => $d->draw_at->format('d/m H:i'),
        'winning_numbers' => $d->winning_numbers,
        'total_tickets'   => $d->total_tickets,
        'total_pot'       => $d->total_pot,
        'total_payout'    => $d->total_payout,
    ])->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},
    recentWeekly: {!! json_encode($recentWeekly->map(fn($d) => [
        'id'              => $d->id,
        'draw_at'         => $d->draw_at->format('d/m H:i'),
        'winning_numbers' => $d->winning_numbers,
        'total_tickets'   => $d->total_tickets,
        'total_pot'       => $d->total_pot,
        'total_payout'    => $d->total_payout,
    ])->values(), JSON_HEX_TAG | JSON_HEX_APOS) !!},
    balance:  {{ $balance }},
    myHistory: {!! json_encode($myHistory, JSON_HEX_TAG | JSON_HEX_APOS) !!},
    lastSettledDaily:  {!! json_encode($lastSettledDaily,  JSON_HEX_TAG | JSON_HEX_APOS) !!},
    lastSettledWeekly: {!! json_encode($lastSettledWeekly, JSON_HEX_TAG | JSON_HEX_APOS) !!},
    routes: {
        state:   '{{ route('entertainment.lottery.state') }}',
        ticket:  '{{ route('entertainment.lottery.ticket') }}',
        jackpot: '{{ route('entertainment.lottery.jackpot') }}',
        back:    '{{ route('entertainment.index') }}',
    },
    csrf: '{{ csrf_token() }}',
};
</script>
<lottery-lobby
    :init-daily="window.__lottery.daily"
    :init-weekly="window.__lottery.weekly"
    :init-jackpot="window.__lottery.jackpot"
    :init-recent-jackpot="window.__lottery.recentJackpot"
    :init-recent-daily="window.__lottery.recentDaily"
    :init-recent-weekly="window.__lottery.recentWeekly"
    :init-balance="window.__lottery.balance"
    :init-history="window.__lottery.myHistory"
    :init-last-settled-daily="window.__lottery.lastSettledDaily"
    :init-last-settled-weekly="window.__lottery.lastSettledWeekly"
    :routes="window.__lottery.routes"
    :csrf="window.__lottery.csrf"
></lottery-lobby>
@endsection
