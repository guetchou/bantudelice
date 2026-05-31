@extends('layouts.restaurant_app')
@section('title', 'Modifier le produit | ' . \App\Services\ConfigService::getCompanyName())
@section('topbar_title', 'Modifier le produit')
@section('product_nav', 'active')

@section('content')
<div style="max-width:800px;">

    @if(session()->has('alert'))
        <div class="alert alert-{{ session()->get('alert.type') }} alert-dismissible" role="alert">
            {{ session()->get('alert.message') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div style="background:var(--bd-surface);border:1px solid var(--bd-border);border-radius:var(--bd-radius);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--bd-border-2);display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--bd-text);">Modifier « {{ $product->name }} »</div>
                <div style="font-size:11px;color:var(--bd-text-3);margin-top:2px;">Mettez à jour les informations du produit</div>
            </div>
            <a href="{{ route('product.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;text-decoration:none;transition:.12s;"
               onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
               onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <form method="post" action="{{ route('product.update', $product->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="padding:24px 20px;display:flex;flex-direction:column;gap:20px;">

                {{-- ── Ligne 1 : Nom + Catégorie --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label for="name" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Nom du produit <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="text" name="name" id="name"
                               value="{{ old('name', $product->name) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('name') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('name')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="category_id" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Catégorie
                        </label>
                        <select name="category_id" id="category_id"
                                style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--bd-border);border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;appearance:auto;">
                            <option value="">Sans catégorie</option>
                            @foreach(\App\Category::where('restaurant_id', auth()->user()->restaurant->id)->orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ── Ligne 2 : Prix + Prix remisé + Poids --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                    <div>
                        <label for="price" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Prix (FCFA) <span style="color:#dc2626;">*</span>
                        </label>
                        <input required type="number" name="price" id="price"
                               value="{{ old('price', $product->price) }}" min="0"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('price') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='{{ $errors->has('price') ? '#dc2626' : 'var(--bd-border)' }}';" />
                        @error('price')
                            <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="discount_price" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Prix remisé (FCFA)
                        </label>
                        <input type="number" name="discount_price" id="discount_price"
                               value="{{ old('discount_price', $product->discount_price) }}" min="0" placeholder="Optionnel"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--bd-border);border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='var(--bd-border)';" />
                    </div>
                    <div>
                        <label for="size" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                            Poids / Taille
                        </label>
                        <input type="text" name="size" id="size"
                               value="{{ old('size', $product->size) }}"
                               style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--bd-border);border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;transition:border-color .12s;"
                               onfocus="this.style.borderColor='var(--bd-green)';"
                               onblur="this.style.borderColor='var(--bd-border)';" />
                    </div>
                </div>

                {{-- ── Description --}}
                <div>
                    <label for="description" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:6px;">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="4"
                              placeholder="Décrivez le produit : ingrédients, allergènes, particularités…"
                              style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid {{ $errors->has('description') ? '#dc2626' : 'var(--bd-border)' }};border-radius:var(--bd-radius);font-size:13px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;resize:vertical;transition:border-color .12s;"
                              onfocus="this.style.borderColor='var(--bd-green)';"
                              onblur="this.style.borderColor='{{ $errors->has('description') ? '#dc2626' : 'var(--bd-border)' }}';">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ── Image --}}
                <div style="background:var(--bd-surface-2);border:1px solid var(--bd-border-2);border-radius:var(--bd-radius);padding:16px 18px;">
                    <div style="font-size:12px;font-weight:700;color:var(--bd-text);margin-bottom:14px;">Image du produit</div>

                    @php
                        $img = $product->image ?? null;
                        $imgSrc = method_exists($product, 'publicImageUrl')
                            ? $product->publicImageUrl()
                            : ($img ? (strpos($img, 'http') === 0 ? $img : asset('images/product_images/' . $img)) : null);
                    @endphp

                    <div style="display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:flex-start;">
                        <div id="imgPreviewWrap"
                             style="width:88px;height:88px;border-radius:var(--bd-radius);border:2px dashed var(--bd-border);background:var(--bd-surface);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            @if($imgSrc)
                                <img id="logo" src="{{ $imgSrc }}" alt="{{ $product->name }}"
                                     style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.style.display='none';document.getElementById('imgIcon').style.display='block';">
                                <i id="imgIcon" class="fas fa-image" style="display:none;font-size:22px;color:var(--bd-border);"></i>
                            @else
                                <img id="logo" src="" style="display:none;width:100%;height:100%;object-fit:cover;">
                                <i id="imgIcon" class="fas fa-image" style="font-size:22px;color:var(--bd-border);"></i>
                            @endif
                        </div>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div>
                                <label for="file-input"
                                       style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--bd-radius);border:1px solid var(--bd-border);background:var(--bd-surface);color:var(--bd-text-2);font-size:12px;font-weight:600;cursor:pointer;transition:.12s;"
                                       onmouseover="this.style.borderColor='var(--bd-green)';this.style.color='var(--bd-green)';"
                                       onmouseout="this.style.borderColor='var(--bd-border)';this.style.color='var(--bd-text-2)';">
                                    <i class="fas fa-upload"></i> Remplacer l'image
                                </label>
                                <input type="file" id="file-input" name="image" accept="image/*"
                                       style="display:none;" onchange="logo1(this)">
                                <div style="font-size:11px;color:var(--bd-text-3);margin-top:4px;">Laisser vide pour conserver l'actuelle</div>
                                @error('image')
                                    <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="image_url" style="display:block;font-size:12px;font-weight:600;color:var(--bd-text);margin-bottom:5px;">
                                    Ou URL d'image
                                </label>
                                <input type="url" name="image_url" id="image_url"
                                       value="{{ old('image_url', (strpos($product->image ?? '', 'http') === 0) ? $product->image : '') }}"
                                       placeholder="https://…"
                                       style="width:100%;box-sizing:border-box;padding:8px 11px;border:1px solid var(--bd-border);border-radius:var(--bd-radius);font-size:12px;font-family:var(--bd-font);background:var(--bd-surface);color:var(--bd-text);outline:none;"
                                       oninput="previewImageUrl(this.value)">
                                @error('image_url')
                                    <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                                @enderror
                            </div>
                            @if(!empty($mediaLibraryOptions))
                            <div>
                                @include('partials.unified_media_select', [
                                    'name' => 'image_media_path',
                                    'label' => 'Ou médiathèque CMS',
                                    'selected' => old('image_media_path'),
                                    'options' => $mediaLibraryOptions ?? [],
                                    'previewTarget' => 'logo',
                                ])
                                @error('image_media_path')
                                    <div style="font-size:11px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <div style="padding:14px 20px;border-top:1px solid var(--bd-border-2);background:var(--bd-surface-2);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <a href="{{ route('product.index') }}"
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
function logo1(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('logo');
        const icon = document.getElementById('imgIcon');
        img.src = e.target.result;
        img.style.display = 'block';
        if (icon) icon.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}
function previewImageUrl(url) {
    if (!url) return;
    const img = document.getElementById('logo');
    const icon = document.getElementById('imgIcon');
    img.src = url;
    img.style.display = 'block';
    if (icon) icon.style.display = 'none';
}
document.querySelectorAll('.js-unified-media-select').forEach(function(select) {
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const previewUrl = option ? option.dataset.preview : '';
        if (previewUrl) previewImageUrl(previewUrl);
    });
});
</script>
@endsection
