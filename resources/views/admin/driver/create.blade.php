@extends('layouts.admin-modern')
@section('title', 'Créer un livreur | Buntu Delice ')
@section('page_title', 'Nouveau livreur')
@section('nav_active', 'drivers')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-taxi mr-2"></i>Créer un livreur</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item "><a href="{{route('driver.index')}}">Livreurs</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content" style="padding: 20px; ">
        <div class="container-fluid main-content">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card card-warning shadow-sm">
                        <div class="card-header text-center">
                            <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Provisioning express</h3>
                        </div>
                        <form method="post" action="{{ route('driver.store') }}">
                            @csrf
                            <input type="hidden" name="provision_batch" value="1">
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Crée rapidement un lot de comptes livreur opérationnels avec identifiants, CNIC, email et téléphone générés automatiquement.
                                </p>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="provision_restaurant_id">Restaurant ciblé</label>
                                        <select name="restaurant_id" id="provision_restaurant_id" class="form-control {{ $errors->has('restaurant_id') ? ' is-invalid' : '' }}">
                                            <option value="">Pool mutualisé</option>
                                            @foreach($restaurants as $restaurant)
                                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id') == $restaurant->id ? 'selected' : '' }}>{{ $restaurant->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="quantity">Quantité</label>
                                        <input type="number" min="1" max="20" name="quantity" value="{{ old('quantity', 1) }}" class="form-control {{ $errors->has('quantity') ? ' is-invalid' : '' }}" id="quantity">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="provision_status">Statut initial</label>
                                        <select name="status" id="provision_status" class="form-control {{ $errors->has('status') ? ' is-invalid' : '' }}">
                                            <option value="online" {{ old('status', 'online') === 'online' ? 'selected' : '' }}>Online</option>
                                            <option value="offline" {{ old('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="name_prefix">Préfixe nom</label>
                                        <input type="text" name="name_prefix" value="{{ old('name_prefix') }}" class="form-control {{ $errors->has('name_prefix') ? ' is-invalid' : '' }}" id="name_prefix" placeholder="Ex: Livreur Ops Nganda">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label for="provision_address">Adresse de base</label>
                                        <input type="text" name="address" value="{{ old('address') }}" class="form-control {{ $errors->has('address') ? ' is-invalid' : '' }}" id="provision_address" placeholder="Ex: Brazzaville">
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-center">
                                        <div class="form-check mt-4 pt-2">
                                            <input class="form-check-input" type="checkbox" id="provision_approved" name="approved" value="1" {{ old('approved', 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="provision_approved">
                                                Compte approuvé immédiatement
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-warning btn-sm float-right">
                                    <i class="fas fa-cubes mr-1"></i>Provisionner le lot
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card card-primary shadow-sm">
                        <div class="card-header text-center">
                            <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Ajouter un livreur</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form role="form" method="post" action="{{ route('driver.store') }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <div class="qr-el qr-el-3"
                                             style="min-height: auto;  box-shadow: 2px 0px 30px 5px rgba(0, 0, 0, 0.03); padding:25px; margin:0px 20px;">
                                            <label for="file-input" class="hoviringdell uploadBox" id="uploadTrigger"
                                                   style="height: 110px; text-align:center; width:100%; border:dotted 2px #cccccc;">
                                                <img src="" style="width: 90px;" id="logo" alt="license">
                                                <div class="uploadText" style="font-size: 12px;">
                                                    <span style="color:#ff5a1f;"><i class="fas fa-upload mr-1"></i>Télécharger le permis</span><br>
                                                    Taille 90x90
                                                </div>
                                            </label>
                                            <input type="file" id="file-input" name="licence_image"
                                                   onchange="logo1(this);">
                                            @if($errors->has('licence_image'))
                                                <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('licence_image') }}</strong>
                                        </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="qr-el qr-el-3"
                                             style="min-height: auto;  box-shadow: 2px 0px 30px 5px rgba(0, 0, 0, 0.03); padding:25px; margin:0px 20px;">
                                            <label for="upload_file" class="hoviringdell uploadBox"
                                                   id="uploadTrigger"
                                                   style="height: 110px; text-align:center; width:100%; border:dotted 2px #cccccc;">
                                                <img src="" style="width: 180px;" id="cover" alt="image">
                                                <div class="uploadText" style="font-size: 12px;">
                                                    <span style="color:#ff5a1f;"><i class="fas fa-upload mr-1"></i>Télécharger une photo de profil</span><br>
                                                    Taille 320x220
                                                </div>
                                            </label>
                                            <input type="file" id="upload_file" name="image" onchange="cover1(this);">
                                            @if($errors->has('image'))
                                                <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('image') }}</strong>
                                        </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                                <div class="form-group ">
                                    <h4 style="text-align:center; padding:10px; margin-top:50px;"
                                        class="text-light bg-dark">
                                        <i class="fas fa-user mr-2"></i>Informations du livreur
                                    </h4>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="restaurant_id">Restaurant rattaché</label>
                                        <select name="restaurant_id" id="restaurant_id" class="form-control {{ $errors->has('restaurant_id') ? ' is-invalid' : '' }}">
                                            <option value="">Pool mutualisé</option>
                                            @foreach($restaurants as $restaurant)
                                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id') == $restaurant->id ? 'selected' : '' }}>{{ $restaurant->name }}</option>
                                            @endforeach
                                        </select>
                                        @if($errors->has('restaurant_id'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('restaurant_id') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="name">Nom <span class="text-danger">*</span></label>
                                        <input type="text" name="name" value="{{old('name')}}"
                                               class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}"
                                               id="name" placeholder="Ex: Jean Dupont" required>
                                        @if($errors->has('name'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" value="{{old('email')}}"
                                               class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                               id="email" placeholder="Ex: jean.dupont@example.com" required>
                                        @if($errors->has('email'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="pass">Mot de passe <span class="text-danger">*</span></label>
                                        <input type="password" name="password"
                                               class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}"
                                               id="pass" placeholder="Minimum 6 caractères" required>
                                        @if($errors->has('password'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="phone">Téléphone</label>
                                        <input type="text" name="phone" value="{{old('phone')}}"
                                               class="form-control {{ $errors->has('phone') ? ' is-invalid' : '' }}"
                                               id="phone">
                                        @if($errors->has('phone'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('phone') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="address">Adresse</label>
                                        <input type="text" name="address" value="{{old('address')}}"
                                               class="form-control {{ $errors->has('address') ? ' is-invalid' : '' }}"
                                               id="address" placeholder="">
                                        @if($errors->has('address'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('address') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="cnic">CNIC / Référence <span class="text-danger">*</span></label>
                                        <input type="text" name="cnic" value="{{old('cnic')}}"
                                               class="form-control {{ $errors->has('cnic') ? ' is-invalid' : '' }}"
                                               id="cnic" placeholder="Ex: CNIC-DRV-001" required>
                                        @if($errors->has('cnic'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('cnic') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="status">Statut initial</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="offline" {{ old('status', 'offline') === 'offline' ? 'selected' : '' }}>Offline</option>
                                            <option value="online" {{ old('status') === 'online' ? 'selected' : '' }}>Online</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-center">
                                        <div class="form-check mt-4 pt-2">
                                            <input class="form-check-input" type="checkbox" id="approved" name="approved" value="1" {{ old('approved', 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="approved">
                                                Compte approuvé
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group ">
                                    <h4 style="text-align:center; padding:10px; margin-top:50px;"
                                        class="text-light bg-dark">
                                        <i class="fas fa-university mr-2"></i>Détails bancaires
                                    </h4>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="account_name">Nom du titulaire</label>
                                        <input type="text" value="{{old('account_name')}}"
                                               class="form-control {{ $errors->has('account_name') ? ' is-invalid' : '' }}"
                                               id="account_name" name="account_name" placeholder="Ex: Jean Dupont">
                                        @if($errors->has('account_name'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('account_name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="account_number">Numéro de compte</label>
                                        <input type="text"
                                               class="form-control {{ $errors->has('account_number') ? ' is-invalid' : '' }}"
                                               id="account_number" name="account_number" placeholder="Ex: 1234567890">
                                        @if($errors->has('account_number'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('account_number') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="bank_name">Nom de la banque</label>
                                        <input type="text" value="{{old('bank_name')}}"
                                               class="form-control {{ $errors->has('bank_name') ? ' is-invalid' : '' }}"
                                               id="bank_name" name="bank_name" placeholder="Ex: BGFI Bank">
                                        @if($errors->has('bank_name'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('bank_name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="branch_name">Numéro d'agence</label>
                                        <input type="text"
                                               class="form-control {{ $errors->has('branch_name') ? ' is-invalid' : '' }}"
                                               id="branch_name" name="branch_name" placeholder="Ex: 001">
                                        @if($errors->has('branch_name'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('branch_name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="branch_address">Adresse de l'agence</label>
                                        <input type="text" value="{{old('branch_address')}}"
                                               class="form-control {{ $errors->has('branch_address') ? ' is-invalid' : '' }}"
                                               id="branch_address" name="branch_address" placeholder="Ex: Centre-Ville, Brazzaville">
                                        @if($errors->has('branch_address'))
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('branch_address') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <a   style="float:right; margin:0 5px;" href="{{ route('driver.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times mr-1"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="float:right;">
                                        <i class="fas fa-save mr-1"></i>Ajouter
                                </button>
                            </div>
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->
    </section>
    <script>
        function logo1(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#logo')
                        .attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        function licen(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#licence')
                        .attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
