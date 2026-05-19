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

<div class="card">
    <div class="card-body">
        <input type="hidden" name="context_media_status" value="{{ $backlogContext['media_status'] ?? request('media_status') }}">
        <input type="hidden" name="context_restaurant_id" value="{{ $backlogContext['restaurant_id'] ?? request('restaurant_id') }}">
        <input type="hidden" name="backlog_next_product_id" value="{{ $nextBacklogProductId ?? 0 }}">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="restaurant_id">Restaurant</label>
                    <select id="restaurant_id" name="restaurant_id" class="form-control {{ $errors->has('restaurant_id') ? 'is-invalid' : '' }}" required>
                        <option value="">Choisir...</option>
                        @foreach($restaurants as $restaurant)
                            <option value="{{ $restaurant->id }}" {{ (string) old('restaurant_id', $product->restaurant_id ?? '') === (string) $restaurant->id ? 'selected' : '' }}>
                                {{ $restaurant->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($errors->has('restaurant_id'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('restaurant_id') }}</strong></span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="category_id">Catégorie</label>
                    <select id="category_id" name="category_id" class="form-control {{ $errors->has('category_id') ? 'is-invalid' : '' }}" required>
                        <option value="">Choisir...</option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                data-restaurant-id="{{ $category->restaurant_id }}"
                                {{ (string) old('category_id', $product->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }} - Restaurant #{{ $category->restaurant_id }}
                            </option>
                        @endforeach
                    </select>
                    @if($errors->has('category_id'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('category_id') }}</strong></span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="name">Nom du plat</label>
                    <input type="text" id="name" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $product->name ?? '') }}" required>
                    @if($errors->has('name'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('name') }}</strong></span>
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="price">Prix</label>
                        <input type="text" id="price" name="price" class="form-control {{ $errors->has('price') ? 'is-invalid' : '' }}" value="{{ old('price', $product->price ?? '') }}" required>
                        @if($errors->has('price'))
                            <span class="invalid-feedback"><strong>{{ $errors->first('price') }}</strong></span>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="discount_price">Prix remisé</label>
                        <input type="text" id="discount_price" name="discount_price" class="form-control {{ $errors->has('discount_price') ? 'is-invalid' : '' }}" value="{{ old('discount_price', $product->discount_price ?? '') }}">
                        @if($errors->has('discount_price'))
                            <span class="invalid-feedback"><strong>{{ $errors->first('discount_price') }}</strong></span>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="size">Poids / taille</label>
                    <input type="text" id="size" name="size" class="form-control {{ $errors->has('size') ? 'is-invalid' : '' }}" value="{{ old('size', $product->size ?? '') }}">
                    @if($errors->has('size'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('size') }}</strong></span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}">{{ old('description', $product->description ?? '') }}</textarea>
                    @if($errors->has('description'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('description') }}</strong></span>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="image">Image du plat</label>
                    <input type="file" id="image" name="image" class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" accept=".jpg,.jpeg,.png,.webp">
                    @if($errors->has('image'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('image') }}</strong></span>
                    @endif
                    <small class="form-text text-muted">Formats: jpg, png, webp. Taille max: 8 Mo.</small>
                </div>

                <div class="form-group">
                    <label for="image_url">Ou URL d'image</label>
                    <input type="url" id="image_url" name="image_url" class="form-control {{ $errors->has('image_url') ? 'is-invalid' : '' }}" value="{{ $imageValue }}" placeholder="https://...">
                    @if($errors->has('image_url'))
                        <span class="invalid-feedback"><strong>{{ $errors->first('image_url') }}</strong></span>
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
                    <span class="text-danger d-block mt-2"><strong>{{ $errors->first('image_media_path') }}</strong></span>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Aperçu</h3>
                    </div>
                    <div class="card-body text-center">
                        <img id="product-image-preview" src="{{ $previewImage }}" alt="Aperçu" style="max-width:100%; width:280px; height:220px; object-fit:cover; border-radius:12px; border:1px solid #d1d5db;">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-right">
        @php
            $cancelQuery = array_filter([
                'media_status' => $backlogContext['media_status'] ?? request('media_status'),
                'restaurant_id' => $backlogContext['restaurant_id'] ?? request('restaurant_id'),
            ]);
            $cancelUrl = !empty($cancelQuery) ? route('total.pro', $cancelQuery) : url('/admin/all-products');
        @endphp
        <a href="{{ $cancelUrl }}" class="btn btn-secondary">Annuler</a>
        @if($isEdit && !empty($backlogContext['media_status']))
            <button type="submit" name="continue_work" value="1" class="btn btn-success">Enregistrer et suivant</button>
        @endif
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer le plat' }}</button>
    </div>
</div>

@section('script')
@parent
<script>
    (function () {
        const restaurantSelect = document.getElementById('restaurant_id');
        const categorySelect = document.getElementById('category_id');
        const imageInput = document.getElementById('image');
        const imageUrlInput = document.getElementById('image_url');
        const preview = document.getElementById('product-image-preview');

        function filterCategories() {
            const restaurantId = restaurantSelect.value;
            Array.from(categorySelect.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const matches = !restaurantId || option.dataset.restaurantId === restaurantId;
                option.hidden = !matches;

                if (!matches && option.selected) {
                    option.selected = false;
                }
            });
        }

        restaurantSelect.addEventListener('change', filterCategories);
        filterCategories();

        imageInput.addEventListener('change', function () {
            const file = imageInput.files && imageInput.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });

        imageUrlInput.addEventListener('input', function () {
            if (imageUrlInput.value.trim()) {
                preview.src = imageUrlInput.value.trim();
            }
        });

        document.querySelectorAll('.js-unified-media-select').forEach(function (select) {
            select.addEventListener('change', function () {
                const option = this.options[this.selectedIndex];
                const previewUrl = option ? option.dataset.preview : '';
                if (previewUrl) {
                    preview.src = previewUrl;
                }
            });
        });
    })();
</script>
@endsection
