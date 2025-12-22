@extends('layouts.app')
@section('title','Médias | Restaurant')
@section('media_nav', 'active')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
    .media-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        background: #fff;
        transition: all .2s ease;
    }
    .media-dropzone.dragover {
        border-color: #05944F;
        background: #f0fdf4;
    }
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
    .media-card {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .media-thumb {
        width: 100%;
        height: 160px;
        object-fit: cover;
        background: #f3f4f6;
        display: block;
    }
    .media-card-footer {
        padding: 12px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 8px;
    }
    .handle {
        cursor: grab;
    }
    .handle:active {
        cursor: grabbing;
    }
    .crop-preview {
        width: 100%;
        max-height: 60vh;
        display: block;
        background: #111827;
    }
</style>
@endsection

@section('content')
<div class="content-header">
    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }}">
            {{ session()->get('alert.message') }}
        </div>
    @endif
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Gestion des médias</h1>
                <div class="text-muted">Galerie multi-photos (upload + URL) • drag&drop • recadrage (crop)</div>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('restaurant.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Médias</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="media-dropzone" id="dropzone">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="font-weight-bold">Upload (multi)</label>
                            <input type="file" id="images" class="form-control" multiple accept="image/*">
                            <small class="text-muted">Glisser-déposer possible. JPG/PNG/WEBP, 5 Mo max par image.</small>
                            <div class="mt-2 d-flex" style="gap: 8px; flex-wrap: wrap;">
                                <button class="btn btn-success" id="btnUpload">
                                    <i class="fas fa-cloud-upload-alt"></i> Uploader
                                </button>
                                <button class="btn btn-outline-primary" id="btnCrop" disabled>
                                    <i class="fas fa-crop-alt"></i> Recadrer (1 image)
                                </button>
                                <button class="btn btn-outline-secondary" id="btnClear">
                                    <i class="fas fa-times"></i> Vider la sélection
                                </button>
                            </div>
                            <div class="mt-3">
                                <label class="font-weight-bold">Texte alternatif (optionnel)</label>
                                <input type="text" id="alt_text" class="form-control" placeholder="Ex: Plat signature, intérieur du restaurant...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold">Ajouter via URL (externe)</label>
                            <div class="input-group">
                                <input type="url" id="external_url" class="form-control" placeholder="https://...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" id="btnAddUrl">
                                        <i class="fas fa-link"></i> Ajouter
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Recommandé: images dont vous avez les droits ou sources libres.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Galerie</h3>
            </div>
            <div class="card-body">
                <div class="media-grid" id="mediaGrid">
                    @foreach($media as $m)
                        @php
                            $src = $m->source === 'external'
                                ? $m->external_url
                                : ($m->file_name ? asset('images/restaurant_gallery/' . $m->file_name) : asset('images/placeholder.png'));
                        @endphp
                        <div class="media-card" data-id="{{ $m->id }}">
                            <img class="media-thumb" src="{{ $src }}" onerror="this.src='{{ asset('images/placeholder.png') }}'">
                            <div class="media-card-footer">
                                <div class="text-muted" style="font-size: 12px;">
                                    <i class="fas fa-grip-vertical handle"></i>
                                    <span class="ml-1">{{ $m->source === 'external' ? 'URL' : 'Upload' }}</span>
                                </div>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="{{ $m->id }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal crop -->
<div class="modal fade" id="cropModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recadrer l’image</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <img id="cropImage" class="crop-preview" alt="Crop preview">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnCropSave">
                    <i class="fas fa-check"></i> Appliquer & uploader
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
    const csrf = '{{ csrf_token() }}';
    const storeUrl = '{{ route('restaurant.media.store') }}';
    const reorderUrl = '{{ route('restaurant.media.reorder') }}';
    let cropper = null;
    let selectedFile = null;

    function getSelectedFiles() {
        const input = document.getElementById('images');
        return input.files ? Array.from(input.files) : [];
    }

    function setCropEnabled() {
        const files = getSelectedFiles();
        document.getElementById('btnCrop').disabled = !(files.length === 1);
        selectedFile = files.length === 1 ? files[0] : null;
    }

    async function uploadFiles(files, altText) {
        const fd = new FormData();
        files.forEach(f => fd.append('images[]', f));
        if (altText) fd.append('alt_text', altText);

        const res = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: fd
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur upload');
        window.location.reload();
    }

    async function uploadExternalUrl(url, altText) {
        const fd = new FormData();
        fd.append('external_url', url);
        if (altText) fd.append('alt_text', altText);
        const res = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: fd
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur URL');
        window.location.reload();
    }

    async function deleteMedia(id) {
        const url = '{{ url('/') }}/restaurant/media/' + id;
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur suppression');
        window.location.reload();
    }

    async function saveReorder() {
        const ids = Array.from(document.querySelectorAll('#mediaGrid .media-card')).map(el => parseInt(el.dataset.id, 10));
        const res = await fetch(reorderUrl, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ids })
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Erreur tri');
    }

    // Dropzone
    const dropzone = document.getElementById('dropzone');
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        const input = document.getElementById('images');
        input.files = e.dataTransfer.files;
        setCropEnabled();
    });

    document.getElementById('images').addEventListener('change', setCropEnabled);
    document.getElementById('btnClear').addEventListener('click', () => {
        const input = document.getElementById('images');
        input.value = '';
        setCropEnabled();
    });

    document.getElementById('btnUpload').addEventListener('click', async () => {
        const files = getSelectedFiles();
        const altText = document.getElementById('alt_text').value || '';
        if (!files.length) return alert('Sélectionnez au moins une image.');
        try {
            await uploadFiles(files, altText);
        } catch (e) {
            alert(e.message);
        }
    });

    document.getElementById('btnAddUrl').addEventListener('click', async () => {
        const url = document.getElementById('external_url').value || '';
        const altText = document.getElementById('alt_text').value || '';
        if (!url) return alert('Veuillez coller une URL.');
        try {
            await uploadExternalUrl(url, altText);
        } catch (e) {
            alert(e.message);
        }
    });

    // Crop (1 image)
    document.getElementById('btnCrop').addEventListener('click', () => {
        if (!selectedFile) return;
        const img = document.getElementById('cropImage');
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
            $('#cropModal').modal('show');
        };
        reader.readAsDataURL(selectedFile);
    });

    $('#cropModal').on('shown.bs.modal', function () {
        const img = document.getElementById('cropImage');
        cropper = new Cropper(img, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
        });
    }).on('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });

    document.getElementById('btnCropSave').addEventListener('click', async () => {
        if (!cropper) return;
        const altText = document.getElementById('alt_text').value || '';
        const canvas = cropper.getCroppedCanvas({ width: 1200 });
        canvas.toBlob(async (blob) => {
            try {
                const file = new File([blob], (selectedFile ? selectedFile.name : 'cropped.jpg'), { type: blob.type || 'image/jpeg' });
                await uploadFiles([file], altText);
            } catch (e) {
                alert(e.message);
            }
        }, 'image/jpeg', 0.9);
    });

    // Delete
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Supprimer cette image ?')) return;
            try {
                await deleteMedia(btn.dataset.id);
            } catch (e) {
                alert(e.message);
            }
        });
    });

    // Sortable
    new Sortable(document.getElementById('mediaGrid'), {
        handle: '.handle',
        animation: 150,
        onEnd: async function() {
            try {
                await saveReorder();
            } catch (e) {
                alert(e.message);
            }
        }
    });
</script>
@endsection


