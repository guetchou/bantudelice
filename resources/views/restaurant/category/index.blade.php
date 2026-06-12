@extends('layouts.restaurant_app')
@section('title', 'Catégories | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Catégories')
@section('category_nav', 'active')

@section('style')
<style>
.cat { display: flex; flex-direction: column; gap: 20px; }

.cat-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}

.cat-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.cat-btn--primary { background: var(--bd-green); color: #fff; }
.cat-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.cat-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.cat-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }

.cat-form-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 20px 24px;
}
.cat-form-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); margin-bottom: 14px; }
.cat-form-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.cat-input {
    flex: 1; min-width: 200px;
    padding: 8px 12px; border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    font-size: 13px; font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.cat-input:focus { border-color: var(--bd-green); }
.cat-input--error { border-color: #dc2626; }
.cat-error { font-size: 11px; color: #dc2626; margin-top: 4px; }

.cat-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.cat-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.cat-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.cat-card__count { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.cat-table-wrap { overflow-x: auto; }
.cat-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.cat-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.cat-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.cat-table tbody tr:last-child { border-bottom: none; }
.cat-table tbody tr:hover { background: var(--bd-surface-2); }
.cat-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

.cat-name { font-weight: 600; color: var(--bd-text); }

.cat-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.cat-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s; text-decoration: none;
}
.cat-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.cat-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.cat-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.cat-empty {
    padding: 40px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.cat-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }
.cat-empty p { margin: 0 0 14px; }
</style>
@endsection

@section('content')
<div class="cat">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Formulaire ajout / édition ──────────────────── --}}
    <div class="cat-form-card">
        <div class="cat-form-card__title">
            {{ isset($category) ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
        </div>
        <form method="post"
              action="{{ isset($category) ? route('category.update', $category->id) : route('category.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if(isset($category)) @method('PUT') @endif
            <div class="cat-form-row">
                <input required name="name" id="name" type="text"
                       value="{{ isset($category) ? $category->name : old('name') }}"
                       placeholder="Nom de la catégorie"
                       class="cat-input {{ $errors->has('name') ? 'cat-input--error' : '' }}" />
                <button type="submit" class="cat-btn cat-btn--primary">
                    {{ isset($category) ? 'Mettre à jour' : 'Ajouter' }}
                </button>
                @if(isset($category))
                    <a href="{{ route('category.index') }}" class="cat-btn cat-btn--outline">Annuler</a>
                @endif
            </div>
            @error('name')
                <div class="cat-error">{{ $message }}</div>
            @enderror
        </form>
    </div>

    {{-- ── Tableau catégories ───────────────────────────── --}}
    <div class="cat-card">
        <div class="cat-card__head">
            <div>
                <div class="cat-card__title">Toutes les catégories</div>
                <div class="cat-card__count">{{ $categories->count() }} catégorie(s)</div>
            </div>
            <a href="{{ route('restaurant.menu.index') }}" class="cat-btn cat-btn--outline" style="padding:5px 11px;font-size:11px;">
                <i class="fas fa-arrows-up-down-left-right"></i> Réordonner dans le menu
            </a>
        </div>

        @if($categories->isEmpty())
            <div class="cat-empty">
                <i class="fas fa-layer-group"></i>
                <p>Aucune catégorie créée.</p>
            </div>
        @else
            <div class="cat-table-wrap">
                <table class="cat-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Créée le</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                        <tr>
                            <td><span class="cat-name">{{ $cat->name }}</span></td>
                            <td style="font-size:12px;">
                                {{ $cat->created_at ? \Carbon\Carbon::parse($cat->created_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td>
                                <div class="cat-actions">
                                    <a href="{{ route('category.edit', $cat->id) }}" class="cat-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('category.destroy', $cat->id) }}"
                                          method="post" style="display:inline;"
                                          onsubmit="return confirm('Supprimer cette catégorie ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="cat-action-btn cat-action-btn--delete" title="Supprimer">
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
