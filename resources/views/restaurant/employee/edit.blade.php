@extends('layouts.restaurant_app')
@section('title', 'Modifier un membre | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Modifier un membre')
@section('employee_nav', 'active')

@section('content')
<div style="max-width:680px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div style="background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--bd-border-2);display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Modifier « {{ $employee->name }} »</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">Mettez à jour les informations du membre</div>
            </div>
            <a href="{{ route('employee.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
               onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
               onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <form method="post" action="{{ route('employee.update', $employee->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="padding:24px 20px;display:flex;flex-direction:column;gap:18px;">

                {{-- Photo de profil --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:8px;">
                        Photo de profil
                    </label>
                    <div style="display:flex;align-items:center;gap:16px;">
                        @php
                            $currentImg = $employee->image ? asset('images/employee_images/' . $employee->image) : null;
                            $initials = strtoupper(substr($employee->name ?? 'E', 0, 2));
                        @endphp
                        <div id="avatarPreview"
                             style="width:72px;height:72px;border-radius:50%;background:rgba(0,149,67,.1);border:2px solid var(--bd-border);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            @if($currentImg)
                                <img src="{{ $currentImg }}" style="width:100%;height:100%;object-fit:cover;" alt="{{ $employee->name }}"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span style="display:none;font-size:18px;font-weight:700;color:var(--bd-green);">{{ $initials }}</span>
                            @else
                                <span style="font-size:18px;font-weight:700;color:var(--bd-green);">{{ $initials }}</span>
                            @endif
                        </div>
                        <div>
                            <label for="upload_file"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;cursor:pointer;transition:.12s;"
                                   onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                                   onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                                <i class="fas fa-upload"></i> Changer la photo
                            </label>
                            <input type="file" id="upload_file" name="image" accept="image/*"
                                   style="display:none;" onchange="previewEmpAvatar(this)">
                            <div style="font-size:11px;color:var(--bd-text-3);margin-top:5px;">Laisser vide pour conserver l'actuelle</div>
                            @error('image')
                                <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Nom + Téléphone --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="name" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Nom complet <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="text" name="name" id="name"
                               value="{{ old('name', $employee->name) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('name')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Téléphone
                        </label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $employee->phone) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('phone') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('phone') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('phone')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Email (readonly) + Mot de passe --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="email" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Email
                        </label>
                        <input type="email" name="email" id="email"
                               value="{{ $employee->email }}" readonly
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--bd-border-2);border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface-2);color:var(--bd-text-3);outline:none;cursor:not-allowed;" />
                        <div style="font-size:11px;color:var(--bd-text-3);margin-top:4px;">Non modifiable</div>
                    </div>
                    <div>
                        <label for="pass" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Nouveau mot de passe
                        </label>
                        <input type="password" name="password" id="pass"
                               placeholder="Laisser vide pour ne pas changer"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('password') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('password') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('password')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Adresse --}}
                <div>
                    <label for="address" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                        Adresse
                    </label>
                    <input type="text" name="address" id="address"
                           value="{{ old('address', $employee->address) }}"
                           style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('address') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                           onfocus="this.style.borderColor='var(--bd-green)';"
                           onblur="this.style.borderColor='{{ $errors->has('address') ? '#dc2626' : 'var(--bd-border)' }}';" />
                    @error('address')
                        <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div style="padding:14px 20px;border-top:1px solid var(--bd-border-2);background:var(--bd-surface-2);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <a href="{{ route('employee.index') }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
                   onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                   onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                    Annuler
                </a>
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--bd-radius);background:var(--bd-green);color:#fff;font-size:12px;font-weight:700;border:none;cursor:pointer;font-family:var(--bd-font);transition:.12s;"
                        onmouseover="this.style.background='var(--bd-green-dark,#007836)';"
                        onmouseout="this.style.background='var(--bd-green)';">
                    <i class="fas fa-check"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function previewEmpAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endsection
