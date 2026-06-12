@extends('frontend.layouts.app-modern')
@php $foodBrandName = \App\Services\ConfigService::getCompanyName(); @endphp
@section('title', 'Mes points de fidélité | ' . $foodBrandName)
@section('body_class', 'bd-loyalty-page')

@section('style')
<style>
/* ===== Shell ===== */
.loy-shell {
    padding: 96px 0 60px;
}
.loy-container {
    max-width: 760px;
    margin: 0 auto;
    padding: 0 16px;
}
.loy-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #009543;
    font-size: .88rem;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 24px;
}
.loy-back:hover { text-decoration: underline; }

/* ===== Hero card ===== */
.loy-hero {
    background: linear-gradient(135deg, #007836 0%, #009543 55%, #00b850 100%);
    border-radius: 20px;
    padding: 32px 28px;
    color: #fff;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.loy-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,.07);
    border-radius: 50%;
}
.loy-hero__label {
    font-size: .82rem;
    font-weight: 600;
    opacity: .8;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 4px;
}
.loy-hero__points {
    font-size: 3.2rem;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 4px;
}
.loy-hero__unit {
    font-size: 1rem;
    opacity: .85;
    margin-bottom: 20px;
}
.loy-hero__equiv {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.18);
    border-radius: 12px;
    padding: 10px 16px;
    font-size: .92rem;
    backdrop-filter: blur(8px);
}
.loy-hero__equiv strong { font-size: 1.05rem; }
.loy-hero__stats {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,.2);
    flex-wrap: wrap;
}
.loy-hero__stat { font-size: .82rem; opacity: .85; }
.loy-hero__stat strong { display: block; font-size: 1rem; font-weight: 800; }

