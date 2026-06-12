@extends('layouts.admin-modern')
@section('page_title', 'Mon profil')
@section('nav_active', 'profile')
@section('style')
<style>
.pfx-page { padding:24px; display:grid; gap:20px; }
.pfx-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
.pfx-stat { border-radius:18px; padding:18px 20px; display:grid; gap:6px; text-decoration:none; }
.pfx-stat--info { background:#0ea5e9; }
.pfx-stat--success { background:#22c55e; }
.pfx-stat--warning { background:#f59e0b; }
.pfx-stat--danger { background:#ef4444; }
.pfx-stat__val { font-size:1.7rem; font-weight:900; color:#fff; line-height:1; }
.pfx-stat__label { font-size:.78rem; color:rgba(255,255,255,.85); font-weight:600; }
.pfx-stat__more { font-size:.72rem; color:rgba(255,255,255,.7); display:flex; align-items:center; gap:4px; margin-top:4px; }
.pfx-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; align-items:start; }
.pfx-profile-hero { border-radius:20px; overflow:hidden; border:1px solid rgba(15,23,42,.08); }
.pfx-profile-bg { height:200px; background:url(images/img-4.jpeg) center/cover no-repeat; }
.pfx-profile-identity { display:flex; align-items:flex-end; gap:20px; padding:0 24px 20px; margin-top:-50px; }
.pfx-profile-avatar { width:100px; height:100px; border-radius:50%; object-fit:cover; border:4px solid #fff; box-shadow:0 8px 20px rgba(15,23,42,.15); flex-shrink:0; }
.pfx-profile-name { color:#fff; padding-bottom:4px; }
.pfx-profile-name h2 { margin:0; font-size:1.4rem; font-weight:800; color:#0f172a; }
.pfx-profile-name p { margin:2px 0 0; font-size:.9rem; color:#64748b; }
.pfx-charts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.pfx-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; overflow:hidden; }
.pfx-card__head { padding:14px 18px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
.pfx-card__head h3 { margin:0; font-size:.95rem; font-weight:800; color:#0f172a; }
.pfx-card__head a { font-size:.78rem; color:#0369a1; text-decoration:none; font-weight:600; }
.pfx-card__body { padding:18px; }
.pfx-chart-meta { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; }
.pfx-chart-meta__val { font-size:1.2rem; font-weight:800; color:#0f172a; }
.pfx-chart-meta__sub { font-size:.78rem; color:#64748b; margin-top:2px; }
.pfx-chart-meta__trend { text-align:right; color:#16a34a; font-size:.82rem; font-weight:700; }
.pfx-chart-meta__muted { font-size:.72rem; color:#94a3b8; margin-top:2px; }
.pfx-info-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; overflow:hidden; }
.pfx-info-card__body { padding:18px; }
.pfx-info-row { display:flex; align-items:center; gap:10px; font-size:.88rem; color:#374151; padding:8px 0; border-bottom:1px solid #f3f4f6; }
.pfx-info-row:last-child { border-bottom:none; }
.pfx-info-row i { color:#f59e0b; width:18px; flex-shrink:0; }
.pfx-hours-toggle { background:none; border:none; cursor:pointer; color:#94a3b8; padding:2px 4px; }
.pfx-hours-row { display:grid; grid-template-columns:auto 1fr; gap:8px; font-size:.8rem; color:#374151; padding:4px 0; }
.pfx-hours-row b { color:#0f172a; }
.pfx-wide-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; overflow:hidden; }
.pfx-wide-card__head { padding:14px 18px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
.pfx-wide-card__head h3 { margin:0; font-size:.95rem; font-weight:800; color:#0f172a; }
.pfx-wide-card__body { padding:18px; }
@media (max-width:1100px) { .pfx-layout { grid-template-columns:1fr; } .pfx-stats { grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px) { .pfx-charts { grid-template-columns:1fr; } .pfx-stats { grid-template-columns:1fr; } .pfx-page { padding:14px; } }
</style>
@endsection
@section('content')
<div class="pfx-page">
    <div class="pfx-stats">
        <a href="{{ route('admin.pending_orders') }}" class="pfx-stat pfx-stat--info">
            <div class="pfx-stat__val">150</div>
            <div class="pfx-stat__label">Nouvelles commandes</div>
            <div class="pfx-stat__more"><i class="fas fa-arrow-circle-right"></i> Voir</div>
        </a>
        <a href="{{ route('admin.metrics') }}" class="pfx-stat pfx-stat--success">
            <div class="pfx-stat__val">53<sup style="font-size:1rem;">%</sup></div>
            <div class="pfx-stat__label">Customer Reviews</div>
            <div class="pfx-stat__more"><i class="fas fa-arrow-circle-right"></i> Voir</div>
        </a>
        <a href="{{ route('user.index') }}" class="pfx-stat pfx-stat--warning">
            <div class="pfx-stat__val">44</div>
            <div class="pfx-stat__label">User Registrations</div>
            <div class="pfx-stat__more"><i class="fas fa-arrow-circle-right"></i> Voir</div>
        </a>
        <a href="{{ route('home') }}" class="pfx-stat pfx-stat--danger">
            <div class="pfx-stat__val">65</div>
            <div class="pfx-stat__label">Unique Visitors</div>
            <div class="pfx-stat__more"><i class="fas fa-arrow-circle-right"></i> Voir</div>
        </a>
    </div>

    <div class="pfx-layout">
        <div>
            <div class="pfx-profile-hero">
                <div class="pfx-profile-bg"></div>
                <div style="padding:0 24px 24px;background:#fff;">
                    <div style="display:flex;align-items:flex-end;gap:20px;margin-top:-50px;padding-bottom:16px;border-bottom:1px solid #f3f4f6;">
                        <img src="images/user3-128x128.jpg" alt="" class="pfx-profile-avatar">
                        <div style="padding-bottom:4px;">
                            <h2 style="margin:0;font-size:1.4rem;font-weight:800;color:#0f172a;">Kalz Burger</h2>
                            <p style="margin:2px 0 0;font-size:.9rem;color:#64748b;">A Name of Trust</p>
                        </div>
                    </div>
                    <div class="pfx-charts" style="margin-top:16px;">
                        <div class="pfx-card">
                            <div class="pfx-card__head">
                                <h3>Online Store Visitors</h3>
                                <a href="{{ route('admin.metrics') }}">View Report</a>
                            </div>
                            <div class="pfx-card__body">
                                <div class="pfx-chart-meta">
                                    <div>
                                        <div class="pfx-chart-meta__val">820</div>
                                        <div class="pfx-chart-meta__sub">Visitors Over Time</div>
                                    </div>
                                    <div>
                                        <div class="pfx-chart-meta__trend"><i class="fas fa-arrow-up"></i> 12.5%</div>
                                    </div>
                                </div>
                                <canvas id="visitors-chart" height="200" style="display:block;width:100%;"></canvas>
                            </div>
                        </div>
                        <div class="pfx-card">
                            <div class="pfx-card__head">
                                <h3>Posts</h3>
                                <a href="{{ route('news.index') }}">View Report</a>
                            </div>
                            <div class="pfx-card__body">
                                <div class="pfx-chart-meta">
                                    <div>
                                        <div class="pfx-chart-meta__val">&nbsp;</div>
                                        <div class="pfx-chart-meta__sub">Posts Over Time</div>
                                    </div>
                                    <div>
                                        <div class="pfx-chart-meta__trend"><i class="fas fa-arrow-up"></i> 33.1%</div>
                                        <div class="pfx-chart-meta__muted">Since last month</div>
                                    </div>
                                </div>
                                <canvas id="sales-chart" height="200" style="display:block;width:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pfx-wide-card" style="margin-top:16px;">
                <div class="pfx-wide-card__head">
                    <h3>Monthly Rating Report</h3>
                </div>
                <div class="pfx-wide-card__body">
                    <canvas id="salesChart" height="180" style="display:block;width:100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="pfx-info-card">
            <div class="pfx-info-card__body">
                <p style="font-size:.78rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px;">@ Cuisines, Fast Food</p>
                <p style="font-size:.85rem;color:#374151;margin:0 0 12px;">This is the description or about info of the restaurant. This is the description or about info of the restaurant.</p>
                <hr style="border:none;border-top:1px solid #f3f4f6;margin:0 0 10px;">
                <div class="pfx-info-row"><i class="fas fa-phone"></i> +92 123 123 1234</div>
                <div class="pfx-info-row"><i class="fas fa-envelope"></i> example123@gmail.com</div>
                <div class="pfx-info-row" style="justify-content:space-between;">
                    <div><div class="pfx-hours-row"><b>Sunday</b><span>7:00 AM – 10:00 PM</span></div></div>
                    <button class="pfx-hours-toggle" onclick="document.getElementById('pfx-hours').style.display=document.getElementById('pfx-hours').style.display==='none'?'block':'none'">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="pfx-hours" style="display:none;">
                    <div class="pfx-hours-row"><b>Sunday</b><span>7:00 AM – 10:00 PM</span></div>
                    <div class="pfx-hours-row"><b>Sunday</b><span>7:00 AM – 10:00 PM</span></div>
                    <div class="pfx-hours-row"><b>Sunday</b><span>7:00 AM – 10:00 PM</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ asset('dist/js/pages/dashboard3.js')}}"></script>
<script src="{{ asset('dist/js/pages/dashboard2.js')}}"></script>
@endsection
