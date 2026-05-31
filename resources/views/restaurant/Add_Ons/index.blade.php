@extends('layouts.restaurant_app')
@section('title', 'Suppléments | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Suppléments')
@section('add_on_nav', 'active')

@section('style')
<style>
.adn { display: flex; flex-direction: column; gap: 20px; }

.adn-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font); border: none; transition: .12s;
    text-decoration: none;
}
.adn-btn--primary { background: var(--bd-green); color: #fff; }
.adn-btn--primary:hover { background: var(--bd-green-dark, #007836); color: #fff; }
.adn-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.adn-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }

.adn-form-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 20px 24px;
}
.adn-form-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); margin-bottom: 14px; }
.adn-form-row { display: flex; align-items: flex-start; gap: 10px; flex-wrap: wrap; }
.adn-input {
    flex: 1; min-width: 160px;
    padding: 8px 12px; border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    font-size: 13px; font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.adn-input:focus { border-color: var(--bd-green); }
.adn-input--error { border-color: #dc2626; }
.adn-error { font-size: 11px; color: #dc2626; margin-top: 4px; width: 100%; }

.adn-card {
    background: var(--bd-surface);
    border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius);
    overflow: hidden;
}
.adn-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    border-bottom: 1px solid var(--bd-border-2);
    flex-wrap: wrap;
}
.adn-card__title { font-size: 13px; font-weight: 600; color: var(--bd-text); }
.adn-card__count { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.adn-table-wrap { overflow-x: auto; }
.adn-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.adn-table thead th {
    padding: 9px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--bd-text-3);
    border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.adn-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.adn-table tbody tr:last-child { border-bottom: none; }
.adn-table tbody tr:hover { background: var(--bd-surface-2); }
.adn-table td { padding: 11px 16px; color: var(--bd-text-2); vertical-align: middle; }

.adn-name { font-weight: 600; color: var(--bd-text); }
.adn-product-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--bd-surface-2); color: var(--bd-text-2);
    font-size: 11px; padding: 2px 8px; border-radius: 6px;
    border: 1px solid var(--bd-border);
}

.adn-actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.adn-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border);
    background: var(--bd-surface); color: var(--bd-text-2);
    cursor: pointer; font-size: 12px; transition: .12s; text-decoration: none;
}
.adn-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.adn-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.adn-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.adn-empty {
    padding: 40px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.adn-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }
.adn-empty p { margin: 0; }
</style>
@endsection

@section('content')
<div class="adn">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Formulaire ajout / édition ──────────────────── --}}
    <div class="adn-form-card">
        <div class="adn-form-card__title">
            {{ isset($addonstitle) ? 'Modifier le supplément' : 'Ajouter un supplément' }}
        </div>
        <form method="post"
              action="{{ isset($addonstitle) ? route('add-on.update', $addonstitle->id) : route('add-on.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if(isset($addonstitle)) @method('PUT') @endif
            <div class="adn-form-row">
                <input required name="title" id="title" type="text"
                       placeholder="Nom du supplément"
                       value="{{ isset($addonstitle) ? $addonstitle->title : old('title') }}"
                       class="adn-input {{ $errors->has('title') ? 'adn-input--error' : '' }}" />
                <select required name="product_id" id="Product_id"
                        class="adn-input {{ $errors->has('product_id') ? 'adn-input--error' : '' }}">
                    <option value="">Choisir un produit…</option>
                    @foreach($prod as $product)
                        <option value="{{ $product->id }}"
                            {{ (isset($addonstitle) && $addonstitle->product_id == $product->id) || old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="adn-btn adn-btn--primary">
                    {{ isset($addonstitle) ? 'Mettre à jour' : 'Ajouter' }}
                </button>
                @if(isset($addonstitle))
                    <a href="{{ route('add-on.index') }}" class="adn-btn adn-btn--outline">Annuler</a>
                @endif
            </div>
            @error('title')
                <div class="adn-error">{{ $message }}</div>
            @enderror
            @error('product_id')
                <div class="adn-error">{{ $message }}</div>
            @enderror
        </form>
    </div>

    {{-- ── Tableau suppléments ──────────────────────────── --}}
    <div class="adn-card">
        <div class="adn-card__head">
            <div>
                <div class="adn-card__title">Tous les suppléments</div>
                <div class="adn-card__count">{{ $addon->count() }} supplément(s)</div>
            </div>
        </div>

        @if($addon->isEmpty())
            <div class="adn-empty">
                <i class="fas fa-circle-plus"></i>
                <p>Aucun supplément créé.</p>
            </div>
        @else
            <div class="adn-table-wrap">
                <table class="adn-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Produit associé</th>
                            <th>Créé le</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($addon as $addons)
                        <tr>
                            <td><span class="adn-name">{{ $addons->title }}</span></td>
                            <td>
                                <span class="adn-product-tag">
                                    <i class="fas fa-utensils" style="font-size:9px;"></i>
                                    {{ $addons->product->name ?? '—' }}
                                </span>
                            </td>
                            <td style="font-size:12px;">
                                {{ $addons->created_at ? \Carbon\Carbon::parse($addons->created_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td>
                                <div class="adn-actions">
                                    <a href="{{ route('add-on.edit', $addons->id) }}" class="adn-action-btn" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('add-on.destroy', $addons->id) }}"
                                          method="post" style="display:inline;"
                                          onsubmit="return confirm('Supprimer ce supplément ?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="adn-action-btn adn-action-btn--delete" title="Supprimer">
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