/* ===== Progress ===== */
.loy-progress-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(15,23,42,.05);
}
.loy-progress-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.loy-progress-title { font-size: .9rem; font-weight: 700; color: #0f172a; }
.loy-progress-next { font-size: .78rem; color: #64748b; }
.loy-progress-bar-wrap {
    height: 10px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 8px;
}
.loy-progress-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #009543 0%, #f59e0b 100%);
    transition: width .6s ease;
}
.loy-progress-milestones {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
}
.loy-milestone {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex: 1;
    position: relative;
}
.loy-milestone__dot {
    width: 20px; height: 20px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .6rem;
    transition: all .3s;
}
.loy-milestone.done .loy-milestone__dot {
    background: #009543;
    border-color: #009543;
    color: #fff;
}
.loy-milestone.active .loy-milestone__dot {
    border-color: #f59e0b;
    background: #fffbeb;
}
.loy-milestone__label {
    font-size: .68rem;
    color: #94a3b8;
    text-align: center;
    line-height: 1.3;
    font-weight: 600;
}
.loy-milestone.done .loy-milestone__label { color: #009543; }
.loy-milestone.active .loy-milestone__label { color: #d97706; }

/* ===== Sections card ===== */
.loy-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(15,23,42,.05);
}
.loy-card__title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.loy-card__title i { color: #009543; }

/* ===== How it works ===== */
.loy-how {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.loy-how-step {
    display: flex;
    gap: 14px;
    align-items: flex-start;
}
.loy-how-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: #009543;
    color: #fff;
    font-size: .85rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.loy-how-text { font-size: .88rem; color: #334155; line-height: 1.5; }
.loy-how-text strong { color: #0f172a; }

/* ===== Récompenses ===== */
.loy-rewards {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.loy-reward {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #fafafa;
    transition: border-color .2s;
}
.loy-reward.unlocked {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.loy-reward.next-up {
    border-color: #fde68a;
    background: #fffbeb;
}
.loy-reward__icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.loy-reward.unlocked .loy-reward__icon { background: #dcfce7; color: #009543; }
.loy-reward.next-up .loy-reward__icon { background: #fef3c7; color: #d97706; }
.loy-reward.locked .loy-reward__icon { background: #f1f5f9; color: #94a3b8; }
.loy-reward__body { flex: 1; }
.loy-reward__name {
    font-size: .88rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}
.loy-reward.locked .loy-reward__name { color: #64748b; }
.loy-reward__desc { font-size: .78rem; color: #64748b; }
.loy-reward__badge {
    font-size: .72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
    flex-shrink: 0;
    white-space: nowrap;
}
.loy-reward.unlocked .loy-reward__badge { background: #009543; color: #fff; }
.loy-reward.next-up .loy-reward__badge { background: #f59e0b; color: #fff; }
.loy-reward.locked .loy-reward__badge { background: #e2e8f0; color: #64748b; }

/* ===== Historique ===== */
.loy-tx-list { display: flex; flex-direction: column; gap: 8px; }
.loy-tx {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    background: #fafafa;
}
.loy-tx__icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
}
.loy-tx__icon--earned  { background: #dcfce7; color: #009543; }
.loy-tx__icon--spent   { background: #fee2e2; color: #ef4444; }
.loy-tx__icon--expired { background: #fef3c7; color: #d97706; }
.loy-tx__icon--bonus   { background: #eff6ff; color: #3b82f6; }
.loy-tx__body { flex: 1; min-width: 0; }
.loy-tx__desc {
    font-size: .85rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.loy-tx__date { font-size: .75rem; color: #94a3b8; margin-top: 2px; }
.loy-tx__pts {
    font-size: .95rem;
    font-weight: 800;
    flex-shrink: 0;
    white-space: nowrap;
}
.loy-tx__pts--pos { color: #009543; }
.loy-tx__pts--neg { color: #ef4444; }

/* ===== Pagination ===== */
.loy-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
}
.loy-page-btn {
    padding: 7px 14px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    font-size: .84rem;
    text-decoration: none;
    transition: background .15s;
}
.loy-page-btn:hover { background: #f8fafc; }
.loy-page-btn.is-disabled {
    background: #f1f5f9;
    color: #94a3b8;
    pointer-events: none;
    border-color: transparent;
}
.loy-page-info {
    padding: 7px 14px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #64748b;
    font-size: .84rem;
}

/* ===== Empty ===== */
.loy-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}
.loy-empty__icon {
    font-size: 2.5rem;
    opacity: .4;
    margin-bottom: 12px;
}
.loy-empty__cta {
    display: inline-block;
    margin-top: 12px;
    background: #009543;
    color: #fff;
    font-size: .84rem;
    font-weight: 700;
    padding: 8px 20px;
    border-radius: 99px;
    text-decoration: none;
}
.loy-empty__cta:hover { background: #007836; color: #fff; }
</style>
@endsection

@section('content')
@php
    use App\Services\LoyaltyService;
    $pts = $loyalty->points;

    /* Paliers de progression */
    $milestones = [
        ['pts' => 100,  'label' => "1ère\nréduction"],
        ['pts' => 500,  'label' => "Niveau\nArgent"],
        ['pts' => 1500, 'label' => "Niveau\nOr"],
        ['pts' => 3000, 'label' => "Niveau\nPlatine"],
    ];
    $nextMilestone = collect($milestones)->first(fn($m) => $pts < $m['pts']);
    $prevThreshold = 0;
    foreach ($milestones as $m) {
        if ($pts >= $m['pts']) { $prevThreshold = $m['pts']; }
    }
    $progressPct = $nextMilestone
        ? min(100, round(($pts - $prevThreshold) / ($nextMilestone['pts'] - $prevThreshold) * 100))
        : 100;
@endphp

<section class="loy-shell">
    <div class="loy-container">

        <a href="{{ route('user.profile') }}" class="loy-back">
            <i class="fas fa-arrow-left"></i> Retour au profil
        </a>

        {{-- ===== HERO ===== --}}
        <div class="loy-hero">
            <div class="loy-hero__label">Vos points de fidélité</div>
            <div class="loy-hero__points">{{ number_format($pts, 0, ',', ' ') }}</div>
            <div class="loy-hero__unit">points disponibles</div>
            <div class="loy-hero__equiv">
                <i class="fas fa-tag"></i>
                Valeur : <strong>{{ number_format($discount, 0, ',', ' ') }} FCFA</strong> de réduction
            </div>
            <div class="loy-hero__stats">
                <div class="loy-hero__stat">
                    <strong>{{ number_format($loyalty->total_earned, 0, ',', ' ') }}</strong>
                    Points gagnés au total
                </div>
                <div class="loy-hero__stat">
                    <strong>{{ number_format($loyalty->total_spent, 0, ',', ' ') }}</strong>
                    Points utilisés
                </div>
                <div class="loy-hero__stat">
                    <strong>{{ number_format(LoyaltyService::calculateDiscount($loyalty->total_earned), 0, ',', ' ') }} FCFA</strong>
                    Économies cumulées
                </div>
            </div>
        </div>

        {{-- ===== PROGRESSION ===== --}}
        <div class="loy-progress-card">
            <div class="loy-progress-head">
                <span class="loy-progress-title">Progression vers le palier suivant</span>
                @if($nextMilestone)
                <span class="loy-progress-next">{{ $nextMilestone['pts'] - $pts }} pts pour atteindre {{ $nextMilestone['pts'] }} pts</span>
                @else
                <span class="loy-progress-next" style="color:#009543;font-weight:700;">Palier Platine atteint !</span>
                @endif
            </div>
            <div class="loy-progress-bar-wrap">
                <div class="loy-progress-bar-fill" style="width:{{ $progressPct }}%"></div>
            </div>
            <div class="loy-progress-milestones">
                @foreach($milestones as $m)
                @php
                    $isDone = $pts >= $m['pts'];
                    $isActive = !$isDone && $nextMilestone && $nextMilestone['pts'] === $m['pts'];
                @endphp
                <div class="loy-milestone {{ $isDone ? 'done' : ($isActive ? 'active' : '') }}">
                    <div class="loy-milestone__dot">
                        @if($isDone)<i class="fas fa-check" style="font-size:.55rem;"></i>@endif
                    </div>
                    <div class="loy-milestone__label">
                        {{ number_format($m['pts'], 0, ',', ' ') }} pts<br>
                        {!! nl2br(e($m['label'])) !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ===== RÉCOMPENSES ===== --}}
        <div class="loy-card">
            <h2 class="loy-card__title"><i class="fas fa-gift"></i> Vos récompenses</h2>
            <div class="loy-rewards">
                @php
                    $rewards = [
                        [
                            'threshold' => 100,
                            'icon' => 'fa-percent',
                            'name' => 'Réductions à la commande',
                            'desc' => '100 pts = 1 000 FCFA de réduction (max 20 % par commande)',
                        ],
                        [
                            'threshold' => 500,
                            'icon' => 'fa-star',
                            'name' => 'Niveau Argent — bonus points',
                            'desc' => 'Gagnez des points bonus sur chaque commande',
                        ],
                        [
                            'threshold' => 1500,
                            'icon' => 'fa-crown',
                            'name' => 'Niveau Or — livraison offerte',
                            'desc' => 'Une livraison gratuite par mois sur éligibilité',
                        ],
                        [
                            'threshold' => 3000,
                            'icon' => 'fa-gem',
                            'name' => 'Niveau Platine — accès prioritaire',
                            'desc' => 'Commandes traitées en priorité, support dédié',
                        ],
                    ];
                @endphp
                @foreach($rewards as $reward)
                @php
                    $unlocked = $pts >= $reward['threshold'];
                    $isNext = !$unlocked && $nextMilestone && $nextMilestone['pts'] === $reward['threshold'];
                    $cls = $unlocked ? 'unlocked' : ($isNext ? 'next-up' : 'locked');
                    $badgeLabel = $unlocked ? 'Débloqué' : ($isNext ? 'Prochain' : number_format($reward['threshold'], 0, ',', ' ').' pts');
                @endphp
                <div class="loy-reward {{ $cls }}">
                    <div class="loy-reward__icon">
                        <i class="fas {{ $reward['icon'] }}"></i>
                    </div>
                    <div class="loy-reward__body">
                        <div class="loy-reward__name">{{ $reward['name'] }}</div>
                        <div class="loy-reward__desc">{{ $reward['desc'] }}</div>
                    </div>
                    <span class="loy-reward__badge">{{ $badgeLabel }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ===== COMMENT GAGNER ===== --}}
        <div class="loy-card">
            <h2 class="loy-card__title"><i class="fas fa-info-circle"></i> Comment ça marche ?</h2>
            <div class="loy-how">
                <div class="loy-how-step">
                    <div class="loy-how-num">1</div>
                    <div class="loy-how-text">
                        <strong>Gagnez des points</strong> — 10 pts pour chaque 1 000 FCFA dépensés sur une commande livrée.
                    </div>
                </div>
                <div class="loy-how-step">
                    <div class="loy-how-num">2</div>
                    <div class="loy-how-text">
                        <strong>Utilisez vos points</strong> — Au checkout, cochez "Utiliser mes points". 100 pts = 1 000 FCFA de réduction (maximum 20 % du montant de la commande).
                    </div>
                </div>
                <div class="loy-how-step">
                    <div class="loy-how-num">3</div>
                    <div class="loy-how-text">
                        <strong>Validité 1 an</strong> — Vos points expirent 365 jours après avoir été gagnés. Commandez régulièrement pour les conserver.
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== HISTORIQUE ===== --}}
        <div class="loy-card">
            <h2 class="loy-card__title"><i class="fas fa-history"></i> Historique des transactions</h2>

            @if($history->count() > 0)
            <div class="loy-tx-list">
                @foreach($history as $tx)
                @php
                    $iconMap = [
                        'earned'  => ['icon' => 'fa-plus',  'cls' => 'earned'],
                        'spent'   => ['icon' => 'fa-minus', 'cls' => 'spent'],
                        'expired' => ['icon' => 'fa-clock', 'cls' => 'expired'],
                        'bonus'   => ['icon' => 'fa-gift',  'cls' => 'bonus'],
                    ];
                    $ic = $iconMap[$tx->type] ?? ['icon' => 'fa-circle', 'cls' => 'earned'];
                @endphp
                <div class="loy-tx">
                    <div class="loy-tx__icon loy-tx__icon--{{ $ic['cls'] }}">
                        <i class="fas {{ $ic['icon'] }}"></i>
                    </div>
                    <div class="loy-tx__body">
                        <div class="loy-tx__desc">{{ $tx->description ?? 'Transaction' }}</div>
                        <div class="loy-tx__date">{{ $tx->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                    <div class="loy-tx__pts {{ $tx->points >= 0 ? 'loy-tx__pts--pos' : 'loy-tx__pts--neg' }}">
                        {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points, 0, ',', ' ') }} pts
                    </div>
                </div>
                @endforeach
            </div>

            @if($history->lastPage() > 1)
            <div class="loy-pagination">
                @if($history->onFirstPage())
                    <span class="loy-page-btn is-disabled">← Précédent</span>
                @else
                    <a href="{{ $history->previousPageUrl() }}" class="loy-page-btn">← Précédent</a>
                @endif
                <span class="loy-page-info">Page {{ $history->currentPage() }} / {{ $history->lastPage() }}</span>
                @if($history->hasMorePages())
                    <a href="{{ $history->nextPageUrl() }}" class="loy-page-btn">Suivant →</a>
                @else
                    <span class="loy-page-btn is-disabled">Suivant →</span>
                @endif
            </div>
            @endif

            @else
            <div class="loy-empty">
                <div class="loy-empty__icon"><i class="fas fa-star"></i></div>
                <p style="font-weight:700;color:#475569;margin-bottom:4px;">Aucune transaction pour l'instant</p>
                <p style="font-size:.85rem;margin:0;">Passez votre première commande pour commencer à gagner des points !</p>
                <a href="{{ route('restaurants.all') }}" class="loy-empty__cta">Voir les restaurants →</a>
            </div>
            @endif
        </div>

    </div>
</section>
@endsection
