@extends('layouts.admin-modern')
@section('title', 'Modifier une actualité | Admin')
@section('page_title', 'Modifier actualité')
@section('nav_active', 'news')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="bd-admin-editor-shell">
            <section class="bd-admin-editor-hero">
                <div>
                    <p class="bd-admin-editor-hero__eyebrow">Newsroom</p>
                    <h1>Modifier une actualité</h1>
                    <p>Réajustez le contenu avant republication ou notification ciblée.</p>
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
                                <h3>Mise à jour</h3>
                                <p>Conservez un wording clair et directement exploitable sur le site public.</p>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('news.update', $news->id) }}">
                        @csrf
                        @method('put')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Titre</label>
                                <input type="text" name="title" id="title" class="form-control{{ $errors->has('title') ? ' is-invalid' : ''}}" value="{{ old('title', $news->title) }}">
                                @if($errors->has('title'))
                                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('title') }}</strong></span>
                                @endif
                            </div>
                            <div class="form-group mb-0">
                                <label for="description">Description</label>
                                <textarea id="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : ''}}" name="description" rows="6">{{ old('description', $news->description) }}</textarea>
                                @if($errors->has('description'))
                                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('description') }}</strong></span>
                                @endif
                            </div>
                        </div>
                        <div class="card-footer border-0 d-flex justify-content-end gap-2">
                            <a href="{{ route('news.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
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
