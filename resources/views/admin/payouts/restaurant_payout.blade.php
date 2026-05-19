@extends('layouts.admin-modern')
@section('title', 'Reversements restaurants | Finance')
@section('page_title', 'Reversements restaurants')
@section('nav_active', 'payouts-restaurants')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
@endsection

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
                    <h1 class="m-0 text-dark"><i class="fas fa-building mr-2"></i>Reversements restaurants</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/admin')}}">Accueil</a></li>
                        <li class="breadcrumb-item active">Reversements restaurants</li>
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
                    <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true"><b>Demandes de reversement</b></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-one-profile-tab" data-toggle="pill" href="#custom-tabs-one-profile" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false"><b>Historique des reversements</b></a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                    <div class="card shadow">
                        
                        <!-- /.card-header -->
                        <div class="card-body table-responsive shadow p-5">
                            <div class="alert alert-info border-0 mb-4" style="border-radius:16px; background:#eff6ff; color:#1e3a8a;">
                                Cet écran pilote les <strong>reversements restaurants</strong>. Il est distinct du cockpit des paiements entrants clients. Trois cas existent ici : <strong>lancer l’API MTN</strong>, <strong>suivre une référence MTN déjà lancée</strong>, ou <strong>marquer un reversement manuel</strong>.
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                                <div class="pr-3">
                                    <h5 class="mb-1"><i class="fas fa-file-csv text-success mr-2"></i>Fallback bulk MTN</h5>
                                    <p class="text-muted mb-2">
                                        Si le <code>transfer</code> temps reel MTN reste refuse, exportez les demandes <code>pending</code> en CSV minimal pour le portail bulk payment.
                                    </p>
                                    <small class="text-muted">Colonnes exportees: <code>Payee Name</code>, <code>MSISDN</code>, <code>Amount (FCFA)</code>.</small>
                                </div>
                                <a href="{{ route('restaurant_payout.export_csv') }}"
                                   class="btn btn-success btn-sm {{ $requests->isEmpty() ? 'disabled' : '' }}"
                                   @if($requests->isEmpty()) aria-disabled="true" @endif>
                                    <i class="fas fa-file-export mr-1"></i>Export CSV bulk MTN
                                </a>
                            </div>
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Lic#</th>
                                    <th>Restaurant</th>
                                    <th>Phone Number</th>
                                    
                                    <th>Montant à reverser</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($requests as $index=> $request)
                                @php($hasAutoReference = \Illuminate\Support\Str::isUuid($request->transaction_id))
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($request->date)->diffForhumans() }}</td>
                                    <td>{{++$index}}</td>
                                    <td>{{$request->name}}</td>
                                    <td>{{$request->phone}}</td>
                                    
                                    <td>{{number_format($request->payout_amount, 0, ',', ' ')}} FCFA</td>
                                    <td>
                                        @if($hasAutoReference)
                                            <span class="badge badge-info">Décaissement API en cours</span>
                                            <div class="small text-muted mt-1">Réf. MTN: {{$request->transaction_id}}</div>
                                        @else
                                            <span class="badge badge-warning">Prêt à lancer</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-success btn-sm" title="Traiter le reversement" data-toggle="modal" data-target="#modal-sm{{$request->request_id}}">
                                            <i class="fas {{ $hasAutoReference ? 'fa-sync-alt' : 'fa-paper-plane' }} mr-1"></i>{{ $hasAutoReference ? 'Vérifier le statut' : 'Lancer le reversement' }}
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
              <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Reversement restaurant</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
            
                <div class="card-body">
                  <div class="form-group">
                    <label for="transaction_id">Référence manuelle <span class="text-muted">(optionnel)</span></label>
                    <input type="text" class="form-control" name="transaction_id" placeholder="Ex: TXN123456789">
                    <small class="form-text text-muted">
                        Laissez vide pour {{ $hasAutoReference ? 'vérifier le décaissement MTN API déjà lancé' : 'lancer un reversement MTN MoMo automatique' }}.
                    </small>
                    @if($hasAutoReference)
                        <small class="form-text text-muted">Référence MTN en cours: {{$request->transaction_id}}</small>
                    @else
                        <small class="form-text text-muted">Si l’API reste refusée, utilisez plutôt l’export CSV bulk MTN ci-dessus.</small>
                    @endif
                  </div>
                </div>
              
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                <i class="fas fa-times mr-1"></i>Fermer
              </button>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-check mr-1"></i>Confirmer le traitement
              </button>
            </div>
          </div>
          </form>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Aucune demande de paiement en attente.</td>
                                </tr>
                                @endforelse
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
                                    <th>Montant reversé</th>
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
                                    <td><span class="badge badge-success">Reversement confirmé</span></td>
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
              <h4 class="modal-title"><i class="fas fa-receipt mr-2"></i>Détail du reversement</h4>
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
                  <b>Référence #{{$request->transaction_id}}</b><br>
                  <br>
                  <b>Date de reversement:</b> {{$request->date}}<br>
                  <b>Montant reversé:</b> {{number_format($request->payout_amount, 0, ',', ' ')}} FCFA
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
<script src="{{asset('plugins/datatables/jquery.dataTables.js')}}"></script>
<script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.js')}}"></script>
<script>
    $(function () {
      $("#example1").DataTable();
      $("#example2").DataTable();
    });
  </script>
@endsection
