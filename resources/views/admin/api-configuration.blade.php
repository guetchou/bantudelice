@extends('layouts.admin-modern')
@section('title', 'Configuration API | Admin')
@section('page_title', 'API & Intégrations')
@section('nav_active', 'api-config')

@section('style')
<style>
/* =========================================================
   API Configuration — scoped custom styles (.api-*)
   No Bootstrap classes used below this line.
   ========================================================= */

.api-page {
    padding: 24px;
    display: grid;
    gap: 20px;
}

/* ---- Cards ---- */
.api-card {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(15,23,42,.08);
    background: #fff;
    box-shadow: 0 14px 30px rgba(15,23,42,.05);
}
.api-card__head {
    padding: 14px 20px;
    border-bottom: 1px solid #f3f4f6;
}
.api-card__head--primary   { background: #0f172a; color: #fff; }
.api-card__head--warning   { background: #f59e0b; color: #1c1917; }
.api-card__head--info      { background: #0ea5e9; color: #fff; }
.api-card__head--success   { background: #16a34a; color: #fff; }
.api-card__head--secondary { background: #6b7280; color: #fff; }
.api-card__body { padding: 18px 20px; }
.api-card__foot { padding: 14px 20px; border-top: 1px solid #f3f4f6; }

/* ---- Status grid ---- */
.api-status-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.api-status-item {
    padding: 14px 16px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid rgba(15,23,42,.06);
    display: flex;
    align-items: center;
    gap: 12px;
}
.api-status-item__text { flex: 1; }
.api-status-item__label {
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 4px;
}
.api-status-item__value { font-weight: 700; }
.api-status-item__icon { font-size: 1.4rem; opacity: .7; }

.api-status-item--geo   { border-left: 4px solid #3b82f6; }
.api-status-item--sms   { border-left: 4px solid #f59e0b; }
.api-status-item--momo  { border-left: 4px solid #0ea5e9; }
.api-status-item--social{ border-left: 4px solid #16a34a; }
.api-status-item--email { border-left: 4px solid #ef4444; }

/* ---- Tabs ---- */
.api-tab-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    border-bottom: 2px solid #f3f4f6;
    padding-bottom: 0;
    margin-bottom: 20px;
}
.api-tab-btn {
    padding: 8px 16px;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    font-size: .85rem;
    font-weight: 700;
    color: #64748b;
    background: none;
    cursor: pointer;
    transition: color .15s, border-color .15s;
}
.api-tab-btn:hover { color: #0f172a; }
.api-tab-btn.is-active { color: #0f172a; border-bottom-color: #0f172a; }
.api-tab-panel { display: none; }
.api-tab-panel.is-active { display: block; }

/* ---- Collapse (details/summary) ---- */
.api-collapse { margin-bottom: 12px; border: 1px solid rgba(15,23,42,.08); border-radius: 12px; overflow: hidden; }
.api-collapse-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    font-weight: 700;
    font-size: .88rem;
    background: #f8fafc;
    list-style: none;
    user-select: none;
}
.api-collapse-trigger::-webkit-details-marker { display: none; }
.api-collapse-trigger::after {
    content: '▸';
    font-size: .75rem;
    color: #94a3b8;
    transition: transform .2s;
}
details[open] > .api-collapse-trigger::after { transform: rotate(90deg); }
.api-collapse-body { padding: 16px; background: #fff; }

/* ---- Two-column grids inside collapse ---- */
.api-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.api-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 16px; }
.api-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0 16px; }
.api-grid-6-3-3 { display: grid; grid-template-columns: 6fr 3fr 3fr; gap: 0 16px; }
.api-grid-full { display: grid; grid-template-columns: 1fr; }

/* ---- Forms ---- */
.api-field { margin-bottom: 16px; }
.api-label {
    font-size: .83rem;
    font-weight: 700;
    color: #374151;
    display: block;
    margin-bottom: 6px;
}
.api-label--required { color: #ef4444; margin-left: 2px; }
.api-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: .9rem;
    box-sizing: border-box;
    background: #fff;
    color: #0f172a;
}
.api-input:focus { border-color: #0f172a; outline: none; box-shadow: 0 0 0 2px rgba(15,23,42,.08); }
.api-hint {
    font-size: .76rem;
    color: #6b7280;
    margin-top: 4px;
    display: block;
}

/* ---- Checkbox ---- */
.api-check { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
.api-check__input { width: 16px; height: 16px; cursor: pointer; }
.api-check__label { font-size: .88rem; color: #374151; cursor: pointer; }

/* ---- Buttons ---- */
.api-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: .83rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: opacity .15s;
}
.api-btn:hover { opacity: .88; }
.api-btn--primary   { background: #0f172a; color: #fff; }
.api-btn--success   { background: #16a34a; color: #fff; }
.api-btn--secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.api-btn--sm { padding: 5px 10px; font-size: .78rem; }

/* ---- Button group ---- */
.api-btn-group { display: inline-flex; gap: 8px; flex-wrap: wrap; }

/* ---- Badges ---- */
.api-badge--success   { display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#dcfce7;color:#15803d; }
.api-badge--secondary { display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#f3f4f6;color:#374151; }
.api-badge--info      { display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#e0f2fe;color:#0369a1; }
.api-badge--warning   { display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#fef3c7;color:#b45309; }
.api-badge--danger    { display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#fee2e2;color:#b91c1c; }

/* ---- Alerts ---- */
.api-alert--info    { padding:12px 16px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:.84rem;margin-bottom:16px; }
.api-alert--warning { padding:12px 16px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:.84rem;margin-bottom:16px; }
.api-alert--danger  { padding:12px 16px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.84rem;margin-bottom:16px; }
.api-alert--success { padding:12px 16px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:.84rem;margin-bottom:16px; }
.api-alert--light   { padding:12px 16px;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb;color:#374151;font-size:.84rem;margin-bottom:16px; }
.api-alert--secondary { padding:12px 16px;border-radius:10px;background:#f3f4f6;border:1px solid #d1d5db;color:#374151;font-size:.84rem;margin-bottom:16px; }

/* ---- Result divs ---- */
.api-result { padding:10px 14px; border-radius:8px; margin-top:12px; font-size:.83rem; font-family:monospace; }

/* ---- Misc layout helpers ---- */
.api-row-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:8px; }
.api-text-center { text-align:center; }
.api-mt-12 { margin-top:12px; }
.api-mt-16 { margin-top:16px; }
.api-mt-20 { margin-top:20px; }
.api-mb-0  { margin-bottom:0; }
.api-mb-4  { margin-bottom:4px; }
.api-mb-8  { margin-bottom:8px; }
.api-mb-12 { margin-bottom:12px; }
.api-hr    { border:none; border-top:1px solid #e5e7eb; margin:24px 0; }

/* ---- Social cards ---- */
.api-social-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.api-social-card { text-align:center; padding:24px 16px; }
.api-social-icon-google   { font-size:2.5rem; color:#ef4444; margin-bottom:12px; }
.api-social-icon-facebook { font-size:2.5rem; color:#3b82f6; margin-bottom:12px; }

/* ---- Responsive ---- */
@media (max-width: 900px) {
    .api-status-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .api-grid-2, .api-grid-3, .api-grid-4, .api-grid-6-3-3 { grid-template-columns: 1fr; }
    .api-social-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .api-status-grid { grid-template-columns: 1fr 1fr; }
    .api-page { padding: 12px; }
}
</style>
@endsection

@section('content')
<div class="api-page">

    <!-- Main wrapper card -->
    <div class="api-card">
        <div class="api-card__head api-card__head--primary">
            <h4 style="margin:0 0 2px;">
                <i class="fas fa-cog"></i> Configuration des API Externes
            </h4>
            <small>Configurez et testez les services API de la plateforme</small>
        </div>
        <div class="api-card__body">

            <!-- Status Cards -->
            <div class="api-status-grid">
                <div class="api-status-item api-status-item--geo">
                    <div class="api-status-item__text">
                        <div class="api-status-item__label" style="color:#3b82f6;">Géolocalisation</div>
                        <div class="api-status-item__value" id="geo-status">
                            <span class="api-badge--success">OpenStreetMap</span>
                        </div>
                    </div>
                    <div class="api-status-item__icon" style="color:#3b82f6;">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                </div>

                <div class="api-status-item api-status-item--sms">
                    <div class="api-status-item__text">
                        <div class="api-status-item__label" style="color:#f59e0b;">SMS</div>
                        <div class="api-status-item__value" id="sms-status">
                            <span class="api-badge--secondary">Mode Démo</span>
                        </div>
                    </div>
                    <div class="api-status-item__icon" style="color:#f59e0b;">
                        <i class="fas fa-sms fa-2x"></i>
                    </div>
                </div>

                <div class="api-status-item api-status-item--momo">
                    <div class="api-status-item__text">
                        <div class="api-status-item__label" style="color:#0ea5e9;">Mobile Money</div>
                        <div class="api-status-item__value" id="momo-status">
                            <span class="api-badge--secondary">Mode Démo</span>
                        </div>
                    </div>
                    <div class="api-status-item__icon" style="color:#0ea5e9;">
                        <i class="fas fa-mobile-alt fa-2x"></i>
                    </div>
                </div>

                <div class="api-status-item api-status-item--social">
                    <div class="api-status-item__text">
                        <div class="api-status-item__label" style="color:#16a34a;">Social Auth</div>
                        <div class="api-status-item__value" id="social-status">
                            <span class="api-badge--secondary">Non configuré</span>
                        </div>
                    </div>
                    <div class="api-status-item__icon" style="color:#16a34a;">
                        <i class="fas fa-sign-in-alt fa-2x"></i>
                    </div>
                </div>

                <div class="api-status-item api-status-item--email">
                    <div class="api-status-item__text">
                        <div class="api-status-item__label" style="color:#ef4444;">Email/SMTP</div>
                        <div class="api-status-item__value" id="email-status">
                            <span class="api-badge--secondary">Non configuré</span>
                        </div>
                    </div>
                    <div class="api-status-item__icon" style="color:#ef4444;">
                        <i class="fas fa-envelope fa-2x"></i>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="api-alert--info">
                <h5 style="margin:0 0 8px;"><i class="fas fa-info-circle"></i> Instructions</h5>
                <ol style="margin:0;padding-left:20px;">
                    <li>Ajoutez vos clés API dans l'onglet <strong>"Configuration"</strong> ci-dessous</li>
                    <li>Ou modifiez directement le fichier <code>.env</code> (voir guide ci-dessous)</li>
                    <li>Videz le cache après modification : <button class="api-btn api-btn--secondary api-btn--sm" onclick="clearCache()">Vider le cache</button></li>
                    <li>Testez chaque service avec les onglets ci-dessous</li>
                </ol>
            </div>

            <!-- Tabs nav -->
            <div class="api-tab-nav" id="apiTabs">
                <button class="api-tab-btn is-active" data-tab="configuration" onclick="showApiTab('configuration')">
                    <i class="fas fa-key"></i> Configuration
                </button>
                <button class="api-tab-btn" data-tab="geolocation" onclick="showApiTab('geolocation')">
                    <i class="fas fa-map-marker-alt"></i> Géolocalisation
                </button>
                <button class="api-tab-btn" data-tab="sms" onclick="showApiTab('sms')">
                    <i class="fas fa-sms"></i> SMS
                </button>
                <button class="api-tab-btn" data-tab="momo" onclick="showApiTab('momo')">
                    <i class="fas fa-mobile-alt"></i> Mobile Money
                </button>
                <button class="api-tab-btn" data-tab="social" onclick="showApiTab('social')">
                    <i class="fas fa-sign-in-alt"></i> Social Auth
                </button>
                <button class="api-tab-btn" data-tab="email" onclick="showApiTab('email')">
                    <i class="fas fa-envelope"></i> Email/SMTP
                </button>
            </div>

            <!-- ======================== CONFIGURATION TAB ======================== -->
            <div class="api-tab-panel is-active" data-tab="configuration">
                <div class="api-card">
                    <div class="api-card__head api-card__head--warning">
                        <h5 style="margin:0 0 2px;"><i class="fas fa-key"></i> Configuration des Clés API</h5>
                        <small>Ajoutez ou modifiez vos clés API directement depuis cette interface</small>
                    </div>
                    <div class="api-card__body">
                        <form id="api-keys-form">
                            @csrf

                            <!-- Google Maps -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-map-marker-alt"></i> Google Maps (Optionnel)</span>
                                    <span class="api-badge--info" style="margin-right:20px;">OpenStreetMap fonctionne gratuitement</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-field">
                                        <label class="api-label">GOOGLE_MAPS_API_KEY</label>
                                        <input type="text" name="keys[GOOGLE_MAPS_API_KEY]"
                                               class="api-input api-key-input"
                                               placeholder="Votre clé API Google Maps"
                                               id="key-GOOGLE_MAPS_API_KEY">
                                        <span class="api-hint">Optionnel. OpenStreetMap fonctionne déjà gratuitement.</span>
                                    </div>
                                </div>
                            </details>

                            <!-- SMS - Twilio -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-sms"></i> SMS - Twilio</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">TWILIO_SID</label>
                                            <input type="text" name="keys[TWILIO_SID]"
                                                   class="api-input api-key-input"
                                                   placeholder="ACxxxxxxxxxxxxx"
                                                   id="key-TWILIO_SID">
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">TWILIO_TOKEN</label>
                                            <input type="password" name="keys[TWILIO_TOKEN]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre token secret"
                                                   id="key-TWILIO_TOKEN">
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">TWILIO_FROM</label>
                                            <input type="text" name="keys[TWILIO_FROM]"
                                                   class="api-input api-key-input"
                                                   placeholder="+242064000000"
                                                   id="key-TWILIO_FROM">
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">TWILIO_VERIFY_SID (Optionnel)</label>
                                            <input type="text" name="keys[TWILIO_VERIFY_SID]"
                                                   class="api-input api-key-input"
                                                   placeholder="VAxxxxxxxxxxxxx"
                                                   id="key-TWILIO_VERIFY_SID">
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- SMS - Africa's Talking -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-sms"></i> SMS - Africa's Talking</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-grid-3">
                                        <div class="api-field">
                                            <label class="api-label">AFRICASTALKING_USERNAME</label>
                                            <input type="text" name="keys[AFRICASTALKING_USERNAME]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre username"
                                                   id="key-AFRICASTALKING_USERNAME">
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">AFRICASTALKING_API_KEY</label>
                                            <input type="password" name="keys[AFRICASTALKING_API_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre API key"
                                                   id="key-AFRICASTALKING_API_KEY">
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">AFRICASTALKING_FROM</label>
                                            <input type="text" name="keys[AFRICASTALKING_FROM]"
                                                   class="api-input api-key-input"
                                                   placeholder="Plateforme"
                                                   id="key-AFRICASTALKING_FROM">
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- SMS - BulkGate -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-sms"></i> SMS - BulkGate</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-alert--info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>BulkGate:</strong> Service SMS européen. Vous devez disposer de votre Application ID et API Key depuis votre compte BulkGate.
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">BULKGATE_APPLICATION_ID</label>
                                            <input type="text" name="keys[BULKGATE_APPLICATION_ID]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre Application ID"
                                                   id="key-BULKGATE_APPLICATION_ID">
                                            <span class="api-hint">ID de votre application BulkGate</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">BULKGATE_API_KEY</label>
                                            <input type="password" name="keys[BULKGATE_API_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre API Key"
                                                   id="key-BULKGATE_API_KEY">
                                            <span class="api-hint">Clé API de votre compte BulkGate</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">BULKGATE_SENDER_ID</label>
                                            <input type="text" name="keys[BULKGATE_SENDER_ID]"
                                                   class="api-input api-key-input"
                                                   placeholder="Plateforme"
                                                   value="Plateforme"
                                                   id="key-BULKGATE_SENDER_ID">
                                            <span class="api-hint">Nom de l'expéditeur (max 11 caractères)</span>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- Mobile Money - MoMo -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-mobile-alt"></i> Mobile Money - MoMo</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <p style="color:#6b7280;margin-bottom:12px;font-size:.85rem;">
                                        Configurez les identifiants officiels MTN MoMo.
                                        Les clés `primary` et `secondary` sont des subscription keys interchangeables.
                                        Les clés `collections` servent aux encaissements et les clés `disbursements` aux décaissements.
                                    </p>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_COLLECTIONS_PRIMARY_KEY</label>
                                            <input type="text" name="keys[MOMO_COLLECTIONS_PRIMARY_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Primary Subscription Key Collections"
                                                   id="key-MOMO_COLLECTIONS_PRIMARY_KEY">
                                            <span class="api-hint">Subscription key primaire pour les encaissements MoMo</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_COLLECTIONS_SECONDARY_KEY</label>
                                            <input type="text" name="keys[MOMO_COLLECTIONS_SECONDARY_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Secondary Subscription Key Collections"
                                                   id="key-MOMO_COLLECTIONS_SECONDARY_KEY">
                                            <span class="api-hint">Subscription key secondaire pour les encaissements MoMo</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_COLLECTIONS_SUBSCRIPTION_KEY</label>
                                            <input type="text" name="keys[MOMO_COLLECTIONS_SUBSCRIPTION_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Subscription Key Collections"
                                                   id="key-MOMO_COLLECTIONS_SUBSCRIPTION_KEY">
                                            <span class="api-hint">Override explicite de la subscription key utilisee a la place de primary/secondary</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_COLLECTIONS_API_USER</label>
                                            <input type="text" name="keys[MOMO_COLLECTIONS_API_USER]"
                                                   class="api-input api-key-input"
                                                   placeholder="API User Collections"
                                                   id="key-MOMO_COLLECTIONS_API_USER">
                                            <span class="api-hint">API user pour les encaissements MoMo</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_COLLECTIONS_API_KEY</label>
                                            <input type="password" name="keys[MOMO_COLLECTIONS_API_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="API Key Collections"
                                                   id="key-MOMO_COLLECTIONS_API_KEY">
                                            <span class="api-hint">API key pour les encaissements MoMo</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_CALLBACK_URL</label>
                                            <input type="text" name="keys[MOMO_CALLBACK_URL]"
                                                   class="api-input api-key-input"
                                                   placeholder="https://votresite.com/api/payments/callback/momo"
                                                   id="key-MOMO_CALLBACK_URL">
                                            <span class="api-hint">URL de callback utilisée pour les notifications MTN</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_DISBURSEMENTS_PRIMARY_KEY</label>
                                            <input type="text" name="keys[MOMO_DISBURSEMENTS_PRIMARY_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Primary Subscription Key Disbursements"
                                                   id="key-MOMO_DISBURSEMENTS_PRIMARY_KEY">
                                            <span class="api-hint">Subscription key primaire pour les decaissements MoMo</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_DISBURSEMENTS_SECONDARY_KEY</label>
                                            <input type="text" name="keys[MOMO_DISBURSEMENTS_SECONDARY_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Secondary Subscription Key Disbursements"
                                                   id="key-MOMO_DISBURSEMENTS_SECONDARY_KEY">
                                            <span class="api-hint">Subscription key secondaire pour les decaissements MoMo</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_DISBURSEMENTS_SUBSCRIPTION_KEY</label>
                                            <input type="text" name="keys[MOMO_DISBURSEMENTS_SUBSCRIPTION_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="Subscription Key Disbursements"
                                                   id="key-MOMO_DISBURSEMENTS_SUBSCRIPTION_KEY">
                                            <span class="api-hint">Override explicite de la subscription key utilisee a la place de primary/secondary</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_DISBURSEMENTS_API_USER</label>
                                            <input type="text" name="keys[MOMO_DISBURSEMENTS_API_USER]"
                                                   class="api-input api-key-input"
                                                   placeholder="API User Disbursements"
                                                   id="key-MOMO_DISBURSEMENTS_API_USER">
                                            <span class="api-hint">API user pour les décaissements MoMo</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_DISBURSEMENTS_API_KEY</label>
                                            <input type="password" name="keys[MOMO_DISBURSEMENTS_API_KEY]"
                                                   class="api-input api-key-input"
                                                   placeholder="API Key Disbursements"
                                                   id="key-MOMO_DISBURSEMENTS_API_KEY">
                                            <span class="api-hint">API key pour les décaissements MoMo</span>
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">MOMO_ENVIRONMENT</label>
                                            <input type="text" name="keys[MOMO_ENVIRONMENT]"
                                                   class="api-input api-key-input"
                                                   placeholder="sandbox"
                                                   id="key-MOMO_ENVIRONMENT">
                                            <span class="api-hint">Valeurs attendues: sandbox ou production</span>
                                        </div>
                                    </div>
                                    <div class="api-grid-full">
                                        <div class="api-field">
                                            <label class="api-label">MOMO_TARGET_ENVIRONMENT</label>
                                            <input type="text" name="keys[MOMO_TARGET_ENVIRONMENT]"
                                                   class="api-input api-key-input"
                                                   placeholder="sandbox"
                                                   id="key-MOMO_TARGET_ENVIRONMENT">
                                            <span class="api-hint">Sandbox pour les tests, ou la valeur cible fournie par MTN en production</span>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- Mobile Money - Airtel -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fas fa-mobile-alt"></i> Mobile Money - Airtel Money</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-grid-2">
                                        <div class="api-field">
                                            <label class="api-label">AIRTEL_MONEY_CLIENT_ID</label>
                                            <input type="text" name="keys[AIRTEL_MONEY_CLIENT_ID]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre Client ID"
                                                   id="key-AIRTEL_MONEY_CLIENT_ID">
                                        </div>
                                        <div class="api-field">
                                            <label class="api-label">AIRTEL_MONEY_CLIENT_SECRET</label>
                                            <input type="password" name="keys[AIRTEL_MONEY_CLIENT_SECRET]"
                                                   class="api-input api-key-input"
                                                   placeholder="Votre Client Secret"
                                                   id="key-AIRTEL_MONEY_CLIENT_SECRET">
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- Social Auth - Google -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fab fa-google"></i> Authentification Sociale - Google</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-grid-2">
                                        <div>
                                            <div class="api-field">
                                                <label class="api-label">GOOGLE_AUTH_ENABLED</label>
                                                <input type="text" name="keys[GOOGLE_AUTH_ENABLED]"
                                                       class="api-input api-key-input"
                                                       placeholder="true"
                                                       id="key-GOOGLE_AUTH_ENABLED">
                                                <span class="api-hint">Mettre <code>true</code> pour activer Google Sign-In.</span>
                                            </div>
                                            <div class="api-field">
                                                <label class="api-label">GOOGLE_CLIENT_ID</label>
                                                <input type="text" name="keys[GOOGLE_CLIENT_ID]"
                                                       class="api-input api-key-input"
                                                       placeholder="xxx.apps.googleusercontent.com"
                                                       id="key-GOOGLE_CLIENT_ID">
                                            </div>
                                            <div class="api-field">
                                                <label class="api-label">GOOGLE_REDIRECT_URI</label>
                                                <input type="text" name="keys[GOOGLE_REDIRECT_URI]"
                                                       class="api-input api-key-input"
                                                       placeholder="{{ $socialAuthHints['google']['callback_uri'] }}"
                                                       id="key-GOOGLE_REDIRECT_URI">
                                                <span class="api-hint">URI de callback exacte à déclarer dans Google Cloud.</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="api-field">
                                                <label class="api-label">GOOGLE_CLIENT_SECRET</label>
                                                <input type="password" name="keys[GOOGLE_CLIENT_SECRET]"
                                                       class="api-input api-key-input"
                                                       placeholder="Votre Client Secret"
                                                       id="key-GOOGLE_CLIENT_SECRET">
                                            </div>
                                            <div class="api-alert--light">
                                                <div><strong>Origine JavaScript autorisée</strong></div>
                                                @foreach($socialAuthHints['google']['authorized_origins'] as $origin)
                                                    <div><code>{{ $origin }}</code></div>
                                                @endforeach
                                                <div style="margin-top:8px;"><strong>Redirect URI autorisée</strong></div>
                                                <div><code>{{ $socialAuthHints['google']['callback_uri'] }}</code></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- Social Auth - Facebook -->
                            <details class="api-collapse">
                                <summary class="api-collapse-trigger">
                                    <span><i class="fab fa-facebook"></i> Authentification Sociale - Facebook</span>
                                </summary>
                                <div class="api-collapse-body">
                                    <div class="api-grid-2">
                                        <div>
                                            <div class="api-field">
                                                <label class="api-label">FACEBOOK_AUTH_ENABLED</label>
                                                <input type="text" name="keys[FACEBOOK_AUTH_ENABLED]"
                                                       class="api-input api-key-input"
                                                       placeholder="true"
                                                       id="key-FACEBOOK_AUTH_ENABLED">
                                                <span class="api-hint">Mettre <code>true</code> pour activer Facebook Login.</span>
                                            </div>
                                            <div class="api-field">
                                                <label class="api-label">FACEBOOK_CLIENT_ID</label>
                                                <input type="text" name="keys[FACEBOOK_CLIENT_ID]"
                                                       class="api-input api-key-input"
                                                       placeholder="Votre App ID"
                                                       id="key-FACEBOOK_CLIENT_ID">
                                            </div>
                                            <div class="api-field">
                                                <label class="api-label">FACEBOOK_REDIRECT_URI</label>
                                                <input type="text" name="keys[FACEBOOK_REDIRECT_URI]"
                                                       class="api-input api-key-input"
                                                       placeholder="{{ $socialAuthHints['facebook']['callback_uri'] }}"
                                                       id="key-FACEBOOK_REDIRECT_URI">
                                                <span class="api-hint">URI de callback exacte à déclarer dans Meta Developers.</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="api-field">
                                                <label class="api-label">FACEBOOK_CLIENT_SECRET</label>
                                                <input type="password" name="keys[FACEBOOK_CLIENT_SECRET]"
                                                       class="api-input api-key-input"
                                                       placeholder="Votre App Secret"
                                                       id="key-FACEBOOK_CLIENT_SECRET">
                                            </div>
                                            <div class="api-alert--light">
                                                <div><strong>Site/App Domain</strong></div>
                                                @foreach($socialAuthHints['facebook']['authorized_origins'] as $origin)
                                                    <div><code>{{ preg_replace('#^https?://#', '', $origin) }}</code></div>
                                                @endforeach
                                                <div style="margin-top:8px;"><strong>Redirect URI autorisée</strong></div>
                                                <div><code>{{ $socialAuthHints['facebook']['callback_uri'] }}</code></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <div class="api-alert--warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> Après avoir sauvegardé vos clés, n'oubliez pas de vider le cache!
                            </div>

                            <div class="api-row-actions">
                                <button type="button" class="api-btn api-btn--secondary" onclick="loadApiKeys()">
                                    <i class="fas fa-sync"></i> Recharger
                                </button>
                                <button type="submit" class="api-btn api-btn--primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Clés
                                </button>
                            </div>
                        </form>

                        <div id="save-result" class="api-mt-12"></div>
                    </div>
                </div>
            </div>

            <!-- ======================== GÉOLOCALISATION TAB ======================== -->
            <div class="api-tab-panel" data-tab="geolocation">
                <div class="api-card">
                    <div class="api-card__head">
                        <h5 style="margin:0;"><i class="fas fa-map-marker-alt"></i> Test Géolocalisation</h5>
                    </div>
                    <div class="api-card__body">
                        <div class="api-grid-2">
                            <div>
                                <h6>Géocodage d'adresse</h6>
                                <div class="api-field">
                                    <label class="api-label">Adresse</label>
                                    <input type="text" id="geocode-address" class="api-input"
                                           placeholder="Ex: Centre-ville, Brazzaville">
                                </div>
                                <button class="api-btn api-btn--primary" onclick="testGeocode()">
                                    <i class="fas fa-search"></i> Géocoder
                                </button>
                            </div>
                            <div>
                                <h6>Géocodage inverse</h6>
                                <div class="api-grid-2">
                                    <div class="api-field">
                                        <label class="api-label">Latitude</label>
                                        <input type="number" id="reverse-lat" class="api-input"
                                               step="0.000001" value="-4.2767">
                                    </div>
                                    <div class="api-field">
                                        <label class="api-label">Longitude</label>
                                        <input type="number" id="reverse-lng" class="api-input"
                                               step="0.000001" value="15.2832">
                                    </div>
                                </div>
                                <button class="api-btn api-btn--primary" onclick="testReverseGeocode()">
                                    <i class="fas fa-map"></i> Obtenir l'adresse
                                </button>
                            </div>
                        </div>

                        <hr class="api-hr">

                        <div class="api-field">
                            <label class="api-label">Calcul de distance</label>
                            <div class="api-grid-4" style="margin-bottom:10px;">
                                <input type="number" id="distance-lat1" class="api-input"
                                       placeholder="Lat 1" value="-4.2767" step="0.000001">
                                <input type="number" id="distance-lng1" class="api-input"
                                       placeholder="Lng 1" value="15.2832" step="0.000001">
                                <input type="number" id="distance-lat2" class="api-input"
                                       placeholder="Lat 2" value="-4.2700" step="0.000001">
                                <input type="number" id="distance-lng2" class="api-input"
                                       placeholder="Lng 2" value="15.2600" step="0.000001">
                            </div>
                            <button class="api-btn api-btn--primary" onclick="testDistance()">
                                <i class="fas fa-ruler"></i> Calculer la distance
                            </button>
                        </div>

                        <div id="geolocation-result" class="api-mt-12"></div>
                    </div>
                </div>
            </div>

            <!-- ======================== SMS TAB ======================== -->
            <div class="api-tab-panel" data-tab="sms">
                <div class="api-card">
                    <div class="api-card__head">
                        <h5 style="margin:0;"><i class="fas fa-sms"></i> Test SMS</h5>
                    </div>
                    <div class="api-card__body">
                        <div class="api-field">
                            <label class="api-label">Numéro de téléphone</label>
                            <input type="tel" id="sms-phone" class="api-input"
                                   placeholder="+242 06 XXX XX XX" value="+242064000000">
                            <span class="api-hint">Format international requis</span>
                        </div>

                        <div class="api-field">
                            <label class="api-label">Message (optionnel pour OTP)</label>
                            <textarea id="sms-message" class="api-input" rows="3"
                                      placeholder="Laissez vide pour tester l'OTP"></textarea>
                        </div>

                        <div class="api-btn-group">
                            <button class="api-btn api-btn--primary" onclick="testSms()">
                                <i class="fas fa-paper-plane"></i> Envoyer SMS
                            </button>
                            <button class="api-btn api-btn--success" onclick="testOtp()">
                                <i class="fas fa-key"></i> Envoyer OTP
                            </button>
                        </div>

                        <div id="sms-result" class="api-mt-12"></div>
                    </div>
                </div>
            </div>

            <!-- ======================== MOBILE MONEY TAB ======================== -->
            <div class="api-tab-panel" data-tab="momo">
                <div class="api-card">
                    <div class="api-card__head">
                        <h5 style="margin:0;"><i class="fas fa-mobile-alt"></i> Test Mobile Money</h5>
                    </div>
                    <div class="api-card__body">
                        <div class="api-alert--warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Mode démo:</strong> Les paiements sont simulés. Configurez les clés API pour activer les paiements réels.
                        </div>

                        <div class="api-field">
                            <label class="api-label">Numéro de téléphone</label>
                            <input type="tel" id="momo-phone" class="api-input"
                                   placeholder="+242 06 XXX XX XX" value="+242064000000">
                        </div>

                        <div class="api-grid-2">
                            <div class="api-field">
                                <label class="api-label">Montant (FCFA)</label>
                                <input type="number" id="momo-amount" class="api-input"
                                       value="5000" min="100">
                            </div>
                            <div class="api-field">
                                <label class="api-label">Opérateur</label>
                                <select id="momo-operator" class="api-input">
                                    <option value="mtn">MTN MoMo</option>
                                    <option value="airtel">Airtel Money</option>
                                </select>
                            </div>
                        </div>

                        <button class="api-btn api-btn--primary" onclick="testMobileMoney()">
                            <i class="fas fa-credit-card"></i> Tester le paiement
                        </button>

                        <div id="momo-result" class="api-mt-12"></div>
                    </div>
                </div>
            </div>

            <!-- ======================== SOCIAL AUTH TAB ======================== -->
            <div class="api-tab-panel" data-tab="social">
                <div class="api-card">
                    <div class="api-card__head">
                        <h5 style="margin:0;"><i class="fas fa-sign-in-alt"></i> Authentification Sociale</h5>
                    </div>
                    <div class="api-card__body">
                        <div class="api-alert--info">
                            <h6 style="margin:0 0 6px;">Configuration requise:</h6>
                            <ul style="margin:0 0 6px;padding-left:20px;">
                                <li><strong>Google:</strong> Créez un projet sur <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
                                <li><strong>Facebook:</strong> Créez une app sur <a href="https://developers.facebook.com" target="_blank">Facebook Developers</a></li>
                            </ul>
                            <p style="margin:0;">Voir le guide complet dans <code>docs/GUIDE_CONFIGURATION_API.md</code></p>
                        </div>

                        <div class="api-social-grid">
                            <div class="api-card">
                                <div class="api-card__body api-social-card">
                                    <div class="api-social-icon-google"><i class="fab fa-google fa-3x"></i></div>
                                    <h5>Google Sign-In</h5>
                                    <p style="color:#6b7280;margin-bottom:8px;">Authentification via Google</p>
                                    <span id="google-status" class="api-badge--secondary">Non configuré</span>
                                </div>
                            </div>
                            <div class="api-card">
                                <div class="api-card__body api-social-card">
                                    <div class="api-social-icon-facebook"><i class="fab fa-facebook fa-3x"></i></div>
                                    <h5>Facebook Login</h5>
                                    <p style="color:#6b7280;margin-bottom:8px;">Authentification via Facebook</p>
                                    <span id="facebook-status" class="api-badge--secondary">Non configuré</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== EMAIL/SMTP TAB ======================== -->
            <div class="api-tab-panel" data-tab="email">
                <div class="api-card">
                    <div class="api-card__head api-card__head--info">
                        <h5 style="margin:0 0 2px;"><i class="fas fa-envelope"></i> Configuration Email/SMTP</h5>
                        <small>Configurez les paramètres SMTP pour l'envoi d'emails</small>
                    </div>
                    <div class="api-card__body">
                        <!-- Status Card -->
                        <div id="email-status-alert" class="api-alert--info">
                            <i class="fas fa-spinner fa-spin"></i> Chargement du statut...
                        </div>

                        <!-- Configuration Form -->
                        <form id="mail-config-form">
                            @csrf

                            <div class="api-grid-6-3-3">
                                <div class="api-field">
                                    <label class="api-label">Serveur SMTP (MAIL_HOST) <span class="api-label--required">*</span></label>
                                    <input type="text" name="MAIL_HOST" id="mail-host"
                                           class="api-input"
                                           placeholder="mail.bantudelice.cg"
                                           required>
                                    <span class="api-hint">Adresse du serveur SMTP</span>
                                </div>
                                <div class="api-field">
                                    <label class="api-label">Port SMTP (MAIL_PORT) <span class="api-label--required">*</span></label>
                                    <input type="number" name="MAIL_PORT" id="mail-port"
                                           class="api-input"
                                           placeholder="465"
                                           value="465"
                                           min="1" max="65535" required>
                                    <span class="api-hint">Port SMTP (465 pour SSL, 587 pour TLS)</span>
                                </div>
                                <div class="api-field">
                                    <label class="api-label">Chiffrement (MAIL_ENCRYPTION)</label>
                                    <select name="MAIL_ENCRYPTION" id="mail-encryption" class="api-input">
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                    </select>
                                    <span class="api-hint">SSL pour port 465, TLS pour port 587</span>
                                </div>
                            </div>

                            <div class="api-grid-2">
                                <div class="api-field">
                                    <label class="api-label">Nom d'utilisateur (MAIL_USERNAME) <span class="api-label--required">*</span></label>
                                    <input type="text" name="MAIL_USERNAME" id="mail-username"
                                           class="api-input"
                                           placeholder="noreply@bantudelice.cg"
                                           required>
                                    <span class="api-hint">Adresse email ou nom d'utilisateur SMTP</span>
                                </div>
                                <div class="api-field">
                                    <label class="api-label">Mot de passe (MAIL_PASSWORD) <span class="api-label--required">*</span></label>
                                    <input type="password" name="MAIL_PASSWORD" id="mail-password"
                                           class="api-input"
                                           placeholder="Votre mot de passe SMTP"
                                           required>
                                    <span class="api-hint">Mot de passe SMTP</span>
                                </div>
                            </div>

                            <div class="api-grid-2">
                                <div class="api-field">
                                    <label class="api-label">Adresse expéditeur (MAIL_FROM_ADDRESS) <span class="api-label--required">*</span></label>
                                    <input type="email" name="MAIL_FROM_ADDRESS" id="mail-from-address"
                                           class="api-input"
                                           placeholder="noreply@bantudelice.cg"
                                           required>
                                    <span class="api-hint">Adresse email d'expédition</span>
                                </div>
                                <div class="api-field">
                                    <label class="api-label">Nom expéditeur (MAIL_FROM_NAME)</label>
                                    <input type="text" name="MAIL_FROM_NAME" id="mail-from-name"
                                           class="api-input"
                                           placeholder="Plateforme"
                                           value="Plateforme">
                                    <span class="api-hint">Nom affiché comme expéditeur</span>
                                </div>
                            </div>

                            <div class="api-check">
                                <input type="checkbox" name="MAIL_ENABLED" id="mail-enabled"
                                       class="api-check__input" value="1" checked>
                                <label class="api-check__label" for="mail-enabled">
                                    Activer l'envoi d'emails (MAIL_ENABLED)
                                </label>
                            </div>

                            <div class="api-alert--warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> Après avoir sauvegardé, n'oubliez pas de vider le cache!
                            </div>

                            <div class="api-row-actions">
                                <button type="button" class="api-btn api-btn--secondary" onclick="loadMailConfig()">
                                    <i class="fas fa-sync"></i> Recharger
                                </button>
                                <button type="submit" class="api-btn api-btn--primary">
                                    <i class="fas fa-save"></i> Sauvegarder la Configuration
                                </button>
                            </div>
                        </form>

                        <div id="mail-save-result" class="api-mt-12"></div>

                        <hr class="api-hr">

                        <!-- Test Email Section -->
                        <div class="api-card">
                            <div class="api-card__head api-card__head--success">
                                <h5 style="margin:0;"><i class="fas fa-paper-plane"></i> Test d'Envoi d'Email</h5>
                            </div>
                            <div class="api-card__body">
                                <div class="api-field">
                                    <label class="api-label">Adresse email de destination</label>
                                    <input type="email" id="test-email-to" class="api-input"
                                           placeholder="test@example.com" required>
                                    <span class="api-hint">L'email de test sera envoyé à cette adresse</span>
                                </div>

                                <div class="api-field">
                                    <label class="api-label">Sujet (optionnel)</label>
                                    <input type="text" id="test-email-subject" class="api-input"
                                           placeholder="Test Email - Plateforme">
                                </div>

                                <div class="api-field">
                                    <label class="api-label">Message (optionnel)</label>
                                    <textarea id="test-email-message" class="api-input" rows="3"
                                              placeholder="Ceci est un email de test depuis la plateforme..."></textarea>
                                </div>

                                <button class="api-btn api-btn--success" onclick="testEmail()">
                                    <i class="fas fa-paper-plane"></i> Envoyer l'Email de Test
                                </button>

                                <div id="test-email-result" class="api-mt-12"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.api-card__body -->
    </div><!-- /.api-card (main) -->

    <!-- Guide -->
    <div class="api-card">
        <div class="api-card__head api-card__head--secondary">
            <h5 style="margin:0;"><i class="fas fa-book"></i> Guide de Configuration</h5>
        </div>
        <div class="api-card__body">
            <p>Consultez le fichier <code>docs/GUIDE_CONFIGURATION_API.md</code> pour les instructions détaillées.</p>
            <p>Ou éditez directement le fichier <code>.env</code> avec vos clés API.</p>

            <h6>Variables principales à configurer:</h6>
            <ul>
                <li><code>GOOGLE_MAPS_API_KEY</code> - Pour Google Maps (optionnel, OpenStreetMap fonctionne)</li>
                <li><code>TWILIO_SID</code> et <code>TWILIO_TOKEN</code> - Pour SMS via Twilio</li>
                <li><code>AFRICASTALKING_USERNAME</code> et <code>AFRICASTALKING_API_KEY</code> - Pour SMS via Africa's Talking</li>
                <li><code>BULKGATE_APPLICATION_ID</code> et <code>BULKGATE_API_KEY</code> - Pour SMS via BulkGate</li>
                <li><code>MOMO_COLLECTIONS_PRIMARY_KEY</code> / <code>MOMO_COLLECTIONS_SECONDARY_KEY</code> ou <code>MOMO_COLLECTIONS_SUBSCRIPTION_KEY</code>, puis <code>MOMO_COLLECTIONS_API_USER</code> et <code>MOMO_COLLECTIONS_API_KEY</code> - Pour les encaissements MoMo</li>
                <li><code>MOMO_DISBURSEMENTS_PRIMARY_KEY</code> / <code>MOMO_DISBURSEMENTS_SECONDARY_KEY</code> ou <code>MOMO_DISBURSEMENTS_SUBSCRIPTION_KEY</code>, puis <code>MOMO_DISBURSEMENTS_API_USER</code> et <code>MOMO_DISBURSEMENTS_API_KEY</code> - Pour les décaissements MoMo</li>
                <li><code>MOMO_ENVIRONMENT</code> et <code>MOMO_TARGET_ENVIRONMENT</code> - Pour le mode d'exécution MoMo</li>
                <li><code>MOMO_CALLBACK_URL</code> - Pour les notifications MTN MoMo en environnement live</li>
                <li><code>AIRTEL_MONEY_CLIENT_ID</code> - Pour Airtel Money</li>
                <li><code>GOOGLE_CLIENT_ID</code> - Pour Google Sign-In</li>
                <li><code>FACEBOOK_CLIENT_ID</code> - Pour Facebook Login</li>
            </ul>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // ---- Vanilla JS tab switcher (replaces Bootstrap data-toggle="tab") ----
    function showApiTab(key) {
        document.querySelectorAll('.api-tab-btn').forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.tab === key);
        });
        document.querySelectorAll('.api-tab-panel').forEach(function(p) {
            p.classList.toggle('is-active', p.dataset.tab === key);
        });
    }

    // Charger le statut au chargement
    $(document).ready(function() {
        showApiTab('configuration');

        loadApiStatus();
        loadApiKeys();
        loadMailConfig();
        loadMailStatus();

        // Gérer la soumission du formulaire de clés
        $('#api-keys-form').on('submit', function(e) {
            e.preventDefault();
            saveApiKeys();
        });

        // Gérer la soumission du formulaire email
        $('#mail-config-form').on('submit', function(e) {
            e.preventDefault();
            saveMailConfig();
        });
    });

    // Charger les clés API existantes
    function loadApiKeys() {
        $.get('{{ route("admin.api.keys") }}')
            .done(function(data) {
                if (data.success && data.keys) {
                    // Remplir les champs avec les valeurs masquées ou vides
                    Object.keys(data.keys).forEach(function(key) {
                        const input = $('#key-' + key);
                        if (input.length) {
                            const currentValue = input.val();
                            const maskedValue = data.keys[key];

                            // Si le champ est vide ou contient des astérisques, ne pas remplacer
                            // Sinon, garder la valeur actuelle (l'utilisateur peut la modifier)
                            if (!currentValue || currentValue.includes('*')) {
                                // Ne pas remplacer, laisser vide pour que l'utilisateur saisisse
                            }
                        }
                    });
                }
            })
            .fail(function() {
                console.error('Erreur lors du chargement des clés');
            });
    }

    // Sauvegarder les clés API
    function saveApiKeys() {
        const formData = {};

        // Collecter toutes les valeurs des champs
        $('.api-key-input').each(function() {
            const key = $(this).attr('name').replace('keys[', '').replace(']', '');
            const value = $(this).val().trim();

            // Ne pas envoyer les valeurs vides
            if (value) {
                formData[key] = value;
            }
        });

        if (Object.keys(formData).length === 0) {
            alert('Aucune clé à sauvegarder. Remplissez au moins un champ.');
            return;
        }

        showLoading('#save-result');

        $.ajax({
            url: '{{ route("admin.api.keys.save") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                keys: formData
            },
            success: function(data) {
                if (data.success) {
                    showResult('#save-result', {
                        success: true,
                        message: data.message || 'Clés sauvegardées avec succès!',
                    });

                    // Recharger le statut
                    setTimeout(function() {
                        loadApiStatus();
                        clearCache();
                    }, 1000);
                } else {
                    showError('#save-result', data.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                showError('#save-result', xhr.responseJSON?.message || 'Erreur lors de la sauvegarde');
            }
        });
    }

    function loadApiStatus() {
        $.get('{{ route("admin.api.status") }}')
            .done(function(data) {
                if (data.success) {
                    updateStatusDisplay(data.status);
                }
            });
    }

    function updateStatusDisplay(status) {
        // Géolocalisation
        if (status.geolocation.google_maps.configured) {
            $('#geo-status').html('<span class="api-badge--success">Google Maps</span>');
        } else {
            $('#geo-status').html('<span class="api-badge--info">OpenStreetMap (Gratuit)</span>');
        }

        // SMS
        if (status.sms.twilio.configured) {
            $('#sms-status').html('<span class="api-badge--success">Twilio</span>');
        } else if (status.sms.africastalking.configured) {
            $('#sms-status').html('<span class="api-badge--success">Africa\'s Talking</span>');
        } else if (status.sms.bulkgate && status.sms.bulkgate.configured) {
            $('#sms-status').html('<span class="api-badge--success">BulkGate</span>');
        } else {
            $('#sms-status').html('<span class="api-badge--secondary">Mode Démo</span>');
        }

        // Mobile Money
        if (status.mobile_money.mtn_momo.configured) {
            $('#momo-status').html('<span class="api-badge--success">MTN MoMo</span>');
        } else if (status.mobile_money.airtel_money.configured) {
            $('#momo-status').html('<span class="api-badge--success">Airtel Money</span>');
        } else {
            $('#momo-status').html('<span class="api-badge--secondary">Mode Démo</span>');
        }

        // Social Auth
        let socialConfigured = false;
        if (status.social_auth.google.configured) {
            $('#google-status').removeClass('api-badge--secondary').addClass('api-badge--success').text('Configuré');
            socialConfigured = true;
        }
        if (status.social_auth.facebook.configured) {
            $('#facebook-status').removeClass('api-badge--secondary').addClass('api-badge--success').text('Configuré');
            socialConfigured = true;
        }
        if (socialConfigured) {
            $('#social-status').html('<span class="api-badge--success">Configuré</span>');
        }

        // Email
        if (status.email && status.email.smtp) {
            if (status.email.smtp.configured && status.email.smtp.enabled) {
                $('#email-status').html('<span class="api-badge--success">Configuré</span>');
            } else if (status.email.smtp.configured) {
                $('#email-status').html('<span class="api-badge--warning">Configuré (désactivé)</span>');
            } else {
                $('#email-status').html('<span class="api-badge--secondary">Non configuré</span>');
            }
        } else {
            $('#email-status').html('<span class="api-badge--secondary">Non configuré</span>');
        }
    }

    function testGeocode() {
        const address = $('#geocode-address').val();
        if (!address) {
            alert('Veuillez entrer une adresse');
            return;
        }

        showLoading('#geolocation-result');

        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            address: address
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function testReverseGeocode() {
        const lat = $('#reverse-lat').val();
        const lng = $('#reverse-lng').val();

        showLoading('#geolocation-result');

        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            lat: lat,
            lng: lng
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function testDistance() {
        const lat1 = $('#distance-lat1').val();
        const lng1 = $('#distance-lng1').val();
        const lat2 = $('#distance-lat2').val();
        const lng2 = $('#distance-lng2').val();

        showLoading('#geolocation-result');

        $.post('{{ route("admin.api.test.geolocation") }}', {
            _token: '{{ csrf_token() }}',
            lat: lat1,
            lng: lng1,
            lat2: lat2,
            lng2: lng2
        })
        .done(function(data) {
            showResult('#geolocation-result', data);
        })
        .fail(function(xhr) {
            showError('#geolocation-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function testSms() {
        const phone = $('#sms-phone').val();
        const message = $('#sms-message').val();

        if (!phone) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }

        showLoading('#sms-result');

        $.post('{{ route("admin.api.test.sms") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone,
            message: message
        })
        .done(function(data) {
            showResult('#sms-result', data);
            if (data.demo && data.result.otp_code) {
                $('#sms-result').append('<div class="api-alert--warning api-mt-12"><strong>Code OTP (mode démo):</strong> ' + data.result.otp_code + '</div>');
            }
        })
        .fail(function(xhr) {
            showError('#sms-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function testOtp() {
        const phone = $('#sms-phone').val();

        if (!phone) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }

        showLoading('#sms-result');

        $.post('{{ route("admin.api.test.otp") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone
        })
        .done(function(data) {
            showResult('#sms-result', data);
            if (data.otp_code) {
                $('#sms-result').append('<div class="api-alert--info api-mt-12"><strong>Code OTP (mode démo):</strong> ' + data.otp_code + '</div>');
            }
        })
        .fail(function(xhr) {
            showError('#sms-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function testMobileMoney() {
        const phone = $('#momo-phone').val();
        const amount = $('#momo-amount').val();
        const operator = $('#momo-operator').val();

        if (!phone || !amount) {
            alert('Veuillez remplir tous les champs');
            return;
        }

        showLoading('#momo-result');

        $.post('{{ route("admin.api.test.momo") }}', {
            _token: '{{ csrf_token() }}',
            phone: phone,
            amount: amount,
            operator: operator
        })
        .done(function(data) {
            showResult('#momo-result', data);
        })
        .fail(function(xhr) {
            showError('#momo-result', xhr.responseJSON?.message || 'Erreur');
        });
    }

    function clearCache() {
        if (!confirm('Voulez-vous vider le cache de configuration?')) {
            return;
        }

        $.post('{{ route("admin.api.clear-cache") }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(data) {
            alert('Cache vidé avec succès!');
            loadApiStatus(); // Recharger le statut
        })
        .fail(function() {
            alert('Erreur lors du vidage du cache');
        });
    }

    function showLoading(selector) {
        $(selector).html('<div class="api-alert--info"><i class="fas fa-spinner fa-spin"></i> En cours...</div>');
    }

    function showResult(selector, data) {
        const alertClass = data.success ? 'api-alert--success' : 'api-alert--danger';
        const icon = data.success ? 'fa-check-circle' : 'fa-times-circle';

        let html = '<div class="' + alertClass + '">';
        html += '<i class="fas ' + icon + '"></i> <strong>' + (data.message || 'Résultat') + '</strong>';

        if (data.demo) {
            html += '<br><small style="color:#6b7280;">Mode démo activé</small>';
        }

        if (data.result) {
            html += '<pre class="api-mt-12" style="max-height:300px;overflow:auto;margin-bottom:0;">' + JSON.stringify(data.result, null, 2) + '</pre>';
        }

        html += '</div>';

        $(selector).html(html);
    }

    function showError(selector, message) {
        $(selector).html('<div class="api-alert--danger"><i class="fas fa-exclamation-triangle"></i> ' + message + '</div>');
    }

    // ========== Fonctions Email/SMTP ==========

    function loadMailStatus() {
        $.get('{{ route("admin.api.mail.status") }}')
            .done(function(data) {
                if (data.success && data.status) {
                    const status = data.status;
                    let alertClass = 'api-alert--secondary';
                    let statusText = 'Non configuré';

                    if (status.configured) {
                        alertClass = status.enabled ? 'api-alert--success' : 'api-alert--warning';
                        statusText = status.enabled ? 'Configuré et activé' : 'Configuré mais désactivé';
                    }

                    let html = '<div class="' + alertClass + '">';
                    html += '<i class="fas fa-envelope"></i> <strong>Statut Email:</strong> ' + statusText;
                    if (status.configured) {
                        html += '<br><small>';
                        html += 'Serveur: ' + (status.host || 'N/A') + ':' + (status.port || 'N/A');
                        html += ' | Chiffrement: ' + (status.encryption || 'N/A');
                        html += ' | De: ' + (status.from_address || 'N/A');
                        html += '</small>';
                    }
                    html += '</div>';

                    $('#email-status-alert').html(html);

                    // Mettre à jour aussi la carte de statut
                    if (status.configured && status.enabled) {
                        $('#email-status').html('<span class="api-badge--success">Configuré</span>');
                    } else if (status.configured) {
                        $('#email-status').html('<span class="api-badge--warning">Configuré (désactivé)</span>');
                    } else {
                        $('#email-status').html('<span class="api-badge--secondary">Non configuré</span>');
                    }
                }
            })
            .fail(function() {
                $('#email-status-alert').html('<div class="api-alert--danger">Erreur lors du chargement du statut</div>');
            });
    }

    function loadMailConfig() {
        // Charger les valeurs depuis les clés API (qui incluent maintenant les variables MAIL_*)
        $.get('{{ route("admin.api.keys") }}')
            .done(function(data) {
                if (data.success && data.keys) {
                    const keys = data.keys;

                    // Ne remplir que si les champs sont vides (pour ne pas écraser les modifications en cours)
                    if (!$('#mail-host').val()) {
                        $('#mail-host').val(keys.MAIL_HOST || '');
                    }
                    if (!$('#mail-port').val() || $('#mail-port').val() === '587') {
                        $('#mail-port').val(keys.MAIL_PORT || '465');
                    }
                    if (keys.MAIL_ENCRYPTION) {
                        $('#mail-encryption').val(keys.MAIL_ENCRYPTION);
                    }
                    if (!$('#mail-username').val()) {
                        $('#mail-username').val(keys.MAIL_USERNAME || '');
                    }
                    // Ne pas remplir le mot de passe automatiquement
                    if (!$('#mail-from-address').val()) {
                        $('#mail-from-address').val(keys.MAIL_FROM_ADDRESS || '');
                    }
                    if (!$('#mail-from-name').val() || $('#mail-from-name').val() === 'Plateforme') {
                        $('#mail-from-name').val(keys.MAIL_FROM_NAME || 'Plateforme');
                    }
                    if (keys.MAIL_ENABLED) {
                        $('#mail-enabled').prop('checked', keys.MAIL_ENABLED === 'true' || keys.MAIL_ENABLED === true);
                    }
                }
            })
            .fail(function() {
                console.error('Erreur lors du chargement de la configuration email');
            });
    }

    function saveMailConfig() {
        const formData = {
            MAIL_HOST: $('#mail-host').val().trim(),
            MAIL_PORT: parseInt($('#mail-port').val()),
            MAIL_USERNAME: $('#mail-username').val().trim(),
            MAIL_PASSWORD: $('#mail-password').val(),
            MAIL_ENCRYPTION: $('#mail-encryption').val(),
            MAIL_FROM_ADDRESS: $('#mail-from-address').val().trim(),
            MAIL_FROM_NAME: $('#mail-from-name').val().trim(),
            MAIL_ENABLED: $('#mail-enabled').is(':checked'),
        };

        // Validation basique
        if (!formData.MAIL_HOST || !formData.MAIL_PORT || !formData.MAIL_USERNAME || !formData.MAIL_PASSWORD || !formData.MAIL_FROM_ADDRESS) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        showLoading('#mail-save-result');

        $.ajax({
            url: '{{ route("admin.api.mail.save") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ...formData
            },
            success: function(data) {
                if (data.success) {
                    showResult('#mail-save-result', {
                        success: true,
                        message: data.message || 'Configuration sauvegardée avec succès!',
                    });

                    // Recharger le statut
                    setTimeout(function() {
                        loadMailStatus();
                        clearCache();
                    }, 1000);
                } else {
                    showError('#mail-save-result', data.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                showError('#mail-save-result', xhr.responseJSON?.message || 'Erreur lors de la sauvegarde');
            }
        });
    }

    function testEmail() {
        const to = $('#test-email-to').val().trim();
        const subject = $('#test-email-subject').val().trim() || 'Test Email - Plateforme';
        const message = $('#test-email-message').val().trim() || 'Ceci est un email de test depuis la plateforme. Si vous recevez ce message, la configuration SMTP fonctionne correctement.';

        if (!to) {
            alert('Veuillez entrer une adresse email de destination');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
            alert('Veuillez entrer une adresse email valide');
            return;
        }

        showLoading('#test-email-result');

        $.post('{{ route("admin.api.test.email") }}', {
            _token: '{{ csrf_token() }}',
            to: to,
            subject: subject,
            message: message
        })
        .done(function(data) {
            showResult('#test-email-result', data);
        })
        .fail(function(xhr) {
            showError('#test-email-result', xhr.responseJSON?.message || 'Erreur lors de l\'envoi de l\'email');
        });
    }
</script>
@endsection
