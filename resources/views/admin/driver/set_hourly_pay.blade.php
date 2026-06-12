@extends('layouts.admin-modern')
@section('title', 'Tarif horaire livreur | BantuDelice')
@section('page_title', 'Tarif horaire livreur')
@section('nav_active', 'drivers')

@section('style')
<style>
/* ── drv-set-pay ────────────────────────────────────────────── */
.drv-page { padding: 24px; }

.drv-pay-wrap {
    display: flex;
    justify-content: center;
    padding: 16px 0 32px;
}

.drv-pay-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    width: 100%;
    max-width: 420px;
    overflow: hidden;
}

.drv-pay-card__header {
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    padding: 16px 20px;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.drv-pay-card__body { padding: 24px 20px; }

.drv-field { margin-bottom: 4px; }

.drv-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.drv-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    color: #111827;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.drv-input:focus {
    outline: none;
    border-color: var(--adm-accent, #1e3a5f);
    box-shadow: 0 0 0 3px rgba(30,58,95,.12);
}
.drv-input--error { border-color: #ef4444; }

.drv-field-error {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #dc2626;
    font-weight: 500;
}

.drv-pay-card__footer {
    padding: 14px 20px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
}

.drv-btn-cancel {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #d1d5db;
    color: #374151;
    background: #fff;
    transition: background .15s;
}
.drv-btn-cancel:hover { background: #f9fafb; color: #111827; text-decoration: none; }

.drv-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    background: var(--adm-accent, #1e3a5f);
    color: #fff;
    border: none;
    cursor: pointer;
    transition: opacity .15s;
}
.drv-btn-save:hover { opacity: .85; }
</style>
@endsection

@section('content')
<div class="drv-page">
    <div class="drv-pay-wrap">
        <div class="drv-pay-card">

            <div class="drv-pay-card__header">
                <i class="fas fa-money-bill-wave"></i>
                Tarif horaire — {{ $driver->name }}
            </div>

            <form role="form" method="post" action="{{ route('admin.set_hourly_pay', $driver->id) }}">
                @csrf

                <div class="drv-pay-card__body">
                    <div class="drv-field">
                        <label for="hourly_pay" class="drv-label">Tarif horaire (FCFA)</label>
                        <input
                            type="text"
                            id="hourly_pay"
                            name="hourly_pay"
                            value="{{ old('hourly_pay') }}"
                            placeholder="Ex : 2500"
                            class="drv-input {{ $errors->has('hourly_pay') ? 'drv-input--error' : '' }}">
                        @if($errors->has('hourly_pay'))
                            <span class="drv-field-error" role="alert">
                                {{ $errors->first('hourly_pay') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="drv-pay-card__footer">
                    <a href="{{ route('driver.index') }}" class="drv-btn-cancel">Annuler</a>
                    <button type="submit" class="drv-btn-save">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
