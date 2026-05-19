@extends('layouts.restaurant_app')
@section('title','Suppléments')
@section('topbar_title', 'Suppléments')
@section('add_on_nav', 'active')
@section('style')
<link rel="stylesheet" href="{{asset('assets/plugins/toastr/toastr.min.css')}}">
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
                    <h1 class="m-0 text-dark">Tous les suppléments</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Suppléments</li>
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
                        <div class="card-body">
                            <label>Ajouter un supplément</label>
                            <form class="form-inline justify-content-center" role="form" method="post" action="{{ isset($addonstitle) ? route('add-on.update',$addonstitle->id) : route('add-on.store') }}"  enctype="multipart/form-data">
                                @csrf
                                @if (isset($addonstitle))
                                    @method('PUT')
                                @endif
                                <div class="form-group">
                                    <input required name="title" id="title" type="text" placeholder="Nom du supplément"  value="{{ isset($addonstitle) ? $addonstitle->title: '' }}" class="rounded-0 form-control {{ $errors->has('title') ? ' is-invalid' : ''}}" />
                                </div>
                                <div class="form-group">
                                    <select required name="product_id" id="Product_id" class="rounded-0 form-control {{ $errors->has('product_id') ? ' is-invalid' : ''}}" >
                                        <option  selected>Choisir un produit</option>
                                        @foreach($prod as $product)
                                        <option value="{{$product->id}}" >{{$product->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="rounded-0 btn btn-outline-danger">Ajouter</button>
                                    
                                </div>
                                @if($errors->has('name'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                    @endif
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tous les suppléments</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive ">
                            <table  id="example1" class="table table-head-fixed text-nowrap">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Produit</th>
                                    <th>Créé le</th>
                                    <th>Action</th>

                                </tr>
                                </thead>
                                <tbody>
                                     @foreach($addon as $index=> $addons)
                                    <tr>
                                        <td>{{++$index}}</td>
                                        <td>{{$addons->title}}</td>
                                        <td>{{$addons->product_id}}</td>
                                        <td>{{$addons->created_at}}</td>
                                        <td>
                                            <a href="{{ route('add-on.edit', $addons->id) }}"
                                               class="btn btn-outline-info"><i class="fa fa-pen"></i></a>
                                            <form action="{{ route('add-on.destroy', $addons->id) }}"
                                                  method="post"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Voulez-vous vraiment supprimer ce supplément ?');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="fa fa-trash-alt"></i>
                                                </button>
                                            </form>
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
<script src="{{asset('assets/plugins/sweetalert2/sweetalert2.min.js')}}"></script>
<script src="{{asset('assets/plugins/toastr/toastr.min.js')}}"></script>
 
<script>
    $(function () {
      $("#example1").DataTable();
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
