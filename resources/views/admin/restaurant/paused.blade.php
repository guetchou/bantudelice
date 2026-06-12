@extends('layouts.admin-modern')
@section('title', 'Restaurants en pause')
@section('nav_active', 'restaurants-paused')
@section('page_title', 'Restaurants en pause')

@section('content')
<div style="max-width:1100px;margin:0 auto;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.3rem;font-weight:900;color:#111827;margin:0;">Restaurants en pause</h1>
            <p style="font-size:.82rem;color:#9ca3af;margin:4px 0 0;">Surveillance temps réel — {{ $paused->count() }} en pause actuellement</p>
        </div>
        <a href="{{ route('restaurant.index') }}" style="font-size:.82rem;color:#e85d04;font-weight:700;text-decoration:none;">
            ← Tous les restaurants
        </a>
    </div>

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#166534;font-size:.85rem;font-weight:600;">
        ✓ {{ session('success') }}
    </div>
    @endif

    {{-- Restaurants en pause --}}
    @if($paused->isEmpty())
    <div style="background:#fff;border-radius:16px;border:1px dashed #e0e0e0;padding:48px;text-align:center;margin-bottom:32px;">
        <div style="font-size:2rem;margin-bottom:12px;">✅</div>
        <div style="font-size:1rem;font-weight:700;color:#1a1a1a;">Aucun restaurant en pause</div>
        <div style="font-size:.85rem;color:#9ca3af;margin-top:4px;">Tous les restaurants actifs sont opérationnels.</div>
    </div>
    @else
    <div style="display:grid;gap:16px;margin-bottom:40px;">
        @foreach($paused as $r)
        <div style="background:#fff;border-radius:16px;border:1px solid #fca5a5;padding:20px;box-shadow:0 2px 8px rgba(239,68,68,.08);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#b91c1c;border-radius:99px;padding:3px 10px;font-size:.72rem;font-weight:800;">
                            <span style="width:6px;height:6px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                            EN PAUSE
                        </span>
                        <strong style="font-size:.95rem;color:#111827;">{{ $r->name }}</strong>
                        <span style="font-size:.78rem;color:#9ca3af;">{{ $r->city }}</span>
                    </div>
                    @if($r->pause_reason)
                    <div style="font-size:.82rem;color:#374151;margin-bottom:4px;">
                        <strong>Raison :</strong> {{ $r->pause_reason }}
                    </div>
                    @endif
                    @if($r->paused_until)
                    <div style="font-size:.78rem;color:#9ca3af;">
                        Reprend le {{ \Carbon\Carbon::parse($r->paused_until)->format('d/m/Y à H:i') }}
                    </div>
                    @else
                    <div style="font-size:.78rem;color:#9ca3af;">Pause indefinie</div>
                    @endif
                    @if($r->orders->isNotEmpty())
                    <div style="margin-top:8px;font-size:.78rem;color:#d97706;font-weight:700;">
                        ⚠️ {{ $r->orders->count() }} commande(s) en attente
                    </div>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('admin.restaurant.show', $r->id) }}" style="padding:7px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-size:.78rem;font-weight:700;text-decoration:none;">
                        Détails
                    </a>
                    <form method="POST" action="{{ route('admin.restaurants.force_resume', $r->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="padding:7px 14px;border-radius:8px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;">
                            ▶ Reprendre
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Force-pause un restaurant actif --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #f0f0f0;padding:24px;">
        <h2 style="font-size:.9rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin:0 0 16px;">Mettre en pause un restaurant</h2>
        <form method="POST" id="forcePauseForm" action="#" style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:12px;align-items:end;">
            @csrf
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:4px;">Restaurant</label>
                <select name="_restaurant_id" id="pauseRestaurantSelect" style="width:100%;border:1px solid #e5e7eb;border-radius:9px;padding:9px 12px;font-size:.82rem;" required>
                    <option value="">Sélectionner...</option>
                    @foreach($all as $r)
                    <option value="{{ $r->id }}" data-url="{{ route('admin.restaurants.force_pause', $r->id) }}">
                        {{ $r->name }} — {{ $r->city }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:4px;">Raison</label>
                <input type="text" name="reason" placeholder="Ex: Problème d'approvisionnement" style="width:100%;border:1px solid #e5e7eb;border-radius:9px;padding:9px 12px;font-size:.82rem;">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:4px;">Jusqu'au (optionnel)</label>
                <input type="datetime-local" name="paused_until" style="border:1px solid #e5e7eb;border-radius:9px;padding:9px 12px;font-size:.82rem;">
            </div>
            <button type="submit" style="padding:10px 18px;border-radius:9px;background:#e85d04;color:#fff;font-size:.82rem;font-weight:700;border:none;cursor:pointer;white-space:nowrap;">
                Mettre en pause
            </button>
        </form>
    </div>

</div>

@endsection

@section('scripts')
<script>
document.getElementById('pauseRestaurantSelect').addEventListener('change', function() {
    var url = this.options[this.selectedIndex].dataset.url;
    if (url) document.getElementById('forcePauseForm').action = url;
});
</script>
@endsection
