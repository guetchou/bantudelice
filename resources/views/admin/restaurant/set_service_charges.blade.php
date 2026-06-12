@extends('layouts.admin-modern')

@section('title', 'Restaurant')
@section('page_title', 'Frais de service')
@section('nav_active', 'restaurants')

@section('style')
<style>
.rst-page { padding:24px; }
.rst-alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid transparent; margin-bottom:16px; }
.rst-alert--success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.rst-alert--danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.rst-alert--warning { background:#fefce8; color:#854d0e; border-color:#fde68a; }

/* Card */
.rst-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:20px; max-width:480px; margin-left:auto; margin-right:auto; }
.rst-card__header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
.rst-card__title { font-size:14px; font-weight:700; color:#111827; margin:0; }
.rst-card__body { padding:20px; }
.rst-card__footer { padding:14px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; align-items:center; gap:10px; }

/* Form */
.rst-field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.rst-label { font-size:13px; font-weight:600; color:#374151; }
.rst-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.rst-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.rst-input--error { border-color:#ef4444; }
.rst-field-error { font-size:11px; color:#dc2626; font-weight:500; }

/* Buttons */
.rst-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:opacity .15s; }
.rst-btn-primary:hover { opacity:.85; color:#fff; }
.rst-btn-cancel { display:inline-flex; align-items:center; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #d1d5db; color:#374151; background:#fff; transition:background .15s; }
.rst-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
</style>
@endsection

@section('content')
<div class="rst-page">
    <div class="rst-card">
        <div class="rst-card__header">
            <h3 class="rst-card__title">Add Service Charges</h3>
        </div>
        <form role="form" method="post" action="{{ route('admin.set_service_charges',$restaurant->id) }}">
            @csrf
            <div class="rst-card__body">
                <div class="rst-field">
                    <label class="rst-label" for="services">Services Used By Restaurant</label>
                    <input type="text"
                           value="{{$restaurant->services}}"
                           name="services"
                           id="services"
                           placeholder="Enter"
                           class="rst-input{{ $errors->has('services') ? ' rst-input--error' : ''}}">
                    @if($errors->has('services'))
                        <span class="rst-field-error" role="alert">
                            <strong>{{ $errors->first('services') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="rst-field">
                    <label class="rst-label" for="service_charges">Service Charges</label>
                    <input type="text"
                           value="{{old('service_charges')}}"
                           name="service_charges"
                           id="service_charges"
                           placeholder="Enter"
                           class="rst-input{{ $errors->has('service_charges') ? ' rst-input--error' : ''}}">
                    @if($errors->has('service_charges'))
                        <span class="rst-field-error" role="alert">
                            <strong>{{ $errors->first('service_charges') }}</strong>
                        </span>
                    @endif
                </div>
            </div>
            <div class="rst-card__footer">
                <a href="{{ route('restaurant.index') }}" class="rst-btn-cancel">Annuler</a>
                <button type="submit" class="rst-btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
