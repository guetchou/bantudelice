@extends('layouts.app')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('charge_nav', 'active')

@section('title', 'Gestion des charges | BantuDelice')

@section('content')
<div class="content-header">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-dollar-sign mr-2"></i>Gestion des charges</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                    @if(!$charge)
                      <li class="breadcrumb-item " ><a href="{{route('charge.index')}}">Charges</a></li>
                      <li class="breadcrumb-item active">Ajouter</li>
                    @else
                      <li class="breadcrumb-item active">Charges</li>
                    @endif
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<section class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-11">
        @if(!$charge)
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="card shadow-sm">
              <div class="card-header text-center">
                <h4><i class="fas fa-plus-circle mr-2"></i><b>Ajouter des charges</b></h4>
              </div>
              <div class="card-body">
                <form role="form" method="post" action="{{route('charge.store')}}">
                  @csrf
                  <div class="form-group">
                    <label for="service_fee">Frais de service (%) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="service_fee" id="service_fee" placeholder="Ex: 10" required />
                    <small class="form-text text-muted">Pourcentage des frais de service</small>
                  </div>
                  <div class="form-group">
                    <label for="tax">Taxe (%) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="tax" id="tax" placeholder="Ex: 5" required />
                    <small class="form-text text-muted">Pourcentage de taxe</small>
                  </div>
                  <div class="form-group">
                    <label for="delivery_fee">Frais de livraison (FCFA) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="delivery_fee" id="delivery_fee" placeholder="Ex: 2000" required />
                    <small class="form-text text-muted">Montant fixe pour la livraison</small>
                  </div>
                  <div class="form-group">
                    <label for="pickup_fee">Frais de retrait (FCFA)</label>
                    <input type="text" class="form-control" name="pickup_fee" id="pickup_fee" placeholder="Ex: 500" />
                    <small class="form-text text-muted">Montant fixe pour le retrait sur place</small>
                  </div>
                  <div class="form-group">
                    <button type="reset" class="btn bg-gradient-secondary">
                      <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn bg-gradient-primary">
                      <i class="fas fa-save mr-1"></i>Enregistrer
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="col-md-5 align-self-center ">
            <div class="card py-5">
              <div class="card-body my-4">
                <img class="w-100" src="{{asset(env('ASSET_URL') .'images/banner-in-gif.gif')}}" />
              </div>
            </div>
          </div>
        </div>
        @else
        <div class="row">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header text-center">
                <h3><i class="fas fa-cog mr-2"></i><b>Gestion des charges</b></h3>
              </div>
              <div class="card-body table-responsive">
                <table id="example1" class="table table-head-fixed text-nowrap">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Frais de service (%)</th>
                      <th>Taxe (%)</th>
                      <th>Frais de livraison (FCFA)</th>
                      <th>Frais de retrait (FCFA)</th>
                      <th>Modifié le</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <form role="form" method="post" action="{{route('charge.update',$charge)}}"> 
                        @csrf
                        @method('PUT')
                        <td>1</td>
                        <td><input class="form-control form-control-sm" type="text" name="service_fee" value="{{$charge->service_fee}}" required /></td>
                        <td><input class="form-control form-control-sm" type="text" name="tax" value="{{$charge->tax}}" required /></td>
                        <td><input class="form-control form-control-sm" type="text" name="delivery_fee" value="{{$charge->delivery_fee}}" required /></td>
                        <td><input class="form-control form-control-sm" type="text" name="pickup_fee" value="{{$charge->pickup_fee}}"/></td>
                        <td>{{$charge->updated_at->format('d/m/Y H:i')}}</td>
                        <td>
                          <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fas fa-save mr-1"></i>Mettre à jour
                          </button>
                      </form>
                        </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
@section('script')
<script src="{{asset(env('ASSET_URL') .'plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset(env('ASSET_URL') .'plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
  
    });
  </script>
@endsection
