@extends('layouts.admin-modern')
@section('title', 'Ajouter un plat')
@section('page_title', 'Nouveau produit')
@section('nav_active', 'products')

@section('content')
<div style="padding:24px;">
    <form method="post" action="{{ url('/admin/product') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.product._form')
    </form>
</div>
@endsection
