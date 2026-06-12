@extends('layouts.admin-modern')
@section('title', 'Créer une promotion')
@section('page_title', 'Nouvelle promotion')
@section('nav_active', 'promotions')

@section('content')
<div style="padding:24px;">
    <form method="POST" action="{{ route('admin.promotions.store') }}">
        @csrf
        @include('admin.vouchers._form', ['voucher' => $voucher, 'restaurants' => $restaurants])
    </form>
</div>
@endsection
