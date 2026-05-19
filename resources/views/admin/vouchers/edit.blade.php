@extends('layouts.admin-modern')
@section('title', 'Modifier une promotion')
@section('page_title', 'Modifier promotion')
@section('nav_active', 'promotions')
@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Modifier la promotion {{ $voucher->name }}</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="POST" action="{{ route('admin.promotions.update', $voucher) }}">
            @csrf
            @method('PUT')
            @include('admin.vouchers._form', ['voucher' => $voucher, 'restaurants' => $restaurants])
        </form>
    </div>
</section>
@endsection
