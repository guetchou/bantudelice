@extends('layouts.admin-modern')
@section('title', 'Créer une actualité | Admin')
@section('page_title', 'Nouvelle actualité')
@section('nav_active', 'news')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="bd-admin-editor-shell">
            <section class="bd-admin-editor-hero">
                <div>
                    <p class="bd-admin-editor-hero__eyebrow">Newsroom</p>
                    <h1>Ajouter une actualité</h1>
                    <p>Créez une publication claire et prête à être diffusée sur le site ou via notification.</p>
                </div>
                <a href="{{ route('news.index') }}" class="btn btn-light">Retour à la liste</a>
            </section>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card bd-admin-editor-card">
                    <div class="card-header border-0">
                        <div class="bd-admin-editor-card__header">
                            <div>
                                <h3>Nouvelle actualité</h3>
                                <p>Renseignez un titre précis et une description exploitable sur le site public.</p>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('news.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Titre <span class="text-danger">*</span></label>
                                <input type="text" value="{{ old('title') }}" name="title" id="title" class="form-control{{ $errors->has('title') ? ' is-invalid' : ''}}" placeholder="Ex: Nouvelle promotion disponible" required>
                                @if($errors->has('title'))
                                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('title') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group mb-0">
                                <label for="description">Description <span class="text-danger">*</span></label>
                                <textarea rows="6" id="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : ''}}" name="description" placeholder="Décrivez l'actualité...">{{ old('description') }}</textarea>
                                @if($errors->has('description'))
                                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('description') }}</strong></span>
                                @endif
                            </div>
                        </div>
                        <div class="card-footer border-0 d-flex justify-content-end gap-2">
                            <a href="{{ route('news.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Publier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .bd-admin-editor-shell { display:grid; gap:20px; }
    .bd-admin-editor-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; padding:28px 32px; border-radius:32px; background:linear-gradient(135deg,#020617 0%,#0f172a 60%,#155e75 100%); color:#fff; box-shadow:0 20px 60px rgba(15,23,42,.22); }
    .bd-admin-editor-hero__eyebrow { margin:0 0 8px; font-size:.78rem; letter-spacing:.18em; text-transform:uppercase; font-weight:800; color:#bae6fd; }
    .bd-admin-editor-hero h1 { margin:0; color:#fff !important; font-size:clamp(2rem,4vw,3rem); font-weight:900; line-height:1.04; }
    .bd-admin-editor-hero p { margin:14px 0 0; max-width:760px; color:rgba(255,255,255,.82); line-height:1.8; }
    .bd-admin-editor-card__header h3 { margin:0; color:#020617; font-size:1.2rem; font-weight:900; }
    .bd-admin-editor-card__header p { margin:8px 0 0; color:#64748b; line-height:1.7; }
    @media (max-width: 991.98px) { .bd-admin-editor-hero { flex-direction:column; align-items:flex-start; } }
</style>
@endsection
