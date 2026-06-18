@extends('frontend.layouts.app-modern')
@php $foodBrandName = \App\Services\ConfigService::getCompanyName(); @endphp
@section('title', 'Rejoignez ' . $foodBrandName . ' — Devenez partenaire')
@section('body_class', 'bd-partenaires-page')

@section('style')
<style>
/* ===== Variables ===== */
:root {
    --prt-green: #009543;
    --prt-green-dark: #007836;
    --prt-green-light: #f0fdf4;
    --prt-amber: #f59e0b;
    --prt-slate: #0f172a;
    --prt-muted: #64748b;
    --prt-border: #e2e8f0;
    --prt-surface: #fff;
    --prt-radius: 16px;
}

/* ===== Reset de section ===== */
.prt-shell { padding-top: 80px; }

/* ===== HERO ===== */
.prt-hero {
    background: linear-gradient(135deg, #007836 0%, #009543 50%, #00b850 100%);
    padding: 72px 16px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.prt-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.prt-hero__inner {
    position: relative;
    max-width: 720px;
    margin: 0 auto;
}
.prt-hero__badge {
    display: inline-block;
    background: rgba(255,255,255,.15);
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 99px;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,.2);
}
.prt-hero__title {
    font-size: clamp(1.8rem, 5vw, 3rem);
    font-weight: 900;
    color: #fff;
    margin: 0 0 16px;
    line-height: 1.15;
}
.prt-hero__sub {
    font-size: clamp(.95rem, 2.5vw, 1.15rem);
    color: rgba(255,255,255,.85);
    margin: 0 0 36px;
    line-height: 1.6;
}
.prt-hero__ctas {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.prt-hero__cta {
    padding: 13px 28px;
    border-radius: 99px;
    font-size: .92rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.prt-hero__cta--restaurant {
    background: #fff;
    color: var(--prt-green-dark);
}
.prt-hero__cta--restaurant:hover {
    background: #f0fdf4;
    color: var(--prt-green-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
}
.prt-hero__cta--driver {
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 2px solid rgba(255,255,255,.4);
}
.prt-hero__cta--driver:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
    transform: translateY(-2px);
}

/* ===== SECTION WRAPPER ===== */
.prt-section {
    padding: 64px 16px;
}
.prt-section--alt {
    background: #f8fafc;
}
.prt-container {
    max-width: 960px;
    margin: 0 auto;
}
.prt-section-title {
    font-size: clamp(1.3rem, 3vw, 1.8rem);
    font-weight: 900;
    color: var(--prt-slate);
    text-align: center;
    margin: 0 0 8px;
}
.prt-section-sub {
    text-align: center;
    color: var(--prt-muted);
    font-size: .95rem;
    margin: 0 0 40px;
}

/* ===== CARTES CHOIX ===== */
.prt-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 640px) {
    .prt-cards { grid-template-columns: 1fr; }
}
.prt-card {
    background: var(--prt-surface);
    border-radius: var(--prt-radius);
    border: 2px solid var(--prt-border);
    padding: 32px 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    transition: border-color .2s, box-shadow .2s;
}
.prt-card:hover {
    border-color: var(--prt-green);
    box-shadow: 0 8px 32px rgba(0,149,67,.1);
}
.prt-card__icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.prt-card--restaurant .prt-card__icon { background: #f0fdf4; color: var(--prt-green); }
.prt-card--driver .prt-card__icon { background: #fffbeb; color: var(--prt-amber); }
.prt-card__title {
    font-size: 1.15rem;
    font-weight: 900;
    color: var(--prt-slate);
    margin: 0;
}
.prt-card__hook {
    font-size: .9rem;
    color: var(--prt-muted);
    line-height: 1.5;
    margin: 0;
}
.prt-card__reqs {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.prt-card__reqs li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: .85rem;
    color: #334155;
    line-height: 1.4;
}
.prt-card__reqs li::before {
    content: '';
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--prt-green);
    flex-shrink: 0;
    margin-top: 1px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2 6l3 3 5-5' stroke='%23fff' stroke-width='1.8' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 10px;
}
.prt-card__badge {
    display: inline-block;
    background: #f0fdf4;
    color: var(--prt-green);
    font-size: .78rem;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 99px;
    border: 1px solid #bbf7d0;
    width: fit-content;
}
.prt-card__time {
    font-size: .78rem;
    color: var(--prt-muted);
}
.prt-card__cta {
    margin-top: auto;
    display: block;
    text-align: center;
    padding: 13px 20px;
    border-radius: 99px;
    font-size: .9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .2s;
}
.prt-card--restaurant .prt-card__cta {
    background: var(--prt-green);
    color: #fff;
}
.prt-card--restaurant .prt-card__cta:hover {
    background: var(--prt-green-dark);
    color: #fff;
    transform: translateY(-1px);
}
.prt-card--driver .prt-card__cta {
    background: var(--prt-amber);
    color: #fff;
}
.prt-card--driver .prt-card__cta:hover {
    background: #d97706;
    color: #fff;
    transform: translateY(-1px);
}

/* ===== TIMELINE 3 ÉTAPES ===== */
.prt-timeline {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
    position: relative;
}
@media (max-width: 640px) {
    .prt-timeline { grid-template-columns: 1fr; }
    .prt-timeline-line { display: none; }
}
.prt-timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    position: relative;
    padding: 0 8px;
}
.prt-timeline-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 24px;
    left: calc(50% + 28px);
    right: calc(-50% + 28px);
    height: 2px;
    background: linear-gradient(90deg, var(--prt-green) 0%, #bbf7d0 100%);
}
@media (max-width: 640px) {
    .prt-timeline-step:not(:last-child)::after { display: none; }
}
.prt-timeline-num {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--prt-green);
    color: #fff;
    font-size: 1.1rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,149,67,.25);
}
.prt-timeline-title {
    font-size: .95rem;
    font-weight: 800;
    color: var(--prt-slate);
}
.prt-timeline-desc {
    font-size: .82rem;
    color: var(--prt-muted);
    line-height: 1.5;
}

