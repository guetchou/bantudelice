@extends('layouts.admin-modern')

@section('title', 'Ajouter une cuisine | Food ops')
@section('page_title', 'Nouvelle cuisine')
@section('nav_active', 'cuisine')

@section('content')
<style>
/* ── Scoped cuisine create ── */
.cuis-form-wrap {
    max-width: 460px;
    margin: 32px auto;
}
.cuis-form-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.10);
    overflow: hidden;
}
.cuis-form-head {
    padding: 14px 20px;
    background: #1e3a5f;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 15px;
    font-weight: 600;
}
.cuis-form-head i { color: #22c55e; }

.cuis-form-body { padding: 20px 24px; }

.cuis-field { margin-bottom: 18px; }
.cuis-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.cuis-field label span { color: #ef4444; }

.cuis-input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #d1d5db;
    border-radius: 6px;
    font-size: 13.5px;
    color: #111827;
    background: #f9fafb;
    outline: none;
    box-sizing: border-box;
    transition: border-color .18s;
}
.cuis-input:focus { border-color: #1e3a5f; background: #fff; }
.cuis-input-error { border-color: #ef4444; }

.cuis-field-error {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #ef4444;
    font-weight: 500;
}
.cuis-field-hint {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #6b7280;
}

.cuis-form-footer {
    padding: 14px 24px;
    border-top: 1px solid #f0f4f8;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.cuis-btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 18px;
    border-radius: 6px;
    border: 1.5px solid #6b7280;
    color: #6b7280;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: border-color .15s, color .15s;
}
.cuis-btn-cancel:hover { border-color: #1e3a5f; color: #1e3a5f; text-decoration: none; }

.cuis-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 20px;
    border-radius: 6px;
    border: none;
    background: #22c55e;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.cuis-btn-submit:hover { background: #16a34a; }
</style>

<div class="cuis-form-wrap">
    <div class="cuis-form-card">
        <div class="cuis-form-head">
            <i class="fas fa-plus-circle"></i>
            Ajouter une cuisine
        </div>

        <form role="form" method="post" action="{{ route('cuisine.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="cuis-form-body">

                <div class="cuis-field">
                    <label for="name">Nom de la cuisine <span>*</span></label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Ex: Cuisine africaine"
                           class="cuis-input{{ $errors->has('name') ? ' cuis-input-error' : '' }}"
                           required>
                    @if($errors->has('name'))
                        <span class="cuis-field-error" role="alert">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="cuis-field">
                    <label for="image">Image <span>*</span></label>
                    <input type="file"
                           id="image"
                           name="image"
                           class="cuis-input{{ $errors->has('image') ? ' cuis-input-error' : '' }}"
                           accept=".jpg,.jpeg,.png,.webp"
                           required>
                    <span class="cuis-field-hint">Formats acceptés : JPG, PNG, WEBP. Taille maximale : 8 Mo.</span>
                    @if($errors->has('image'))
                        <span class="cuis-field-error" role="alert">
                            <strong>{{ $errors->first('image') }}</strong>
                        </span>
                    @endif
                </div>

            </div>

            <div class="cuis-form-footer">
                <a href="{{ route('cuisine.index') }}" class="cuis-btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="cuis-btn-submit">
                    <i class="fas fa-save"></i> Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
