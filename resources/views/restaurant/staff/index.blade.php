@extends('layouts.restaurant_app')
@section('title', 'Équipe restaurant | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Équipe et accès')

@section('style')
<style>
.staff-page{display:grid;gap:20px}.staff-card{background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden}.staff-card__head{padding:16px 20px;border-bottom:1px solid var(--bd-border-2)}.staff-card__title{font-weight:800;color:var(--bd-text)}.staff-card__sub{font-size:12px;color:var(--bd-text-3);margin-top:3px}.staff-form{display:grid;grid-template-columns:minmax(220px,1fr) minmax(180px,.55fr) auto;gap:12px;padding:18px 20px;align-items:end}.staff-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--bd-text-3);margin-bottom:5px}.staff-field input,.staff-field select{width:100%;height:40px;border:1px solid var(--bd-border);border-radius:9px;background:var(--bd-surface);color:var(--bd-text);padding:0 11px}.staff-btn{height:40px;border:0;border-radius:9px;padding:0 16px;font-weight:700;cursor:pointer}.staff-btn--primary{background:var(--bd-green);color:#fff}.staff-btn--danger{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}.staff-btn--outline{background:var(--bd-surface);color:var(--bd-text-2);border:1px solid var(--bd-border)}.staff-table-wrap{overflow:auto}.staff-table{width:100%;border-collapse:collapse}.staff-table th{background:var(--bd-surface-2);font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--bd-text-3);text-align:left;padding:10px 14px}.staff-table td{padding:12px 14px;border-top:1px solid var(--bd-border-2);vertical-align:middle}.staff-user{font-weight:700;color:var(--bd-text)}.staff-email{font-size:11px;color:var(--bd-text-3)}.staff-state{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700}.staff-state--on{background:#dcfce7;color:#15803d}.staff-state--off{background:#f1f5f9;color:#64748b}.staff-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.staff-actions form{display:flex;gap:8px;align-items:center}.staff-actions select{height:34px;border:1px solid var(--bd-border);border-radius:8px;padding:0 8px;background:var(--bd-surface)}.staff-actions .staff-btn{height:34px}.staff-empty{padding:35px;text-align:center;color:var(--bd-text-3)}@media(max-width:760px){.staff-form{grid-template-columns:1fr}.staff-actions form{align-items:stretch;flex-direction:column}.staff-actions select,.staff-actions .staff-btn{width:100%}}
</style>
@endsection

@section('content')
<div class="staff-page">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session('alert.type') }}">{{ session('alert.message') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <section class="staff-card">
        <div class="staff-card__head">
            <div class="staff-card__title">Ajouter ou réactiver un collaborateur</div>
            <div class="staff-card__sub">Le collaborateur doit déjà disposer d’un compte de type restaurant.</div>
        </div>
        <form method="post" action="{{ route('restaurant.staff.store') }}" class="staff-form">
            @csrf
            <div class="staff-field">
                <label for="staffEmail">Adresse e-mail du compte</label>
                <input id="staffEmail" type="email" name="email" value="{{ old('email') }}" required autocomplete="off">
            </div>
            <div class="staff-field">
                <label for="staffRole">Rôle</label>
                <select id="staffRole" name="role" required>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="staff-btn staff-btn--primary">Enregistrer l’accès</button>
        </form>
    </section>

    <section class="staff-card">
        <div class="staff-card__head">
            <div class="staff-card__title">Collaborateurs de {{ $restaurant->name }}</div>
            <div class="staff-card__sub">Les droits sont appliqués à chaque requête par le middleware restaurant.</div>
        </div>
        @if($members->isEmpty())
            <div class="staff-empty">Aucun collaborateur enregistré.</div>
        @else
            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead><tr><th>Collaborateur</th><th>Rôle</th><th>État</th><th>Dernier accès</th><th>Actions</th></tr></thead>
                    <tbody>
                    @foreach($members as $member)
                        <tr>
                            <td>
                                <div class="staff-user">{{ $member->user->name ?? 'Compte supprimé' }}</div>
                                <div class="staff-email">{{ $member->user->email ?? '—' }}</div>
                            </td>
                            <td>{{ $roles[$member->role] ?? ucfirst($member->role) }}</td>
                            <td><span class="staff-state {{ $member->is_active ? 'staff-state--on' : 'staff-state--off' }}">{{ $member->is_active ? 'Actif' : 'Inactif' }}</span></td>
                            <td>{{ optional($member->last_access_at)->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                            <td>
                                <div class="staff-actions">
                                    <form method="post" action="{{ route('restaurant.staff.update', $member) }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="role" required>
                                            @foreach($roles as $value => $label)
                                                <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 1 : 0 }}">
                                        <button type="submit" class="staff-btn staff-btn--outline">Mettre à jour</button>
                                    </form>
                                    @if($member->is_active)
                                        <form method="post" action="{{ route('restaurant.staff.deactivate', $member) }}" onsubmit="return confirm('Désactiver cet accès ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="staff-btn staff-btn--danger">Désactiver</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('restaurant.staff.update', $member) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="role" value="{{ $member->role }}">
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="staff-btn staff-btn--primary">Réactiver</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
