@extends('layouts.restaurant_app')
@section('title','Commandes assignées')
@section('topbar_title', 'Commandes assignées')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<link rel="stylesheet" href="{{asset('plugins/sweetalert2/sweetalert2.css')}}">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css">
@endsection
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')
@section('order_nav_pending', 'active')

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
                    <h1 class="m-0 text-dark">Commandes assignées</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Commandes assignées</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
            <!-- Small boxes (Stat box) -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Filtre</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="get">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                            <input type="text" class="form-control float-right" name="date" id="reservationtime">
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <button class="btn btn-danger" type="submit">Filtrer</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                               <div class="card-header">
                           <div class="row">
                               <div class="col-sm-6"><h6 class="m-0"><b>Total :</b> {{ number_format((float) $orders->sum('total'), 0, ',', ' ') }} FCFA</h6></div> 
                        </div>
                          
                      </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-2">
                            <table class="table table-head-fixed text-nowrap" id="example1">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>N° de commande.</th>
                                    <!--<th>Customer Name</th>-->
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
            <td>{{$order->order_no}}</td>
            <!--<td>{{$order->user->name}}</td>-->
            <td>{{$order->restaurant->name}}</td>
            <td>{{$order->total}}</td>
            <td>{{$order->delivery_address}}</td>
            <td>{{$order->status}}</td>
    <td><a href="{{route('restaurant.show_order',$order->order_no)}}" class="btn btn-outline-info btn-sm" title="view">Voir</a></td>
</tr>
                                  
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Total :</th>
                                    <th></th>
                                </tr>
                                </tfoot>
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
<!-- bootstrap color picker -->
<script src="{{asset('plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js')}}"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
<script>
    $(function () {
        $("#example1").DataTable({
            dom: 'Bfrtip',
            buttons: [
                'colvis',
                { extend: 'excelHtml5', footer: true },
                { extend: 'csvHtml5', footer: true },
                { extend: 'pdfHtml5', footer: true }
            ],
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };

                // Total over all pages
                total = api
                    .column( 3 )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );

                // Total over this page
                pageTotal = api
                    .column( 3, { page: 'current'} )
                    .data()
                    .reduce( function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0 );

                // Update footer
                $( api.column( 6 ).footer() ).html(
                    '$'+pageTotal.toFixed(2) +' ( $'+ total.toFixed(2) +' total)'
                );
            }
        } );
        //Date range picker
        $('#reservation').daterangepicker()
        //Date range picker with time picker
        $('#reservationtime').daterangepicker({
            timePicker: true,
            timePickerIncrement: 30,
            locale: {
                format: 'MM/DD/YYYY hh:mm A'
            }
        })

    });
</script>

<script>
(function () {
    var POLL_URL   = "{{ route('restaurant.notifications.poll') }}";
    var POLL_MS    = 15000;
    var notifBadge = null;
    var lastCount  = -1;

    var _pendingAudioCtx = null;
    var _pendingAudioUnlocked = false;
    (function () {
        function unlock() { _pendingAudioUnlocked = true; }
        document.addEventListener('click',    unlock, { once: true });
        document.addEventListener('keydown',  unlock, { once: true });
        document.addEventListener('touchstart', unlock, { once: true, passive: true });
    })();

    function playNotifSound() {
        if (!_pendingAudioUnlocked) return;
        try {
            var C = window.AudioContext || window.webkitAudioContext;
            if (!C) return;
            if (!_pendingAudioCtx || _pendingAudioCtx.state === 'closed') _pendingAudioCtx = new C();
            if (_pendingAudioCtx.state === 'suspended') _pendingAudioCtx.resume();
            var ctx = _pendingAudioCtx;
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880;
            var t = ctx.currentTime;
            gain.gain.setValueAtTime(0.001, t);
            gain.gain.exponentialRampToValueAtTime(0.22, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.35);
            osc.start(t); osc.stop(t + 0.36);
        } catch (e) {}
    }

    function updateBadge(count) {
        if (!notifBadge) {
            notifBadge = document.createElement('span');
            notifBadge.id = 'pendingOrdersBadge';
            notifBadge.style.cssText = 'display:inline-block;background:#dc3545;color:#fff;border-radius:50%;padding:2px 7px;font-size:12px;margin-left:6px;font-weight:700;';
            var h1 = document.querySelector('h1.m-0.text-dark');
            if (h1) h1.appendChild(notifBadge);
        }
        notifBadge.textContent = count;
        notifBadge.style.display = count > 0 ? 'inline-block' : 'none';
    }

    function poll() {
        fetch(POLL_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store',
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data || !data.status) return;
            var count = parseInt(data.count) || 0;
            updateBadge(count);
            if (data.new && lastCount !== -1 && count > lastCount) {
                playNotifSound();
                if (typeof toastr !== 'undefined') toastr.warning('🔔 Nouvelle commande reçue !');
                setTimeout(function () { window.location.reload(); }, 1200);
            }
            lastCount = count;
        })
        .catch(function () {});
    }

    poll();
    setInterval(poll, POLL_MS);

    @if(config('broadcasting.default') !== 'log')
    if (window.Echo) {
        @php $restaurantModel = auth()->user() ? \App\Restaurant::where('user_id', auth()->id())->first() : null; @endphp
        @if($restaurantModel)
        window.Echo.private('food.restaurant.{{ $restaurantModel->id }}.orders')
            .listen('.food.order.status.updated', function (e) {
                if ((e.business_status || '') === 'pending_restaurant_acceptance') {
                    playNotifSound();
                    if (typeof toastr !== 'undefined') toastr.warning('🔔 Nouvelle commande reçue !');
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            });
        @endif
    }
    @endif
})();
</script>
@endsection
