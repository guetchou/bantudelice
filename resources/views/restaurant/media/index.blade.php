@extends('layouts.restaurant_app')
@section('title', 'Médias | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Médias')
@section('media_nav', 'active')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
.med { display: flex; flex-direction: column; gap: 20px; }

.med-upload-card {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.med-upload-card__head { padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 13px; font-weight: 700; color: var(--bd-text); }
.med-upload-card__sub { font-size: 11px; color: var(--bd-text-3); font-weight: 400; margin-top: 1px; }
.med-upload-card__body { padding: 20px 18px; }

.med-dropzone {
    border: 2px dashed var(--bd-border);
    border-radius: var(--bd-radius);
    padding: 20px; background: var(--bd-surface-2);
    transition: all .2s;
}
.med-dropzone.dragover { border-color: var(--bd-green); background: rgba(0,149,67,.04); }

.med-upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 640px) { .med-upload-grid { grid-template-columns: 1fr; } }

.med-field-label { font-size: 12px; font-weight: 600; color: var(--bd-text); margin-bottom: 6px; }
.med-field-hint  { font-size: 11px; color: var(--bd-text-3); margin-top: 4px; }

.med-input, .med-file-input {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); font-size: 13px;
    font-family: var(--bd-font); background: var(--bd-surface);
    color: var(--bd-text); outline: none; transition: border-color .12s;
}
.med-input:focus, .med-file-input:focus { border-color: var(--bd-green); }

