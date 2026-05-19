@extends('errors.layout')
@section('title', 'Maintenance en cours')
@section('body')
<div class="bd-err-code">503</div>
<div class="bd-err-title">Maintenance en cours</div>
<div class="bd-err-msg">
    BantuDelice est temporairement indisponible pour maintenance.<br>
    Nous revenons très bientôt avec de nouveaux plats pour vous.
</div>
@if(!empty($exception) && $exception->getMessage())
<p style="font-size:.8rem;color:#aaa;margin-bottom:24px;">{{ $exception->getMessage() }}</p>
@endif
<a href="/" class="bd-err-btn" onclick="location.reload();return false;">Réessayer</a>
@endsection
