@extends('layouts.admin-modern')
@section('title', 'Ajouter un plat')
@section('page_title', 'Nouveau produit')
@section('nav_active', 'products')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Ajouter un plat</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/admin/all-products') }}">Plats</a></li>
                        <li class="breadcrumb-item active">Ajouter</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="post" action="{{ url('/admin/product') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.product._form')
            </form>
        </div>
    </section>
@endsection
