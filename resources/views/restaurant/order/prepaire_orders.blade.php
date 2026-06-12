@extends('layouts.restaurant_app')
@section('title', 'Préparation des commandes | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Préparation des commandes')
@section('order_nav', 'active')
@section('order_nav_open', 'menu-open')
@section('order_nav_preparing', 'active')

@section('style')
<style>
.prep { display: flex; flex-direction: column; gap: 20px; }

.prep-filter {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.prep-filter__head { padding: 12px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 12px; font-weight: 700; color: var(--bd-text); }
.prep-filter__body { padding: 14px 18px; display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.prep-input {
    flex: 1; min-width: 200px; padding: 9px 12px;
    border: 1px solid var(--bd-border); border-radius: var(--bd-radius);
    font-size: 13px; font-family: var(--bd-font);
    background: var(--bd-surface); color: var(--bd-text); outline: none;
    transition: border-color .12s;
}
.prep-input:focus { border-color: var(--bd-green); }
.prep-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 700; border: none; cursor: pointer;
    font-family: var(--bd-font); transition: .12s;
}
.prep-btn--green { background: var(--bd-green); color: #fff; }
.prep-btn--green:hover { background: var(--bd-green-dark, #007836); }

.prep-card { background: var(--bd-surface); border: 1px solid var(--bd-border); border-radius: var(--bd-radius); overflow: hidden; }
.prep-card__head {
    padding: 12px 18px; border-bottom: 1px solid var(--bd-border-2);
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.prep-card__total { font-size: 13px; font-weight: 700; color: var(--bd-text); }
.prep-card__total span { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 15px; font-weight: 800; }

.prep-btn--outline {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 700; cursor: pointer;
    background: var(--bd-surface); color: var(--bd-green);
    border: 1px solid var(--bd-green); font-family: var(--bd-font);
    transition: .12s; text-decoration: none;
}
.prep-btn--outline:hover { background: var(--bd-green); color: #fff; }

.prep-table-wrap { overflow-x: auto; }
.prep-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.prep-table thead th {
    padding: 8px 14px; font-size: 10px; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: var(--bd-text-3); border-bottom: 1px solid var(--bd-border-2);
    background: var(--bd-surface-2); text-align: left; white-space: nowrap;
}
.prep-table tbody tr { border-bottom: 1px solid var(--bd-border-2); transition: background .1s; }
.prep-table tbody tr:last-child { border-bottom: none; }
.prep-table tbody tr:hover { background: var(--bd-surface-2); }
.prep-table td { padding: 10px 14px; color: var(--bd-text-2); vertical-align: middle; }
.prep-check { width: 16px; height: 16px; cursor: pointer; accent-color: var(--bd-green); }
.prep-amount { font-family: var(--bd-font-display,'League Spartan',sans-serif); font-size: 13px; font-weight: 800; color: var(--bd-text); white-space: nowrap; }
.prep-amount-cur { font-size: 10px; font-weight: 600; color: var(--bd-text-3); font-family: var(--bd-font); }
.prep-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; background: var(--bd-surface-2); color: var(--bd-text-3); }
.prep-view-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid var(--bd-border); background: var(--bd-surface);
    color: var(--bd-text-2); cursor: pointer; font-size: 11px;
    transition: .12s; text-decoration: none;
}
.prep-view-btn:hover { border-color: var(--bd-green); color: var(--bd-green); }
.prep-empty { padding: 32px 20px; text-align: center; color: var(--bd-text-3); font-size: 13px; }
.prep-empty i { font-size: 24px; display: block; margin-bottom: 8px; color: var(--bd-border); }
</style>
@endsection

@section('content')
<div class="prep">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Filtre date ──────────────────────────────────── --}}
    <div class="prep-filter">
        <div class="prep-filter__head"><i class="fas fa-filter" style="margin-right:6px;color:var(--bd-text-3);"></i> Filtrer par période</div>
        <form action="" method="get" class="prep-filter__body">
            <input type="text" name="date" id="reservationtime" class="prep-input" placeholder="Plage de dates…">
            <button type="submit" class="prep-btn prep-btn--green">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </form>
    </div>

    {{-- ── Tableau des commandes ─────────────────────────── --}}
    <div class="prep-card">
        <form action="{{ route('restaurant.assign_driver') }}" method="post">
            @csrf
            <div class="prep-card__head">
                <div class="prep-card__total">
                    Total : <span>{{ number_format((float) $orders->sum('total'), 0, ',', ' ') }}</span>
                    <span style="font-size:11px;font-weight:600;color:var(--bd-text-3);font-family:var(--bd-font);"> FCFA</span>
                </div>
                <button type="submit" class="prep-btn--outline">
                    <i class="fas fa-motorcycle"></i> Assigner les commandes
                </button>
            </div>

            @if($orders->isEmpty())
                <div class="prep-empty">
                    <i class="fas fa-clipboard-list"></i>
                    <p>Aucune commande en préparation.</p>
                </div>
            @else
                <div class="prep-table-wrap">
                    <table class="prep-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>N° commande</th>
                                <th>Restaurant</th>
                                <th>Montant</th>
                                <th>Adresse</th>
                                <th>Statut</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td><input type="checkbox" class="prep-check" name="id[]" value="{{ $order->order_no }}"></td>
                                <td style="font-family:monospace;font-size:12px;">{{ $order->order_no }}</td>
                                <td style="font-weight:600;color:var(--bd-text);">{{ $order->restaurant->name ?? '—' }}</td>
                                <td>
                                    <span class="prep-amount">{{ number_format((float)($order->total ?? 0), 0, ',', ' ') }}<span class="prep-amount-cur"> FCFA</span></span>
                                </td>
                                <td>{{ $order->delivery_address ?? '—' }}</td>
                                <td><span class="prep-badge">{{ $order->status ?? '—' }}</span></td>
                                <td style="text-align:right;">
                                    <a href="{{ route('restaurant.show_order', $order->order_no) }}" class="prep-view-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </form>
    </div>

</div>
@endsection

@section('script')
<script>
    if (typeof $.fn.daterangepicker !== 'undefined') {
        $('#reservationtime').daterangepicker({
            timePicker: true,
            timePickerIncrement: 30,
            locale: {
                format: 'DD/MM/YYYY HH:mm',
                separator: ' - ',
                applyLabel: 'Appliquer',
                cancelLabel: 'Annuler',
                fromLabel: 'Du',
                toLabel: 'Au',
                weekLabel: 'S',
                daysOfWeek: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
                monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
            }
        });
    }
</script>
@endsection
