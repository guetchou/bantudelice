@extends('layouts.driver-modern')
@section('title', 'Ma note & avis | ' . \App\Services\ConfigService::getCompanyName())
@section('nav_note', 'is-active')
@section('driver_initials', strtoupper(substr($driver->name ?? 'L', 0, 2)))
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', ($driver->status ?? 'offline') === 'online' ? '' : 'offline')
@section('online_pill_label', ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Ma note & avis clients')

@section('style')
<style>
.nt-body { padding: 20px 24px 48px; display: flex; flex-direction: column; gap: 18px; }

/* Hero */
.nt-hero {
    background: var(--c-dark);
    border-radius: 14px; padding: 22px;
    display: flex; align-items: center; gap: 20px;
    flex-wrap: wrap;
}
.nt-big-score { font-size: 3.2rem; font-weight: 900; color: #fff; line-height: 1; letter-spacing: -.04em; }
.nt-score-sub { font-size: .7rem; color: rgba(255,255,255,.4); font-weight: 600; margin-top: 4px; }
.nt-stars { display: flex; gap: 3px; margin-top: 6px; }
.nt-star-fa { font-size: .9rem; color: var(--c-warn); }
.nt-star-fa.empty { color: rgba(255,255,255,.2); }
.nt-divider { width: 1px; background: rgba(255,255,255,.1); align-self: stretch; flex-shrink: 0; }
.nt-bars { flex: 1; display: flex; flex-direction: column; gap: 7px; min-width: 140px; }
.nt-bar-row { display: flex; align-items: center; gap: 8px; }
.nt-bar-stars { font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.6); width: 22px; flex-shrink: 0; text-align: right; }
.nt-bar-track { flex: 1; height: 6px; border-radius: 99px; background: rgba(255,255,255,.1); overflow: hidden; }
.nt-bar-fill  { height: 100%; border-radius: 99px; background: var(--c-warn); }
.nt-bar-n     { font-size: .68rem; font-weight: 700; color: rgba(255,255,255,.4); width: 20px; text-align: right; }

/* Card */
.nt-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 14px; overflow: hidden; }
.nt-card-head { padding: 14px 16px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; gap: 7px; }
.nt-card-head-icon { font-size: .9rem; color: var(--c-primary); }
.nt-card-head-title { font-size: .85rem; font-weight: 800; color: var(--c-text); }

/* Métriques */
.nt-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 14px 16px; }
.nt-metric { background: var(--c-bg); border: 1px solid var(--c-border); border-radius: 11px; padding: 13px 10px; text-align: center; }
.nt-metric-val { font-size: 1.3rem; font-weight: 900; color: var(--c-text); line-height: 1; }
.nt-metric-val.green { color: var(--c-green-lt); }
.nt-metric-lbl { font-size: .62rem; font-weight: 700; color: var(--c-text-dim); text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Avis */
.nt-review {
    display: flex; align-items: flex-start; gap: 11px;
    padding: 13px 16px; border-bottom: 1px solid var(--c-bg);
}
.nt-review:last-child { border-bottom: none; }
.nt-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--c-primary); display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 900; color: #fff; flex-shrink: 0; }
.nt-review-name    { font-size: .8rem; font-weight: 800; color: var(--c-text); }
.nt-review-stars   { display: flex; gap: 2px; margin-top: 2px; }
.nt-review-star    { font-size: .7rem; color: var(--c-warn); }
.nt-review-star.e  { color: var(--c-border); }
.nt-review-comment { font-size: .78rem; color: var(--c-text-2); margin-top: 5px; line-height: 1.5; font-style: italic; }
.nt-review-date    { font-size: .62rem; color: var(--c-text-dim); margin-top: 3px; }

/* Tips */
.nt-tips { display: flex; flex-direction: column; gap: 0; }
.nt-tip  { display: flex; align-items: flex-start; gap: 11px; padding: 13px 16px; border-bottom: 1px solid var(--c-bg); }
.nt-tip:last-child { border-bottom: none; }
.nt-tip-icon  { width: 32px; height: 32px; border-radius: 9px; background: var(--c-bg); display: flex; align-items: center; justify-content: center; font-size: .85rem; color: var(--c-primary); flex-shrink: 0; }
.nt-tip-title { font-size: .8rem; font-weight: 800; color: var(--c-text); }
.nt-tip-text  { font-size: .75rem; color: var(--c-text-muted); margin-top: 2px; line-height: 1.5; }

.nt-empty { text-align: center; padding: 36px 20px; }

@media (max-width: 768px) {
    .nt-body { padding: 14px 14px 40px; }
    .nt-metrics { grid-template-columns: repeat(2, 1fr); }
    .nt-hero { flex-direction: column; gap: 14px; }
    .nt-divider { display: none; }
}
</style>
@endsection

@section('content')
@php
    use App\Delivery;

    $avgRating    = $driver->rating ?? null;
    $totalRatings = $driver->total_ratings ?? 0;
    $starDist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];

    if (class_exists('\App\Review')) {
        $reviews = \App\Review::where('driver_id', $driver->id)->latest()->take(50)->get();
        foreach ($reviews as $r) $starDist[min(5,max(1,(int)$r->rating))]++;
        $totalRatings = array_sum($starDist);
        $avgRating = $totalRatings > 0 ? round(array_sum(array_map(fn($s,$n)=>$s*$n, array_keys($starDist), array_values($starDist))) / $totalRatings, 1) : null;
    } else {
        $reviews = collect();
    }

    $avgDisplay = $avgRating ? number_format($avgRating,1) : '—';

    $totalDelivered = Delivery::where('driver_id',$driver->id)->where('status','DELIVERED')->count();
    $totalCancelled = Delivery::where('driver_id',$driver->id)->where('status','CANCELLED')->count();
    $successRate = ($totalDelivered + $totalCancelled) > 0
        ? round($totalDelivered / max(1, $totalDelivered + $totalCancelled) * 100)
        : 100;
