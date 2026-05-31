@extends('layouts.restaurant_app')
@section('title', 'Équipe | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Équipe')
@section('employee_nav', 'active')

@section('style')
<style>
.emp { display: flex; flex-direction: column; gap: 20px; }

.emp-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}

.emp-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.emp-btn--primary { background: var(--bd-green); color: #fff; }
.emp-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }

.emp-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.emp-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.emp-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.emp-card__count { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.emp-table-wrap { overflow-x: auto; }
.emp-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.emp-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.emp-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.emp-table tbody tr:last-child { border-bottom: none; }
.emp-table tbody tr:hover { background: var(--bd-surface-2); }
.emp-table td { padding: 12px 16px; color: var(--bd-text-2); vertical-align: middle; }

.emp-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    object-fit: cover; background: var(--bd-surface-2);
    flex-shrink: 0;
}
.emp-avatar--fallback {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(0,149,67,.12); color: var(--bd-green);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
}
.emp-name { font-weight: 600; color: var(--bd-text); display: block; line-height: 1.3; }
.emp-sub  { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.emp-contact { font-size: 12px; color: var(--bd-text-2); }
.emp-contact i { color: var(--bd-text-3); width: 14px; margin-right: 4px; }

.emp-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.emp-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s; text-decoration: none;
}
.emp-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.emp-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.emp-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.emp-empty {
    padding: 48px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.emp-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }
.emp-empty p { margin: 0 0 16px; }

@media (max-width: 768px) { .emp-col-hide { display: none; } }
</style>
@endsection

@section('content')
<div class="emp">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Barre outils ────────────────────────────────── --}}
    <div class="emp-toolbar">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--bd-text);">Équipe</div>
            <div style="font-size:12px;color:var(--bd-text-3);margin-top:3px;">
                {{ $employees->count() }} membre(s) enregistré(s)
            </div>
        </div>
        <a href="{{ route('employee.create') }}" class="emp-btn emp-btn--primary">
            <i class="fas fa-user-plus"></i> Ajouter un membre
        </a>
    </div>

    {{-- ── Tableau ──────────────────────────────────────── --}}
    <div class="emp-card">
        <div class="emp-card__head">
            <div>
                <div class="emp-card__title">Membres de l'équipe</div>
                <div class="emp-card__count">{{ $employees->count() }} membre(s)</div>
            </div>
        </div>

        @if($employees->isEmpty())
            <div class="emp-empty">
                <i class="fas fa-users"></i>
                <p>Aucun membre dans votre équipe.</p>
                <a href="{{ route('employee.create') }}" class="emp-btn emp-btn--primary">
                    <i class="fas fa-user-plus"></i> Ajouter le premier membre
                </a>
            </div>
        @else
            <div class="emp-table-wrap">
                <table class="emp-table">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th class="emp-col-hide">Contact</th>
                            <th class="emp-col-hide">Adresse</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                        @php
                            $imgSrc = $employee->image
                                ? asset('images/employee_images/' . $employee->image)
                                : null;
                            $initials = strtoupper(substr($employee->name ?? 'E', 0, 2));
                        @endphp
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    @if($imgSrc)
                                        <img class="emp-avatar" src="{{ $imgSrc }}" alt="{{ $employee->name }}"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                        <span class="emp-avatar--fallback" style="display:none;">{{ $initials }}</span>
                                    @else
                                        <span class="emp-avatar--fallback">{{ $initials }}</span>
                                    @endif
                                    <div>
                                        <span class="emp-name">{{ $employee->name }}</span>
                                        <span class="emp-sub">{{ $employee->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="emp-col-hide">
                                <div class="emp-contact">
                                    @if($employee->phone)
                                        <div><i class="fas fa-phone"></i>{{ $employee->phone }}</div>
                                    @else
                                        <span style="color:var(--bd-text-3);">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="emp-col-hide">
                                <span style="font-size:12px;color:var(--bd-text-2);max-width:200px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $employee->address ?: '—' }}
                                </span>
                            </td>
                            <td>
                                <div class="emp-actions">
                                    <a href="{{ route('employee.edit', $employee->id) }}" class="emp-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('employee.destroy', $employee->id) }}"
                                          method="post" style="display:inline;"
                                          onsubmit="return confirm('Supprimer cet employé ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="emp-action-btn emp-action-btn--delete" title="Supprimer">
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