/* ===== CHIFFRES / SOCIAL PROOF ===== */
.prt-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    text-align: center;
}
@media (max-width: 480px) {
    .prt-stats { grid-template-columns: 1fr 1fr; }
    .prt-stats__item:last-child { grid-column: span 2; }
}
.prt-stats__item {
    background: var(--prt-surface);
    border: 1px solid var(--prt-border);
    border-radius: var(--prt-radius);
    padding: 24px 16px;
}
.prt-stats__num {
    font-size: 2rem;
    font-weight: 900;
    color: var(--prt-green);
    line-height: 1;
    margin-bottom: 4px;
}
.prt-stats__label {
    font-size: .82rem;
    color: var(--prt-muted);
    font-weight: 500;
}

/* ===== FAQ ACCORDION ===== */
.prt-faq {
    max-width: 680px;
    margin: 0 auto;
}
.prt-faq-item {
    border: 1px solid var(--prt-border);
    border-radius: 12px;
    margin-bottom: 8px;
    overflow: hidden;
    background: var(--prt-surface);
}
.prt-faq-question {
    width: 100%;
    background: none;
    border: none;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    text-align: left;
    font-size: .92rem;
    font-weight: 700;
    color: var(--prt-slate);
    cursor: pointer;
    transition: background .15s;
}
.prt-faq-question:hover { background: #f8fafc; }
.prt-faq-chevron {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    transition: transform .25s;
    color: var(--prt-muted);
}
.prt-faq-item.is-open .prt-faq-chevron { transform: rotate(180deg); }
.prt-faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height .3s ease;
}
.prt-faq-item.is-open .prt-faq-answer { max-height: 300px; }
.prt-faq-answer-inner {
    padding: 0 20px 18px;
    font-size: .88rem;
    color: var(--prt-muted);
    line-height: 1.65;
}

/* ===== WHATSAPP CTA ===== */
.prt-wa {
    background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);
    border: 1px solid #bbf7d0;
    border-radius: var(--prt-radius);
    padding: 40px 28px;
    text-align: center;
    max-width: 560px;
    margin: 0 auto;
}
.prt-wa__icon {
    width: 56px;
    height: 56px;
    background: #25d366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.5rem;
    color: #fff;
    box-shadow: 0 4px 16px rgba(37,211,102,.3);
}
.prt-wa__title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--prt-slate);
    margin: 0 0 8px;
}
.prt-wa__sub {
    font-size: .88rem;
    color: var(--prt-muted);
    margin: 0 0 20px;
    line-height: 1.5;
}
.prt-wa__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #25d366;
    color: #fff;
    font-size: .9rem;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 99px;
    text-decoration: none;
    transition: all .2s;
}
.prt-wa__btn:hover {
    background: #1da851;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37,211,102,.35);
}
.prt-wa__secondary {
    display: block;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--prt-muted);
}
.prt-wa__secondary a {
    color: var(--prt-green);
    font-weight: 600;
    text-decoration: none;
}
.prt-wa__secondary a:hover { text-decoration: underline; }
</style>
@endsection

