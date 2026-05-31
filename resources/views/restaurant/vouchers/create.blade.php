@extends('layouts.restaurant_app')
@section('title', 'Ajouter un bon de réduction | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Ajouter un bon de réduction')
@section('vouchers_nav', 'active')

@section('content')
<div style="max-width:600px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div style="background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--bd-border-2);display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Créer un bon de réduction</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">Offrez des remises à vos clients sur une période définie</div>
            </div>
            <a href="{{ route('voucher.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
               onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
               onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <form method="post" action="{{ route('voucher.store') }}">
            @csrf
            <div style="padding:24px 20px;display:flex;flex-direction:column;gap:18px;">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="name" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Nom du bon <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="text" name="name" id="name"
                               value="{{ old('name') }}"
                               placeholder="Ex : PROMO10, BIENVENUE…"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('name')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="discount" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Réduction (%) <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="number" name="discount" id="discount"
                               value="{{ old('discount') }}"
                               min="1" max="100" placeholder="Ex : 10"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('discount') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('discount') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('discount')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="start_date" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Date de début <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="date" name="start_date" id="start_date"
                               value="{{ old('start_date') }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('start_date') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;" />
                        @error('start_date')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Date de fin <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="date" name="end_date" id="end_date"
                               value="{{ old('end_date') }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('end_date') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;" />
                        @error('end_date')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>

            <div style="padding:14px 20px;border-top:1px solid var(--bd-border-2);background:var(--bd-surface-2);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <a href="{{ route('voucher.index') }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
                   onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                   onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                    Annuler
                </a>
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--bd-radius);background:var(--bd-green);color:#fff;font-size:12px;font-weight:700;border:none;cursor:pointer;font-family:var(--bd-font);transition:.12s;"
                        onmouseover="this.style.background='var(--bd-green-dark,#007836)';"
                        onmouseout="this.style.background='var(--bd-green)';">
                    <i class="fas fa-plus"></i> Créer le bon
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
