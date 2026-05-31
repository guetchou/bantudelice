@php
    $isEdit = isset($voucher) && !empty($voucher->exists);
    $rulesValue = old('rules_json');
    if ($rulesValue === null) {
        $rulesValue = !empty($voucher->rules)
            ? json_encode($voucher->rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
    }
@endphp

<style>
.vch-form-layout { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
.vch-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px; }
.vch-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.vch-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.vch-card__body { padding:20px; }
.vch-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; }
.vch-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.vch-field:last-child { margin-bottom:0; }
.vch-label { font-size:13px; font-weight:600; color:#374151; }
.vch-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s; box-sizing:border-box; }
.vch-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.vch-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.vch-switch-row { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
.vch-switch-row input[type=checkbox] { width:16px; height:16px; accent-color:#1e3a5f; cursor:pointer; }
.vch-switch-row label { font-size:13px; color:#374151; cursor:pointer; font-weight:500; }
.vch-info-box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px 14px; font-size:13px; color:#374151; }
.vch-info-box strong { display:block; margin-bottom:4px; }
.vch-btn-primary { display:inline-flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:10px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; margin-bottom:8px; }
.vch-btn-primary:hover { opacity:.85; }
.vch-btn-cancel { display:inline-flex; align-items:center; justify-content:center; padding:10px 18px; width:100%; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; color:#374151; background:#fff; transition:background .15s; }
.vch-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
@media (max-width:900px) { .vch-form-layout { grid-template-columns:1fr; } }
</style>

<div class="vch-form-layout">
    {{-- Colonne principale --}}
    <div>
        <div class="vch-card">
            <div class="vch-card__header">
                <h3 class="vch-card__title">Paramètres de la promotion</h3>
            </div>
            <div class="vch-card__body">
                <div class="vch-form-grid" style="grid-template-columns:2fr 1fr;">
                    <div class="vch-field">
                        <label class="vch-label">Code promo</label>
                        <input type="text" name="name" value="{{ old('name', $voucher->name ?? '') }}" class="vch-input" placeholder="Ex : ORANGE20">
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Restaurant cible</label>
                        <select name="restaurant_id" class="vch-input vch-select">
                            <option value="">Global</option>
                            @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" {{ (string) old('restaurant_id', $voucher->restaurant_id ?? '') === (string) $restaurant->id ? 'selected' : '' }}>
                                    {{ $restaurant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="vch-form-grid">
                    <div class="vch-field">
                        <label class="vch-label">Type de remise</label>
                        <select name="discount_type" class="vch-input vch-select">
                            @foreach(['percentage' => 'Pourcentage', 'fixed' => 'Montant fixe'] as $key => $label)
                                <option value="{{ $key }}" {{ old('discount_type', $voucher->discount_type ?? 'percentage') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Valeur remise</label>
                        <input type="number" min="0.01" step="0.01" name="discount_value" value="{{ old('discount_value', $voucher->discount_value ?? $voucher->discount ?? 0) }}" class="vch-input">
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Plafond remise</label>
                        <input type="number" min="0" step="0.01" name="max_discount_amount" value="{{ old('max_discount_amount', $voucher->max_discount_amount ?? '') }}" class="vch-input" placeholder="Optionnel">
                    </div>
                </div>

                <div class="vch-form-grid">
                    <div class="vch-field">
                        <label class="vch-label">Montant minimum</label>
                        <input type="number" min="0" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $voucher->min_order_amount ?? 0) }}" class="vch-input">
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Quota global</label>
                        <input type="number" min="1" step="1" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit ?? '') }}" class="vch-input" placeholder="Optionnel">
                    </div>
                    <div class="vch-field">
                        <label class="vch-label">Quota par utilisateur</label>
                        <input type="number" min="1" step="1" name="per_user_limit" value="{{ old('per_user_limit', $voucher->per_user_limit ?? 1) }}" class="vch-input">
                    </div>
                </div>

                <div class="vch-field">
                    <label class="vch-label">Règles additionnelles (JSON)</label>
                    <textarea name="rules_json" rows="5" class="vch-input" style="resize:vertical;" placeholder='{"channels":["food"],"cities":["Brazzaville"]}'>{{ $rulesValue }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne droite : publication --}}
    <div>
        <div class="vch-card">
            <div class="vch-card__header">
                <h3 class="vch-card__title">Publication</h3>
            </div>
            <div class="vch-card__body">
                <div class="vch-field">
                    <label class="vch-label">Début</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($voucher->starts_at ?? $voucher->start_date ?? null)->format('Y-m-d\\TH:i')) }}" class="vch-input">
                </div>
                <div class="vch-field">
                    <label class="vch-label">Fin</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($voucher->ends_at ?? $voucher->end_date ?? null)->format('Y-m-d\\TH:i')) }}" class="vch-input">
                </div>
                <div class="vch-switch-row">
                    <input type="checkbox" name="stackable" value="1" id="stackableSwitch" {{ old('stackable', $voucher->stackable ?? false) ? 'checked' : '' }}>
                    <label for="stackableSwitch">Cumulable avec d'autres promos</label>
                </div>
                <div class="vch-switch-row">
                    <input type="checkbox" name="is_active" value="1" id="activeSwitch" {{ old('is_active', $voucher->is_active ?? true) ? 'checked' : '' }}>
                    <label for="activeSwitch">Promotion active</label>
                </div>
                @if($isEdit)
                    <div class="vch-info-box">
                        <strong>Statistiques</strong>
                        Utilisations : {{ (int) ($voucher->used_count ?? 0) }}<br>
                        Redemptions : {{ (int) ($voucher->redemptions_count ?? 0) }}
                    </div>
                @endif
            </div>
        </div>
        <div class="vch-card">
            <div class="vch-card__body">
                <button type="submit" class="vch-btn-primary">
                    <i class="fas fa-save"></i> {{ $isEdit ? 'Mettre à jour' : 'Créer la promotion' }}
                </button>
                <a href="{{ route('admin.promotions.index') }}" class="vch-btn-cancel">Retour</a>
            </div>
        </div>
    </div>
</div>
