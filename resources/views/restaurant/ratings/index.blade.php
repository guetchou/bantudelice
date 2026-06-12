@extends('layouts.restaurant_app')
@section('title', 'Avis clients')
@section('topbar_title', 'Avis clients')
@section('ratings_nav', 'active')

@section('style')
<style>
/* ── Avis clients ─────────────────────────────────────────────────────────── */
.rav-page { max-width: 860px; margin: 0 auto; padding: 0 0 60px; }

/* Score global */
.rav-score-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 28px 24px;
    display: flex;
    align-items: center;
    gap: 32px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.rav-score-big {
    text-align: center;
    min-width: 110px;
}
.rav-score-num {
    font-size: 3.2rem;
    font-weight: 800;
    color: #111827;
    line-height: 1;
}
.rav-stars-row { display: flex; gap: 3px; justify-content: center; margin: 6px 0 4px; }
.rav-star      { font-size: 1.1rem; }
.rav-star.on   { color: #f59e0b; }
.rav-star.off  { color: #e5e7eb; }
.rav-score-sub { font-size: .78rem; color: #6b7280; }

/* Distribution bars */
.rav-dist { flex: 1; min-width: 200px; }
.rav-dist-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.rav-dist-lbl { font-size: .78rem; color: #374151; width: 18px; text-align: right; flex-shrink: 0; }
.rav-dist-bar-wrap {
    flex: 1;
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}
.rav-dist-bar { height: 100%; background: #f59e0b; border-radius: 4px; transition: width .4s; }
.rav-dist-cnt { font-size: .72rem; color: #9ca3af; width: 24px; text-align: left; flex-shrink: 0; }

/* Filters */
.rav-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.rav-filter-btn {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: .82rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    text-decoration: none;
    transition: background .15s, color .15s, border-color .15s;
    white-space: nowrap;
}
.rav-filter-btn:hover { border-color: #007836; color: #007836; }
.rav-filter-btn.active { background: #007836; color: #fff; border-color: #007836; }

/* Rating cards */
.rav-list { display: flex; flex-direction: column; gap: 12px; }

.rav-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 16px 18px;
}
.rav-card-head {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}
.rav-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background: #f3f4f6;
}
.rav-avatar-placeholder {
    width: 38px; height: 38px; border-radius: 50%;
    background: #007836; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 700; flex-shrink: 0;
}
.rav-client-info { flex: 1; min-width: 0; }
.rav-client-name {
    font-size: .88rem;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rav-date { font-size: .74rem; color: #9ca3af; margin-top: 1px; }
.rav-rating-stars { display: flex; gap: 2px; flex-shrink: 0; }
.rav-rating-stars .rav-star { font-size: .9rem; }

.rav-comment {
    font-size: .85rem;
    color: #374151;
    line-height: 1.5;
    margin-bottom: 8px;
}
.rav-no-comment { font-size: .82rem; color: #9ca3af; font-style: italic; }

.rav-card-foot {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rav-order-link {
    font-size: .74rem;
    color: #007836;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}
.rav-order-link:hover { text-decoration: underline; }
.rav-score-badge {
    font-size: .7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}
.rav-score-5,.rav-score-4 { background: #dcfce7; color: #166534; }
.rav-score-3 { background: #fef9c3; color: #854d0e; }
.rav-score-2,.rav-score-1 { background: #fee2e2; color: #991b1b; }

/* Empty */
.rav-empty {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
}
.rav-empty-icon { font-size: 2.5rem; margin-bottom: 12px; color: #d1d5db; }
.rav-empty h3   { font-size: 1rem; color: #374151; margin-bottom: 6px; }

/* Pagination */
.rav-pagination { margin-top: 24px; display: flex; justify-content: center; }
</style>
@endsection

@section('content')
<div class="rav-page">

    {{-- Score global ─────────────────────────────────────────────────────── --}}
    <div class="rav-score-card">
        <div class="rav-score-big">
            <div class="rav-score-num">{{ number_format($avgRating, 1) }}</div>
            <div class="rav-stars-row">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star rav-star {{ $i <= round($avgRating) ? 'on' : 'off' }}"></i>
                @endfor
            </div>
            <div class="rav-score-sub">{{ $totalCount }} {{ $totalCount > 1 ? 'avis' : 'avis' }}</div>
        </div>

        <div class="rav-dist">
            @php $maxCnt = $distribution->max('cnt') ?: 1; @endphp
            @for($star = 5; $star >= 1; $star--)
                @php $cnt = $distribution->get($star)?->cnt ?? 0; @endphp
                <div class="rav-dist-row">
                    <span class="rav-dist-lbl">{{ $star }}</span>
                    <i class="fas fa-star" style="color:#f59e0b;font-size:.7rem;flex-shrink:0;"></i>
                    <div class="rav-dist-bar-wrap">
                        <div class="rav-dist-bar" style="width:{{ $totalCount > 0 ? round(($cnt / $totalCount) * 100) : 0 }}%;"></div>
                    </div>
                    <span class="rav-dist-cnt">{{ $cnt }}</span>
                </div>
            @endfor
        </div>
    </div>

    {{-- Filtres ──────────────────────────────────────────────────────────── --}}
    <div class="rav-filters">
        <a href="{{ route('restaurant.ratings') }}" class="rav-filter-btn {{ $filter === 'all' ? 'active' : '' }}">
            Tous ({{ $totalCount }})
        </a>
        <a href="{{ route('restaurant.ratings', ['filter' => 'good']) }}" class="rav-filter-btn {{ $filter === 'good' ? 'active' : '' }}">
            <i class="fas fa-thumbs-up"></i>
            Excellents ({{ ($distribution->get(5)?->cnt ?? 0) + ($distribution->get(4)?->cnt ?? 0) }})
        </a>
        <a href="{{ route('restaurant.ratings', ['filter' => 'ok']) }}" class="rav-filter-btn {{ $filter === 'ok' ? 'active' : '' }}">
            Moyens ({{ $distribution->get(3)?->cnt ?? 0 }})
        </a>
        <a href="{{ route('restaurant.ratings', ['filter' => 'bad']) }}" class="rav-filter-btn {{ $filter === 'bad' ? 'active' : '' }}">
            <i class="fas fa-thumbs-down"></i>
            À améliorer ({{ ($distribution->get(2)?->cnt ?? 0) + ($distribution->get(1)?->cnt ?? 0) }})
        </a>
    </div>

    {{-- Liste ────────────────────────────────────────────────────────────── --}}
    @if($ratings->isEmpty())
        <div class="rav-empty">
            <div class="rav-empty-icon"><i class="far fa-star"></i></div>
            <h3>Aucun avis{{ $filter !== 'all' ? ' dans ce filtre' : '' }}</h3>
            <p>Les avis de vos clients apparaîtront ici après chaque livraison.</p>
        </div>
    @else
        <div class="rav-list">
            @foreach($ratings as $rating)
                @php
                    $user      = $rating->user;
                    $firstName = $user ? trim(explode(' ', (string) $user->name)[0]) : null;
                    $initial   = $firstName ? strtoupper(substr($firstName, 0, 1)) : 'C';
                    $userName  = $user?->name ?? 'Client anonyme';
                    $score     = (int) $rating->rating;
                    $orderNo   = $rating->order?->order_no ?? null;
                @endphp
                <div class="rav-card">
                    <div class="rav-card-head">
                        @if($user && $user->avatar)
                            <img src="{{ asset('images/profile/' . $user->avatar) }}" alt="{{ $userName }}" class="rav-avatar">
                        @else
                            <div class="rav-avatar-placeholder">{{ $initial }}</div>
                        @endif

                        <div class="rav-client-info">
                            <div class="rav-client-name">{{ $userName }}</div>
                            <div class="rav-date">
                                <i class="far fa-clock" style="margin-right:3px;"></i>
                                {{ $rating->created_at->diffForHumans() }}
                            </div>
                        </div>

                        <div class="rav-rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star rav-star {{ $i <= $score ? 'on' : 'off' }}"></i>
                            @endfor
                        </div>
                    </div>

                    @if($rating->reviews)
                        <p class="rav-comment">"{{ $rating->reviews }}"</p>
                    @else
                        <p class="rav-no-comment">Aucun commentaire laissé</p>
                    @endif

                    <div class="rav-card-foot">
                        <span class="rav-score-badge rav-score-{{ $score }}">
                            {{ $score }}/5
                        </span>
                        @if($orderNo)
                            <a href="{{ route('restaurant.all_orders') }}?focus={{ $orderNo }}" class="rav-order-link">
                                <i class="fas fa-receipt"></i> Commande #{{ $orderNo }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($ratings->hasPages())
            <div class="rav-pagination">
                {{ $ratings->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
