@extends('layouts.driver-modern')
@section('title', 'Mes documents | KYC')
@section('nav_documents', 'is-active')
@section('driver_initials', strtoupper(substr($driver->name ?? 'L', 0, 2)))
@section('driver_name', $driver->name ?? 'Livreur')
@section('driver_phone', $driver->phone ?? '')
@section('online_pill_class', ($driver->status ?? 'offline') === 'online' ? '' : 'offline')
@section('online_pill_label', ($driver->status ?? 'offline') === 'online' ? 'En ligne' : 'Hors ligne')
@section('page_title', 'Mes documents')

@section('style')
<style>
.kyc-body { padding: 20px 20px 60px; display: flex; flex-direction: column; gap: 16px; max-width: 600px; margin: 0 auto; }

.kyc-banner {
    background: #f0faf4; border: 1px solid #bbf7d0; border-radius: 12px;
    padding: 14px 16px; font-size: .83rem; color: #166534; display: flex; gap: 10px; align-items: flex-start;
}
.kyc-banner.warn { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }

.kyc-card {
    background: var(--c-surface, #fff); border: 1px solid var(--c-border, #e5e7eb);
    border-radius: 14px; padding: 20px; position: relative;
}
.kyc-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.kyc-card-icon {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.kyc-card-title { font-size: .95rem; font-weight: 700; color: var(--c-text, #111827); }
.kyc-card-sub   { font-size: .78rem; color: var(--c-text-muted, #6b7280); margin-top: 2px; }

.kyc-status-badge {
    position: absolute; top: 16px; right: 16px;
    padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700;
}
.kyc-status-badge.pending  { background: #fff7ed; color: #c2410c; }
.kyc-status-badge.approved { background: #dcfce7; color: #166534; }
.kyc-status-badge.rejected { background: #fee2e2; color: #991b1b; }
.kyc-status-badge.none     { background: #f1f5f9; color: #64748b; }

.kyc-rejection { background: #fee2e2; border-radius: 8px; padding: 10px 12px; font-size: .8rem; color: #991b1b; margin-bottom: 12px; }

.kyc-file-current {
    display: flex; align-items: center; gap: 10px;
    background: var(--c-bg, #f9fafb); border-radius: 8px; padding: 10px 12px;
    margin-bottom: 12px; font-size: .82rem;
}
.kyc-file-current a { color: var(--c-primary, #007836); text-decoration: none; font-weight: 600; }
.kyc-file-current a:hover { text-decoration: underline; }

.kyc-upload-area {
    border: 2px dashed var(--c-border, #e5e7eb); border-radius: 10px;
    padding: 16px; text-align: center; cursor: pointer; transition: border-color .2s;
}
.kyc-upload-area:hover { border-color: var(--c-primary, #007836); }
.kyc-upload-area input[type=file] { display: none; }
.kyc-upload-label { font-size: .83rem; color: var(--c-text-muted, #6b7280); cursor: pointer; }
.kyc-upload-label strong { color: var(--c-primary, #007836); }

.kyc-upload-btn {
    margin-top: 10px; width: 100%; padding: 10px;
    background: var(--c-primary, #007836); color: #fff;
    border: none; border-radius: 10px; font-size: .88rem; font-weight: 700; cursor: pointer;
    display: none;
}
.kyc-upload-btn:disabled { opacity: .6; cursor: not-allowed; }
.kyc-upload-btn.visible { display: block; }

.kyc-delete-btn {
    background: none; border: none; color: #9ca3af; cursor: pointer;
    font-size: .8rem; padding: 4px 8px; border-radius: 6px;
    transition: color .15s, background .15s;
}
.kyc-delete-btn:hover { color: #dc2626; background: #fee2e2; }
</style>
@endsection

@section('content')
<div class="kyc-body">

    {{-- Bannière état global --}}
    @php
        $allApproved = count($docs) === count(\App\DriverDocument::$types) && $docs->every(fn($d) => $d->isApproved());
        $hasRejected = $docs->some(fn($d) => $d->isRejected());
    @endphp

    @if($allApproved)
    <div class="kyc-banner">
        <i class="fas fa-circle-check" style="font-size:1.1rem;flex-shrink:0;"></i>
        <div><strong>KYC validé !</strong> Tous vos documents ont été approuvés. Votre compte est pleinement actif.</div>
    </div>
    @elseif($hasRejected)
    <div class="kyc-banner warn">
        <i class="fas fa-triangle-exclamation" style="font-size:1.1rem;flex-shrink:0;"></i>
        <div><strong>Action requise.</strong> Certains documents ont été refusés. Consultez les motifs ci-dessous et soumettez de nouveaux fichiers.</div>
    </div>
    @else
    <div class="kyc-banner" style="background:#eff6ff;border-color:#bfdbfe;color:#1e40af;">
        <i class="fas fa-info-circle" style="font-size:1.1rem;flex-shrink:0;"></i>
        <div>Soumettez vos 3 documents pour activer votre compte livreur. Vérification sous <strong>48h ouvrées</strong>.</div>
    </div>
    @endif

    {{-- Carte par type de document --}}
    @foreach(\App\DriverDocument::$types as $type => $meta)
    @php
        $doc = $docs->get($type);
        $status = $doc ? $doc->status : 'none';
        $iconColors = ['permis' => '#2563eb', 'assurance' => '#7c3aed', 'cni' => '#d97706'];
        $iconBgs    = ['permis' => '#eff6ff', 'assurance' => '#f5f3ff', 'cni' => '#fff7ed'];
    @endphp
    <div class="kyc-card" id="card-{{ $type }}">
        <span class="kyc-status-badge {{ $status }}">
            @if($status === 'approved') <i class="fas fa-check"></i> Approuvé
            @elseif($status === 'pending') <i class="fas fa-clock"></i> En attente
            @elseif($status === 'rejected') <i class="fas fa-xmark"></i> Refusé
            @else <i class="fas fa-upload"></i> À soumettre
            @endif
        </span>

        <div class="kyc-card-head">
            <div class="kyc-card-icon" style="background:{{ $iconBgs[$type] }};color:{{ $iconColors[$type] }};">
                <i class="fas {{ $meta['icon'] }}"></i>
            </div>
            <div>
                <div class="kyc-card-title">{{ $meta['label'] }}</div>
                <div class="kyc-card-sub">JPEG, PNG ou PDF · max 8 Mo</div>
            </div>
        </div>

        @if($doc && $doc->isRejected() && $doc->rejection_reason)
        <div class="kyc-rejection">
            <i class="fas fa-circle-exclamation"></i>
            <strong>Motif du refus :</strong> {{ $doc->rejection_reason }}
        </div>
        @endif

        @if($doc)
        <div class="kyc-file-current">
            <i class="fas fa-file" style="color:#9ca3af;"></i>
            <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank">
                {{ $doc->original_name ?? basename($doc->file_path) }}
            </a>
            <span style="color:#9ca3af;font-size:.75rem;margin-left:auto;">{{ $doc->updated_at->format('d/m/Y') }}</span>
            @if(!$doc->isApproved())
            <button class="kyc-delete-btn" onclick="kycDelete({{ $doc->id }}, '{{ $type }}')" title="Supprimer">
                <i class="fas fa-trash-can"></i>
            </button>
            @endif
        </div>
        @endif

        @if(!$doc || $doc->isRejected())
        <div class="kyc-upload-area" onclick="document.getElementById('file-{{ $type }}').click()">
            <input type="file" id="file-{{ $type }}" accept=".jpg,.jpeg,.png,.pdf"
                onchange="kycPreview('{{ $type }}', this)">
            <div class="kyc-upload-label" id="label-{{ $type }}">
                <i class="fas fa-cloud-arrow-up" style="font-size:1.4rem;display:block;margin-bottom:6px;color:#d1d5db;"></i>
                <strong>Cliquez pour sélectionner</strong> ou glissez votre fichier ici
            </div>
        </div>
        <button class="kyc-upload-btn" id="btn-{{ $type }}" onclick="kycUpload('{{ $type }}')">
            <i class="fas fa-paper-plane"></i> Soumettre ce document
        </button>
        @elseif($doc->isPending())
        <div style="font-size:.8rem;color:#6b7280;margin-top:8px;">
            <i class="fas fa-hourglass-half"></i> En cours de vérification par notre équipe
        </div>
        @elseif($doc->isApproved())
        <div style="font-size:.8rem;color:#166534;margin-top:8px;">
            <i class="fas fa-circle-check"></i> Validé le {{ $doc->reviewed_at?->format('d/m/Y') }}
        </div>
        @endif
    </div>
    @endforeach

</div>
@endsection

@section('script')
<script>
var CSRF = '{{ csrf_token() }}';

function kycPreview(type, input) {
    if (!input.files[0]) return;
    var name = input.files[0].name;
    document.getElementById('label-' + type).innerHTML =
        '<i class="fas fa-file" style="font-size:1rem;display:block;margin-bottom:4px;color:#007836;"></i>' + name;
    var btn = document.getElementById('btn-' + type);
    if (btn) btn.classList.add('visible');
}

function kycUpload(type) {
    var input = document.getElementById('file-' + type);
    var btn   = document.getElementById('btn-' + type);
    if (!input.files[0]) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi…';

    var form = new FormData();
    form.append('_token', CSRF);
    form.append('type', type);
    form.append('file', input.files[0]);

    fetch('{{ route("driver.documents.store") }}', {
        method: 'POST', body: form, credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Soumettre ce document';
            alert(d.message || 'Erreur lors de l\'envoi');
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Soumettre'; });
}

function kycDelete(id, type) {
    if (!confirm('Supprimer ce document ?')) return;
    fetch('{{ url("driver/documents") }}/' + id, {
        method: 'DELETE', credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(() => location.reload());
}
</script>
@endsection