.med-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.med-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 8px 16px; border-radius: var(--bd-radius);
    font-size: 12px; font-weight: 700; cursor: pointer;
    border: none; font-family: var(--bd-font); transition: .12s;
}
.med-btn--green   { background: var(--bd-green); color: #fff; }
.med-btn--green:hover { background: var(--bd-green-dark, #007836); }
.med-btn--outline { background: var(--bd-surface); color: var(--bd-text-2); border: 1px solid var(--bd-border); }
.med-btn--outline:hover { border-color: var(--bd-green); color: var(--bd-green); }
.med-btn:disabled { opacity: .5; cursor: not-allowed; }

.med-gallery-card {
    background: var(--bd-surface); border: 1px solid var(--bd-border);
    border-radius: var(--bd-radius); overflow: hidden;
}
.med-gallery-card__head { padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 13px; font-weight: 700; color: var(--bd-text); }
.med-gallery-card__body { padding: 18px; }

.med-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }

.med-item {
    border-radius: calc(var(--bd-radius) - 1px);
    overflow: hidden; border: 1px solid var(--bd-border);
    background: var(--bd-surface);
}
.med-thumb {
    width: 100%; height: 150px; object-fit: cover;
    background: var(--bd-surface-2); display: block;
}
.med-item-footer {
    padding: 10px 12px; display: flex; align-items: center;
    justify-content: space-between; gap: 8px;
    border-top: 1px solid var(--bd-border-2);
}
.med-source-badge {
    font-size: 10px; font-weight: 700; color: var(--bd-text-3);
    display: flex; align-items: center; gap: 5px;
}
.handle { cursor: grab; color: var(--bd-text-3); }
.handle:active { cursor: grabbing; }
.med-delete-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 6px;
    border: 1px solid rgba(220,38,38,.25); background: var(--bd-surface);
    color: #dc2626; cursor: pointer; font-size: 11px; transition: .12s;
}
.med-delete-btn:hover { background: rgba(220,38,38,.08); }

.med-empty { padding: 40px 20px; text-align: center; color: var(--bd-text-3); font-size: 13px; }
.med-empty i { font-size: 28px; display: block; margin-bottom: 10px; color: var(--bd-border); }

/* Crop modal */
.med-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1050;
    background: rgba(0,0,0,.6); align-items: center; justify-content: center; padding: 16px;
}
.med-modal-overlay.open { display: flex; }
.med-modal {
    background: var(--bd-surface); border-radius: var(--bd-radius);
    border: 1px solid var(--bd-border); width: 100%; max-width: 860px;
    max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;
}
.med-modal__head { padding: 14px 18px; border-bottom: 1px solid var(--bd-border-2); font-size: 13px; font-weight: 700; color: var(--bd-text); display: flex; align-items: center; justify-content: space-between; }
.med-modal__close { background: none; border: none; color: var(--bd-text-3); cursor: pointer; font-size: 18px; line-height: 1; padding: 0; }
.med-modal__body { flex: 1; overflow: auto; background: #111827; }
.med-modal__foot { padding: 12px 18px; border-top: 1px solid var(--bd-border-2); display: flex; justify-content: flex-end; gap: 8px; }
.crop-preview { width: 100%; max-height: 60vh; display: block; }
</style>
@endsection

@section('content')
<div class="med">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ── Upload ─────────────────────────────────────────── --}}
    <div class="med-upload-card">
        <div class="med-upload-card__head">
            Ajouter des médias
            <div class="med-upload-card__sub">Galerie multi-photos · upload ou URL · recadrage · drag &amp; drop</div>
        </div>
        <div class="med-upload-card__body">
            <div class="med-dropzone" id="dropzone">
                <div class="med-upload-grid">
                    <div>
                        <div class="med-field-label">Upload (multi)</div>
                        <input type="file" id="images" class="med-file-input" multiple accept="image/*">
                        <div class="med-field-hint">Glisser-déposer possible · JPG/PNG/WEBP · 5 Mo max par image</div>
                        <div class="med-actions">
                            <button class="med-btn med-btn--green" id="btnUpload">
                                <i class="fas fa-cloud-arrow-up"></i> Uploader
                            </button>
                            <button class="med-btn med-btn--outline" id="btnCrop" disabled>
                                <i class="fas fa-crop-alt"></i> Recadrer
                            </button>
                            <button class="med-btn med-btn--outline" id="btnClear">
                                <i class="fas fa-xmark"></i> Vider
                            </button>
                        </div>
                        <div style="margin-top:14px;">
                            <div class="med-field-label">Texte alternatif <span style="font-weight:400;color:var(--bd-text-3);">(optionnel)</span></div>
                            <input type="text" id="alt_text" class="med-input" placeholder="Ex : Plat signature, intérieur du restaurant…">
                        </div>
                    </div>
                    <div>
                        <div class="med-field-label">Ajouter via URL</div>
                        <div style="display:flex;gap:8px;">
                            <input type="url" id="external_url" class="med-input" placeholder="https://…" style="flex:1;">
                            <button class="med-btn med-btn--green" id="btnAddUrl" style="white-space:nowrap;">
                                <i class="fas fa-link"></i> Ajouter
                            </button>
                        </div>
                        <div class="med-field-hint">Recommandé : images dont vous avez les droits ou sources libres</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Galerie ─────────────────────────────────────────── --}}
    <div class="med-gallery-card">
        <div class="med-gallery-card__head">
            Galerie <span style="font-size:11px;font-weight:400;color:var(--bd-text-3);">· glisser pour réordonner</span>
        </div>
        <div class="med-gallery-card__body">
            @if($media->isEmpty())
                <div class="med-empty">
                    <i class="fas fa-images"></i>
                    <p>Aucun média enregistré. Ajoutez des photos via upload ou URL.</p>
                </div>
            @else
                <div class="med-grid" id="mediaGrid">
                    @foreach($media as $m)
                        @php
                            $src = $m->source === 'external'
                                ? $m->external_url
                                : ($m->file_name ? asset('images/restaurant_gallery/' . $m->file_name) : asset('images/placeholder.png'));
                        @endphp
                        <div class="med-item" data-id="{{ $m->id }}">
                            <img class="med-thumb" src="{{ $src }}" alt="{{ $m->alt_text ?? '' }}"
                                 onerror="this.src='{{ asset('images/placeholder.png') }}'">
                            <div class="med-item-footer">
                                <span class="med-source-badge">
                                    <i class="fas fa-grip-vertical handle"></i>
                                    {{ $m->source === 'external' ? 'URL' : 'Upload' }}
                                </span>
                                <button type="button" class="med-delete-btn btnDelete" data-id="{{ $m->id }}" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Modal crop ──────────────────────────────────────── --}}
