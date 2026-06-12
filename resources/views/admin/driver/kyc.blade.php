@extends('layouts.admin-modern')
@section('title', 'KYC — ' . $driver->name)
@section('topbar_title', 'KYC livreur : ' . $driver->name)

@section('content')
<div style="padding:24px;max-width:900px;margin:0 auto;">

    {{-- Back + header --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;flex-wrap:wrap;">
        <a href="{{ route('driver.index') }}" style="color:#6b7280;text-decoration:none;font-size:.85rem;">
            <i class="fas fa-arrow-left"></i> Retour livreurs
        </a>
        <div style="flex:1;"></div>
        <div style="text-align:right;">
            <div style="font-size:1.1rem;font-weight:800;color:#111827;">{{ $driver->name }}</div>
            <div style="font-size:.8rem;color:#6b7280;">{{ $driver->email }} · {{ $driver->phone }}</div>
        </div>
        <div style="background:{{ $driver->approved ? '#dcfce7' : '#fee2e2' }};color:{{ $driver->approved ? '#166534' : '#991b1b' }};padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;">
            {{ $driver->approved ? 'Actif' : 'Inactif' }}
        </div>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:.85rem;">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('warning'))
    <div style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:.85rem;">
        <i class="fas fa-triangle-exclamation"></i> {{ session('warning') }}
    </div>
    @endif

    @if($allApproved)
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:.85rem;color:#166534;display:flex;gap:10px;align-items:center;">
        <i class="fas fa-circle-check" style="font-size:1.1rem;"></i>
        <strong>KYC complet et approuvé.</strong> Ce livreur a soumis et validé tous ses documents.
    </div>
    @endif

    {{-- Documents --}}
    @foreach(\App\DriverDocument::$types as $type => $meta)
    @php $doc = $docs->get($type); @endphp
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;margin-bottom:14px;">

        <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
            <div>
                <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:2px;">
                    <i class="fas {{ $meta['icon'] }}" style="color:#007836;margin-right:6px;"></i>
                    {{ $meta['label'] }}
                </div>
                @if(!$doc)
                <span style="background:#f1f5f9;color:#64748b;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700;">
                    Non soumis
                </span>
                @else
                <span style="background:{{ $doc->isApproved() ? '#dcfce7' : ($doc->isRejected() ? '#fee2e2' : '#fff7ed') }};color:{{ $doc->isApproved() ? '#166534' : ($doc->isRejected() ? '#991b1b' : '#c2410c') }};padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700;">
                    {{ $doc->isApproved() ? 'Approuvé' : ($doc->isRejected() ? 'Refusé' : 'En attente') }}
                </span>
                @if($doc->reviewed_at)
                <span style="font-size:.72rem;color:#9ca3af;margin-left:8px;">le {{ $doc->reviewed_at->format('d/m/Y') }}</span>
                @endif
                @endif
            </div>

            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                @if($doc)
                {{-- Lien fichier --}}
                <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank"
                   style="padding:7px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.82rem;color:#374151;text-decoration:none;">
                    <i class="fas fa-eye"></i> Voir
                </a>
                @if(!$doc->isApproved())
                {{-- Approuver --}}
                <form method="POST" action="{{ route('admin.driver.kyc.approve', [$driver, $doc]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="padding:7px 14px;background:#007836;color:#fff;border:none;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;">
                        <i class="fas fa-check"></i> Approuver
                    </button>
                </form>
                @endif
                @if(!$doc->isRejected())
                {{-- Refuser --}}
                <button onclick="showRejectModal({{ $doc->id }}, '{{ $type }}', '{{ addslashes($meta['label']) }}')"
                        style="padding:7px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;">
                    <i class="fas fa-xmark"></i> Refuser
                </button>
                @endif
                @endif
            </div>
        </div>

        @if($doc && $doc->isRejected() && $doc->rejection_reason)
        <div style="background:#fee2e2;border-radius:8px;padding:10px 12px;margin-top:12px;font-size:.8rem;color:#991b1b;">
            <i class="fas fa-circle-exclamation"></i>
            <strong>Motif communiqué au livreur :</strong> {{ $doc->rejection_reason }}
        </div>
        @endif

        @if($doc && $doc->original_name)
        <div style="font-size:.75rem;color:#9ca3af;margin-top:8px;">
            <i class="fas fa-file"></i> {{ $doc->original_name }}
        </div>
        @endif
    </div>
    @endforeach

</div>

{{-- Modal refus --}}
<div id="rejectOverlay" onclick="closeReject()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1040;"></div>
<div id="rejectModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:min(480px,95vw);background:#fff;border-radius:16px;padding:24px;z-index:1050;box-shadow:0 12px 40px rgba(0,0,0,.18);">
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:#111827;">
        Motif du refus — <span id="rejectDocLabel"></span>
    </h3>
    <form id="rejectForm" method="POST">
        @csrf
        <textarea name="reason" required maxlength="500" rows="4"
            placeholder="Expliquez au livreur pourquoi ce document est refusé…"
            style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.85rem;resize:vertical;outline:none;box-sizing:border-box;"></textarea>
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button type="button" onclick="closeReject()"
                style="flex:1;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.88rem;color:#374151;background:#fff;cursor:pointer;font-weight:600;">
                Annuler
            </button>
            <button type="submit"
                style="flex:1;padding:10px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;">
                <i class="fas fa-xmark"></i> Confirmer le refus
            </button>
        </div>
    </form>
</div>

<script>
function showRejectModal(docId, type, label) {
    document.getElementById('rejectDocLabel').textContent = label;
    document.getElementById('rejectForm').action =
        '{{ url("admin/drivers") }}/' + {{ $driver->id }} + '/kyc/' + docId + '/reject';
    document.getElementById('rejectOverlay').style.display = 'block';
    document.getElementById('rejectModal').style.display = 'block';
}
function closeReject() {
    document.getElementById('rejectOverlay').style.display = 'none';
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endsection
