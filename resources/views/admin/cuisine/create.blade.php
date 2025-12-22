@extends('layouts.app')
@section('cuisine_nav', 'active')
@section('cuisine_nav_open', 'menu-open')
@section('cuisine_nav_add', 'active')

@section('title', 'Ajouter une cuisine | BantuDelice')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-utensils mr-2"></i>Ajouter une cuisine</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item "><a href="{{route('cuisine.index')}}">Cuisines</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card card-primary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Ajouter une cuisine</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form role="form" method="post" action="{{ route('cuisine.store') }}"  enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Nom de la cuisine <span class="text-danger">*</span></label>
                                    <input type="text" value="{{old('name')}}" name="name"
                                           class="form-control{{ $errors->has('name') ? ' is-invalid' : ''}}" id="name"
                                           placeholder="Ex: Cuisine africaine" required>
                                    @if($errors->has('name'))
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="image">Image <span class="text-danger">*</span></label>
                                    <input type="file" value="{{old('image')}}" name="image"
                                           class="form-control{{ $errors->has('image') ? ' is-invalid' : ''}}" id="image"
                                           accept="image/*" required>
                                    <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF. Taille recommandée: 300x300px</small>
                                    @if($errors->has('image'))
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('image') }}</strong>
                                </span>
                                    @endif
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <a   style="float:right; margin:0 5px;" href="{{ route('cuisine.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times mr-1"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="float:right;">
                                        <i class="fas fa-save mr-1"></i>Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
{{-->>>>>>> ff096d4b12bff8b424f347de443c0ea84fcf26cd--}}
