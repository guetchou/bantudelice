@extends('layouts.restaurant_app')
@section('title', 'Horaires d\'ouverture')
@section('topbar_title', 'Horaires d\'ouverture')
@section('working_hour_nav', 'active')

@section('content')
@php
    $dayLabels = [
        'monday'    => 'Lundi',
        'tuesday'   => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday'  => 'Jeudi',
        'friday'    => 'Vendredi',
        'saturday'  => 'Samedi',
        'sunday'    => 'Dimanche',
    ];
    $standardDays = array_keys($dayLabels);

    $regularHours = collect($working_hours)->filter(fn($h) => in_array(strtolower($h->Day), $standardDays));
    $specialClosures = collect($special_closures ?? []);

    $specialIcons = [
        'Férié'                    => 'fas fa-flag',
        'Travaux'                  => 'fas fa-hard-hat',
        'Inventaire'               => 'fas fa-clipboard-list',
        'Congé'                    => 'fas fa-umbrella-beach',
        'Événement privé'          => 'fas fa-lock',
        'Fermeture exceptionnelle' => 'fas fa-triangle-exclamation',
    ];
@endphp

@if(session('alert'))
    <div class="alert alert-{{ session('alert.type', 'success') }} alert-dismissible fade show" role="alert">
        {{ session('alert.message', session('alert')) }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

{{-- ── Horaires hebdomadaires ──────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="font-size:1.05rem;font-weight:700;color:#111827;">Gérer vos horaires</div>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;">Ajoutez vos horaires réguliers et vos fermetures exceptionnelles depuis deux actions séparées.</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('working_hour.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Ajouter un horaire
            </a>
            <a href="{{ route('restaurant.special_closures.create') }}" class="btn btn-outline-warning">
                <i class="fas fa-calendar-xmark mr-1"></i> Ajouter une fermeture spéciale
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div class="card-title">Horaires hebdomadaires</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">Jours et plages horaires d'ouverture réguliers</div>
        </div>
        <a href="{{ route('working_hour.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Ajouter un horaire
        </a>
    </div>
    <div class="card-body" style="padding:0;">
        @if($regularHours->isEmpty())
            <div style="padding:32px;text-align:center;color:#9ca3af;">
                <i class="fas fa-clock" style="font-size:32px;margin-bottom:12px;display:block;"></i>
                Aucun horaire configuré.
                <a href="{{ route('working_hour.create') }}" style="color:#009543;font-weight:600;">Ajouter le premier</a>
            </div>
        @else
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th>#</th>
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
                        @endphp
                        <tr>
                            <td style="color:#9ca3af;font-size:12px;">{{ $loop->iteration }}</td>
                            <td>
                                <span style="font-weight:600;color:#111827;">{{ $label }}</span>
                                @if($isToday)
                                    <span style="display:inline-block;margin-left:6px;background:#dcfce7;color:#007836;font-size:10px;font-weight:700;padding:1px 7px;border-radius:999px;">Aujourd'hui</span>
                                @endif
                            </td>
                            <td><span style="font-family:monospace;font-size:13px;">{{ substr($wh->opening_time, 0, 5) }}</span></td>
                            <td><span style="font-family:monospace;font-size:13px;">{{ substr($wh->closing_time, 0, 5) }}</span></td>
                            <td style="color:#6b7280;font-size:12px;">
                                @php
                                    try {
                                        $open  = \Carbon\Carbon::createFromTimeString($wh->opening_time);
                                        $close = \Carbon\Carbon::createFromTimeString($wh->closing_time);
                                        $diff  = $open->diffInMinutes($close);
                                        echo floor($diff / 60) . 'h' . ($diff % 60 ? str_pad($diff % 60, 2, '0', STR_PAD_LEFT) : '');
                                    } catch (\Throwable) { echo '—'; }
                                @endphp
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="{{ route('working_hour.edit', $wh->id) }}" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-pen fa-xs"></i> Modifier
                                </a>
                                <form action="{{ route('working_hour.destroy', $wh->id) }}" method="post"
                                      style="display:inline;" onsubmit="return confirm('Supprimer cet horaire ?');">
                                    @csrf @method('delete')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

{{-- ── Fermetures & informations spéciales ────────────── --}}
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div class="card-title">Fermetures & informations spéciales</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                Jours fériés, travaux, inventaire, congé, fermeture exceptionnelle
            </div>
        </div>
        <a href="{{ route('restaurant.special_closures.create') }}" class="btn btn-sm"
           style="background:#fffbeb;border:1px solid #fcd34d;color:#b45309;font-size:13px;font-weight:500;padding:6px 12px;border-radius:6px;text-decoration:none;">
            <i class="fas fa-calendar-xmark mr-1"></i> Ajouter une fermeture spéciale
        </a>
    </div>
    <div class="card-body" style="padding:0;">
        @if($specialClosures->isEmpty())
            <div style="padding:24px;text-align:center;color:#9ca3af;">
                <i class="fas fa-calendar-check" style="font-size:28px;margin-bottom:10px;display:block;color:#d1d5db;"></i>
                Aucune fermeture spéciale planifiée.
            </div>
        @else
            <table class="table" style="margin:0;">
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
                                <span style="display:inline-flex;align-items:center;gap:8px;">
                                    <span style="width:28px;height:28px;border-radius:6px;background:#fef3c7;color:#d97706;display:inline-flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;">
                                        <i class="{{ $icon }}"></i>
                                    </span>
                                    <strong>{{ $closure->label }}</strong>
                                </span>
                            </td>
                            <td style="font-size:13px;color:#374151;">{{ optional($closure->starts_on)->format('d/m/Y') }}</td>
                            <td style="font-size:13px;color:#374151;">{{ optional($closure->ends_on)->format('d/m/Y') }}</td>
                            <td style="font-size:13px;color:#6b7280;">{{ $closure->notes ?: '—' }}</td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="{{ route('restaurant.special_closures.edit', $closure->id) }}" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-pen fa-xs"></i>
                                </a>
                                <form action="{{ route('restaurant.special_closures.destroy', $closure->id) }}" method="post"
                                      style="display:inline;" onsubmit="return confirm('Supprimer cette fermeture ?');">
                                    @csrf @method('delete')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
