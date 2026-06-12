@extends('layouts.admin-modern')
@section('title', 'Réservations Transport')
@section('page_title', 'Réservations transport')
@section('nav_active', 'transport')
@section('style')
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
    .bd-pay-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; }
    .bd-pay-pill--paid { background:#d1fae5; color:#065f46; }
    .bd-pay-pill--pending { background:#fef3c7; color:#92400e; }
    .bd-pay-pill--info { background:#dbeafe; color:#1e40af; }
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
    <div style="padding:24px;">
        <div class="bd-bookings-shell">
            <div class="adm-page-bar">
                <div class="adm-page-bar__left">
                    <nav class="adm-page-bar__breadcrumb">
                        <span>Kende</span><span class="sep">/</span><span>Reservations</span>
                    </nav>
                    <h1 class="adm-page-bar__title">Reservations transport</h1>
                </div>
                <div class="adm-page-bar__right">
                    <span class="adm-page-bar__badge {{ $requestedCount + $unassignedCount > 0 ? 'adm-page-bar__badge--danger' : 'adm-page-bar__badge--ok' }}">{{ $requestedCount + $unassignedCount > 0 ? 'Tension active' : 'Flux stable' }}</span>
                </div>
            </div>
            <div class="ops-kpi-grid" style="margin-bottom:1rem;">
                <div class="ops-kpi">
                    <div class="ops-kpi__label">Reservations chargees</div>
                    <div class="ops-kpi__value">{{ number_format($bookingCollection->count(), 0, ',', ' ') }}</div>
                    <div class="ops-kpi__sub">Page {{ $bookings->currentPage() }} de supervision</div>
                </div>
                <div class="ops-kpi">
                    <div class="ops-kpi__label">En attente</div>
                    <div class="ops-kpi__value">{{ number_format($requestedCount, 0, ',', ' ') }}</div>
                    <div class="ops-kpi__sub">Courses non encore dispatchees</div>
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
            <div class="ops-panel ops-alerts" style="margin-bottom:1rem;">
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
                                        <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $booking->created_at->format('d/m/Y H:i') }}</div>
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

        <div class="bd-bookings-main-card" style="margin-top:20px;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap;" id="bookingsTable">
                <thead>
                    <tr>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Booking No</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Type</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Client</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Chauffeur</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Trajet</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Montant</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Statut</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Paiement</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:left;">Date</th>
                        <th style="padding:9px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;border-bottom:1px solid #f3f4f6;background:#f9fafb;text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $statusClass = match((string) $booking->status) {
                                'completed' => 'bd-bookings-status--done',
                                'in_progress' => 'bd-bookings-status--progress',
                                'cancelled', 'failed' => 'bd-bookings-status--fail',
                                default => 'bd-bookings-status--requested',
                            };
                            $experience = $booking->payment_experience ?? null;
                            $payStatus = $experience['status'] ?? strtoupper($booking->payment_status ?? 'pending');
                            $payClass = $payStatus === 'PAID' ? 'bd-pay-pill--paid' : ($payStatus === 'PENDING' ? 'bd-pay-pill--pending' : 'bd-pay-pill--info');
                        @endphp
                        <tr>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $booking->booking_no }}</td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;"><span class="bd-bookings-type">{{ $booking->type->label() }}</span></td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ optional($booking->user)->name ?? 'N/A' }}</td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $booking->driver->name ?? 'Non assigné' }}</td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;max-width:200px;white-space:normal;">
                                <div style="font-size:12px;">De: {{ $booking->pickup_address }}</div>
                                <div style="font-size:12px;margin-top:2px;">À: {{ $booking->dropoff_address }}</div>
                            </td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ number_format($booking->total_price ?? $booking->estimated_price, 0, ',', ' ') }} FCFA</td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                                <span class="bd-bookings-status {{ $statusClass }}">{{ $booking->status->label() }}</span>
                            </td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                                <span class="bd-pay-pill {{ $payClass }}">{{ $payStatus }}</span>
                                @if($experience)
                                    <div style="font-size:12px;color:#9ca3af;margin-top:3px;">{{ $experience['customer_message'] }}</div>
                                @endif
                            </td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;">{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding:10px 14px;color:#374151;vertical-align:middle;border-bottom:1px solid #f3f4f6;text-align:right;">
                                <a href="{{ route('admin.transport.bookings.show', $booking->id) }}" style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #1e3a5f;color:#1e3a5f;border-radius:5px;font-size:12px;font-weight:600;text-decoration:none;"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
@endsection
