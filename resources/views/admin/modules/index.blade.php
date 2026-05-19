@extends('layouts.admin-modern')
@section('title', 'Modules & Santé | Admin')
@section('page_title', 'Modules & Santé opératoire')
@section('nav_active', 'modules')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @include('admin.partials.control_hub_nav')
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->has('modules'))
                <div class="alert alert-danger">{{ $errors->first('modules') }}</div>
            @endif
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fas fa-layer-group"></i> Modules & Sante Operatoire</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.modules.update') }}">
                        @csrf
                        <div class="row">
                            @foreach($modules as $key => $module)
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 border-{{ $module['healthy'] ? 'success' : 'warning' }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1">{{ $module['label'] }}</h5>
                                                    <span class="badge badge-{{ $module['healthy'] ? 'success' : 'warning' }}">
                                                        {{ $module['healthy'] ? 'Sain' : 'A surveiller' }}
                                                    </span>
                                                </div>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="toggle-{{ $key }}" name="{{ $key }}_enabled" value="1" {{ $module['enabled'] ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="toggle-{{ $key }}">Actif</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>File de queue</label>
                                                <input type="text" class="form-control" name="queue_{{ $key }}" value="{{ $module['queue'] }}">
                                            </div>
                                            <ul class="list-unstyled small mb-0">
                                                <li>Base: <strong>{{ $module['database_ok'] ? 'OK' : 'KO' }}</strong></li>
                                                @foreach($module['tables'] as $table => $state)
                                                    <li>{{ $table }}: <strong>{{ $state ? 'OK' : 'KO' }}</strong></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Journal de développement</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Répertoire dédié: <code>{{ $developmentReportsPath }}</code>
                    </p>
                    @if($developmentReports->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
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
                        <p class="mb-0 text-muted">Aucun rapport de développement trouvé.</p>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-plug"></i> Dependances Externes</h5>
                        </div>
                        <div class="card-body">
                            @foreach($dependencies as $name => $dependency)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>{{ ucfirst($name) }}</strong>
                                        <span class="badge badge-{{ $dependency['healthy'] ? 'success' : 'warning' }}">
                                            {{ $dependency['healthy'] ? 'Pret' : 'Partiel / Demo' }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mt-2">
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
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-stream"></i> Queues</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Driver global: <strong>{{ $queues['driver'] }}</strong></p>
                            <p class="mb-2">Table jobs: <strong>{{ $queues['jobs_table'] ? 'OK' : 'Absente' }}</strong></p>
                            <p class="mb-3">Table failed_jobs: <strong>{{ $queues['failed_jobs_table'] ? 'OK' : 'Absente' }}</strong></p>
                            <ul class="list-unstyled mb-0">
                                @foreach($queues['queues'] as $name => $queue)
                                    <li class="mb-2">
                                        <div><strong>{{ $name }}</strong> -> {{ $queue['connection'] }} / {{ $queue['name'] }}</div>
                                        <div class="small text-muted">
                                            pending={{ $queue['pending_jobs'] }},
                                            reserved={{ $queue['reserved_jobs'] }},
                                            failed={{ $queue['failed_jobs'] }},
                                            oldest_pending_s={{ $queue['oldest_pending_seconds'] ?? 'n/a' }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-heartbeat"></i> Workers</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Etat global: <strong>{{ $workers['healthy'] ? 'OK' : 'A surveiller' }}</strong></p>
                            <ul class="list-unstyled mb-0">
                                @foreach($workers['services'] as $worker)
                                    <li class="mb-2">
                                        <div><strong>{{ $worker['module'] }}</strong> -> {{ $worker['service'] }}</div>
                                        <div class="small text-muted">
                                            status={{ $worker['status'] ?? 'unknown' }},
                                            active={{ $worker['active'] === null ? 'unknown' : ($worker['active'] ? 'yes' : 'no') }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Failed Jobs</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                @forelse($failedJobs['counts'] as $failed)
                                    <div class="col-md-3 mb-2">
                                        <div class="border rounded p-3">
                                            <div><strong>{{ $failed['module'] }}</strong></div>
                                            <div class="small text-muted">queue={{ $failed['queue'] }}</div>
                                            <div class="h5 mb-0">{{ $failed['total'] }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="mb-0 text-muted">Aucun failed job.</p>
                                    </div>
                                @endforelse
                            </div>
                            @if(!empty($failedJobs['recent']))
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
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
                                                    <td class="small">{{ $failed['exception_head'] }}</td>
                                                    <td>
                                                        <form method="POST" action="{{ route('admin.modules.failed_jobs.retry', $failed['id']) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Retry</button>
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
            </div>
        </div>
    </div>
</div>
@endsection
