@extends('layouts.admin-modern')
@section('title','Commandes en preparation')
@section('page_title', 'Commandes en préparation')
@section('nav_active', 'orders')

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
                    <h1 class="m-0 text-dark">Commandes en preparation</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Commandes</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-2">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Restaurant</th>
                                    <th>Montant</th>
                                    <th>Adresse</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $index => $order)
                                    <tr>
                                        <td>{{++$index}}</td>
                                        <td>{{$order->user->name}}</td>
                                        <td>{{$order->restaurant->name}}</td>
                                        <td>{{$order->total}}</td>
                                        <td>{{$order->delivery_address}}</td>
                                        <td>{{$order->status}}</td>
                                        <td>
                                            <a href="{{route('admin.show_order',$order->order_no)}}" class="btn btn-outline-warning"title="view"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
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
      $("#example1").DataTable({
          language: window.bdAdminDataTableLanguage
      });
  
    });
  </script>
@endsection
