@extends('layouts.app')
@section('title', 'Nouveau Point Relais | BantuDelice')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Nouveau Point Relais</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <form action="{{ route('admin.relay-points.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nom du partenaire</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Station Total Poto-Poto" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Téléphone de contact</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="06 000 00 00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Ville</label>
                            <input type="text" name="city" class="form-control" value="Brazzaville" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Quartier</label>
                            <input type="text" name="district" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Adresse exacte</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Horaires d'ouverture</label>
                        <input type="text" name="opening_hours" class="form-control" placeholder="Ex: 8h00 - 20h00">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.relay-points.index') }}" class="btn btn-default">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

