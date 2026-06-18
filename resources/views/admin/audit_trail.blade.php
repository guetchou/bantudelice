@extends('layouts.admin-modern')
@section('title', 'Audit trail | Admin')
@section('topbar_title', 'Journal des actions admin')
@section('nav_active', 'audit')

@section('content')
<div style="padding:24px;max-width:1200px;margin:0 auto;">

    {{-- Filtres --}}
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:flex-end;">
        <div>
            <label style="font-size:.75rem;font-weight:700;color:#6b7280;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Recherche</label>
            <input name="q" value="{{ request('q') }}" placeholder="Email, route, chemin…"
                style="padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.85rem;width:260px;outline:none;">
        </div>
        <div>
            <label style="font-size:.75rem;font-weight:700;color:#6b7280;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Méthode</label>
            <select name="method" style="padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.85rem;outline:none;">
                <option value="">Toutes</option>
                @foreach(['POST','PUT','PATCH','DELETE'] as $m)
                <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="padding:9px 18px;background:#007836;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;">
            <i class="fas fa-magnifying-glass"></i> Filtrer
        </button>
        @if(request('q') || request('method'))
        <a href="{{ route('admin.audit_trail') }}" style="padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.85rem;color:#374151;text-decoration:none;">
            <i class="fas fa-xmark"></i> Réinitialiser
        </a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">Date</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Admin</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Méth.</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Route / Chemin</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        $methodColor = match($log->method) {
                            'POST'   => '#2563eb',
                            'PUT','PATCH' => '#d97706',
                            'DELETE' => '#dc2626',
                            default  => '#6b7280',
                        };
                        $statusColor = $log->response_status >= 400 ? '#dc2626' : ($log->response_status >= 300 ? '#d97706' : '#16a34a');
                    @endphp
                    <tr style="border-bottom:1px solid #f3f4f6;" x-data="{ open: false }">
                        <td style="padding:10px 14px;white-space:nowrap;color:#6b7280;">
                            {{ $log->created_at->format('d/m H:i:s') }}
                        </td>
                        <td style="padding:10px 14px;">
                            <div style="font-weight:600;color:#111827;">{{ $log->admin_email ?? 'Inconnu' }}</div>
                        </td>
                        <td style="padding:10px 14px;">
                            <span style="background:{{ $methodColor }}1a;color:{{ $methodColor }};padding:2px 8px;border-radius:6px;font-size:.7rem;font-weight:700;font-family:monospace;">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td style="padding:10px 14px;max-width:320px;">
                            <div style="color:#111827;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                /{{ $log->path }}
                            </div>
                            @if($log->route_name)
                            <div style="color:#9ca3af;font-size:.72rem;font-family:monospace;">{{ $log->route_name }}</div>
                            @endif
                            @if($log->payload)
                            <details style="margin-top:4px;">
                                <summary style="cursor:pointer;font-size:.72rem;color:#007836;">Payload</summary>
                                <pre style="font-size:.7rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:6px;margin-top:4px;overflow-x:auto;max-width:400px;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                            @endif
                        </td>
                        <td style="padding:10px 14px;">
                            <span style="color:{{ $statusColor }};font-weight:700;font-family:monospace;">{{ $log->response_status }}</span>
                        </td>
                        <td style="padding:10px 14px;color:#9ca3af;font-size:.75rem;white-space:nowrap;">{{ $log->ip }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:40px;text-align:center;color:#9ca3af;">
                            <i class="fas fa-clipboard-list" style="font-size:2rem;margin-bottom:10px;display:block;color:#d1d5db;"></i>
                            Aucune action enregistrée
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div style="margin-top:20px;display:flex;justify-content:center;">
        {{ $logs->links() }}
    </div>
    @endif

</div>
@endsection
