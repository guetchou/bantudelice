@php
    $isEdit = isset($product);
    $imageValue = old('image_url', ($isEdit && strpos($product->image ?? '', 'http') === 0) ? $product->image : '');
    $previewImage = old('image_url');
    $selectedMediaPath = old('image_media_path');
    if (!$previewImage && $isEdit) {
        $previewImage = method_exists($product, 'publicImageUrl')
            ? $product->publicImageUrl()
            : (!empty($product->image)
                ? (strpos($product->image, 'http') === 0 ? $product->image : asset('images/product_images/' . $product->image))
                : asset('images/product_images/default-food.jpg'));
    }
    if (!$previewImage) {
        $previewImage = asset('images/product_images/default-food.jpg');
    }
@endphp

<style>
.prd-form-wrap { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.prd-form-col { display:flex; flex-direction:column; gap:14px; }
.prd-field { display:flex; flex-direction:column; gap:5px; }
.prd-label { font-size:13px; font-weight:600; color:#374151; }
.prd-input { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:#111827; background:#fff; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.prd-input:focus { outline:none; border-color:#1e3a5f; box-shadow:0 0 0 3px rgba(30,58,95,.12); }
.prd-input--error { border-color:#ef4444; }
.prd-field-error { font-size:11px; color:#dc2626; font-weight:500; }
.prd-field-hint { font-size:11px; color:#9ca3af; margin-top:2px; }
.prd-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-size:18px; padding-right:36px; }
.prd-price-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.prd-preview-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.prd-preview-card__head { padding:10px 14px; border-bottom:1px solid #f3f4f6; font-size:12px; font-weight:700; color:#374151; }
.prd-preview-card__body { padding:14px; text-align:center; }
.prd-footer { display:flex; justify-content:flex-end; align-items:center; gap:10px; padding:14px 0 0; border-top:1px solid #f3f4f6; margin-top:8px; }
.prd-btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#1e3a5f; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.prd-btn-primary:hover { opacity:.85; }
.prd-btn-success { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#16a34a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
.prd-btn-success:hover { opacity:.85; }
.prd-btn-cancel { display:inline-flex; align-items:center; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #d1d5db; color:#374151; background:#fff; transition:background .15s; }
.prd-btn-cancel:hover { background:#f9fafb; color:#111827; text-decoration:none; }
@media (max-width:768px) {
    .prd-form-wrap { grid-template-columns:1fr; }
    .prd-price-row { grid-template-columns:1fr; }
}
</style>

<input type="hidden" name="context_media_status" value="{{ $backlogContext['media_status'] ?? request('media_status') }}">
<input type="hidden" name="context_restaurant_id" value="{{ $backlogContext['restaurant_id'] ?? request('restaurant_id') }}">
<input type="hidden" name="backlog_next_product_id" value="{{ $nextBacklogProductId ?? 0 }}">

<div class="prd-form-wrap">
    {{-- Colonne gauche : données produit --}}
    <div class="prd-form-col">
        <div class="prd-field">
            <label class="prd-label" for="restaurant_id">Restaurant</label>
            <select id="restaurant_id" name="restaurant_id" class="prd-input prd-select {{ $errors->has('restaurant_id') ? 'prd-input--error' : '' }}" required>
                <option value="">Choisir...</option>
                @foreach($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}" {{ (string) old('restaurant_id', $product->restaurant_id ?? '') === (string) $restaurant->id ? 'selected' : '' }}>
                        {{ $restaurant->name }}
                    </option>
                @endforeach
            </select>
            @if($errors->has('restaurant_id'))
                <span class="prd-field-error">{{ $errors->first('restaurant_id') }}</span>
            @endif
        </div>

        <div class="prd-field">
            <label class="prd-label" for="category_id">Catégorie</label>
            <select id="category_id" name="category_id" class="prd-input prd-select {{ $errors->has('category_id') ? 'prd-input--error' : '' }}" required>
                <option value="">Choisir...</option>
                @foreach($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        data-restaurant-id="{{ $category->restaurant_id }}"
                        {{ (string) old('category_id', $product->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }} — Restaurant #{{ $category->restaurant_id }}
                    </option>
                @endforeach
            </select>
            @if($errors->has('category_id'))
                <span class="prd-field-error">{{ $errors->first('category_id') }}</span>
            @endif
        </div>

        <div class="prd-field">
            <label class="prd-label" for="name">Nom du plat</label>
            <input type="text" id="name" name="name" class="prd-input {{ $errors->has('name') ? 'prd-input--error' : '' }}" value="{{ old('name', $product->name ?? '') }}" required>
            @if($errors->has('name'))
                <span class="prd-field-error">{{ $errors->first('name') }}</span>
            @endif
        </div>

        <div class="prd-price-row">
            <div class="prd-field">
                <label class="prd-label" for="price">Prix (FCFA)</label>
                <input type="text" id="price" name="price" class="prd-input {{ $errors->has('price') ? 'prd-input--error' : '' }}" value="{{ old('price', $product->price ?? '') }}" required>
                @if($errors->has('price'))
                    <span class="prd-field-error">{{ $errors->first('price') }}</span>
                @endif
            </div>
            <div class="prd-field">
                <label class="prd-label" for="discount_price">Prix remisé</label>
                <input type="text" id="discount_price" name="discount_price" class="prd-input {{ $errors->has('discount_price') ? 'prd-input--error' : '' }}" value="{{ old('discount_price', $product->discount_price ?? '') }}">
                @if($errors->has('discount_price'))
                    <span class="prd-field-error">{{ $errors->first('discount_price') }}</span>
                @endif
            </div>
        </div>

        <div class="prd-field">
            <label class="prd-label" for="size">Poids / taille</label>
            <input type="text" id="size" name="size" class="prd-input {{ $errors->has('size') ? 'prd-input--error' : '' }}" value="{{ old('size', $product->size ?? '') }}">
            @if($errors->has('size'))
                <span class="prd-field-error">{{ $errors->first('size') }}</span>
            @endif
        </div>

        <div class="prd-field">
            <label class="prd-label" for="description">Description</label>
            <textarea id="description" name="description" rows="5" class="prd-input {{ $errors->has('description') ? 'prd-input--error' : '' }}" style="resize:vertical;">{{ old('description', $product->description ?? '') }}</textarea>
            @if($errors->has('description'))
                <span class="prd-field-error">{{ $errors->first('description') }}</span>
            @endif
        </div>
    </div>

    {{-- Colonne droite : image --}}
    <div class="prd-form-col">
        <div class="prd-field">
            <label class="prd-label" for="image">Image du plat</label>
            <input type="file" id="image" name="image" class="prd-input {{ $errors->has('image') ? 'prd-input--error' : '' }}" accept=".jpg,.jpeg,.png,.webp">
            @if($errors->has('image'))
                <span class="prd-field-error">{{ $errors->first('image') }}</span>
            @endif
            <span class="prd-field-hint">Formats : jpg, png, webp — max 8 Mo</span>
        </div>

        <div class="prd-field">
            <label class="prd-label" for="image_url">Ou URL d'image</label>
            <input type="url" id="image_url" name="image_url" class="prd-input {{ $errors->has('image_url') ? 'prd-input--error' : '' }}" value="{{ $imageValue }}" placeholder="https://...">
            @if($errors->has('image_url'))
                <span class="prd-field-error">{{ $errors->first('image_url') }}</span>
            @endif
        </div>

        @include('partials.unified_media_select', [
            'name' => 'image_media_path',
            'label' => 'Ou choisir depuis la médiathèque CMS',
            'selected' => $selectedMediaPath,
            'options' => $mediaLibraryOptions ?? [],
            'previewTarget' => 'product-image-preview',
        ])
        @if($errors->has('image_media_path'))
            <span class="prd-field-error">{{ $errors->first('image_media_path') }}</span>
        @endif

        <div class="prd-preview-card">
            <div class="prd-preview-card__head">Aperçu</div>
            <div class="prd-preview-card__body">
                <img id="product-image-preview" src="{{ $previewImage }}" alt="Aperçu" style="max-width:100%; width:280px; height:220px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb;">
            </div>
        </div>
    </div>
</div>

<div class="prd-footer">
    @php
        $cancelQuery = array_filter([
            'media_status' => $backlogContext['media_status'] ?? request('media_status'),
            'restaurant_id' => $backlogContext['restaurant_id'] ?? request('restaurant_id'),
        ]);
        $cancelUrl = !empty($cancelQuery) ? route('total.pro', $cancelQuery) : url('/admin/all-products');
    @endphp
    <a href="{{ $cancelUrl }}" class="prd-btn-cancel">Annuler</a>
    @if($isEdit && !empty($backlogContext['media_status']))
        <button type="submit" name="continue_work" value="1" class="prd-btn-success">
            <i class="fas fa-arrow-right"></i> Enregistrer et suivant
        </button>
    @endif
    <button type="submit" class="prd-btn-primary">
        <i class="fas fa-save"></i> {{ $isEdit ? 'Mettre à jour' : 'Créer le plat' }}
    </button>
</div>

@section('script')
@parent
<script>
(function () {
    var restaurantSelect = document.getElementById('restaurant_id');
    var categorySelect = document.getElementById('category_id');
    var imageInput = document.getElementById('image');
    var imageUrlInput = document.getElementById('image_url');
    var preview = document.getElementById('product-image-preview');

    function filterCategories() {
        var restaurantId = restaurantSelect.value;
        Array.from(categorySelect.options).forEach(function (option) {
            if (!option.value) { option.hidden = false; return; }
            var matches = !restaurantId || option.dataset.restaurantId === restaurantId;
            option.hidden = !matches;
            if (!matches && option.selected) { option.selected = false; }
        });
    }

    restaurantSelect.addEventListener('change', filterCategories);
    filterCategories();

    imageInput.addEventListener('change', function () {
        var file = imageInput.files && imageInput.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    });

    imageUrlInput.addEventListener('input', function () {
        if (imageUrlInput.value.trim()) preview.src = imageUrlInput.value.trim();
    });

    document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
        select.addEventListener('change', function () {
            var option = this.options[this.selectedIndex];
            if (option && option.dataset.preview) preview.src = option.dataset.preview;
        });
    });
})();
</script>
@endsection
