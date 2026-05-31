@extends('layouts.restaurant_app')
@section('title', 'Bons de réduction | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Bons de réduction')
@section('vouchers_nav', 'active')

@section('style')
<style>
.vch { display: flex; flex-direction: column; gap: 20px; }

.vch-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}

.vch-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.vch-btn--primary { background: var(--bd-green); color: #fff; }
.vch-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }

.vch-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.vch-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.vch-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.vch-card__count { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.vch-table-wrap { overflow-x: auto; }
.vch-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.vch-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.vch-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.vch-table tbody tr:last-child { border-bottom: none; }
.vch-table tbody tr:hover { background: var(--bd-surface-2); }
.vch-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

.vch-name { font-weight: 600; color: var(--bd-text); }
.vch-code {
    font-family: monospace; font-size: 12px;
    background: var(--bd-surface-2); color: var(--bd-text-2);
    padding: 2px 7px; border-radius: 5px; border: 1px solid var(--bd-border);
    display: inline-block; margin-top: 2px;
}
.vch-discount {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(0,149,67,.1); color: var(--bd-green);
    font-size: 12px; font-weight: 700;
    padding: 3px 9px; border-radius: 999px;
}
[data-theme="dark"] .vch-discount { background: rgba(0,201,87,.15); color: #00c957; }

.vch-date { font-size: 12px; white-space: nowrap; }
.vch-date--expired { color: #dc2626; }
.vch-date--active  { color: var(--bd-green); }
.vch-date--future  { color: #6b7280; }

.vch-actions { display: flex; align-items: center; gap: 6px; }
.vch-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s; text-decoration: none;
}
.vch-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.vch-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.vch-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.vch-empty {
    padding: 48px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.vch-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }
.vch-empty p { margin: 0 0 16px; }
</style>
@endsection

@section('content')
@php $now = now(); @endphp

<div class="vch">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Barre outils ────────────────────────────────── --}}
    <div class="vch-toolbar">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--bd-text);">Bons de réduction</div>
            <div style="font-size:12px;color:var(--bd-text-3);margin-top:3px;">
                {{ $vouchers->count() }} bon(s) configuré(s)
            </div>
        </div>
        <a href="{{ route('voucher.create') }}" class="vch-btn vch-btn--primary">
            <i class="fas fa-plus"></i> Nouveau bon
        </a>
    </div>

    {{-- ── Tableau ──────────────────────────────────────── --}}
    <div class="vch-card">
        <div class="vch-card__head">
            <div>
                <div class="vch-card__title">Tous les bons de réduction</div>
                <div class="vch-card__count">{{ $vouchers->count() }} bon(s)</div>
            </div>
        </div>

        @if($vouchers->isEmpty())
            <div class="vch-empty">
                <i class="fas fa-tag"></i>
                <p>Aucun bon de réduction créé.</p>
                <a href="{{ route('voucher.create') }}" class="vch-btn vch-btn--primary">
                    <i class="fas fa-plus"></i> Créer le premier bon
                </a>
            </div>
        @else
            <div class="vch-table-wrap">
                <table class="vch-table">
                    <thead>
                        <tr>
                            <th>Nom / Code</th>
                            <th>Réduction</th>
                            <th>Validité</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vouchers as $voucher)
                        @php
                            $start   = $voucher->start_date ? \Carbon\Carbon::parse($voucher->start_date) : null;
                            $end     = $voucher->end_date   ? \Carbon\Carbon::parse($voucher->end_date)   : null;
                            $expired = $end && $end->isPast();
                            $active  = $start && $end && $now->between($start, $end);
                            $future  = $start && $start->isFuture();
                        @endphp
                        <tr>
                            <td>
                                <div class="vch-name">{{ $voucher->name }}</div>
                                @if($voucher->code ?? null)
                                    <span class="vch-code">{{ $voucher->code }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="vch-discount">
                                    <i class="fas fa-percent" style="font-size:9px;"></i>
                                    {{ number_format((float)$voucher->discount, 0, ',', ' ') }}
                                </span>
                            </td>
                            <td>
                                <span class="vch-date {{ $expired ? 'vch-date--expired' : ($active ? 'vch-date--active' : 'vch-date--future') }}">
                                    {{ $start ? $start->format('d/m/Y') : '—' }}
                                    → {{ $end ? $end->format('d/m/Y') : '—' }}
                                </span>
                            </td>
                            <td>
                                @if($expired)
                                    <span style="font-size:11px;font-weight:700;color:#dc2626;">Expiré</span>
                                @elseif($active)
                                    <span style="font-size:11px;font-weight:700;color:var(--bd-green);">Actif</span>
                                @elseif($future)
                                    <span style="font-size:11px;font-weight:700;color:#6b7280;">À venir</span>
                                @else
                                    <span style="font-size:11px;color:var(--bd-text-3);">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="vch-actions" style="justify-content:flex-end;">
                                    <a href="{{ route('voucher.edit', $voucher->id) }}" class="vch-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('voucher.destroy', $voucher->id) }}"
                                          method="post" style="display:inline;"
                                          onsubmit="return confirm('Supprimer ce bon de réduction ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="vch-action-btn vch-action-btn--delete" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
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
