@extends('errors.layout')
@section('title', 'Erreur serveur')
@section('body')
<div class="bd-err-code">500</div>
<div class="bd-err-title">Erreur serveur</div>
<div class="bd-err-msg">
    Quelque chose a mal tourné de notre côté.<br>
    Notre équipe a été notifiée. Réessayez dans quelques instants.
</div>
<a href="/" class="bd-err-btn">← Retour à l'accueil</a>
@endsection
