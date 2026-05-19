@php
    $isEdit = isset($voucher) && !empty($voucher->exists);
    $rulesValue = old('rules_json');
    if ($rulesValue === null) {
        $rulesValue = !empty($voucher->rules)
            ? json_encode($voucher->rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
    }
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Paramètres de la promotion</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Code promo</label>
                            <input type="text" name="name" value="{{ old('name', $voucher->name ?? '') }}" class="form-control" placeholder="EX: ORANGE20">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Restaurant cible</label>
                            <select name="restaurant_id" class="form-control">
                                <option value="">Global</option>
                                @foreach($restaurants as $restaurant)
                                    <option value="{{ $restaurant->id }}" {{ (string) old('restaurant_id', $voucher->restaurant_id ?? '') === (string) $restaurant->id ? 'selected' : '' }}>
                                        {{ $restaurant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Type de remise</label>
                            <select name="discount_type" class="form-control">
                                @foreach(['percentage' => 'Pourcentage', 'fixed' => 'Montant fixe'] as $key => $label)
                                    <option value="{{ $key }}" {{ old('discount_type', $voucher->discount_type ?? 'percentage') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Valeur remise</label>
                            <input type="number" min="0.01" step="0.01" name="discount_value" value="{{ old('discount_value', $voucher->discount_value ?? $voucher->discount ?? 0) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Plafond remise</label>
                            <input type="number" min="0" step="0.01" name="max_discount_amount" value="{{ old('max_discount_amount', $voucher->max_discount_amount ?? '') }}" class="form-control" placeholder="Optionnel">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Montant minimum</label>
                            <input type="number" min="0" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $voucher->min_order_amount ?? 0) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Quota global</label>
                            <input type="number" min="1" step="1" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit ?? '') }}" class="form-control" placeholder="Optionnel">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Quota par utilisateur</label>
                            <input type="number" min="1" step="1" name="per_user_limit" value="{{ old('per_user_limit', $voucher->per_user_limit ?? 1) }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Règles additionnelles (JSON)</label>
                    <textarea name="rules_json" rows="5" class="form-control" placeholder='{"channels":["food"],"cities":["Brazzaville"]}'>{{ $rulesValue }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Publication</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Début</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($voucher->starts_at ?? $voucher->start_date ?? null)->format('Y-m-d\\TH:i')) }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Fin</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($voucher->ends_at ?? $voucher->end_date ?? null)->format('Y-m-d\\TH:i')) }}" class="form-control">
                </div>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="stackable" value="1" class="custom-control-input" id="stackableSwitch" {{ old('stackable', $voucher->stackable ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="stackableSwitch">Cumulable avec d'autres promos</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="activeSwitch" {{ old('is_active', $voucher->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="activeSwitch">Promotion active</label>
                    </div>
                </div>
                @if($isEdit)
                    <div class="alert alert-light">
                        <strong>Utilisations:</strong> {{ (int) ($voucher->used_count ?? 0) }}<br>
                        <strong>Redemptions:</strong> {{ (int) ($voucher->redemptions_count ?? 0) }}
                    </div>
                @endif
            </div>
        </div>
        <div class="card card-outline card-info">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    {{ $isEdit ? 'Mettre à jour' : 'Créer la promotion' }}
                </button>
                <a href="{{ route('admin.promotions.index') }}" class="btn btn-light btn-block">Retour</a>
            </div>
        </div>
    </div>
</div>
