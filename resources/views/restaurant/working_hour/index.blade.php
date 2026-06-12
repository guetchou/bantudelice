@extends('layouts.restaurant_app')
@section('title', 'Horaires | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Horaires d\'ouverture')
@section('working_hour_nav', 'active')

@section('style')
<style>
.wh { display: flex; flex-direction: column; gap: 20px; }

.wh-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.wh-toolbar__right { display: flex; gap: 8px; flex-wrap: wrap; }

.wh-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.wh-btn--primary { background: var(--bd-green); color: #fff; }
.wh-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.wh-btn--amber   { background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.3); }
.wh-btn--amber:hover { background: rgba(245,158,11,.18); color: #92400e; }

.wh-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.wh-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.wh-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.wh-card__sub   { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.wh-table-wrap { overflow-x: auto; }
.wh-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.wh-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.wh-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.wh-table tbody tr:last-child { border-bottom: none; }
.wh-table tbody tr:hover { background: var(--bd-surface-2); }
.wh-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

.wh-day { font-weight: 600; color: var(--bd-text); }
.wh-today {
    display: inline-block; margin-left: 7px;
    background: rgba(0,149,67,.12); color: var(--bd-green);
    font-size: 10px; font-weight: 700; padding: 1px 8px; border-radius: 999px;
}
[data-theme="dark"] .wh-today { background: rgba(0,201,87,.15); color: #00c957; }

.wh-time { font-family: monospace; font-size: 13px; font-weight: 600; color: var(--bd-text); }
.wh-dur  { font-size: 12px; color: var(--bd-text-3); }

.wh-closure-icon {
    width: 28px; height: 28px; border-radius: 6px;
    background: rgba(245,158,11,.12); color: #b45309;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
[data-theme="dark"] .wh-closure-icon { background: rgba(245,158,11,.18); color: #fbbf24; }

.wh-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.wh-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s; text-decoration: none;
}
.wh-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.wh-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.wh-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.wh-empty {
    padding: 40px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.wh-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }
.wh-empty p { margin: 0 0 14px; }
</style>
@endsection

@section('content')
@php
    $dayLabels = [
        'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
    ];
    $standardDays = array_keys($dayLabels);
    $regularHours = collect($working_hours)->filter(fn($h) => in_array(strtolower($h->Day), $standardDays));
    $specialClosures = collect($special_closures ?? []);
    $specialIcons = [
        'Férié' => 'fas fa-flag', 'Travaux' => 'fas fa-hard-hat',
        'Inventaire' => 'fas fa-clipboard-list', 'Congé' => 'fas fa-umbrella-beach',
        'Événement privé' => 'fas fa-lock', 'Fermeture exceptionnelle' => 'fas fa-triangle-exclamation',
    ];
@endphp

<div class="wh">

    @if(session('alert'))
        <div class="alert alert-{{ session('alert.type', 'success') }} alert-dismissible fade show" role="alert">
            {{ session('alert.message', session('alert')) }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ── Barre outils ────────────────────────────────── --}}
    <div class="wh-toolbar">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--bd-text);">Horaires d'ouverture</div>
            <div style="font-size:12px;color:var(--bd-text-3);margin-top:3px;">
                {{ $regularHours->count() }} créneau(x) régulier(s) · {{ $specialClosures->count() }} fermeture(s) spéciale(s)
            </div>
        </div>
        <div class="wh-toolbar__right">
            <a href="{{ route('working_hour.create') }}" class="wh-btn wh-btn--primary">
                <i class="fas fa-plus"></i> Ajouter un horaire
            </a>
            <a href="{{ route('restaurant.special_closures.create') }}" class="wh-btn wh-btn--amber">
                <i class="fas fa-calendar-xmark"></i> Fermeture spéciale
            </a>
        </div>
    </div>

    {{-- ── Horaires hebdomadaires ──────────────────────── --}}
    <div class="wh-card">
        <div class="wh-card__head">
            <div>
                <div class="wh-card__title">Horaires hebdomadaires</div>
                <div class="wh-card__sub">Plages d'ouverture régulières par jour</div>
            </div>
            <a href="{{ route('working_hour.create') }}" class="wh-btn wh-btn--primary" style="padding:5px 11px;font-size:11px;">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </div>
        @if($regularHours->isEmpty())
            <div class="wh-empty">
                <i class="fas fa-clock"></i>
                <p>Aucun horaire configuré.</p>
                <a href="{{ route('working_hour.create') }}" class="wh-btn wh-btn--primary">
                    <i class="fas fa-plus"></i> Ajouter le premier créneau
                </a>
            </div>
        @else
            <div class="wh-table-wrap">
                <table class="wh-table">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Ouverture</th>
                            <th>Fermeture</th>
                            <th>Durée</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($regularHours->sortBy(fn($h) => array_search(strtolower($h->Day), $standardDays)) as $wh)
                        @php
                            $label    = $dayLabels[strtolower($wh->Day)] ?? ucfirst($wh->Day);
                            $dayIdx   = array_search(strtolower($wh->Day), $standardDays);
                            $todayIdx = (int) date('N') - 1;
                            $isToday  = ($dayIdx === $todayIdx);
                            try {
                                $open  = \Carbon\Carbon::createFromTimeString($wh->opening_time);
                                $close = \Carbon\Carbon::createFromTimeString($wh->closing_time);
                                $diff  = $open->diffInMinutes($close);
                                $dur   = floor($diff / 60) . 'h' . ($diff % 60 ? str_pad($diff % 60, 2, '0', STR_PAD_LEFT) : '');
                            } catch (\Throwable) { $dur = '—'; }
                        @endphp
                        <tr>
                            <td>
                                <span class="wh-day">{{ $label }}</span>
                                @if($isToday)<span class="wh-today">Aujourd'hui</span>@endif
                            </td>
                            <td><span class="wh-time">{{ substr($wh->opening_time, 0, 5) }}</span></td>
                            <td><span class="wh-time">{{ substr($wh->closing_time, 0, 5) }}</span></td>
                            <td><span class="wh-dur">{{ $dur }}</span></td>
                            <td>
                                <div class="wh-actions">
                                    <a href="{{ route('working_hour.edit', $wh->id) }}" class="wh-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('working_hour.destroy', $wh->id) }}" method="post"
                                          style="display:inline;" onsubmit="return confirm('Supprimer cet horaire ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="wh-action-btn wh-action-btn--delete" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Fermetures spéciales ────────────────────────── --}}
    <div class="wh-card">
        <div class="wh-card__head">
            <div>
                <div class="wh-card__title">Fermetures & jours spéciaux</div>
                <div class="wh-card__sub">Jours fériés, travaux, congé, fermeture exceptionnelle</div>
            </div>
            <a href="{{ route('restaurant.special_closures.create') }}" class="wh-btn wh-btn--amber" style="padding:5px 11px;font-size:11px;">
                <i class="fas fa-calendar-xmark"></i> Ajouter
            </a>
        </div>
        @if($specialClosures->isEmpty())
            <div class="wh-empty">
                <i class="fas fa-calendar-check"></i>
                <p>Aucune fermeture spéciale planifiée.</p>
            </div>
        @else
            <div class="wh-table-wrap">
                <table class="wh-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Du</th>
                            <th>Au</th>
                            <th>Note</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($specialClosures as $closure)
                        @php $icon = $specialIcons[$closure->label] ?? 'fas fa-info-circle'; @endphp
                        <tr>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:10px;">
                                    <span class="wh-closure-icon"><i class="{{ $icon }}"></i></span>
                                    <strong style="color:var(--bd-text);">{{ $closure->label }}</strong>
                                </span>
                            </td>
                            <td style="white-space:nowrap;">{{ optional($closure->starts_on)->format('d/m/Y') ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ optional($closure->ends_on)->format('d/m/Y') ?? '—' }}</td>
                            <td style="color:var(--bd-text-3);">{{ $closure->notes ?: '—' }}</td>
                            <td>
                                <div class="wh-actions">
                                    <a href="{{ route('restaurant.special_closures.edit', $closure->id) }}" class="wh-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('restaurant.special_closures.destroy', $closure->id) }}" method="post"
                                          style="display:inline;" onsubmit="return confirm('Supprimer cette fermeture ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="wh-action-btn wh-action-btn--delete" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
