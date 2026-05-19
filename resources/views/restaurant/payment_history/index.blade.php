@extends('layouts.restaurant_app')
@section('title','Historique des reversements')
@section('topbar_title', 'Historique des reversements')
@section('earnings_nav', 'active')
@section('style')
@include('partials.partner_dashboard_style')
@endsection
@section('content')
    <section class="content">
        <div class="container-fluid">
            @php
                $partnerDashboardHeader = [
                    'eyebrow' => 'Reversements',
                    'title' => 'Historique des reversements restaurant',
                    'description' => 'Consultez vos reversements exécutés et demandez un retrait sans mélanger chiffre d’affaires, net partenaire et montants réellement payés.',
                    'stats' => [
                        ['label' => 'Déjà payé', 'value' => number_format(round($withdrwan), 0, ',', ' ') . ' FCFA'],
                        ['label' => 'Aujourd’hui', 'value' => number_format(round($today_earning), 0, ',', ' ') . ' FCFA'],
                        ['label' => 'Cette semaine', 'value' => number_format(round($this_week_earning), 0, ',', ' ') . ' FCFA'],
                    ],
                ];
                $partnerMetricCards = [
                    [
                        'label' => 'CA brut cumulé',
                        'value' => number_format(round($Total_Earning), 0, ',', ' ') . ' FCFA',
                        'hint' => 'Somme brute des commandes terminées côté restaurant.',
                    ],
                    [
                        'label' => 'Taux commission historique',
                        'value' => number_format($pre * 100, 0, ',', ' ') . ' %',
                        'hint' => 'Référence historique utilisée dans cet écran legacy de retrait.',
                    ],
                    [
                        'label' => 'Net théorique',
                        'value' => number_format(round($total), 0, ',', ' ') . ' FCFA',
                        'hint' => 'Estimation historique nette avant prise en compte du ledger de reversement.',
                    ],
                ];
            @endphp
            <div class="bd-partner-page">
            @if(session()->has('alert'))
                <div class="alert alert-{{ session()->get('alert.type') }}">
                    {{ session()->get('alert.message') }}
                </div>
            @endif
            @include('partials.partner_dashboard_header')
            @include('partials.partner_metric_cards')
            @include('partials.partner_finance_cards')
            <div class="row justify-content-center">
                <div class="col-11">
                    <div class="card card-primary card-outline card-outline-tabs">
                        <div class="card-header card-primary p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                              <li class="nav-item">
                                <a class="nav-link active" id="custom-tabs-three-p-hist-tab" data-toggle="pill" href="#custom-tabs-three-p-hist" role="tab" aria-controls="custom-tabs-three-p-hist" aria-selected="true">Historique des reversements</a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link" id="custom-tabs-three-p-send-tab" data-toggle="pill" href="#custom-tabs-three-p-send" role="tab" aria-controls="custom-tabs-three-p-send" aria-selected="false">Demander un reversement</a>
                              </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-three-tabContent">
                                <div class="tab-pane fade show active" id="custom-tabs-three-p-hist" role="tabpanel" aria-labelledby="custom-tabs-three-p-hist-tab">
                                  <div class="card shadow">
                                    <div class="card-header">
                                      <h3></h3>
                                    </div>
                                    <div class="card-body table-responsive">
                                      <table id="example1" class="table table-head-fixed text-nowrap">
                                        <thead>
                                          <tr>
                                            <th>#</th>
                                            <th>Référence reversement</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          @foreach($history as $index => $his)
                                          <tr>
                                            <td>{{++$index}}</td>
                                            <td>{{$his->transaction_id}}</td>
                                            <td>{{ number_format(round($his->payout_amount), 0, ',', ' ') }} FCFA</td>
                                            <td>{{$his->created_at}}</td>
                                            <td>{{$his->status}}</td>
                                            <td>
                                              <button type="button" title="Détail indisponible" class="btn btn-outline-warning" disabled><i class="fa fa-eye"></i></button>
                                            </td>
                                          </tr>
                                          @endforeach
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                                <div class="tab-pane fade show " id="custom-tabs-three-p-send" role="tabpanel" aria-labelledby="custom-tabs-three-p-send-tab">
                                  <div class="card shadow">
                                    <div class="card-header">
                                      <h3></h3>
                                    </div>
                                    <div class="card-body table-responsive">
                                    <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Demande de reversement</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="post" action="{{route('r_earnings.store')}}">
              @csrf
              <input type="hidden" name="restaurant_id" value="{{auth()->user()->restaurant()->first()->id}}">
              <div class="card-body">
                  @if($withdrwan > 50)
                <div class="row">
                <div class="col-3">
                </div>
                  <div class="col-3">
                    <input type="number" class="form-control" name="amount" placeholder="Montant demandé" min="50" max="500">
                  </div>
                  <div class="col-3">
                  <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                  </div>
                  <div class="col-3">
                </div>
                </div>
                @else
                <div class="row">
                <div class="col-12">
                    <p class="text-center">Le montant déjà payé reste insuffisant pour ce formulaire legacy de retrait.</p>
                </div>
                </div>
                @endif
              </div>
              </form>
            </div>
            <!-- /.card -->
                                    </div>
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
<script>
    $(function () {
      $("#example1").DataTable();
      $("#example11").DataTable();
      $("#example12").DataTable();
      $('#example2').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
      });
    });
  </script>
@endsection