<div class="med-modal-overlay" id="cropModal">
    <div class="med-modal">
        <div class="med-modal__head">
            Recadrer l'image
            <button class="med-modal__close" id="btnCropClose">&times;</button>
        </div>
        <div class="med-modal__body">
            <img id="cropImage" class="crop-preview" alt="Aperçu recadrage">
        </div>
        <div class="med-modal__foot">
            <button class="med-btn med-btn--outline" id="btnCropCancel">Annuler</button>
            <button class="med-btn med-btn--green" id="btnCropSave">
                <i class="fas fa-check"></i> Appliquer &amp; uploader
            </button>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
    const csrf       = '{{ csrf_token() }}';
    const storeUrl   = '{{ route('restaurant.media.store') }}';
    const reorderUrl = '{{ route('restaurant.media.reorder') }}';
    let cropper = null;
    let selectedFile = null;

    function getSelectedFiles() {
        const input = document.getElementById('images');
        return input.files ? Array.from(input.files) : [];
    }

    function setCropEnabled() {
        const files = getSelectedFiles();
        const btn = document.getElementById('btnCrop');
        btn.disabled = files.length !== 1;
        selectedFile = files.length === 1 ? files[0] : null;
    }

    async function uploadFiles(files, altText) {
        const fd = new FormData();
        files.forEach(f => fd.append('images[]', f));
        if (altText) fd.append('alt_text', altText);
        const res = await fetch(storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur upload');
        window.location.reload();
    }

    async function uploadExternalUrl(url, altText) {
        const fd = new FormData();
        fd.append('external_url', url);
        if (altText) fd.append('alt_text', altText);
        const res = await fetch(storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur URL');
        window.location.reload();
    }

    async function deleteMedia(id) {
        const res = await fetch('{{ url('/') }}/restaurant/media/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur suppression');
        window.location.reload();
    }

    async function saveReorder() {
        const ids = Array.from(document.querySelectorAll('#mediaGrid .med-item')).map(el => parseInt(el.dataset.id, 10));
        const res = await fetch(reorderUrl, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ids })
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur réordonnancement');
    }

    // Dropzone drag & drop
    const dropzone = document.getElementById('dropzone');
    dropzone.addEventListener('dragover',  e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', ()  => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        document.getElementById('images').files = e.dataTransfer.files;
        setCropEnabled();
    });

    document.getElementById('images').addEventListener('change', setCropEnabled);
    document.getElementById('btnClear').addEventListener('click', () => {
        document.getElementById('images').value = '';
        setCropEnabled();
    });

    document.getElementById('btnUpload').addEventListener('click', async () => {
        const files = getSelectedFiles();
        if (!files.length) return alert('Sélectionnez au moins une image.');
        try { await uploadFiles(files, document.getElementById('alt_text').value || ''); }
        catch (e) { alert(e.message); }
    });

    document.getElementById('btnAddUrl').addEventListener('click', async () => {
        const url = document.getElementById('external_url').value || '';
        if (!url) return alert('Veuillez coller une URL.');
        try { await uploadExternalUrl(url, document.getElementById('alt_text').value || ''); }
        catch (e) { alert(e.message); }
    });

    // Crop modal (custom, no Bootstrap)
    function openCropModal() { document.getElementById('cropModal').classList.add('open'); }
    function closeCropModal() {
        document.getElementById('cropModal').classList.remove('open');
        if (cropper) { cropper.destroy(); cropper = null; }
    }
    document.getElementById('btnCropClose').addEventListener('click',  closeCropModal);
    document.getElementById('btnCropCancel').addEventListener('click', closeCropModal);

    document.getElementById('btnCrop').addEventListener('click', () => {
        if (!selectedFile) return;
        const img = document.getElementById('cropImage');
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            openCropModal();
            setTimeout(() => {
                cropper = new Cropper(img, { viewMode: 1, autoCropArea: 1, responsive: true });
            }, 100);
        };
        reader.readAsDataURL(selectedFile);
    });

    document.getElementById('btnCropSave').addEventListener('click', async () => {
        if (!cropper) return;
        const altText = document.getElementById('alt_text').value || '';
        const canvas  = cropper.getCroppedCanvas({ width: 1200 });
        canvas.toBlob(async blob => {
            closeCropModal();
            try {
                const file = new File([blob], selectedFile ? selectedFile.name : 'cropped.jpg', { type: blob.type || 'image/jpeg' });
                await uploadFiles([file], altText);
            } catch (e) { alert(e.message); }
        }, 'image/jpeg', 0.9);
    });

    // Delete
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Supprimer cette image définitivement ?')) return;
            try { await deleteMedia(btn.dataset.id); }
            catch (e) { alert(e.message); }
        });
    });

    // Sortable
    const grid = document.getElementById('mediaGrid');
    if (grid) {
        new Sortable(grid, {
            handle: '.handle',
            animation: 150,
            onEnd: async function() {
                try { await saveReorder(); }
                catch (e) { alert(e.message); }
            }
        });
    }
</script>
@endsection
