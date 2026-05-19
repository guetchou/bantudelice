@extends('errors.layout')
@section('title', 'Trop de requêtes')
@section('body')
<div class="bd-err-code">429</div>
<div class="bd-err-title">Trop de tentatives</div>
<div class="bd-err-msg">
    Vous avez effectué trop de requêtes en peu de temps.<br>
    Patientez quelques secondes puis réessayez.
</div>
<a href="javascript:history.back()" class="bd-err-btn">← Retour</a>
@endsection
