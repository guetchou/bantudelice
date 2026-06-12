@extends('layouts.restaurant_app')
@section('title', 'Suppléments produit | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Suppléments produit')
@section('product_nav', 'active')

@section('style')
<style>
.aop { display: flex; flex-direction: column; gap: 20px; }

.aop-product-bar {
    display: flex; align-items: center; gap: 14px;
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 14px 18px;
}
.aop-product-img {
    width: 52px; height: 52px; border-radius: 8px;
    object-fit: cover; flex-shrink: 0;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border-2);
}
.aop-product-name { font-size: 14px; font-weight: 700; color: var(--bd-text); }
.aop-product-sub  { font-size: 11px; color: var(--bd-text-3); margin-top: 2px; }

.aop-tabs {
    display: flex; gap: 2px;
    background: var(--bd-surface-2); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); padding: 3px; width: fit-content;
}
.aop-tab {
    padding: 7px 18px; border-radius: calc(var(--bd-radius) - 2px);
    font-size: 12px; font-weight: 600; cursor: pointer;
    color: var(--bd-text-3); border: none; background: transparent;
    font-family: var(--bd-font); transition: .12s;
}
.aop-tab.active {
    background: var(--bd-surface); color: var(--bd-text);
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}

.aop-panel { display: none; }
.aop-panel.active { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 900px) { .aop-panel.active { grid-template-columns: 1fr; } }

.aop-card {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.aop-card__head {
    padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2);
    font-size: 13px; font-weight: 700; color: var(--bd-text);
}
.aop-card__sub { font-size: 11px; color: var(--bd-text-3); font-weight: 400; margin-top: 1px; }

