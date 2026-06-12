@php
    $partnerFinanceRows = $financialDashboard['rows'] ?? [];
@endphp

@if(!empty($partnerFinanceRows))
    <section class="bd-partner-finance">
        @foreach($partnerFinanceRows as $partnerFinanceRow)
            <div class="bd-partner-finance__grid">
                @foreach($partnerFinanceRow as $card)
                    <article class="bd-partner-finance__card is-{{ $card['tone'] }}">
                        <span class="bd-partner-finance__label">{{ $card['label'] }}</span>
                        <strong class="bd-partner-finance__amount">{{ number_format(round((float) ($card['amount'] ?? 0)), 0, ',', ' ') }} FCFA</strong>
                        <p class="bd-partner-finance__description">{{ $card['description'] }}</p>
                        <p class="bd-partner-finance__formula">{{ $card['formula'] }}</p>
                    </article>
                @endforeach
            </div>
        @endforeach
    </section>
@endif
