@extends('layouts.admin-modern')
@section('title', 'Cuisine')
@section('page_title', 'Modifier cuisine')
@section('nav_active', 'cuisine')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Modifier une cuisine</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                        <li class="breadcrumb-item "><a href="{{route('cuisine.index')}}">Cuisines</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
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
                            <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Mettre à jour</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form role="form" method="post" action="{{ route('cuisine.update',$cuisine->id) }}"
                         enctype="multipart/form-data">
                            @csrf
                            @method('put')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Nom de la cuisine</label>
                                    <input type="text"
                                           class="form-control {{ $errors->has('name') ? ' is-invalid' : ''}}"
                                           name="name" id="name" value="{{ old('name', $cuisine->name) }}"/>
                                    @if($errors->has('name'))
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Aperçu actuel</label>
                                    <div>
                                        <img src="{{ !empty($cuisine->image) ? asset('images/cuisine/' . $cuisine->image) : asset('images/placeholder.png') }}"
                                             alt="{{ $cuisine->name }}"
                                             style="width:90px;height:90px;object-fit:cover;border-radius:12px;border:1px solid #dee2e6;padding:4px;background:#fff;"
                                             onerror="this.src='{{ asset('images/placeholder.png') }}'">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="image">Remplacer l'image</label>
                                    <input type="file" name="image"
                                           class="form-control{{ $errors->has('image') ? ' is-invalid' : ''}}" id="image"
                                           accept=".jpg,.jpeg,.png,.webp">
                                    <small class="form-text text-muted">Laissez vide pour conserver l'image actuelle.</small>
                                    @if($errors->has('image'))
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $errors->first('image') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <a   style="float:right; margin:0 5px;" href="{{ route('cuisine.index') }}" class="btn btn-secondary btn-sm">Annuler</a>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="float:right;"><i class="fas fa-save mr-1"></i>Mettre à jour
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