.aop-form { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.aop-label { display: block; font-size: 12px; font-weight: 600; color: var(--bd-text); margin-bottom: 5px; }
.aop-input {
    width: 100%; box-sizing: border-box;
    padding: 8px 11px; border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); font-size: 13px;
    font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.aop-input:focus { border-color: var(--bd-green); }
.aop-input--error { border-color: #dc2626; }

.aop-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.aop-submit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: var(--bd-radius);
    background: var(--bd-green); color: #fff;
    font-size: 12px; font-weight: 700; border: none;
    cursor: pointer; font-family: var(--bd-font); transition: .12s;
}
.aop-submit:hover { background: var(--bd-green-dark, #007836); }

.aop-table-wrap { overflow-x: auto; }
.aop-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; font-family: var(--bd-font-body, 'Inter', sans-serif);
}
.aop-table thead th {
    padding: 8px 14px; font-size: 10px; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: var(--bd-text-3); border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.aop-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.aop-table tbody tr:last-child { border-bottom: none; }
.aop-table tbody tr:hover { background: var(--bd-surface-2); }
.aop-table td { padding: 9px 14px; color: var(--bd-text-2); vertical-align: middle; }
.aop-table td input {
    background: transparent; border: 1px solid transparent;
    border-radius: 5px; padding: 4px 7px; font-size: 12px;
    color: var(--bd-text); font-family: var(--bd-font);
    width: 100%; transition: border-color .12s;
}
.aop-table td input:focus { border-color: var(--bd-green); background: var(--bd-surface); outline: none; }

.aop-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid var(--bd-border); background: var(--bd-surface);
    color: var(--bd-text-2); cursor: pointer; font-size: 11px;
    transition: .12s; text-decoration: none;
}
.aop-action-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.aop-action-btn--delete { color: #dc2626; border-color: rgba(239,68,68,.2); }
.aop-action-btn--delete:hover { background: rgba(239,68,68,.06); border-color: #dc2626; color: #dc2626; }

.aop-empty {
    padding: 32px 20px; text-align: center;
    color: var(--bd-text-3); font-size: 13px;
}
.aop-empty i { font-size: 24px; display: block; margin-bottom: 8px; color: var(--bd-border); }
</style>
@endsection

@section('content')
<div class="aop">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Produit concerné ──────────────────────────── --}}
    <div class="aop-product-bar">
        <a href="{{ route('product.index') }}"
           style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
            <i class="fas fa-arrow-left"></i>
        </a>
        @php
            $addonImg = $prod->image
                ? (strpos($prod->image, 'http') === 0 ? $prod->image : asset('images/product_images/' . $prod->image))
                : asset('images/placeholder.png');
        @endphp
        <img class="aop-product-img" src="{{ $addonImg }}" alt="{{ $prod->name }}"
             onerror="this.src='{{ asset('images/placeholder.png') }}'">
        <div>
            <div class="aop-product-name">{{ $prod->name }}</div>
            <div class="aop-product-sub">Gestion des suppléments optionnels et obligatoires</div>
        </div>
    </div>

    {{-- ── Onglets ────────────────────────────────────── --}}
    <div class="aop-tabs">
        <button class="aop-tab active" onclick="switchTab('optional', this)">
            <i class="fas fa-circle-plus" style="margin-right:5px;"></i>Optionnels
        </button>
        <button class="aop-tab" onclick="switchTab('required', this)">
            <i class="fas fa-star" style="margin-right:5px;"></i>Obligatoires
        </button>
    </div>

    {{-- ── Panneau Optionnels ─────────────────────────── --}}
    <div class="aop-panel active" id="panel-optional">

        {{-- Formulaire ajout --}}
        <div class="aop-card">
            <div class="aop-card__head">
                Ajouter un optionnel
                <div class="aop-card__sub">Le client peut choisir ou non ce supplément</div>
            </div>
            <form method="post" action="{{ route('optional.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $prod->id }}">
                <div class="aop-form">
                    <div>
                        <label class="aop-label" for="opt_family">Famille de suppléments <span style="color:#dc2626;">*</span></label>
                        <select name="add_on_title_id" id="opt_family" required class="aop-input">
                            <option value="">Sélectionner une famille…</option>
                            @foreach($addons as $data)
                                <option value="{{ $data->id }}">{{ $data->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aop-row2">
                        <div>
                            <label class="aop-label" for="opt_title">Nom <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="title" id="opt_title" required class="aop-input"
                                   placeholder="Ex : Sauce piquante">
                        </div>
                        <div>
                            <label class="aop-label" for="opt_price">Prix (FCFA) <span style="color:#dc2626;">*</span></label>
                            <input type="number" name="price" id="opt_price" required class="aop-input"
                                   placeholder="0" min="0">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="aop-submit">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Liste --}}
        <div class="aop-card">
            <div class="aop-card__head">
                Suppléments optionnels
                <div class="aop-card__sub">{{ $optional->count() }} élément(s)</div>
            </div>
            @if($optional->isEmpty())
                <div class="aop-empty">
                    <i class="fas fa-circle-plus"></i>
                    <p>Aucun supplément optionnel.</p>
                </div>
            @else
                <div class="aop-table-wrap">
                    <table class="aop-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prix</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($optional as $data)
                            <tr>
                                <form method="post" action="{{ route('optional.update', $data->id) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="add_on_title_id" value="{{ $data->add_on_title_id }}">
                                    <td><input type="text" name="title" value="{{ $data->title }}"></td>
                                    <td><input type="number" name="price" value="{{ $data->price }}" style="width:80px;"></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:5px;justify-content:flex-end;">
                                            <button type="submit" class="aop-action-btn" title="Enregistrer">
                                                <i class="fas fa-check"></i>
                                            </button>
                                </form>
                                            <form action="{{ route('optional.destroy', $data->id) }}" method="post" style="display:inline;"
                                                  onsubmit="return confirm('Supprimer ce supplément ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="aop-action-btn aop-action-btn--delete" title="Supprimer">
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

    {{-- ── Panneau Obligatoires ───────────────────────── --}}
    <div class="aop-panel" id="panel-required">

        {{-- Formulaire ajout --}}
        <div class="aop-card">
            <div class="aop-card__head">
                Ajouter un obligatoire
                <div class="aop-card__sub">Le client doit choisir ce supplément</div>
            </div>
            <form method="post" action="{{ route('required.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="product_id" value="{{ $prod->id }}">
                <div class="aop-form">
                    <div>
                        <label class="aop-label" for="req_family">Famille de suppléments <span style="color:#dc2626;">*</span></label>
                        <select name="add_on_title_id" id="req_family" required class="aop-input">
                            <option value="">Sélectionner une famille…</option>
                            @foreach($addons as $data)
                                <option value="{{ $data->id }}">{{ $data->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aop-row2">
                        <div>
                            <label class="aop-label" for="req_title">Nom <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="title" id="req_title" required class="aop-input"
                                   placeholder="Ex : Taille de portion">
                        </div>
                        <div>
                            <label class="aop-label" for="req_price">Prix (FCFA) <span style="color:#dc2626;">*</span></label>
                            <input type="number" name="price" id="req_price" required class="aop-input"
                                   placeholder="0" min="0">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="aop-submit">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Liste --}}
        <div class="aop-card">
            <div class="aop-card__head">
                Suppléments obligatoires
                <div class="aop-card__sub">{{ $required->count() }} élément(s)</div>
            </div>
            @if($required->isEmpty())
                <div class="aop-empty">
                    <i class="fas fa-star"></i>
                    <p>Aucun supplément obligatoire.</p>
                </div>
            @else
                <div class="aop-table-wrap">
                    <table class="aop-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prix</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($required as $data)
                            <tr>
                                <form method="post" action="{{ route('required.update', $data->id) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="add_on_title_id" value="{{ $data->add_on_title_id }}">
                                    <td><input type="text" name="title" value="{{ $data->title }}"></td>
                                    <td><input type="number" name="price" value="{{ $data->price }}" style="width:80px;"></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:5px;justify-content:flex-end;">
                                            <button type="submit" class="aop-action-btn" title="Enregistrer">
                                                <i class="fas fa-check"></i>
                                            </button>
                                </form>
                                            <form action="{{ route('required.destroy', $data->id) }}" method="post" style="display:inline;"
                                                  onsubmit="return confirm('Supprimer ce supplément ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="aop-action-btn aop-action-btn--delete" title="Supprimer">
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

</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.aop-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.aop-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}
</script>
@endsection
