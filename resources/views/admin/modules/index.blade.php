@extends('layouts.admin-modern')
@section('title', 'Modules & Santé | Admin')
@section('page_title', 'Modules & Santé opératoire')
@section('nav_active', 'modules')

@section('style')
<style>
.mod-page { padding:24px; display:grid; gap:20px; }
.mod-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; }
.mod-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.mod-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.mod-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.mod-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:10px; }
.mod-card__header--dark { background:#1f2937; }
.mod-card__header--primary { background:#1e3a5f; }
.mod-card__header--info { background:#0369a1; }
.mod-card__header--secondary { background:#4b5563; }
.mod-card__header--success { background:#15803d; }
.mod-card__header--danger { background:#dc2626; }
.mod-card__header--light { background:#f9fafb; }
.mod-card__title { font-size:14px; font-weight:700; color:#fff; margin:0; }
.mod-card__title--dark { color:#111827; }
.mod-card__body { padding:20px; }
.mod-module-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px; }
.mod-module-box { border:1px solid #e5e7eb; border-radius:10px; padding:14px; }
.mod-module-box--healthy { border-color:#bbf7d0; }
.mod-module-box--warn { border-color:#fde68a; }
.mod-module-box__top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
.mod-module-box__name { font-size:14px; font-weight:700; color:#111827; margin-bottom:4px; }
.mod-pill { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.mod-pill--success { background:#d1fae5; color:#065f46; }
.mod-pill--warning { background:#fef3c7; color:#92400e; }
.mod-toggle-row { display:flex; align-items:center; gap:8px; }
.mod-toggle-label { font-size:13px; color:#374151; font-weight:500; }
.mod-field { margin-bottom:0; }
.mod-label { font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:4px; }
.mod-input { width:100%; padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; box-sizing:border-box; }
.mod-check-list { list-style:none; padding:0; margin:10px 0 0; font-size:12px; color:#6b7280; }
.mod-check-list li { padding:2px 0; }
.mod-check-list li strong { color:#374151; }
.mod-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.mod-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.mod-table-wrap { overflow-x:auto; }
.mod-table { width:100%; border-collapse:collapse; font-size:13px; }
.mod-table thead th { padding:8px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#9ca3af; border-bottom:1px solid #f3f4f6; background:#f9fafb; text-align:left; }
.mod-table tbody td { padding:8px 12px; color:#374151; border-bottom:1px solid #f3f4f6; }
.mod-table tbody tr:last-child td { border-bottom:none; }
.mod-dep-item { padding:10px 0; border-bottom:1px solid #f3f4f6; }
.mod-dep-item:last-child { border-bottom:none; }
.mod-dep-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
.mod-dep-top strong { font-size:13px; color:#111827; }
.mod-dep-detail { font-size:12px; color:#9ca3af; }
.mod-queue-item { margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #f3f4f6; }
.mod-queue-item:last-child { border-bottom:none; margin-bottom:0; }
.mod-queue-item strong { font-size:13px; color:#111827; }
.mod-queue-item .mod-queue-detail { font-size:12px; color:#9ca3af; margin-top:2px; }
.mod-failed-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
.mod-failed-box { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
.mod-failed-box strong { display:block; font-size:13px; color:#111827; }
.mod-failed-box .mod-failed-queue { font-size:12px; color:#9ca3af; margin-top:2px; }
.mod-failed-box .mod-failed-count { font-size:1.4rem; font-weight:900; color:#dc2626; margin-top:4px; }
.mod-btn-retry { display:inline-flex; align-items:center; padding:4px 10px; border:1px solid #1e3a5f; color:#1e3a5f; background:#fff; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; }
@media (max-width:900px) { .mod-module-grid { grid-template-columns:1fr 1fr; } .mod-row-2 { grid-template-columns:1fr; } .mod-failed-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:576px) { .mod-module-grid { grid-template-columns:1fr; } .mod-failed-grid { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="mod-page">
    @include('admin.partials.control_hub_nav')

    @if(session('success'))
        <div class="mod-alert mod-alert--success">{{ session('success') }}</div>
    @endif
    @if($errors->has('modules'))
        <div class="mod-alert mod-alert--danger">{{ $errors->first('modules') }}</div>
    @endif

    <div class="mod-card">
        <div class="mod-card__header mod-card__header--dark">
            <i class="fas fa-layer-group" style="color:#fff;"></i>
            <h4 class="mod-card__title">Modules & Sante Operatoire</h4>
        </div>
        <div class="mod-card__body">
            <form method="POST" action="{{ route('admin.modules.update') }}">
                @csrf
                <div class="mod-module-grid">
                    @foreach($modules as $key => $module)
                    <div class="mod-module-box {{ $module['healthy'] ? 'mod-module-box--healthy' : 'mod-module-box--warn' }}">
                        <div class="mod-module-box__top">
                            <div>
                                <div class="mod-module-box__name">{{ $module['label'] }}</div>
                                <span class="mod-pill {{ $module['healthy'] ? 'mod-pill--success' : 'mod-pill--warning' }}">
                                    {{ $module['healthy'] ? 'Sain' : 'A surveiller' }}
                                </span>
                            </div>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="checkbox" name="{{ $key }}_enabled" value="1" id="toggle-{{ $key }}" {{ $module['enabled'] ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer;">
                                <span style="font-size:12px;color:#374151;">Actif</span>
                            </label>
                        </div>
                        <div class="mod-field">
                            <label class="mod-label" for="queue-{{ $key }}">File de queue</label>
                            <input type="text" id="queue-{{ $key }}" class="mod-input" name="queue_{{ $key }}" value="{{ $module['queue'] }}">
                        </div>
                        <ul class="mod-check-list">
                            <li>Base: <strong>{{ $module['database_ok'] ? 'OK' : 'KO' }}</strong></li>
                            @foreach($module['tables'] as $table => $state)
                                <li>{{ $table }}: <strong>{{ $state ? 'OK' : 'KO' }}</strong></li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
                <button type="submit" class="mod-btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-card__header mod-card__header--primary">
            <i class="fas fa-file-alt" style="color:#fff;"></i>
            <h5 class="mod-card__title">Journal de développement</h5>
        </div>
        <div class="mod-card__body">
            <p style="font-size:13px;color:#9ca3af;margin-bottom:14px;">
                Répertoire dédié: <code>{{ $developmentReportsPath }}</code>
            </p>
            @if($developmentReports->isNotEmpty())
                <div class="mod-table-wrap">
                    <table class="mod-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Titre</th>
                                <th>Fichier</th>
                                <th>Chemin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($developmentReports as $report)
                            <tr>
                                <td>{{ $report['date'] ?? 'n/a' }}</td>
                                <td>{{ $report['title'] }}</td>
                                <td><code>{{ $report['filename'] }}</code></td>
                                <td><code>{{ $report['path'] }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="font-size:13px;color:#9ca3af;margin:0;">Aucun rapport de développement trouvé.</p>
            @endif
        </div>
    </div>

    <div class="mod-row-2">
        <div class="mod-card">
            <div class="mod-card__header mod-card__header--info">
                <i class="fas fa-plug" style="color:#fff;"></i>
                <h5 class="mod-card__title">Dependances Externes</h5>
            </div>
            <div class="mod-card__body">
                @foreach($dependencies as $name => $dependency)
                <div class="mod-dep-item">
                    <div class="mod-dep-top">
                        <strong>{{ ucfirst($name) }}</strong>
                        <span class="mod-pill {{ $dependency['healthy'] ? 'mod-pill--success' : 'mod-pill--warning' }}">
                            {{ $dependency['healthy'] ? 'Pret' : 'Partiel / Demo' }}
                        </span>
                    </div>
                    <div class="mod-dep-detail">
                        @foreach(($dependency['providers'] ?? []) as $provider => $state)
                            <div>{{ $provider }}: enabled={{ !empty($state['enabled']) ? 'yes' : 'no' }}, configured={{ !empty($state['configured']) ? 'yes' : 'no' }}</div>
                        @endforeach
                        @if(isset($dependency['driver']))
                            <div>driver={{ $dependency['driver'] }}, host={{ $dependency['host'] ?? 'n/a' }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="mod-card">
            <div class="mod-card__header mod-card__header--secondary">
                <i class="fas fa-stream" style="color:#fff;"></i>
                <h5 class="mod-card__title">Queues</h5>
            </div>
            <div class="mod-card__body">
                <p style="font-size:13px;margin-bottom:6px;">Driver global: <strong>{{ $queues['driver'] }}</strong></p>
                <p style="font-size:13px;margin-bottom:6px;">Table jobs: <strong>{{ $queues['jobs_table'] ? 'OK' : 'Absente' }}</strong></p>
                <p style="font-size:13px;margin-bottom:14px;">Table failed_jobs: <strong>{{ $queues['failed_jobs_table'] ? 'OK' : 'Absente' }}</strong></p>
                @foreach($queues['queues'] as $name => $queue)
                <div class="mod-queue-item">
                    <strong>{{ $name }}</strong> → {{ $queue['connection'] }} / {{ $queue['name'] }}
                    <div class="mod-queue-detail">
                        pending={{ $queue['pending_jobs'] }},
                        reserved={{ $queue['reserved_jobs'] }},
                        failed={{ $queue['failed_jobs'] }},
                        oldest_pending_s={{ $queue['oldest_pending_seconds'] ?? 'n/a' }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mod-row-2">
        <div class="mod-card">
            <div class="mod-card__header mod-card__header--success">
                <i class="fas fa-heartbeat" style="color:#fff;"></i>
                <h5 class="mod-card__title">Workers</h5>
            </div>
            <div class="mod-card__body">
                <p style="font-size:13px;margin-bottom:14px;">Etat global: <strong>{{ $workers['healthy'] ? 'OK' : 'A surveiller' }}</strong></p>
                @foreach($workers['services'] as $worker)
                <div class="mod-queue-item">
                    <strong>{{ $worker['module'] }}</strong> → {{ $worker['service'] }}
                    <div class="mod-queue-detail">
                        status={{ $worker['status'] ?? 'unknown' }},
                        active={{ $worker['active'] === null ? 'unknown' : ($worker['active'] ? 'yes' : 'no') }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div></div>
    </div>

    <div class="mod-card">
        <div class="mod-card__header mod-card__header--danger">
            <i class="fas fa-exclamation-triangle" style="color:#fff;"></i>
            <h5 class="mod-card__title">Failed Jobs</h5>
        </div>
        <div class="mod-card__body">
            <div class="mod-failed-grid">
                @forelse($failedJobs['counts'] as $failed)
                <div class="mod-failed-box">
                    <strong>{{ $failed['module'] }}</strong>
                    <div class="mod-failed-queue">queue={{ $failed['queue'] }}</div>
                    <div class="mod-failed-count">{{ $failed['total'] }}</div>
                </div>
                @empty
                <div style="grid-column:1/-1;font-size:13px;color:#9ca3af;">Aucun failed job.</div>
                @endforelse
            </div>
            @if(!empty($failedJobs['recent']))
            <div class="mod-table-wrap">
                <table class="mod-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module</th>
                            <th>Queue</th>
                            <th>Connexion</th>
                            <th>Failed At</th>
                            <th>Exception</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs['recent'] as $failed)
                        <tr>
                            <td>{{ $failed['id'] }}</td>
                            <td>{{ $failed['module'] }}</td>
                            <td>{{ $failed['queue'] }}</td>
                            <td>{{ $failed['connection'] }}</td>
                            <td>{{ $failed['failed_at'] }}</td>
                            <td style="font-size:12px;">{{ $failed['exception_head'] }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.modules.failed_jobs.retry', $failed['id']) }}">
                                    @csrf
                                    <button type="submit" class="mod-btn-retry">Retry</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
