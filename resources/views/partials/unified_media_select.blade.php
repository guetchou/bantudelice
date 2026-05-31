@php
    $fieldName          = $name ?? 'media_path';
    $fieldLabel         = $label ?? 'Choisir depuis la médiathèque';
    $fieldSelected      = $selected ?? '';
    $fieldPreviewTarget = $previewTarget ?? null;
    $fieldOptions       = $options ?? [];
    $uid                = 'ums_' . preg_replace('/[^a-z0-9]/', '', uniqid('', true));

    // Aplatir les groupes en liste simple
    $allItems = [];
    foreach ($fieldOptions as $groupLabel => $groupItems) {
        foreach ($groupItems as $item) {
            $allItems[] = array_merge($item, ['group' => $groupLabel]);
        }
    }
@endphp

<div class="ums-wrap" id="{{ $uid }}_wrap">
    <input type="hidden" name="{{ $fieldName }}" id="{{ $uid }}_val" value="{{ $fieldSelected }}"
           data-preview-target="{{ $fieldPreviewTarget }}">

    {{-- Déclencheur --}}
    <button type="button" class="ums-trigger" onclick="umsOpen('{{ $uid }}')" id="{{ $uid }}_trigger">
        <span class="ums-trigger__thumb" id="{{ $uid }}_thumb">
            @if($fieldSelected)
                <img src="{{ asset($fieldSelected) }}" alt="">
            @else
                <i class="fas fa-images"></i>
            @endif
        </span>
        <span class="ums-trigger__label" id="{{ $uid }}_label">
            {{ $fieldSelected ? basename($fieldSelected) : 'Choisir depuis la médiathèque' }}
        </span>
        <i class="fas fa-expand-alt ums-trigger__icon"></i>
    </button>

    @if($fieldSelected)
        <button type="button" class="ums-clear" onclick="umsClear('{{ $uid }}')" title="Effacer">
            <i class="fas fa-times"></i>
        </button>
    @endif
</div>

{{-- Modale lightbox --}}
<div class="ums-modal" id="{{ $uid }}_modal" role="dialog" aria-modal="true" style="display:none;">
    <div class="ums-modal__backdrop" onclick="umsClose('{{ $uid }}')"></div>
    <div class="ums-modal__box">

        <div class="ums-modal__head">
            <span class="ums-modal__title"><i class="fas fa-images"></i> Médiathèque</span>
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="text" class="ums-search" id="{{ $uid }}_search"
                       placeholder="Rechercher…"
                       oninput="umsFilter('{{ $uid }}', this.value)">
                <button type="button" class="ums-modal__close" onclick="umsClose('{{ $uid }}')" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Filtres groupes --}}
        @php $uniqueGroups = array_values(array_unique(array_column($allItems, 'group'))); @endphp
        @if(count($uniqueGroups) > 1)
        <div class="ums-filters" id="{{ $uid }}_filters">
            <button type="button" class="ums-filter is-active" data-group="" onclick="umsSetGroup('{{ $uid }}', '', this)">Tous</button>
            @foreach($uniqueGroups as $grp)
                <button type="button" class="ums-filter" data-group="{{ $grp }}" onclick="umsSetGroup('{{ $uid }}', '{{ $grp }}', this)">{{ $grp }}</button>
            @endforeach
        </div>
        @endif

        <div class="ums-grid" id="{{ $uid }}_grid">

            {{-- Option vide --}}
            <div class="ums-item {{ $fieldSelected === '' ? 'is-selected' : '' }}"
                 data-value="" data-label="Aucune" data-preview="" data-group=""
                 data-uid="{{ $uid }}" onclick="umsSelect(this)">
                <div class="ums-item__img ums-item__img--empty">
                    <i class="fas fa-ban"></i>
                </div>
                <span class="ums-item__name">Aucune</span>
            </div>

            @foreach($allItems as $item)
                @php
                    $ext    = strtolower(pathinfo($item['value'], PATHINFO_EXTENSION));
                    $isImg  = in_array($ext, ['jpg','jpeg','png','gif','webp','svg']);
                    $active = $fieldSelected === $item['value'];
                @endphp
                <div class="ums-item {{ $active ? 'is-selected' : '' }}"
                     data-value="{{ $item['value'] }}"
                     data-label="{{ $item['label'] }}"
                     data-preview="{{ $item['preview'] ?? $item['value'] }}"
                     data-group="{{ $item['group'] }}"
                     data-uid="{{ $uid }}"
                     onclick="umsSelect(this)">
                    <div class="ums-item__img">
                        @if($isImg)
                            <img src="{{ asset($item['value']) }}"
                                 alt="{{ $item['label'] }}"
                                 loading="lazy"
                                 onerror="this.parentNode.innerHTML='<i class=\'fas fa-image\' style=\'color:#9ca3af\'></i>'">
                        @else
                            <i class="fas fa-file" style="color:#9ca3af;font-size:1.2rem;"></i>
                        @endif
                    </div>
                    <span class="ums-item__name" title="{{ $item['label'] }}">{{ basename($item['value']) }}</span>
                </div>
            @endforeach

            @if(empty($allItems))
                <div style="grid-column:1/-1;padding:40px;text-align:center;color:var(--bd-text-3,#9ca3af);">
                    <i class="fas fa-images" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    Aucun média disponible
                </div>
            @endif
        </div>

        {{-- Footer : preview + confirmer --}}
        <div class="ums-modal__foot">
            <div class="ums-preview" id="{{ $uid }}_modalpreview">
                <span id="{{ $uid }}_foot_label" style="font-size:12px;color:var(--bd-text-3,#9ca3af);">Aucune image sélectionnée</span>
            </div>
            <button type="button" class="ums-confirm" onclick="umsClose('{{ $uid }}')">
                <i class="fas fa-check"></i> Confirmer
            </button>
        </div>

    </div>
