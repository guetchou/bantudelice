@if(!empty($partnerMetricCards ?? []))
    <section class="bd-partner-metrics">
        @foreach($partnerMetricCards as $metric)
            <article class="bd-partner-metric">
                <span class="bd-partner-metric__label">{{ $metric['label'] }}</span>
                <strong class="bd-partner-metric__value">{{ $metric['value'] }}</strong>
                @if(!empty($metric['hint']))
                    <p class="bd-partner-metric__hint">{{ $metric['hint'] }}</p>
                @endif
            </article>
        @endforeach
    </section>
@endif
