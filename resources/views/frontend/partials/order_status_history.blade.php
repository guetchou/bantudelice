@if($statusHistory->isNotEmpty())
<section class="trk-history" aria-labelledby="trk-history-title">
    <div class="trk-history__head">
        <div>
            <span class="trk-history__eyebrow">Historique</span>
            <h3 id="trk-history-title">Étapes de votre commande</h3>
        </div>
        <span class="trk-history__count">{{ $statusHistory->count() }} étape(s)</span>
    </div>

    <ol class="trk-history__list">
        @foreach($statusHistory as $entry)
            <li class="trk-history__item">
                <span class="trk-history__icon" aria-hidden="true">
                    <i class="fas {{ $entry['icon'] }}"></i>
                </span>
                <span class="trk-history__copy">
                    <strong>{{ $entry['label'] }}</strong>
                    <small>{{ $entry['description'] }}</small>
                </span>
                <time class="trk-history__time" datetime="{{ $entry['occurred_at']->toIso8601String() }}">
                    <strong>{{ $entry['time_label'] }}</strong>
                    <small>{{ $entry['date_label'] }}</small>
                </time>
            </li>
        @endforeach
    </ol>
</section>

<style>
.trk-history{background:#fff;border-radius:18px;box-shadow:0 2px 14px rgba(0,0,0,.09);margin:4px 14px 14px;padding:18px}.trk-history__head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.trk-history__eyebrow{display:block;font-size:.66rem;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;font-weight:800;margin-bottom:3px}.trk-history h3{font-size:.95rem;font-weight:900;color:#111;margin:0}.trk-history__count{font-size:.68rem;font-weight:800;color:#15803d;background:#dcfce7;border-radius:999px;padding:4px 9px;white-space:nowrap}.trk-history__list{list-style:none;margin:0;padding:0}.trk-history__item{display:grid;grid-template-columns:38px minmax(0,1fr) auto;gap:10px;align-items:center;position:relative;padding:9px 0}.trk-history__item:not(:last-child)::after{content:'';position:absolute;left:18px;top:38px;bottom:-8px;width:2px;background:#dcfce7}.trk-history__icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f0fdf4;color:#15803d;position:relative;z-index:1;font-size:.82rem}.trk-history__copy{display:flex;flex-direction:column;min-width:0}.trk-history__copy strong{font-size:.82rem;color:#111;line-height:1.25}.trk-history__copy small{font-size:.72rem;color:#6b7280;line-height:1.35;margin-top:2px}.trk-history__time{text-align:right;display:flex;flex-direction:column;font-variant-numeric:tabular-nums}.trk-history__time strong{font-size:.78rem;color:#111}.trk-history__time small{font-size:.66rem;color:#9ca3af;margin-top:2px}@media(max-width:420px){.trk-history__item{grid-template-columns:34px minmax(0,1fr)}.trk-history__time{grid-column:2;text-align:left;flex-direction:row;gap:5px}.trk-history__icon{width:32px;height:32px}.trk-history__item:not(:last-child)::after{left:15px;top:34px}}
</style>
@endif
