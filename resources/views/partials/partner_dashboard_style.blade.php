<style>
    .bd-partner-page {
        display: grid;
        gap: 1.5rem;
    }
    .bd-restaurant-dashboard {
        display: grid;
        gap: 1.5rem;
    }
    .bd-restaurant-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    .bd-restaurant-action {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 1.2rem 1.2rem 1.15rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        box-shadow: 0 16px 32px rgba(15, 23, 42, .06);
        display: grid;
        gap: .8rem;
    }
    .bd-restaurant-action::after {
        content: '';
        position: absolute;
        inset: auto 0 0 0;
        height: 4px;
        background: linear-gradient(90deg, #ff5a1f, #fb923c);
        opacity: .9;
    }
    .bd-restaurant-action__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .9rem;
    }
    .bd-restaurant-action__eyebrow {
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #9a3412;
    }
    .bd-restaurant-action__title {
        margin: .18rem 0 0;
        color: #0f172a;
        font-size: 1.1rem;
        line-height: 1.15;
        font-weight: 900;
    }
    .bd-restaurant-action__desc {
        margin: .45rem 0 0;
        color: #475569;
        font-size: .9rem;
        line-height: 1.55;
    }
    .bd-restaurant-action__icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff7ed;
        color: #e04d15;
        font-size: 1.1rem;
        box-shadow: inset 0 0 0 1px rgba(251, 146, 60, .14);
    }
    .bd-restaurant-action__meta {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }
    .bd-restaurant-action__value {
        color: #111827;
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
        letter-spacing: -.04em;
    }
    .bd-restaurant-action__hint {
        display: block;
        margin-top: .22rem;
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
    }
    .bd-restaurant-action__link {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        min-height: 40px;
        padding: 0 .95rem;
        border-radius: 999px;
        border: 1px solid #fdba74;
        background: #fff7ed;
        color: #9a3412;
        font-size: .82rem;
        font-weight: 800;
        text-decoration: none !important;
        white-space: nowrap;
    }
    .bd-restaurant-action__link:hover {
        color: #7c2d12;
        background: #ffedd5;
    }
    .bd-restaurant-action__link.is-muted {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #475569;
    }
    .bd-restaurant-insights {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, .9fr);
        gap: 1rem;
    }
    .bd-restaurant-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, .07);
        overflow: hidden;
    }
    .bd-restaurant-panel__head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.35rem 1.35rem 0;
    }
    .bd-restaurant-panel__title {
        margin: 0;
        color: #111827;
        font-size: 1.08rem;
        font-weight: 900;
    }
    .bd-restaurant-panel__subtitle {
        margin: .28rem 0 0;
        color: #64748b;
        font-size: .88rem;
        line-height: 1.55;
    }
    .bd-restaurant-panel__body {
        padding: 1.2rem 1.35rem 1.35rem;
    }
    .bd-restaurant-pill-switch {
        display: inline-flex;
        gap: .45rem;
        padding: .32rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .bd-restaurant-pill-switch .nav-link {
        border-radius: 999px;
        border: 0;
        padding: .55rem .9rem;
        font-size: .78rem;
        font-weight: 800;
        color: #64748b;
    }
    .bd-restaurant-pill-switch .nav-link.active {
        background: linear-gradient(135deg, #e04d15, #ff5a1f);
        color: #fff;
    }
    .bd-restaurant-kpi-stack {
        display: grid;
        gap: .9rem;
    }
    .bd-restaurant-kpi {
        border-radius: 22px;
        padding: 1rem 1.05rem;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
        border: 1px solid rgba(251, 146, 60, .18);
    }
    .bd-restaurant-kpi--cool {
        background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        border-color: #bfdbfe;
    }
    .bd-restaurant-kpi--soft {
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
        border-color: #bbf7d0;
    }
    .bd-restaurant-kpi__label {
        display: block;
        color: #64748b;
        font-size: .76rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .12em;
    }
    .bd-restaurant-kpi__value {
        display: block;
        margin-top: .45rem;
        color: #111827;
        font-size: 1.8rem;
        line-height: 1;
        font-weight: 900;
    }
    .bd-restaurant-kpi__text {
        display: block;
        margin-top: .45rem;
        color: #475569;
        font-size: .86rem;
        line-height: 1.55;
    }
    .bd-restaurant-chart-wrap {
        position: relative;
        height: 320px;
    }
    .bd-restaurant-chart-wrap--compact {
        position: relative;
        height: 260px;
    }
    .bd-partner-shell {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        padding: 1.6rem 1.6rem 1.5rem;
        background:
            radial-gradient(circle at top right, rgba(254, 215, 170, .34), transparent 28%),
            radial-gradient(circle at bottom left, rgba(191, 219, 254, .18), transparent 34%),
            linear-gradient(135deg, #fff7ed 0%, #ffffff 54%, #f8fafc 100%);
        border: 1px solid rgba(251, 146, 60, .18);
        box-shadow: 0 22px 56px rgba(15, 23, 42, .08);
    }
    .bd-partner-shell__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .4rem .72rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .84);
        border: 1px solid rgba(251, 146, 60, .18);
        color: #9a3412;
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .12em;
    }
    .bd-partner-shell__layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(280px, .9fr);
        gap: 1.25rem;
        align-items: end;
        margin-top: 1rem;
    }
    .bd-partner-shell__aside {
        display: grid;
        gap: .9rem;
    }
    .bd-partner-shell h1 {
        margin: 0;
        color: #111827;
        font-size: clamp(2rem, 4vw, 2.8rem);
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: -.03em;
    }
    .bd-partner-shell p {
        margin: .9rem 0 0;
        max-width: 720px;
        color: #475569;
        line-height: 1.7;
        font-size: .96rem;
    }
    .bd-partner-shell__list {
        display: grid;
        gap: .7rem;
    }
    .bd-partner-shell__identity {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr);
        gap: .9rem;
        align-items: center;
        padding: 1rem;
        border-radius: 22px;
        background: rgba(255,255,255,.88);
        border: 1px solid rgba(251, 146, 60, .16);
        box-shadow: 0 16px 36px rgba(15, 23, 42, .06);
    }
    .bd-partner-shell__identity-mark {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        overflow: hidden;
        background: linear-gradient(135deg, #fb923c, #ff5a1f);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        font-weight: 900;
        box-shadow: 0 18px 32px rgba(249, 115, 22, .24);
    }
    .bd-partner-shell__identity-mark img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bd-partner-shell__identity-copy {
        display: grid;
        gap: .2rem;
    }
    .bd-partner-shell__identity-copy strong {
        color: #111827;
        font-size: 1rem;
        line-height: 1.2;
    }
    .bd-partner-shell__identity-copy small {
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
    }
    .bd-partner-shell__identity-tag {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: .32rem .58rem;
        border-radius: 999px;
        background: #ffedd5;
        color: #9a3412;
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .bd-partner-shell__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .9rem 1rem;
        border-radius: 18px;
        background: rgba(255,255,255,.82);
        border: 1px solid rgba(226, 232, 240, .92);
    }
    .bd-partner-shell__item-label {
        color: #64748b;
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .bd-partner-shell__item-value {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 900;
    }
    .bd-partner-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    .bd-partner-metric {
        border-radius: 22px;
        padding: 1.1rem 1.15rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .06);
        display: grid;
        gap: .45rem;
    }
    .bd-partner-metric__label {
        color: #64748b;
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .bd-partner-metric__value {
        color: #0f172a;
        font-size: 1.75rem;
        line-height: 1.02;
        font-weight: 900;
    }
    .bd-partner-metric__hint {
        color: #475569;
        font-size: .9rem;
        line-height: 1.55;
        margin: 0;
    }
    .bd-partner-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .bd-partner-panel__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        padding: 1.25rem 1.35rem 0;
    }
    .bd-partner-panel__title {
        margin: 0;
        color: #111827;
        font-size: 1.08rem;
        font-weight: 900;
    }
    .bd-partner-panel__subtitle {
        margin: .3rem 0 0;
        color: #64748b;
        font-size: .88rem;
        line-height: 1.5;
    }
    .bd-partner-finance {
        display: grid;
        gap: 1rem;
    }
    .bd-partner-finance__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    .bd-partner-finance__card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 1.2rem 1.25rem;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .08);
        display: grid;
        gap: .55rem;
    }
    .bd-partner-finance__label {
        font-size: .82rem;
        font-weight: 800;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .bd-partner-finance__amount {
        font-size: 1.75rem;
        line-height: 1.05;
        color: #0f172a;
    }
    .bd-partner-finance__description,
    .bd-partner-finance__formula {
        margin: 0;
        font-size: .9rem;
        line-height: 1.5;
    }
    .bd-partner-finance__description { color: #475569; }
    .bd-partner-finance__formula { color: #64748b; }
    .bd-partner-finance__card.is-primary { border-color: #bfdbfe; background: #eff6ff; }
    .bd-partner-finance__card.is-primary .bd-partner-finance__amount { color: #1d4ed8; }
    .bd-partner-finance__card.is-success { border-color: #bbf7d0; background: #f0fdf4; }
    .bd-partner-finance__card.is-success .bd-partner-finance__amount { color: #007836; }
    .bd-partner-finance__card.is-orange { border-color: #fed7aa; background: #fff7ed; }
    .bd-partner-finance__card.is-orange .bd-partner-finance__amount { color: #c2410c; }
    .bd-partner-finance__card.is-warning { border-color: #fde68a; background: #fffbeb; }
    .bd-partner-finance__card.is-warning .bd-partner-finance__amount { color: #b45309; }
    .bd-partner-page .small-box {
        border-radius: 22px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .bd-partner-page .small-box .inner {
        padding: 1.2rem 1.15rem .8rem;
    }
    .bd-partner-page .small-box h3 {
        font-size: 1.85rem;
        line-height: 1;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: .45rem;
    }
    .bd-partner-page .small-box p {
        margin: 0;
        color: #64748b;
        font-size: .88rem;
        line-height: 1.5;
    }
    .bd-partner-page .small-box .icon {
        top: 16px;
        right: 14px;
        opacity: .9;
    }
    .bd-partner-page .small-box .icon i {
        font-size: 28px;
    }
    .bd-partner-page .small-box-footer {
        border: 0;
        color: #fff !important;
        font-weight: 800;
        padding: .72rem 1rem;
        text-align: center;
    }
    .bd-partner-page .card {
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 16px 36px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .bd-partner-page .card-header {
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
    }
    .bd-partner-page .card-title {
        font-weight: 800;
        color: #0f172a;
    }
    @media (max-width: 1100px) {
        .bd-partner-shell__layout,
        .bd-partner-metrics,
        .bd-partner-finance__grid,
        .bd-restaurant-actions,
        .bd-restaurant-insights {
            grid-template-columns: 1fr;
        }
    }
</style>
