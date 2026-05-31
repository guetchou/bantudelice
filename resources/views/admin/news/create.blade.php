@extends('layouts.admin-modern')
@section('title', 'Créer une actualité | Admin')
@section('page_title', 'Nouvelle actualité')
@section('nav_active', 'news')

@section('style')
<style>
.nws-form-wrap { max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
.nws-form-hero {
    display: flex; justify-content: space-between; align-items: center;
    gap: 16px; padding: 20px 24px; border-radius: 12px;
    background: linear-gradient(135deg, #020617 0%, #0f172a 60%, #155e75 100%);
    color: #fff; flex-wrap: wrap;
}
.nws-form-hero__eyebrow { font-size: 10px; letter-spacing: .16em; text-transform: uppercase; font-weight: 800; color: #bae6fd; margin: 0 0 4px; }
.nws-form-hero h1 { margin: 0; font-size: 1.3rem; font-weight: 900; color: #fff; }
.nws-form-hero p  { margin: 4px 0 0; color: rgba(255,255,255,.8); font-size: 12px; }
.nws-form-back { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; background: rgba(255,255,255,.12); color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.25); white-space: nowrap; }
.nws-form-back:hover { background: rgba(255,255,255,.22); color: #fff; }

.nws-form-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.nws-form-card__head { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; }
.nws-form-card__head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #111827; }
.nws-form-card__head p  { margin: 3px 0 0; font-size: 12px; color: #6b7280; }

.nws-form-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

.nws-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.nws-field label span { color: #ef4444; }
.nws-input {
    width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 7px;
    font-size: 13px; font-family: 'Manrope', sans-serif; color: #111827;
    background: #fff; outline: none; box-sizing: border-box; transition: border-color .12s;
    resize: vertical;
}
.nws-input:focus { border-color: #1d4ed8; }
.nws-input--error { border-color: #ef4444; }
.nws-field-error { font-size: 11px; color: #ef4444; margin-top: 3px; font-weight: 500; }

.nws-form-footer {
    display: flex; justify-content: flex-end; gap: 8px;
    padding: 14px 20px; border-top: 1px solid #f3f4f6;
}
.nws-btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; transition: .12s; cursor: pointer; }
.nws-btn--outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.nws-btn--outline:hover { border-color: #374151; color: #111827; }
.nws-btn--primary { background: #22c55e; color: #fff; border: none; }
.nws-btn--primary:hover { background: #16a34a; }
</style>
@endsection

@section('content')
<div class="nws-form-wrap">

    <div class="nws-form-hero">
        <div>
            <p class="nws-form-hero__eyebrow">Newsroom</p>
            <h1>Ajouter une actualité</h1>
            <p>Créez une publication claire et prête à être diffusée.</p>
        </div>
        <a href="{{ route('news.index') }}" class="nws-form-back">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="nws-form-card">
        <div class="nws-form-card__head">
            <h3>Nouvelle actualité</h3>
            <p>Renseignez un titre précis et une description exploitable sur le site public.</p>
        </div>
        <form method="post" action="{{ route('news.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="nws-form-body">
                <div class="nws-field">
                    <label for="title">Titre <span>*</span></label>
                    <input type="text" id="title" name="title"
                           value="{{ old('title') }}"
                           placeholder="Ex: Nouvelle promotion disponible"
                           class="nws-input{{ $errors->has('title') ? ' nws-input--error' : '' }}"
                           required>
                    @if($errors->has('title'))
                        <div class="nws-field-error">{{ $errors->first('title') }}</div>
                    @endif
                </div>
                <div class="nws-field">
                    <label for="description">Description <span>*</span></label>
                    <textarea id="description" name="description" rows="6"
                              placeholder="Décrivez l'actualité..."
                              class="nws-input{{ $errors->has('description') ? ' nws-input--error' : '' }}"
                              required>{{ old('description') }}</textarea>
                    @if($errors->has('description'))
                        <div class="nws-field-error">{{ $errors->first('description') }}</div>
                    @endif
                </div>
            </div>
            <div class="nws-form-footer">
                <a href="{{ route('news.index') }}" class="nws-btn nws-btn--outline">Annuler</a>
                <button type="submit" class="nws-btn nws-btn--primary">
                    <i class="fas fa-paper-plane"></i> Publier
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
