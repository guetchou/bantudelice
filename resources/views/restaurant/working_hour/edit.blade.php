@extends('layouts.restaurant_app')
@section('working_hour_nav', 'active')
@section('title', 'Modifier un horaire')
@section('topbar_title', 'Modifier les horaires')

@section('content')
<div style="max-width:640px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Modifier un horaire régulier</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">
                Ajustez le jour et la plage horaire affichés aux clients.
            </div>
        </div>
        <form method="post" action="{{ route('working_hour.update',$workingHour->id) }}">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="form-group">
                    <label for="day">Jour</label>
                    <input type="text" value="{{ $workingHour->Day }}"
                           class="form-control {{ $errors->has('day') ? ' is-invalid' : ''}}"
                           name="day" id="day" placeholder="Saisir"/>
                    @if($errors->has('day'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('day') }}</strong></span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="opening_time">Heure d’ouverture</label>
                    <input type="text" value="{{ $workingHour->opening_time }}"
                           class="form-control {{ $errors->has('opening_time') ? ' is-invalid' : ''}}"
                           name="opening_time" id="opening_time" placeholder="08:00:00"/>
                    @if($errors->has('opening_time'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('opening_time') }}</strong></span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="closing_time">Heure de fermeture</label>
                    <input type="text" value="{{ $workingHour->closing_time }}"
                           class="form-control {{ $errors->has('closing_time') ? ' is-invalid' : ''}}"
                           name="closing_time" id="closing_time" placeholder="18:00:00"/>
                    @if($errors->has('closing_time'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('closing_time') }}</strong></span>
                    @endif
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
                <a href="{{ route('working_hour.index') }}" class="btn btn-secondary btn-sm">Annuler</a>
                <button type="submit" class="btn btn-primary btn-sm">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
@endsection
