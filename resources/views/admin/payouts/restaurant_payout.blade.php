@extends('layouts.app')
@section('title', 'Paiements restaurants | BantuDelice')
@section('style')
<link rel="stylesheet" href="{{asset(env('ASSET_URL') .'/plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset(env('ASSET_URL').'/plugins/sweetalert2/sweetalert2.css')}}">
@endsection
@section('payout_nav', 'active')
@section('payout_nav_open', 'menu-open')
@section('payout_nav_restaurant', 'active')

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
                    <h1 class="m-0 text-dark"><i class="fas fa-building mr-2"></i>Paiements restaurants</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Paiements restaurants</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
   
            <div class="row">
          <div class="col-12 col-sm-12 col-lg-12">
            <div class="card card-primary card-tabs shadow">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true"><b>Demandes de paiement</b></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-one-profile-tab" data-toggle="pill" href="#custom-tabs-one-profile" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false"><b>Historique des paiements</b></a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                    <div class="card shadow">
                        
                        <!-- /.card-header -->
                        <div class="card-body table-responsive shadow p-5">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Lic#</th>
                                    <th>Restaurant</th>
                                    <th>Phone Number</th>
                                    
                                    <th>Amount to Pay</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($requests as $index=> $request)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($request->date)->diffForhumans() }}</td>
                                    <td>{{++$index}}</td>
                                    <td>{{$request->name}}</td>
                                    <td>{{$request->phone}}</td>
                                    
                                    <td>{{number_format($request->payout_amount, 0, ',', ' ')}} FCFA</td>
                                    <td><span class="badge badge-warning">{{ucfirst($request->status)}}</span></td>
                                    <td>
                                        <button class="btn btn-success btn-sm" title="Envoyer le paiement" data-toggle="modal" data-target="#modal-sm{{$request->request_id}}">
                                            <i class="fas fa-paper-plane mr-1"></i>Payer
                                        </button>
                                    </td>
                                </tr>
                                <div class="modal fade" id="modal-sm{{$request->request_id}}">
        <div class="modal-dialog modal-sm">
        <form action="{{route('restaurant_pay')}}" method="post">
        @csrf
        <input type="hidden" name="request_id" value="{{$request->request_id}}">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Paiement</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
            
                <div class="card-body">
                  <div class="form-group">
                    <label for="transaction_id">Numéro de transaction <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="transaction_id" placeholder="Ex: TXN123456789" required>
                    <small class="form-text text-muted">Entrez le numéro de transaction bancaire</small>
                  </div>
                </div>
              
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                <i class="fas fa-times mr-1"></i>Fermer
              </button>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-check mr-1"></i>Confirmer le paiement
              </button>
            </div>
          </div>
          </form>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div> 
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-one-profile" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab">
                      <div class="card shadow">
                        
                        <!-- /.card-header -->
                        <div class="card-body table-responsive shadow p-5">
                            <table class="table table-head-fixed text-nowrap" id="example2">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>N°</th>
                                    <th>Restaurant</th>
                                    <th>Téléphone</th>
                                    <th>Montant payé</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($history as $index=> $request)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($request->date)->diffForhumans() }}</td>
                                    <td>{{++$index}}</td>
                                    <td>{{$request->name}}</td>
                                    <td>{{$request->phone}}</td>
                                    
                                    <td>{{number_format($request->payout_amount, 0, ',', ' ')}} FCFA</td>
                                    <td><span class="badge badge-success">{{ucfirst($request->status)}}</span></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" title="Voir les détails" data-toggle="modal" data-target="#modal-lg{{$request->request_id}}">
                                            <i class="fas fa-eye mr-1"></i>Détails
                                        </button>
                                    </td>
                                </tr>
                                <div class="modal fade" id="modal-lg{{$request->request_id}}">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><i class="fas fa-receipt mr-2"></i>Reçu de paiement</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                 <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                  <strong>De</strong>
                  <address>
                    <strong>{{auth()->user()->name}}</strong><br>
                    {{auth()->user()->address ?? 'N/A'}}<br>
                    Téléphone: {{auth()->user()->phone ?? 'N/A'}}<br>
                    Email: {{auth()->user()->email}}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <strong>À</strong>
                  <address>
                    <strong>{{$request->name}}</strong><br>
                    {{$request->address ?? 'N/A'}}<br>
                    Téléphone: {{$request->phone}}<br>
                    Email: {{$request->email ?? 'N/A'}}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Facture #{{$request->transaction_id}}</b><br>
                  <br>
                  <b>Date de paiement:</b> {{$request->date}}<br>
                  <b>Montant payé:</b> {{number_format($request->payout_amount, 0, ',', ' ')}} FCFA
                </div>
                <!-- /.col -->
              </div>
            </div>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.card -->
            </div>
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
      $("#example2").DataTable();
    });
  </script>
@endsection