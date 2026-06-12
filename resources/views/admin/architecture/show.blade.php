@extends('layouts.admin-modern')
@section('title', 'Architecture admin | Admin')
@section('page_title', 'Architecture admin')
@section('nav_active', 'metrics')

@section('style')
<style>
.bd-admin-architecture {
    display: grid;
    gap: 1.1rem;
}

.bd-admin-architecture__hero,
.bd-admin-architecture__preview,
.bd-admin-architecture__details {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 18px 40px rgba(15,23,42,.04);
}

.bd-admin-architecture__hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: end;
    padding: 1.1rem 1.2rem;
}

.bd-admin-architecture__eyebrow,
.bd-admin-architecture__chip {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 .72rem;
    border-radius: 999px;
    background: rgba(15,23,42,.05);
    color: #334155;
    font-size: .66rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.bd-admin-architecture__eyebrow {
    background: rgba(0,149,67,.08);
    color: #007836;
}

.bd-admin-architecture__hero h1 {
    margin: .65rem 0 .3rem;
    font-family: var(--f-d);
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -.045em;
    color: var(--text);
}

.bd-admin-architecture__hero p {
    margin: 0;
    max-width: 72ch;
    color: var(--text-3);
    font-size: .84rem;
    line-height: 1.55;
}

.bd-admin-architecture__hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    margin-top: .85rem;
}

.bd-admin-architecture__hero-actions {
    display: flex;
    align-items: center;
    gap: .6rem;
}

.bd-admin-architecture__preview {
    overflow: hidden;
}

.bd-admin-architecture__preview-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem .95rem;
    border-bottom: 1px solid rgba(15,23,42,.06);
}

.bd-admin-architecture__preview-head h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.025em;
    color: var(--text);
}

.bd-admin-architecture__preview-head p {
    margin: .2rem 0 0;
    color: var(--text-3);
    font-size: .77rem;
    line-height: 1.45;
}

.bd-admin-architecture__preview-frame {
    width: 100%;
    min-height: calc(100vh - 280px);
    border: 0;
    background: #fff;
}

.bd-admin-architecture__details {
    padding: .2rem .2rem .3rem;
}

.bd-admin-architecture__details summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .9rem 1rem;
    font-size: .83rem;
    font-weight: 700;
    color: var(--text);
}

.bd-admin-architecture__details summary::-webkit-details-marker {
    display: none;
}

.bd-admin-architecture__details-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    padding: 0 1rem 1rem;
}

.bd-admin-architecture__stat {
    padding: .85rem .9rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid rgba(15,23,42,.06);
}

.bd-admin-architecture__stat strong {
    display: block;
    font-size: .68rem;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .08em;
}

.bd-admin-architecture__stat span {
    display: block;
    margin-top: .3rem;
    font-family: var(--f-u);
    font-size: .92rem;
    font-weight: 700;
    color: var(--text);
    overflow-wrap: anywhere;
}

.bd-admin-architecture__empty {
    padding: 1.5rem;
    color: var(--red);
    font-weight: 600;
}

@media (max-width: 1100px) {
    .bd-admin-architecture__hero,
    .bd-admin-architecture__preview-head,
    .bd-admin-architecture__details-meta {
        grid-template-columns: 1fr;
    }

    .bd-admin-architecture__hero-actions {
        justify-content: flex-start;
        flex-wrap: wrap;
    }
}

@media (max-width: 767px) {
    .bd-admin-architecture__details-meta {
        grid-template-columns: 1fr;
    }

    .bd-admin-architecture__preview-frame {
        min-height: 72vh;
    }
}
</style>
@endsection

@section('content')
<div class="bd-admin-architecture">
    <section class="bd-admin-architecture__hero">
        <div>
            <span class="bd-admin-architecture__eyebrow">Prototype branché</span>
            <h1>Architecture admin hiérarchique</h1>
            <p>Vue de convergence entre shell global, navigation par écosystème et dashboard admin. La scène principale doit rester la prévisualisation, pas la documentation technique.</p>
            <div class="bd-admin-architecture__hero-meta">
                <span class="bd-admin-architecture__chip">Sidebar niveau 1: BantuDelice, Kende, Mema</span>
                <span class="bd-admin-architecture__chip">Arborescence unique: pilotage + opérations</span>
                <span class="bd-admin-architecture__chip">Aucune rupture sur les routes actuelles</span>
            </div>
        </div>
        <div class="bd-admin-architecture__hero-actions">
            @if($maquetteAvailable)
                <a href="{{ route('admin.architecture.preview') }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;padding:7px 16px;background:#1e3a5f;color:#fff;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">Ouvrir seule</a>
            @endif
        </div>
    </section>

    <section class="bd-admin-architecture__preview">
        <div class="bd-admin-architecture__preview-head">
            <div>
                <h2>Prévisualisation intégrée</h2>
                <p>La maquette reste le centre de lecture. Les métadonnées techniques sont volontairement déplacées en second niveau.</p>
            </div>
        </div>

        @if($maquetteAvailable)
            <iframe
                class="bd-admin-architecture__preview-frame"
                src="{{ route('admin.architecture.preview') }}"
                title="Maquette admin hiérarchique">
            </iframe>
        @else
            <div class="bd-admin-architecture__empty">La maquette n'est pas disponible sur ce noeud. Vérifie la synchronisation du dossier `maquette/`.</div>
        @endif
    </section>

    <details class="bd-admin-architecture__details">
        <summary>
            <span>Métadonnées techniques</span>
            <span>{{ $maquetteAvailable ? 'Disponibles' : 'Incomplètes' }}</span>
        </summary>
        <div class="bd-admin-architecture__details-meta">
            <div class="bd-admin-architecture__stat">
                <strong>Route wrapper</strong>
                <span>`admin.architecture.show`</span>
            </div>
            <div class="bd-admin-architecture__stat">
                <strong>Route preview</strong>
                <span>`admin.architecture.preview`</span>
            </div>
            <div class="bd-admin-architecture__stat">
                <strong>Source maquette</strong>
                <span>{{ $maquetteAvailable ? 'Fichier détecté' : 'Fichier introuvable' }}</span>
            </div>
        </div>
    </details>
</div>
@endsection
