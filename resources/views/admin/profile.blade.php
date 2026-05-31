@extends('layouts.admin-modern')
@section('title', 'Mon Profil | Admin')
@section('page_title', 'Mon profil')
@section('nav_active', 'profile')

@section('style')
<style>
.prf-page { padding:24px; background:#f9fafb; min-height:calc(100vh - 120px); display:flex; justify-content:center; }
.prf-inner { width:100%; max-width:1040px; }
.prf-alert { padding:12px 16px; border-radius:12px; font-size:13px; font-weight:500; display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.prf-alert--success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.prf-alert--error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
.prf-alert-close { margin-left:auto; background:none; border:none; font-size:18px; color:inherit; cursor:pointer; line-height:1; }
.prf-layout { display:grid; grid-template-columns:300px 1fr; gap:24px; }
.prf-sidebar { display:flex; flex-direction:column; gap:0; }
.prf-avatar-card { border-radius:20px; overflow:hidden; }
.prf-avatar-hero { background:linear-gradient(135deg, #ff5a1f 0%, #F59E0B 100%); padding:40px 32px; text-align:center; color:#fff; position:relative; }
.prf-avatar-wrap { position:relative; display:inline-block; margin-bottom:24px; }
.prf-avatar-img { width:140px; height:140px; object-fit:cover; border-radius:50%; border:5px solid rgba(255,255,255,.3); box-shadow:0 8px 25px rgba(0,0,0,.2); }
.prf-camera-btn { position:absolute; bottom:10px; right:10px; width:40px; height:40px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,.2); cursor:pointer; border:none; }
.prf-camera-btn i { color:#ff5a1f; font-size:16px; }
.prf-name { font-size:1.4rem; font-weight:700; margin:0 0 4px; color:#fff; }
.prf-role { opacity:.9; font-size:.95rem; margin:0; }
.prf-stats { background:rgba(255,255,255,.15); backdrop-filter:blur(10px); border-radius:16px; padding:16px; margin-top:24px; display:grid; grid-template-columns:repeat(3,1fr); }
.prf-stat { text-align:center; }
.prf-stat:nth-child(2) { border-left:1px solid rgba(255,255,255,.2); border-right:1px solid rgba(255,255,255,.2); }
.prf-stat__val { font-size:1.4rem; font-weight:800; color:#fff; }
.prf-stat__lbl { font-size:.7rem; opacity:.85; text-transform:uppercase; letter-spacing:.5px; color:#fff; }
.prf-info-card { background:#fff; padding:20px 24px; border-radius:0 0 20px 20px; border:1px solid #e5e7eb; border-top:none; }
.prf-info-row { display:flex; align-items:center; gap:12px; margin-bottom:12px; font-size:.9rem; color:#6b7280; }
.prf-info-row:last-child { margin-bottom:0; }
.prf-info-row i { color:#ff5a1f; width:20px; flex-shrink:0; }
.prf-divider { margin:20px 0; border:none; border-top:1px solid #e5e7eb; }
.prf-meta { font-size:.85rem; color:#6b7280; }
.prf-meta > div { margin-bottom:8px; }
.prf-meta > div:last-child { margin-bottom:0; }
.prf-form-card { background:#fff; border-radius:20px; border:1px solid #e5e7eb; overflow:hidden; }
.prf-form-head { background:linear-gradient(180deg, #fafafa 0%, #fff 100%); border-bottom:1px solid #e5e7eb; padding:20px 32px; }
.prf-form-head h4 { font-size:1.1rem; font-weight:700; color:#1f2937; margin:0; display:flex; align-items:center; gap:10px; }
.prf-form-head h4 i { color:#ff5a1f; }
.prf-form-body { padding:32px; }
.prf-field { margin-bottom:24px; }
.prf-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:.9rem; }
.prf-label i { color:#ff5a1f; margin-right:4px; }
.prf-input-wrap { position:relative; }
.prf-input { width:100%; padding:14px 16px; border:2px solid #e5e7eb; border-radius:12px; font-size:1rem; box-sizing:border-box; transition:border-color .3s; }
.prf-input:focus { border-color:#ff5a1f; box-shadow:0 0 0 4px rgba(255,107,53,.1); outline:none; }
.prf-eye-toggle { position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#9ca3af; cursor:pointer; background:none; border:none; padding:0; }
.prf-hint { font-size:.8rem; color:#6b7280; margin-top:4px; display:block; }
.prf-error { color:#ef4444; font-size:.85rem; margin-top:6px; display:block; }
.prf-match-ok { color:#009543; font-size:.85rem; margin-top:6px; display:none; }
.prf-submit { display:flex; gap:12px; }
.prf-btn-primary { flex:1; padding:14px 32px; background:linear-gradient(135deg, #ff5a1f 0%, #e04d15 100%); border:none; border-radius:12px; font-weight:600; font-size:.95rem; color:#fff; cursor:pointer; box-shadow:0 4px 15px rgba(255,107,53,.35); transition:all .3s; }
.prf-btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(255,107,53,.45); }
@media (max-width:991px) { .prf-layout { grid-template-columns:1fr; } .prf-page { padding:16px; } }
</style>
@endsection

@section('content')
<div class="prf-page">
<div class="prf-inner">
    @if(session()->has('alert'))
    <div class="prf-alert prf-alert--{{ session()->get('alert.type') === 'success' ? 'success' : 'error' }}">
        <i class="fas fa-{{ session()->get('alert.type') === 'success' ? 'check-circle' : 'exclamation-circle' }}"></i>
        {{ session()->get('alert.message') }}
        <button type="button" class="prf-alert-close" onclick="this.closest('.prf-alert').remove()">&times;</button>
    </div>
    @endif

    <div class="prf-layout">
        {{-- Sidebar --}}
        <div class="prf-sidebar">
            <div class="prf-avatar-card">
                <div class="prf-avatar-hero">
                    <div class="prf-avatar-wrap">
                        <img class="prf-avatar-img"
                             src="{{ $admin->avatarUrl() }}"
                             alt="Photo de profil"
                             id="profileImagePreview">
                        <button type="button" class="prf-camera-btn" onclick="document.getElementById('imageInput').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h3 class="prf-name">{{ $admin->name }}</h3>
                    <p class="prf-role"><i class="fas fa-shield-alt" style="margin-right:4px;"></i> Administrateur</p>
                    <div class="prf-stats">
                        <div class="prf-stat">
                            <div class="prf-stat__val">{{ \App\User::where('type', 'user')->count() }}</div>
                            <div class="prf-stat__lbl">Utilisateurs</div>
                        </div>
                        <div class="prf-stat">
                            <div class="prf-stat__val">{{ \App\Restaurant::count() }}</div>
                            <div class="prf-stat__lbl">Restaurants</div>
                        </div>
                        <div class="prf-stat">
                            <div class="prf-stat__val">{{ \App\Order::count() }}</div>
                            <div class="prf-stat__lbl">Commandes</div>
                        </div>
                    </div>
                </div>
                <div class="prf-info-card">
                    <div class="prf-info-row"><i class="fas fa-envelope"></i> {{ $admin->email }}</div>
                    @if($admin->phone)
                    <div class="prf-info-row"><i class="fas fa-phone"></i> {{ $admin->phone }}</div>
                    @endif
                    <hr class="prf-divider">
                    <div class="prf-meta">
                        <div><strong>Membre depuis:</strong> {{ $admin->created_at->format('d/m/Y') }}</div>
                        <div><strong>Dernière connexion:</strong> {{ $admin->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main card --}}
        <div class="prf-form-card">
            <div class="prf-form-head">
                <h4><i class="fas fa-lock"></i> Changer le mot de passe</h4>
            </div>
            <div class="prf-form-body">
                <form action="{{ route('admin.profile_update') }}" method="post" id="passwordForm" style="max-width:600px;">
                    @csrf

                    <div class="prf-field">
                        <label class="prf-label"><i class="fas fa-lock"></i> Mot de passe actuel</label>
                        <div class="prf-input-wrap">
                            <input type="password" name="current_password"
                                   class="prf-input"
                                   placeholder="Entrez votre mot de passe actuel"
                                   required>
                            <button type="button" class="prf-eye-toggle" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        @if($errors->has('current_password'))
                            <span class="prf-error"><i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>{{ $errors->first('current_password') }}</span>
                        @endif
                    </div>

                    <div class="prf-field">
                        <label class="prf-label"><i class="fas fa-key"></i> Nouveau mot de passe</label>
                        <div class="prf-input-wrap">
                            <input type="password" name="new_password"
                                   class="prf-input"
                                   placeholder="Entrez votre nouveau mot de passe"
                                   required
                                   id="newPassword">
                            <button type="button" class="prf-eye-toggle" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <span class="prf-hint"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Minimum 6 caractères</span>
                        @if($errors->has('new_password'))
                            <span class="prf-error"><i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>{{ $errors->first('new_password') }}</span>
                        @endif
                    </div>

                    <div class="prf-field">
                        <label class="prf-label"><i class="fas fa-check-circle"></i> Confirmer le nouveau mot de passe</label>
                        <div class="prf-input-wrap">
                            <input type="password" name="pwdnew_confirm"
                                   class="prf-input"
                                   placeholder="Confirmez votre nouveau mot de passe"
                                   required
                                   id="confirmPassword">
                            <button type="button" class="prf-eye-toggle" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <span class="prf-match-ok" id="passwordMatch"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Les mots de passe correspondent</span>
                    </div>

                    <div class="prf-submit">
                        <button type="submit" class="prf-btn-primary">
                            <i class="fas fa-save" style="margin-right:8px;"></i>Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<form id="imageForm" action="{{ route('admin.profile_update') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="file" name="image" id="imageInput" accept="image/*" onchange="uploadImage(this)">
</form>
@endsection

@section('script')
<script>
    function togglePassword(btn) {
        const input = btn.previousElementSibling;
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('confirmPassword')?.addEventListener('input', function() {
        const newPwd = document.getElementById('newPassword').value;
        const matchDiv = document.getElementById('passwordMatch');
        if (this.value && newPwd === this.value) {
            matchDiv.style.display = 'block';
            this.style.borderColor = '#009543';
        } else {
            matchDiv.style.display = 'none';
            this.style.borderColor = '#e5e7eb';
        }
    });

    function uploadImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
            document.getElementById('imageForm').submit();
        }
    }

    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
        const newPwd = document.getElementById('newPassword').value;
        const confirmPwd = document.getElementById('confirmPassword').value;
        if (newPwd !== confirmPwd) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
        } else if (newPwd.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères');
        }
    });
</script>
@endsection