</div>

@once
<style>
/* ── UMS Trigger ─────────────────────────────────────────────── */
.ums-wrap { position: relative; display: inline-flex; align-items: center; gap: 6px; width: 100%; }
.ums-trigger {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 8px 12px;
    border: 1px solid var(--bd-border, #e5e7eb);
    border-radius: 8px;
    background: var(--bd-surface, #fff);
    color: var(--bd-text-2, #4b5563);
    cursor: pointer; text-align: left;
    font-family: var(--bd-font, 'Poppins', sans-serif);
    font-size: 12px; font-weight: 500;
    transition: border-color .12s;
}
.ums-trigger:hover { border-color: var(--bd-green, #009543); }
.ums-trigger__thumb {
    width: 36px; height: 36px; border-radius: 6px;
    background: var(--bd-surface-2, #f9fafb);
    border: 1px solid var(--bd-border, #e5e7eb);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden; color: var(--bd-text-3, #9ca3af);
}
.ums-trigger__thumb img { width: 100%; height: 100%; object-fit: cover; }
.ums-trigger__label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ums-trigger__icon { font-size: 11px; color: var(--bd-text-3, #9ca3af); flex-shrink: 0; }
.ums-clear {
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid var(--bd-border, #e5e7eb);
    background: var(--bd-surface-2, #f9fafb);
    color: var(--bd-text-3, #9ca3af);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 11px; flex-shrink: 0;
    transition: background .12s, color .12s;
}
.ums-clear:hover { background: rgba(239,68,68,.1); color: #dc2626; border-color: rgba(239,68,68,.3); }

/* ── UMS Modale ──────────────────────────────────────────────── */
.ums-modal {
    position: fixed; inset: 0; z-index: 9999;
    align-items: center; justify-content: center;
}
.ums-modal__backdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
}
.ums-modal__box {
    position: relative; z-index: 1;
    width: min(680px, 96vw);
    max-height: 85vh;
    background: var(--bd-surface, #fff);
    border-radius: 14px;
    border: 1px solid var(--bd-border, #e5e7eb);
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    display: flex; flex-direction: column;
    overflow: hidden;
}
.ums-modal__head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--bd-border, #e5e7eb);
    flex-shrink: 0;
}
.ums-modal__title {
    font-size: 14px; font-weight: 700;
    color: var(--bd-text, #111827);
    display: flex; align-items: center; gap: 8px;
}
.ums-modal__title i { color: var(--bd-green, #009543); }
.ums-modal__close {
    width: 30px; height: 30px; border-radius: 7px;
    border: 1px solid var(--bd-border, #e5e7eb);
    background: var(--bd-surface-2, #f9fafb);
    color: var(--bd-text-2, #4b5563);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 12px; transition: background .12s;
}
.ums-modal__close:hover { background: rgba(239,68,68,.1); color: #dc2626; }

/* Recherche */
.ums-search {
    height: 30px; padding: 0 10px;
    border: 1px solid var(--bd-border, #e5e7eb); border-radius: 7px;
    background: var(--bd-surface-2, #f9fafb);
    color: var(--bd-text, #111827);
    font-family: var(--bd-font, 'Poppins', sans-serif);
    font-size: 12px; width: 180px;
    transition: border-color .12s;
}
.ums-search:focus { outline: none; border-color: var(--bd-green, #009543); }

/* Filtres groupes */
.ums-filters {
    display: flex; gap: 6px; flex-wrap: wrap;
    padding: 10px 16px;
    border-bottom: 1px solid var(--bd-border-2, #f3f4f6);
    flex-shrink: 0;
}
.ums-filter {
    padding: 4px 12px; border-radius: 999px;
    border: 1px solid var(--bd-border, #e5e7eb);
    background: var(--bd-surface-2, #f9fafb);
    color: var(--bd-text-2, #4b5563);
    font-size: 11px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font, 'Poppins', sans-serif);
    transition: background .12s, color .12s, border-color .12s;
}
.ums-filter:hover { border-color: var(--bd-green, #009543); color: var(--bd-green, #009543); }
.ums-filter.is-active { background: var(--bd-green, #009543); color: #fff; border-color: var(--bd-green, #009543); }

/* Grille */
.ums-grid {
    flex: 1; overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(84px, 1fr));
    gap: 8px;
    padding: 16px;
}
.ums-item {
    display: flex; flex-direction: column; align-items: center; gap: 5px;
    padding: 6px; border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer; transition: border-color .12s, background .12s;
    background: var(--bd-surface-2, #f9fafb);
}
.ums-item:hover { border-color: var(--bd-green, #009543); }
.ums-item.is-selected {
    border-color: var(--bd-green, #009543);
    background: var(--bd-green-pale, #f0fdf4);
}
[data-theme="dark"] .ums-item.is-selected { background: rgba(0,149,67,.12); }
.ums-item__img {
    width: 68px; height: 68px; border-radius: 7px;
    overflow: hidden; background: var(--bd-surface, #fff);
    border: 1px solid var(--bd-border, #e5e7eb);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ums-item__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ums-item__img--empty i { color: var(--bd-text-3, #9ca3af); font-size: 1.1rem; }
.ums-item__name {
    font-size: 10px; color: var(--bd-text-2, #4b5563);
    text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    max-width: 76px; width: 100%;
}
.ums-item.is-hidden { display: none; }

/* Footer modale */
.ums-modal__foot {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 20px;
    border-top: 1px solid var(--bd-border, #e5e7eb);
    background: var(--bd-surface-2, #f9fafb);
    flex-shrink: 0;
}
.ums-preview {
    display: flex; align-items: center; gap: 10px;
    min-width: 0; flex: 1;
}
.ums-preview img { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; border: 1px solid var(--bd-border, #e5e7eb); flex-shrink: 0; }
.ums-confirm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 18px; border-radius: 7px; border: none;
    background: var(--bd-green, #009543); color: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: var(--bd-font, 'Poppins', sans-serif);
    transition: background .12s;
}
.ums-confirm:hover { background: var(--bd-green-dark, #007836); }
</style>
<script>
(function () {
    window.umsOpen = function (uid) {
        var modal = document.getElementById(uid + '_modal');
        if (!modal) return;
        modal.style.display = 'flex';
        var search = document.getElementById(uid + '_search');
        if (search) { search.value = ''; umsFilter(uid, ''); }
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ if(search) search.focus(); }, 50);
    };

    window.umsClose = function (uid) {
        var modal = document.getElementById(uid + '_modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.umsSelect = function (el) {
        var uid     = el.getAttribute('data-uid');
        var val     = el.getAttribute('data-value');
        var label   = el.getAttribute('data-label');
        var preview = el.getAttribute('data-preview');
        var shortName = val ? (label.split(' · ').pop() || label) : 'Choisir depuis la médiathèque';

        // Valeur cachée
        var inp = document.getElementById(uid + '_val');
        if (inp) inp.value = val;

        // Déclencheur : thumb + label
        var thumb = document.getElementById(uid + '_thumb');
        if (thumb) thumb.innerHTML = val && preview
            ? '<img src="' + preview + '" alt="">'
            : '<i class="fas fa-images"></i>';

        var lblEl = document.getElementById(uid + '_label');
        if (lblEl) { lblEl.textContent = shortName; lblEl.style.color = val ? '' : 'var(--bd-text-3,#9ca3af)'; }

        // Preview externe (cfgLogoPreview etc.)
        var ptId = inp ? inp.getAttribute('data-preview-target') : null;
        if (ptId) {
            var pt = document.getElementById(ptId);
            if (pt && pt.tagName === 'IMG') pt.src = preview || '';
        }

        // Footer modale
        var footLabel = document.getElementById(uid + '_foot_label');
        if (footLabel) {
            footLabel.innerHTML = val
                ? '<img src="' + preview + '" alt=""> <span>' + shortName + '</span>'
                : '<span>Aucune image sélectionnée</span>';
        }

        // Activer l'item
        var grid = document.getElementById(uid + '_grid');
        if (grid) grid.querySelectorAll('.ums-item').forEach(function(i){ i.classList.remove('is-selected'); });
        el.classList.add('is-selected');
    };

    window.umsFilter = function (uid, q) {
        var grid = document.getElementById(uid + '_grid');
        if (!grid) return;
        var term = q.toLowerCase().trim();
        grid.querySelectorAll('.ums-item').forEach(function (item) {
            var name = (item.getAttribute('data-label') || '').toLowerCase();
            item.classList.toggle('is-hidden', term !== '' && !name.includes(term));
        });
    };

    window.umsSetGroup = function (uid, group, btn) {
        var filters = document.getElementById(uid + '_filters');
        if (filters) filters.querySelectorAll('.ums-filter').forEach(function(b){ b.classList.remove('is-active'); });
        if (btn) btn.classList.add('is-active');
        var grid = document.getElementById(uid + '_grid');
        if (!grid) return;
        grid.querySelectorAll('.ums-item').forEach(function (item) {
            var g = item.getAttribute('data-group') || '';
            item.classList.toggle('is-hidden', group !== '' && g !== group);
        });
    };

    window.umsClear = function (uid) {
        var inp = document.getElementById(uid + '_val');
        if (inp) inp.value = '';
        var thumb = document.getElementById(uid + '_thumb');
        if (thumb) thumb.innerHTML = '<i class="fas fa-images"></i>';
        var lbl = document.getElementById(uid + '_label');
        if (lbl) { lbl.textContent = 'Choisir depuis la médiathèque'; lbl.style.color = 'var(--bd-text-3,#9ca3af)'; }
        var ptId = inp ? inp.getAttribute('data-preview-target') : null;
        if (ptId) { var pt = document.getElementById(ptId); if (pt && pt.tagName === 'IMG') pt.src = ''; }
        var grid = document.getElementById(uid + '_grid');
        if (grid) grid.querySelectorAll('.ums-item').forEach(function(i){ i.classList.remove('is-selected'); });
    };

    // Fermeture ESC
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.ums-modal').forEach(function (m) {
            if (m.style.display !== 'none') umsClose(m.id.replace('_modal', ''));
        });
    });
}());
</script>
@endonce
