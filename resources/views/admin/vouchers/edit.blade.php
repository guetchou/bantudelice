@extends('layouts.admin-modern')
@section('title', 'Modifier une promotion')
@section('page_title', 'Modifier promotion')
@section('nav_active', 'promotions')

@section('content')
<div style="padding:24px;">
    <form method="POST" action="{{ route('admin.promotions.update', $voucher) }}">
        @csrf
        @method('PUT')
        @include('admin.vouchers._form', ['voucher' => $voucher, 'restaurants' => $restaurants])
    </form>
</div>
@endsection
