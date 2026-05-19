@extends('layouts.admin-modern')
@section('title', 'Réservations Transport')
@section('page_title', 'Réservations transport')
@section('nav_active', 'transport')
@section('style')
<link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.css')}}">
<style>
    .bd-bookings-shell { display:grid; gap:14px; }
    .bd-bookings-main-card {
        border-radius: 18px !important;
        border: 1px solid rgba(14, 116, 144, .12) !important;
        box-shadow: 0 14px 40px rgba(15, 23, 42, .06) !important;
        overflow: hidden;
    }
    .bd-bookings-status {
        display:inline-flex;
        align-items:center;
        min-height:26px;
        padding:0 9px;
        border-radius:999px;
        font-size:.68rem;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .bd-bookings-status--requested { background:rgba(240,140,0,.12); color:#f08c00; }
    .bd-bookings-status--progress { background:rgba(14,116,144,.12); color:#0e7490; }
    .bd-bookings-status--done { background:rgba(22,163,74,.1); color:#16a34a; }
    .bd-bookings-status--fail { background:rgba(217,45,32,.1); color:#d92d20; }
    .bd-bookings-type {
        display:inline-flex;
        align-items:center;
        min-height:24px;
        padding:0 8px;
        border-radius:999px;
        background:#ecfeff;
        color:#0e7490;
        font-size:.68rem;
        font-weight:800;
        text-transform:uppercase;
    }
</style>
@endsection

@section('content')
    @php
        $bookingCollection = collect($bookings->items());
        $requestedCount = $bookingCollection->where('status', 'requested')->count();
        $progressCount = $bookingCollection->where('status', 'in_progress')->count();
        $completedCount = $bookingCollection->where('status', 'completed')->count();
        $unassignedCount = $bookingCollection->filter(fn ($booking) => empty(optional($booking->driver)->name))->count();
        $revenueVisible = $bookingCollection->sum(fn ($booking) => (float) ($booking->total_price ?? $booking->estimated_price ?? 0));
        $paymentPendingCount = $bookingCollection->filter(function ($booking) {
            $status = strtoupper((string) ($booking->payment_experience['status'] ?? $booking->payment_status ?? 'pending'));
            return !in_array($status, ['PAID', 'SUCCESS']);
        })->count();
        $recentRows = $bookingCollection->take(5)->values();
    @endphp
    <section class="content">
        <div class="container-fluid">
            <div class="bd-bookings-shell">
                <section class="ops-context">
                    <div class="ops-panel">
                        <div class="ops-row">
                            <div>
                                <div class="ops-title">Reservations transport</div>
                                <div class="ops-sub">Dispatch, affectation chauffeur et suivi paiement sur le flux Kende.</div>
                            </div>
                            <div class="ops-meta">
                                <span class="ops-chip ops-chip--soft">Kende</span>
                                <span class="ops-pill {{ $requestedCount + $unassignedCount > 0 ? 'ops-pill--danger' : 'ops-pill--ok' }}">
                                    {{ $requestedCount + $unassignedCount > 0 ? 'Tension active' : 'Flux stable' }}
                                </span>
                            </div>
                        </div>
                        <div class="ops-strip">
                            <div class="ops-group">
                                <strong>Menus</strong>
                                <span class="ops-chip">Dashboard</span>
                                <span class="ops-chip">Trajets</span>
                                <span class="ops-chip">Chauffeurs</span>
                                <span class="ops-chip">Zones</span>
                                <span class="ops-chip">Incidents</span>
                                <span class="ops-chip">Paiements</span>
                            </div>
                            <div class="ops-group">
                                <strong>Files</strong>
                                <span class="ops-chip">{{ $requestedCount }} en attente</span>
                                <span class="ops-chip">{{ $progressCount }} en cours</span>
                                <span class="ops-chip">{{ $paymentPendingCount }} paiements a revoir</span>
                                <span class="ops-chip">{{ $unassignedCount }} sans chauffeur</span>
                            </div>
                        </div>
                        <div class="ops-kpi-grid">
                            <div class="ops-kpi">
                                <div class="ops-kpi__label">Reservations chargees</div>
                                <div class="ops-kpi__value">{{ number_format($bookingCollection->count(), 0, ',', ' ') }}</div>
                                <div class="ops-kpi__sub">Page {{ $bookings->currentPage() }} de supervision</div>
                            </div>
                            <div class="ops-kpi">
                                <div class="ops-kpi__label">En attente</div>
                                <div class="ops-kpi__value">{{ number_format($requestedCount, 0, ',', ' ') }}</div>
                                <div class="ops-kpi__sub">Courses non encore dispatchées</div>
                            </div>
                            <div class="ops-kpi">
                                <div class="ops-kpi__label">En execution</div>
                                <div class="ops-kpi__value">{{ number_format($progressCount, 0, ',', ' ') }}</div>
                                <div class="ops-kpi__sub">{{ number_format($completedCount, 0, ',', ' ') }} terminees</div>
                            </div>
                            <div class="ops-kpi">
                                <div class="ops-kpi__label">Montant visible</div>
                                <div class="ops-kpi__value">{{ number_format($revenueVisible, 0, ',', ' ') }} FCFA</div>
                                <div class="ops-kpi__sub">Encours et realise sur la page</div>
                            </div>
                        </div>
                    </div>
                    <div class="ops-panel ops-alerts">
                        <article class="ops-alert">
                            <div class="ops-alert__top">
                                <h3>{{ $unassignedCount }} reservations sans chauffeur</h3>
                                <span class="ops-tag {{ $unassignedCount > 0 ? 'ops-tag--danger' : 'ops-pill--ok' }}">{{ $unassignedCount > 0 ? 'Critique' : 'Stable' }}</span>
                            </div>
                            <p>Priorite sur l affectation des courses ouvertes et des zones sous tension.</p>
                        </article>
                        <article class="ops-alert">
                            <div class="ops-alert__top">
                                <h3>{{ $paymentPendingCount }} paiements a confirmer</h3>
                                <span class="ops-tag {{ $paymentPendingCount > 0 ? 'ops-tag--warn' : 'ops-pill--ok' }}">{{ $paymentPendingCount > 0 ? 'Attention' : 'OK' }}</span>
                            </div>
                            <p>Verifier les statuts pending ou info avant cloture des trajets.</p>
                        </article>
                        <article class="ops-alert">
                            <div class="ops-alert__top">
                                <h3>{{ $requestedCount }} demandes a dispatcher</h3>
                                <span class="ops-tag {{ $requestedCount > 0 ? 'ops-tag--warn' : 'ops-pill--ok' }}">{{ $requestedCount > 0 ? 'File active' : 'Vide' }}</span>
                            </div>
                            <p>Suivre la mise en relation client, chauffeur et ETA de prise en charge.</p>
                        </article>
                    </div>
                </section>

                <section class="ops-grid ops-grid--2">
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Incidents a traiter</h2>
                                <p>Les points qui ralentissent l execution transport.</p>
                            </div>
                        </div>
                        <div class="ops-queue">
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $unassignedCount > 0 ? 'ops-queue-dot--danger' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Courses non attribuees</h3>
                                    <p>{{ $unassignedCount }} reservations n ont pas encore de chauffeur associe.</p>
                                </div>
                                <a href="#bookingsTable">Affecter</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $paymentPendingCount > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Paiements non finalises</h3>
                                    <p>{{ $paymentPendingCount }} lignes demandent une verification avant rapprochement.</p>
                                </div>
                                <a href="{{ route('admin.payments.dashboard') }}">Verifier</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot {{ $requestedCount > 0 ? 'ops-queue-dot--warn' : 'ops-queue-dot--ok' }}"></span>
                                <div>
                                    <h3>Demandes en attente</h3>
                                    <p>{{ $requestedCount }} courses attendent encore une prise en charge dispatch.</p>
                                </div>
                                <a href="{{ route('admin.transport.dashboard') }}">Piloter</a>
                            </div>
                        </div>
                    </div>
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Suivi court</h2>
                                <p>Lecture rapide du flux avant dispatch.</p>
                            </div>
                        </div>
                        <div class="ops-stats-grid">
                            <div class="ops-stat">
                                <strong>{{ number_format($requestedCount, 0, ',', ' ') }}</strong>
                                <span>Demandes ouvertes</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($progressCount, 0, ',', ' ') }}</strong>
                                <span>Trajets en progression</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($completedCount, 0, ',', ' ') }}</strong>
                                <span>Trajets clotures</span>
                            </div>
                            <div class="ops-stat">
                                <strong>{{ number_format($paymentPendingCount, 0, ',', ' ') }}</strong>
                                <span>Paiements a revoir</span>
                            </div>
                        </div>
                        <div class="ops-trend">
                            <svg viewBox="0 0 300 80" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M0,58 C26,56 44,22 76,24 C112,28 126,50 154,48 C186,44 197,15 228,18 C257,21 274,40 300,28" fill="none" stroke="#0e7490" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <section class="ops-grid ops-grid--2">
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Actions requises</h2>
                                <p>Routine de supervision pour le poste dispatch.</p>
                            </div>
                        </div>
                        <div class="ops-queue">
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--danger"></span>
                                <div>
                                    <h3>Affecter les reservations ouvertes</h3>
                                    <p>Reduire le temps d attente avant prise en charge chauffeur.</p>
                                </div>
                                <a href="#bookingsTable">Traiter</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--warn"></span>
                                <div>
                                    <h3>Verifier les paiements pending</h3>
                                    <p>Eviter qu une course cloturee reste avec un statut de paiement incertain.</p>
                                </div>
                                <a href="{{ route('admin.payments.dashboard') }}">Ouvrir</a>
                            </div>
                            <div class="ops-queue-item">
                                <span class="ops-queue-dot ops-queue-dot--ok"></span>
                                <div>
                                    <h3>Suivre le tableau transport</h3>
                                    <p>Revenir au cockpit Kende pour comparer volume, flotte et SLA.</p>
                                </div>
                                <a href="{{ route('admin.transport.dashboard') }}">Retour</a>
                            </div>
                        </div>
                    </div>
                    <div class="ops-card">
                        <div class="ops-card__header">
                            <div>
                                <h2>Tableau principal</h2>
                                <p>Vue courte des reservations recentes et de leur tension actuelle.</p>
                            </div>
                        </div>
                        <div class="ops-table-wrap">
                            <table class="ops-table">
                                <thead>
                                <tr>
                                    <th>Reservation</th>
                                    <th>Client</th>
                                    <th>Chauffeur</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentRows as $booking)
                                    @php
                                        $miniStatus = strtolower((string) $booking->status);
                                        $miniClass = $miniStatus === 'completed' ? 'ops-pill--ok' : ($miniStatus === 'in_progress' ? 'ops-pill--warn' : 'ops-pill--danger');
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $booking->booking_no }}</strong>
                                            <div class="text-muted">{{ $booking->created_at->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td>{{ optional($booking->user)->name ?? 'N/A' }}</td>
                                        <td>{{ optional($booking->driver)->name ?? 'Non assigne' }}</td>
                                        <td>{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</td>
                                        <td><span class="ops-pill {{ $miniClass }}">{{ $booking->status->label() }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">Aucune reservation disponible.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card bd-bookings-main-card">
                <div class="card-body table-responsive">
                    <table class="table table-hover" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Booking No</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th>Chauffeur</th>
                                <th>Trajet</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->booking_no }}</td>
                                    <td><span class="bd-bookings-type">{{ $booking->type->label() }}</span></td>
                                    <td>{{ optional($booking->user)->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->driver->name ?? 'Non assigné' }}</td>
                                    <td>
                                        <small>
                                            De: {{ $booking->pickup_address }}<br>
                                            À: {{ $booking->dropoff_address }}
                                        </small>
                                    </td>
                                    <td>{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</td>
                                    <td>
                                        @php
                                            $statusClass = match((string) $booking->status) {
                                                'completed' => 'bd-bookings-status--done',
                                                'in_progress' => 'bd-bookings-status--progress',
                                                'cancelled', 'failed' => 'bd-bookings-status--fail',
                                                default => 'bd-bookings-status--requested',
                                            };
                                        @endphp
                                        <span class="bd-bookings-status {{ $statusClass }}">{{ $booking->status->label() }}</span>
                                    </td>
                                    <td>
                                        @php $experience = $booking->payment_experience ?? null; @endphp
                                        <div><span class="badge badge-{{ ($experience['status'] ?? null) === 'PAID' ? 'success' : (($experience['status'] ?? null) === 'PENDING' ? 'warning' : 'info') }}">{{ $experience['status'] ?? strtoupper($booking->payment_status ?? 'pending') }}</span></div>
                                        @if($experience)
                                            <small class="text-muted d-block mt-1">{{ $experience['customer_message'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.transport.bookings.show', $booking->id) }}" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $bookings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
