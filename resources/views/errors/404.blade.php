@extends('errors.layout')
@section('title', 'Page introuvable')
@section('body')
<div class="bd-err-code">404</div>
<div class="bd-err-title">Page introuvable</div>
<div class="bd-err-msg">
    Cette page n'existe pas ou a été déplacée.<br>
    Peut-être que ce plat a disparu du menu ?
</div>
<a href="/" class="bd-err-btn">← Retour à l'accueil</a>
<div class="bd-err-divider">ou</div>
<a href="/restaurants" class="bd-err-link">Parcourir les restaurants</a>
@endsection
