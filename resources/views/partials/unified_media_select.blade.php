@php
    $fieldName = $name ?? 'media_path';
    $fieldLabel = $label ?? 'Choisir depuis la médiathèque';
    $fieldSelected = $selected ?? '';
    $fieldPreviewTarget = $previewTarget ?? null;
    $fieldOptions = $options ?? [];
@endphp

<div class="form-group" style="margin-top:0.75rem;">
    <label style="display:block; font-weight:600; color:#374151; margin-bottom:0.5rem;">
        {{ $fieldLabel }}
    </label>
    <select
        name="{{ $fieldName }}"
        class="form-control js-unified-media-select"
        @if($fieldPreviewTarget) data-preview-target="{{ $fieldPreviewTarget }}" @endif
        style="padding:0.875rem 1rem; border:2px solid #E5E7EB; border-radius:12px;">
        <option value="">Aucun choix</option>
        @foreach($fieldOptions as $groupLabel => $groupItems)
            <optgroup label="{{ $groupLabel }}">
                @foreach($groupItems as $item)
                    <option
                        value="{{ $item['value'] }}"
                        data-preview="{{ $item['preview'] }}"
                        @selected($fieldSelected === $item['value'])>
                        {{ $item['label'] }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