@section('content')
<div class="prt-shell">

    {{-- ===== SECTION 1 : HERO ===== --}}
    <div class="prt-hero">
        <div class="prt-hero__inner">
            <span class="prt-hero__badge">Plateforme N°1 au Congo-Brazzaville</span>
            <h1 class="prt-hero__title">Rejoignez la famille {{ $foodBrandName }}</h1>
            <p class="prt-hero__sub">
                Développez votre activité ou gagnez à votre rythme.<br>
                Choisissez votre profil pour commencer.
            </p>
            <div class="prt-hero__ctas">
                <a href="#choisir" class="prt-hero__cta prt-hero__cta--restaurant">
                    <i class="fas fa-store"></i> Je suis un restaurant
                </a>
                <a href="#choisir" class="prt-hero__cta prt-hero__cta--driver">
                    <i class="fas fa-motorcycle"></i> Je suis livreur
                </a>
            </div>
        </div>
    </div>

    {{-- ===== SECTION 2 : DEUX CARTES DE CHOIX ===== --}}
    <section class="prt-section" id="choisir">
        <div class="prt-container">
            <h2 class="prt-section-title">Quel est votre profil ?</h2>
            <p class="prt-section-sub">Chaque parcours est distinct — choisissez le vôtre pour voir les prérequis et commencer.</p>

            <div class="prt-cards">

                {{-- Card Restaurant --}}
                <div class="prt-card prt-card--restaurant">
                    <div class="prt-card__icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="prt-card__title">Partenaire Restaurant</h3>
                    <p class="prt-card__hook">Touchez des milliers de clients à Brazzaville et augmentez votre chiffre d'affaires sans investissement initial.</p>
                    <ul class="prt-card__reqs">
                        <li>Établissement physique ou cuisine professionnelle</li>
                        <li>Menu disponible (photos non obligatoires au démarrage)</li>
                        <li>Compte Mobile Money MTN/Airtel ou compte bancaire</li>
                    </ul>
                    <span class="prt-card__badge">0 FCFA d'inscription</span>
                    <span class="prt-card__time"><i class="fas fa-clock" style="color:var(--prt-green);margin-right:4px;"></i> 5 min pour soumettre votre dossier</span>
                    <a href="{{ route('partner') }}" class="prt-card__cta">
                        <i class="fas fa-arrow-right"></i> Inscrire mon restaurant
                    </a>
                </div>

                {{-- Card Livreur --}}
                <div class="prt-card prt-card--driver">
                    <div class="prt-card__icon">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <h3 class="prt-card__title">Livreur Indépendant</h3>
                    <p class="prt-card__hook">Gérez votre emploi du temps librement et gagnez à chaque livraison. Zéro objectif imposé.</p>
                    <ul class="prt-card__reqs">
                        <li>Véhicule personnel (moto ou vélo)</li>
                        <li>Permis de conduire en cours de validité</li>
                        <li>Smartphone Android ou iOS avec GPS</li>
                    </ul>
                    <span class="prt-card__badge">0 FCFA d'inscription</span>
                    <span class="prt-card__time"><i class="fas fa-clock" style="color:var(--prt-amber);margin-right:4px;"></i> 5 min pour postuler</span>
                    <a href="{{ route('driver') }}" class="prt-card__cta">
                        <i class="fas fa-arrow-right"></i> Devenir livreur
                    </a>
                </div>

            </div>
        </div>
    </section>

    {{-- ===== SECTION 3 : PROCESSUS EN 3 ÉTAPES ===== --}}
    <section class="prt-section prt-section--alt">
        <div class="prt-container">
            <h2 class="prt-section-title">Ce qui se passe après votre inscription</h2>
            <p class="prt-section-sub">Transparence totale — voici les 3 étapes de votre onboarding.</p>

            <div class="prt-timeline">
                <div class="prt-timeline-step">
                    <div class="prt-timeline-num">1</div>
                    <div class="prt-timeline-title">Soumettez</div>
                    <p class="prt-timeline-desc">Remplissez le formulaire en ligne en moins de 5 minutes. Aucune visite physique requise pour commencer.</p>
                </div>
                <div class="prt-timeline-step">
                    <div class="prt-timeline-num">2</div>
                    <div class="prt-timeline-title">Validation</div>
                    <p class="prt-timeline-desc">Notre équipe examine votre dossier et vous contacte <strong>sous 48h ouvrées</strong> par téléphone ou WhatsApp.</p>
                </div>
                <div class="prt-timeline-step">
                    <div class="prt-timeline-num">3</div>
                    <div class="prt-timeline-title">Lancez-vous</div>
                    <p class="prt-timeline-desc">Dès validation, vous accédez à votre espace dédié et pouvez commencer à recevoir des commandes ou des missions.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== SECTION 4 : SOCIAL PROOF ===== --}}
    <section class="prt-section">
        <div class="prt-container">
            <h2 class="prt-section-title">{{ $foodBrandName }} en chiffres</h2>
            <p class="prt-section-sub">Rejoignez une communauté qui grandit chaque semaine.</p>

            <div class="prt-stats">
                <div class="prt-stats__item">
                    <div class="prt-stats__num">50+</div>
                    <div class="prt-stats__label">Restaurants partenaires</div>
                </div>
                <div class="prt-stats__item">
                    <div class="prt-stats__num">100+</div>
                    <div class="prt-stats__label">Livreurs actifs</div>
                </div>
                <div class="prt-stats__item">
                    <div class="prt-stats__num">100%</div>
                    <div class="prt-stats__label">Brazzaville couverte</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== SECTION 5 : FAQ ===== --}}
    <section class="prt-section prt-section--alt">
        <div class="prt-container">
            <h2 class="prt-section-title">Questions fréquentes</h2>
            <p class="prt-section-sub">Ce que tout partenaire potentiel veut savoir avant de s'inscrire.</p>

            <div class="prt-faq">

                <div class="prt-faq-item">
                    <button class="prt-faq-question" onclick="prtToggleFaq(this)">
                        Y a-t-il des frais d'inscription ou d'abonnement ?
                        <svg class="prt-faq-chevron" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="prt-faq-answer">
                        <div class="prt-faq-answer-inner">
                            Non. L'inscription est entièrement gratuite pour les restaurants comme pour les livreurs. {{ $foodBrandName }} perçoit uniquement une commission sur les commandes effectivement livrées — pas de frais fixes, pas d'abonnement mensuel.
                        </div>
                    </div>
                </div>

                <div class="prt-faq-item">
                    <button class="prt-faq-question" onclick="prtToggleFaq(this)">
                        Comment suis-je payé(e) ?
                        <svg class="prt-faq-chevron" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="prt-faq-answer">
                        <div class="prt-faq-answer-inner">
                            Les versements sont effectués <strong>chaque semaine</strong>, directement sur votre compte Mobile Money MTN ou Airtel Money, ou par virement bancaire si vous l'avez indiqué. Les livreurs voient leurs gains accumulés en temps réel dans leur espace et peuvent demander un versement à tout moment.
                        </div>
                    </div>
                </div>

                <div class="prt-faq-item">
                    <button class="prt-faq-question" onclick="prtToggleFaq(this)">
                        Puis-je rejoindre si j'habite en dehors de Brazzaville ?
                        <svg class="prt-faq-chevron" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="prt-faq-answer">
                        <div class="prt-faq-answer-inner">
                            Pour l'instant, {{ $foodBrandName }} est opérationnel uniquement à <strong>Brazzaville</strong>. Nous travaillons à l'extension vers Pointe-Noire et d'autres villes. Si vous êtes en dehors de la zone, vous pouvez déposer votre dossier — notre équipe vous contactera dès que votre secteur est couvert.
                        </div>
                    </div>
                </div>

                <div class="prt-faq-item">
                    <button class="prt-faq-question" onclick="prtToggleFaq(this)">
                        Quels documents faut-il pour devenir livreur ?
                        <svg class="prt-faq-chevron" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="prt-faq-answer">
                        <div class="prt-faq-answer-inner">
                            Pour postuler, vous aurez besoin de : une pièce d'identité valide (CNI ou passeport), votre permis de conduire, la carte grise de votre véhicule, et une photo récente. Si vous n'avez pas encore tous ces documents, vous pouvez commencer le formulaire et finaliser votre dossier ultérieurement.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ===== SECTION 6 : WHATSAPP CTA ===== --}}
    <section class="prt-section">
        <div class="prt-container">
            <div class="prt-wa">
                <div class="prt-wa__icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h3 class="prt-wa__title">Une question avant de vous lancer ?</h3>
                <p class="prt-wa__sub">
                    Notre équipe répond sur WhatsApp en moins d'une heure, du lundi au samedi de 8h à 20h.
                    Pas de formulaire à remplir — juste une conversation.
                </p>
                <a href="https://wa.me/242XXXXXXXXXX" target="_blank" rel="noopener noreferrer" class="prt-wa__btn">
                    <i class="fab fa-whatsapp"></i> Écrire sur WhatsApp
                </a>
                <span class="prt-wa__secondary">
                    Déjà inscrit ? <a href="{{ route('login') }}">Connectez-vous →</a>
                </span>
            </div>
        </div>
    </section>

</div>
@endsection

@section('script')
<script>
function prtToggleFaq(btn) {
    var item = btn.closest('.prt-faq-item');
    var isOpen = item.classList.contains('is-open');
    document.querySelectorAll('.prt-faq-item.is-open').forEach(function(el) {
        el.classList.remove('is-open');
    });
    if (!isOpen) item.classList.add('is-open');
}

// Scroll doux vers #choisir
document.querySelectorAll('a[href="#choisir"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        var target = document.getElementById('choisir');
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>
@endsection
