@extends('layouts.admin-modern')
@section('page_title', 'Historique livreurs')
@section('nav_active', 'payouts-drivers')

@section('style')
<style>
.pay-page { padding:24px; }
.pay-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.pay-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.pay-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.pay-card__body { padding:20px; }
.pay-table-wrap { overflow-x:auto; }
.pay-table { width:100%; border-collapse:collapse; font-size:13px; }
.pay-table thead th { padding:9px 14px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; white-space:nowrap; }
.pay-table tbody td { padding:10px 14px; color:#374151; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.pay-table tbody tr:last-child td { border-bottom:none; }
.pay-btn-action { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:#16a34a; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
</style>
@endsection

@section('content')
<div class="pay-page">
    <div class="pay-card">
        <div class="pay-card__header">
            <h3 class="pay-card__title">Historique transactions livreurs</h3>
        </div>
        <div class="pay-card__body">
            <div class="pay-table-wrap">
                <table class="pay-table" id="dt-dthistory">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Restaurant</th>
                            <th>Total Amount</th>
                            <th>Transaction ID</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Warner</td>
                            <td>$45</td>
                            <td>0001234</td>
                            <td><button class="pay-btn-action">Make Payment</button></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Harry</td>
                            <td>$47</td>
                            <td>0001235</td>
                            <td><button class="pay-btn-action">Make Payment</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('plugins/datatables/jquery.dataTables.js') }}"></script>
<script>
$(function () { $('#dt-dthistory').DataTable({ language: window.bdAdminDataTableLanguage }); });
</script>
@endsection
