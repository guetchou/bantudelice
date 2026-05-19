@extends('layouts.admin-modern')
@section('title', 'Créer une promotion')
@section('page_title', 'Nouvelle promotion')
@section('nav_active', 'promotions')
@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Créer une promotion</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="POST" action="{{ route('admin.promotions.store') }}">
            @csrf
            @include('admin.vouchers._form', ['voucher' => $voucher, 'restaurants' => $restaurants])
        </form>
    </div>
</section>
@endsection
