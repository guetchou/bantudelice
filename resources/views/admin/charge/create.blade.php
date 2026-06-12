@extends('layouts.admin-modern')
@section('title', 'Ajouter des charges')
@section('page_title', 'Nouveau paramètre')
@section('nav_active', 'settings')

@section('style')
<style>
.chg-page { padding:24px; }
.chg-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.chg-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.chg-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.chg-layout { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; margin-bottom:20px; }
.chg-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.chg-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:8px; }
.chg-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.chg-card__body { padding:20px; }
.chg-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:10px; }
.chg-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.chg-label { font-size:13px; font-weight:600; color:#374151; }
.chg-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s; box-sizing:border-box; }
.chg-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.chg-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.chg-btn-primary:hover { opacity:.85; }
.chg-btn-reset { display:inline-flex; align-items:center; padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; color:#374151; background:#fff; cursor:pointer; transition:background .15s; }
.chg-btn-reset:hover { background:#f9fafb; }
.chg-gif-card { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:flex; align-items:center; justify-content:center; padding:20px; }
.chg-table-wrap { overflow-x:auto; }
.chg-table { width:100%; border-collapse:collapse; font-size:13px; }
.chg-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.chg-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.chg-action-btn { display:inline-flex; align-items:center; padding:4px 10px; border-radius:5px; font-size:12px; font-weight:500; border:1px solid #d1d5db; color:#9ca3af; background:none; cursor:not-allowed; }
@media (max-width:768px) { .chg-layout { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="chg-page">

    @if(session()->has('alert'))
        <div class="chg-alert chg-alert--{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif

    <div class="chg-layout">
        <div class="chg-card">
            <div class="chg-card__header">
                <i class="fas fa-plus-circle" style="color:#1e3a5f;"></i>
                <h2 class="chg-card__title">Ajouter des charges</h2>
            </div>
            <form role="form" method="post" action="{{ route('charge.store') }}">
                @csrf
                <div class="chg-card__body">
                    <div class="chg-field">
                        <label class="chg-label" for="service_fee">Frais de service</label>
                        <input type="text" class="chg-input" name="service_fee" id="service_fee">
                    </div>
                    <div class="chg-field">
                        <label class="chg-label" for="tax">Taxe</label>
                        <input type="text" class="chg-input" name="tax" id="tax">
                    </div>
                    <div class="chg-field">
                        <label class="chg-label" for="delivery_fee">Frais de livraison</label>
                        <input type="text" class="chg-input" name="delivery_fee" id="delivery_fee">
                    </div>
                </div>
                <div class="chg-card__footer">
                    <button type="reset" class="chg-btn-reset">Annuler</button>
                    <button type="submit" class="chg-btn-primary">
                        <i class="fas fa-save"></i> Soumettre
                    </button>
                </div>
            </form>
        </div>

        <div class="chg-gif-card">
            <img src="{{ asset('images/banner-in-gif.gif') }}" alt="Finance" style="max-width:100%; border-radius:8px;">
        </div>
    </div>

    <div class="chg-card">
        <div class="chg-card__header">
            <h2 class="chg-card__title">Charges configurées</h2>
        </div>
        <div class="chg-table-wrap">
            <table class="chg-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Frais de service</th>
                        <th>Taxe</th>
                        <th>Frais de livraison</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>12</td>
                        <td>1</td>
                        <td>7</td>
                        <td>
                            <button type="button" class="chg-action-btn" disabled title="Indisponible sur cet écran">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="chg-action-btn" disabled title="Indisponible sur cet écran">
                                <i class="fa fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
