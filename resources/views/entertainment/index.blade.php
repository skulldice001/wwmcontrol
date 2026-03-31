@extends('layouts.admin')

@section('title', __('messages.entertainment_hall'))

@push('styles')
<style>
/* ── Entertainment Hall ─────────────────────────────────── */
.ent-hall-title {
    font-size: 28px; font-weight: 900; color: #fff;
    text-align: center; margin-bottom: 32px; letter-spacing: 1px;
    text-shadow: 0 2px 8px rgba(0,0,0,.5);
}
.ent-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 28px;
}

/* ── Game card ─── */
.ent-card {
    position: relative; border-radius: 20px; overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,.5);
    transition: transform .28s cubic-bezier(.34,1.56,.64,1), box-shadow .28s ease;
    text-decoration: none; display: block; min-height: 220px;
    cursor: pointer;
}
.ent-card:hover {
    transform: translateY(-8px) scale(1.01);
    box-shadow: 0 20px 60px rgba(0,0,0,.65);
    text-decoration: none;
}

/* Poker – casino green felt */
.ent-card-poker {
    background: linear-gradient(145deg, #0b3d1e 0%, #1a6b38 55%, #0b3d1e 100%);
}
.ent-card-poker .ent-deco {
    content: '';
    position: absolute; inset: 0; opacity: .06;
    background-image:
        radial-gradient(circle at 20% 30%, #fff 1px, transparent 1px),
        radial-gradient(circle at 80% 70%, #fff 1px, transparent 1px),
        radial-gradient(circle at 50% 50%, #fff 1px, transparent 1px);
    background-size: 40px 40px;
}
.ent-card-poker .ent-suit-bg {
    position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
    font-size: 120px; opacity: .07; line-height: 1; pointer-events: none;
    user-select: none; color: #fff;
}

/* Blackjack – midnight blue */
.ent-card-blackjack {
    background: linear-gradient(145deg, #080e1c 0%, #132040 55%, #080e1c 100%);
}
.ent-card-blackjack .ent-suit-bg {
    position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
    font-size: 120px; opacity: .09; line-height: 1; pointer-events: none;
    user-select: none; color: #fff;
}

/* Bingo – deep teal */
.ent-card-bingo {
    background: linear-gradient(145deg, #021a1a 0%, #0a4a4a 55%, #021a1a 100%);
}
.ent-card-bingo .ent-suit-bg {
    position: absolute; right: -6px; top: 50%; transform: translateY(-50%);
    font-size: 90px; opacity: .1; line-height: 1; pointer-events: none;
    user-select: none; color: #1abc9c; letter-spacing: 2px;
}
.ent-card-bingo .ent-card-tag { background: rgba(26,188,156,.15); color: #1abc9c; border: 1px solid rgba(26,188,156,.35); }
.ent-card-bingo .ent-card-stripe { background: linear-gradient(90deg,#0e6655,#1abc9c,#0e6655); }
.ent-card-bingo .ent-play-btn { background: linear-gradient(90deg,#0e6655,#1abc9c); color: #fff; }

/* Lottery – deep purple/gold */
.ent-card-lottery {
    background: linear-gradient(145deg, #1a0533 0%, #4a1a7a 55%, #1a0533 100%);
}
.ent-card-lottery .ent-suit-bg {
    position: absolute; right: -6px; top: 50%; transform: translateY(-50%);
    font-size: 100px; opacity: .1; line-height: 1; pointer-events: none;
    user-select: none; color: #f0c040;
}
.ent-card-lottery .ent-card-tag { background: rgba(240,192,64,.15); color: #f0c040; border: 1px solid rgba(240,192,64,.35); }
.ent-card-lottery .ent-card-stripe { background: linear-gradient(90deg,#7b2fbe,#f0c040,#7b2fbe); }
.ent-card-lottery .ent-play-btn { background: linear-gradient(90deg,#7b2fbe,#a855f7); color: #fff; }

/* Tiến Lên – forest green */
.ent-card-tienlen {
    background: linear-gradient(145deg, #0a2010 0%, #1a5030 55%, #0a2010 100%);
}
.ent-card-tienlen .ent-suit-bg {
    position: absolute; right: -6px; top: 50%; transform: translateY(-50%);
    font-size: 100px; opacity: .1; line-height: 1; pointer-events: none;
    user-select: none; color: #4caf50;
}
.ent-card-tienlen .ent-card-tag { background: rgba(76,175,80,.15); color: #4caf50; border: 1px solid rgba(76,175,80,.35); }
.ent-card-tienlen .ent-card-stripe { background: linear-gradient(90deg,#1b5e20,#4caf50,#1b5e20); }
.ent-card-tienlen .ent-play-btn { background: linear-gradient(90deg,#1b5e20,#4caf50); color: #fff; }

/* Tài Xỉu – crimson red */
.ent-card-taixiu {
    background: linear-gradient(145deg, #2e0606 0%, #8b1a1a 55%, #2e0606 100%);
}
.ent-card-taixiu .ent-suit-bg {
    position: absolute; right: -6px; top: 50%; transform: translateY(-50%);
    font-size: 100px; opacity: .1; line-height: 1; pointer-events: none;
    user-select: none; color: #fff; letter-spacing: -4px;
}

/* Shimmer overlay on hover */
.ent-card::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.04) 0%, transparent 60%);
    pointer-events: none;
}

/* Card body */
.ent-card-body {
    position: relative; z-index: 2;
    padding: 28px 28px 24px;
    height: 100%; display: flex; flex-direction: column; justify-content: space-between;
}
.ent-card-tag {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: 12px;
}
.ent-card-poker    .ent-card-tag { background: rgba(46,204,113,.2); color: #2ecc71; border: 1px solid #2ecc7155; }
.ent-card-blackjack .ent-card-tag { background: rgba(52,152,219,.2); color: #5dade2; border: 1px solid #3498db55; }
.ent-card-taixiu   .ent-card-tag { background: rgba(231,76,60,.2);  color: #e74c3c; border: 1px solid #e74c3c55; }

.ent-card-title { font-size: 26px; font-weight: 900; color: #fff; margin-bottom: 4px; }
.ent-card-sub   { font-size: 13px; color: rgba(255,255,255,.4); margin-bottom: 20px; }

/* Stats row */
.ent-stats { display: flex; gap: 10px; margin-bottom: 22px; }
.ent-stat {
    flex: 1; background: rgba(0,0,0,.3); border-radius: 10px;
    padding: 8px 6px; text-align: center;
    border: 1px solid rgba(255,255,255,.07);
}
.ent-stat-num { font-size: 20px; font-weight: 800; color: #fff; line-height: 1; }
.ent-stat-lbl { font-size: 9px; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .8px; margin-top: 3px; }

/* Play button */
.ent-play-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 10px;
    font-weight: 800; font-size: 14px;
    text-decoration: none; transition: all .2s; border: none;
}
.ent-play-btn:hover { transform: scale(1.04); text-decoration: none; }
.ent-card-poker    .ent-play-btn { background: linear-gradient(90deg,#229954,#2ecc71); color: #fff; }
.ent-card-blackjack .ent-play-btn { background: linear-gradient(90deg,#1f618d,#3498db); color: #fff; }
.ent-card-taixiu   .ent-play-btn { background: linear-gradient(90deg,#a93226,#e74c3c); color: #fff; }

/* Top stripe */
.ent-card-stripe {
    height: 3px; width: 100%;
    position: absolute; top: 0; left: 0; right: 0;
}
.ent-card-poker    .ent-card-stripe { background: linear-gradient(90deg,#1e8449,#2ecc71,#1e8449); }
.ent-card-blackjack .ent-card-stripe { background: linear-gradient(90deg,#1f618d,#3498db,#1f618d); }
.ent-card-taixiu   .ent-card-stripe { background: linear-gradient(90deg,#922b21,#e74c3c,#922b21); }
</style>
@endpush

@section('content')
<h2 class="ent-hall-title"><i class="fas fa-gamepad mr-2"></i>{{ __('messages.entertainment_hall') }}</h2>

<div class="ent-grid">

    {{-- Poker --}}
    <a href="{{ route('entertainment.poker') }}" class="ent-card ent-card-poker">
        <div class="ent-card-stripe"></div>
        <div class="ent-deco"></div>
        <div class="ent-suit-bg">♠</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Texas Hold'em</div>
                <div class="ent-card-title">{{ __('messages.poker_texas') }}</div>
                <div class="ent-card-sub">No Limit · Pot Limit · Cash Game</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num">{{ $stats['poker']['total'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_total') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#2ecc71;">{{ $stats['poker']['playing'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_playing') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f6c23e;">{{ $stats['poker']['waiting'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_waiting') }}</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-play"></i> {{ __('messages.play_now') }}
            </span>
        </div>
    </a>

    {{-- Blackjack --}}
    <a href="{{ route('entertainment.blackjack') }}" class="ent-card ent-card-blackjack">
        <div class="ent-card-stripe"></div>
        <div class="ent-suit-bg">♦</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Card Game</div>
                <div class="ent-card-title">{{ __('messages.blackjack') }}</div>
                <div class="ent-card-sub">{{ __('messages.blackjack_description') }}</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num">{{ $stats['blackjack']['total'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_total') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#5dade2;">{{ $stats['blackjack']['playing'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_playing') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f6c23e;">{{ $stats['blackjack']['waiting'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_waiting') }}</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-play"></i> {{ __('messages.play_now') }}
            </span>
        </div>
    </a>

    {{-- Bingo --}}
    <a href="{{ route('entertainment.bingo') }}" class="ent-card ent-card-bingo">
        <div class="ent-card-stripe"></div>
        <div class="ent-suit-bg">B I N G O</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Bingo</div>
                <div class="ent-card-title">Bingo Zoo</div>
                <div class="ent-card-sub">5×5 · 75 số · Bắt đầu khi đủ 2 người</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num">{{ $stats['bingo']['total'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_total') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#1abc9c;">{{ $stats['bingo']['playing'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_playing') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f6c23e;">{{ $stats['bingo']['waiting'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_waiting') }}</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-th"></i> Chơi Ngay
            </span>
        </div>
    </a>

    {{-- Lottery --}}
    <a href="{{ route('entertainment.lottery') }}" class="ent-card ent-card-lottery">
        <div class="ent-card-stripe"></div>
        <div class="ent-suit-bg">🎱</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Lottery</div>
                <div class="ent-card-title">Xổ Số Zoo</div>
                <div class="ent-card-sub">Hàng ngày ×10 · Hàng tuần ×70 · Số 01–99</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f0c040;">{{ number_format($stats['lottery']['daily_pot']) }}</div>
                        <div class="ent-stat-lbl">Quỹ hôm nay</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#a855f7;">{{ number_format($stats['lottery']['weekly_pot']) }}</div>
                        <div class="ent-stat-lbl">Quỹ tuần</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-ticket-alt"></i> Mua Vé
            </span>
        </div>
    </a>

    {{-- Tiến Lên --}}
    <a href="{{ route('entertainment.tienlen.index') }}" class="ent-card ent-card-tienlen">
        <div class="ent-card-stripe"></div>
        <div class="ent-suit-bg">🃏 ♠</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Card Game</div>
                <div class="ent-card-title">Tiến Lên</div>
                <div class="ent-card-sub">Miền Nam · Miền Bắc · 4 người · Chặt heo</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num">{{ $stats['tienlen']['total'] }}</div>
                        <div class="ent-stat-lbl">Bàn chơi</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#4caf50;">{{ $stats['tienlen']['playing'] }}</div>
                        <div class="ent-stat-lbl">Đang chơi</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f6c23e;">{{ $stats['tienlen']['waiting'] }}</div>
                        <div class="ent-stat-lbl">Chờ người</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-play"></i> Chơi ngay
            </span>
        </div>
    </a>

    {{-- Tài Xỉu --}}
    <a href="{{ route('entertainment.taixiu') }}" class="ent-card ent-card-taixiu">
        <div class="ent-card-stripe"></div>
        <div class="ent-suit-bg">⚄ ⚁</div>
        <div class="ent-card-body">
            <div>
                <div class="ent-card-tag">Dice Game</div>
                <div class="ent-card-title">{{ __('messages.taixiu') }}</div>
                <div class="ent-card-sub">Tài (Hi) · Xỉu (Lo) · 3 Dice · 30s rounds</div>
                <div class="ent-stats">
                    <div class="ent-stat">
                        <div class="ent-stat-num">{{ $stats['taixiu']['total'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_total') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#e74c3c;">{{ $stats['taixiu']['playing'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_playing') }}</div>
                    </div>
                    <div class="ent-stat">
                        <div class="ent-stat-num" style="color:#f6c23e;">{{ $stats['taixiu']['waiting'] }}</div>
                        <div class="ent-stat-lbl">{{ __('messages.tx_rooms_waiting') }}</div>
                    </div>
                </div>
            </div>
            <span class="ent-play-btn">
                <i class="fas fa-play"></i> {{ __('messages.play_now') }}
            </span>
        </div>
    </a>

</div>
@endsection