@endphp

<div class="nt-body">

    {{-- ── HERO NOTE ── --}}
    <div class="nt-hero">
        <div>
            <div class="nt-big-score">{{ $avgDisplay }}</div>
            <div class="nt-stars">
                @for($i = 1; $i <= 5; $i++)
                <i class="fas fa-star nt-star-fa {{ ($avgRating && $i <= round($avgRating)) ? '' : 'empty' }}"></i>
                @endfor
            </div>
            <div class="nt-score-sub">{{ $totalRatings }} avis &middot; note globale</div>
        </div>
        <div class="nt-divider"></div>
        <div class="nt-bars">
            @foreach([5,4,3,2,1] as $s)
            @php $pct = $totalRatings > 0 ? round($starDist[$s]/$totalRatings*100) : 0; @endphp
            <div class="nt-bar-row">
                <span class="nt-bar-stars">{{ $s }}</span>
                <div class="nt-bar-track"><div class="nt-bar-fill" style="width:{{ $pct }}%;"></div></div>
                <span class="nt-bar-n">{{ $starDist[$s] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── PERFORMANCES ── --}}
    <div class="nt-card">
        <div class="nt-card-head">
            <span class="nt-card-head-icon"><i class="fas fa-medal"></i></span>
            <span class="nt-card-head-title">Vos performances</span>
        </div>
        <div class="nt-metrics">
            <div class="nt-metric">
                <div class="nt-metric-val green">{{ $totalDelivered }}</div>
                <div class="nt-metric-lbl">Livrées</div>
            </div>
            <div class="nt-metric">
                <div class="nt-metric-val {{ $successRate >= 90 ? 'green' : '' }}">{{ $successRate }}%</div>
                <div class="nt-metric-lbl">Réussite</div>
            </div>
            <div class="nt-metric">
                <div class="nt-metric-val">{{ $avgDisplay }}</div>
                <div class="nt-metric-lbl">Note moy.</div>
            </div>
        </div>
    </div>

    {{-- ── AVIS CLIENTS ── --}}
    <div class="nt-card">
        <div class="nt-card-head">
            <span class="nt-card-head-icon"><i class="fas fa-comments"></i></span>
            <span class="nt-card-head-title">Avis clients récents</span>
        </div>
        @forelse($reviews->take(15) as $review)
        <div class="nt-review">
            <div class="nt-avatar">{{ strtoupper(substr($review->user->name ?? 'C', 0, 1)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="nt-review-name">{{ $review->user->name ?? 'Client' }}</div>
                <div class="nt-review-stars">
                    @for($i=1;$i<=5;$i++)
                    <i class="fas fa-star nt-review-star {{ $i<=(int)$review->rating ? '' : 'e' }}"></i>
                    @endfor
                </div>
                @if(!empty($review->comment))
                <div class="nt-review-comment">&laquo;&nbsp;{{ $review->comment }}&nbsp;&raquo;</div>
                @endif
                <div class="nt-review-date">{{ $review->created_at->diffForHumans() }}</div>
            </div>
        </div>
        @empty
        <div class="nt-empty">
            <i class="fas fa-comment-slash" style="font-size:1.8rem;color:var(--c-text-dim);"></i>
            <div style="font-weight:700;color:var(--c-text);margin-top:10px;margin-bottom:4px;">Aucun avis pour l'instant</div>
            <div style="font-size:.8rem;color:var(--c-text-muted);">Les avis clients apparaîtront ici après chaque livraison évaluée.</div>
        </div>
        @endforelse
    </div>

    {{-- ── CONSEILS ── --}}
    <div class="nt-card">
        <div class="nt-card-head">
            <span class="nt-card-head-icon"><i class="fas fa-lightbulb"></i></span>
            <span class="nt-card-head-title">Conseils pour améliorer votre note</span>
        </div>
        <div class="nt-tips">
            <div class="nt-tip">
                <div class="nt-tip-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="nt-tip-title">Respectez les délais</div>
                    <div class="nt-tip-text">Les clients apprécient la ponctualité. Prévenez-les si vous avez du retard.</div>
                </div>
            </div>
            <div class="nt-tip">
                <div class="nt-tip-icon"><i class="fas fa-box"></i></div>
                <div>
                    <div class="nt-tip-title">Soignez les colis</div>
                    <div class="nt-tip-text">Vérifiez que les plats sont bien emballés avant de partir. Une livraison propre = client satisfait.</div>
                </div>
            </div>
            <div class="nt-tip">
                <div class="nt-tip-icon"><i class="fas fa-handshake"></i></div>
                <div>
                    <div class="nt-tip-title">Restez professionnel</div>
                    <div class="nt-tip-text">Un accueil courtois et soigné à la remise incite à laisser un bon avis.</div>
                </div>
            </div>
            <div class="nt-tip">
                <div class="nt-tip-icon"><i class="fas fa-phone"></i></div>
                <div>
                    <div class="nt-tip-title">Communiquez avec le client</div>
                    <div class="nt-tip-text">Si vous ne trouvez pas l'adresse, appelez le client. Ne restez jamais sans nouvelles.</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
