@extends('frontend.layouts.app-modern')
@section('title', 'Guidance execution | Plateforme')
@section('description', 'Checklist d execution complete issue de guidance.md pour piloter l audit, le recentrage metier et la non-regression.')

@section('content')
<style>
:root {
    --exec-bg: #eef4ff;
    --exec-panel: #ffffff;
    --exec-panel-soft: #f8fbff;
    --exec-ink: #0f172a;
    --exec-muted: #5b6b84;
    --exec-line: rgba(15, 23, 42, 0.1);
    --exec-accent: #0f766e;
    --exec-accent-strong: #0b5d57;
    --exec-success: #009543;
    --exec-alert: #f59e0b;
}

@media (max-width: 960px) {
    .execution-guide-layout {
        grid-template-columns: 1fr !important;
    }

    .execution-guide-sidebar {
        position: static !important;
    }
}

.execution-guide-markdown p,
.execution-guide-item p {
    margin: 0;
}

.execution-guide-shell {
    background:
        radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 24%),
        radial-gradient(circle at top right, rgba(30, 64, 175, 0.12), transparent 28%),
        linear-gradient(180deg, #f3f7ff 0%, #eaf1fb 100%);
}

.execution-guide-topbar {
    position: sticky;
    top: 88px;
    z-index: 30;
    margin-bottom: 1.25rem;
}

.execution-guide-topbar-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.95rem 1rem;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.85);
    border-radius: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

.execution-guide-topbar-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.execution-guide-topbar-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 42px;
    padding: 0 0.95rem;
    border-radius: 999px;
    background: #f7fafc;
    border: 1px solid rgba(148, 163, 184, 0.18);
    color: var(--exec-ink);
    font-weight: 800;
}

.execution-guide-topbar-pill strong {
    color: var(--exec-accent);
}

.execution-guide-topbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.execution-guide-markdown strong,
.execution-guide-item strong {
    color: var(--exec-ink);
}

.execution-guide-markdown code,
.execution-guide-item code {
    background: rgba(15, 118, 110, 0.08);
    color: #115e59;
    padding: 0.12rem 0.4rem;
    border-radius: 999px;
}

.execution-guide-progress-bar {
    position: relative;
    overflow: hidden;
    height: 10px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.08);
}

.execution-guide-progress-bar span {
    display: block;
    height: 100%;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #34d399 0%, #14b8a6 100%);
    transition: width 0.25s ease;
}

.execution-guide-filter {
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: var(--exec-panel);
    color: var(--exec-ink);
    border-radius: 999px;
    padding: 0.7rem 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
}

.execution-guide-filter.is-active,
.execution-guide-filter:hover {
    background: #0f766e;
    border-color: #0f766e;
    color: #fff;
}

.execution-guide-check-item {
    position: relative;
    overflow: hidden;
    transition: 0.2s ease;
}

.execution-guide-check-item::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: transparent;
    transition: 0.2s ease;
}

.execution-guide-check-item.is-complete {
    border-color: rgba(16, 185, 129, 0.35) !important;
    background: #f0fdf4 !important;
}

.execution-guide-check-item.is-complete::before {
    background: var(--exec-success);
}

.execution-guide-check-item.is-complete .execution-guide-item {
    color: #007836 !important;
}

.execution-guide-check-item.is-hidden {
    display: none !important;
}

.execution-guide-check-item[data-status="started"] {
    border-color: rgba(59, 130, 246, 0.28) !important;
    background: #eff6ff !important;
}

.execution-guide-check-item[data-status="started"]::before {
    background: #3b82f6;
}

.execution-guide-check-item[data-status="in_progress"] {
    border-color: rgba(245, 158, 11, 0.28) !important;
    background: #fffbeb !important;
}

.execution-guide-check-item[data-status="in_progress"]::before {
    background: #f59e0b;
}

.execution-guide-check-item[data-status="blocked"] {
    border-color: rgba(239, 68, 68, 0.24) !important;
    background: #fef2f2 !important;
}

.execution-guide-check-item[data-status="blocked"]::before {
    background: #ef4444;
}

.execution-guide-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.execution-guide-kpi {
    background: rgba(255,255,255,0.96);
    border: 1px solid rgba(255,255,255,0.8);
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
    border-radius: 24px;
    padding: 1.1rem 1.15rem;
}

.execution-guide-kpi strong {
    display: block;
    color: var(--exec-ink);
    font-size: 1.65rem;
    line-height: 1.1;
}

.execution-guide-kpi span {
    display: block;
    margin-top: 0.3rem;
    color: var(--exec-muted);
    font-size: 0.9rem;
}

.execution-guide-kpi-label {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 0.65rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 800;
    margin-bottom: 0.75rem;
}

.execution-guide-search {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 18px;
    padding: 0.9rem 1rem;
    color: #0f172a;
    background: #fff;
}

.execution-guide-search:focus {
    outline: none;
    border-color: #0f766e;
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
}

.execution-guide-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.execution-guide-checkbox-ui {
    width: 20px;
    height: 20px;
    margin-top: 0.2rem;
    border-radius: 8px;
    border: 2px solid rgba(15, 23, 42, 0.18);
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s ease;
}

.execution-guide-checkbox-ui::after {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 3px;
    background: transparent;
    transition: 0.2s ease;
}

.execution-guide-check-item.is-complete .execution-guide-checkbox-ui {
    border-color: #009543;
    background: #009543;
}

.execution-guide-check-item.is-complete .execution-guide-checkbox-ui::after {
    background: #fff;
}

.execution-guide-hero-card {
    background: rgba(7, 16, 32, 0.84);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 30px 70px rgba(15, 23, 42, 0.22);
    border-radius: 32px;
    padding: 1.5rem;
}

.execution-guide-summary-card {
    background: var(--exec-panel);
    border: 1px solid rgba(255,255,255,0.88);
    border-radius: 24px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
}

.execution-guide-section-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,251,255,0.98) 100%);
    border-radius: 28px;
    padding: 1.75rem;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(255,255,255,0.86);
}

.execution-guide-section-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.execution-guide-section-id {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.75rem;
    border-radius: 999px;
    background: rgba(15, 118, 110, 0.08);
    color: var(--exec-accent);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.execution-guide-section-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #effaf8;
    color: #0f766e;
    border: 1px solid rgba(15, 118, 110, 0.12);
    border-radius: 999px;
    padding: 0.55rem 0.9rem;
    font-weight: 800;
    white-space: nowrap;
}

.execution-guide-status-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.execution-guide-status-card {
    border-radius: 18px;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.12);
    background: #fbfdff;
}

.execution-guide-status-card strong {
    display: block;
    font-size: 1.25rem;
    color: var(--exec-ink);
}

.execution-guide-status-card span {
    display: block;
    margin-top: 0.2rem;
    color: var(--exec-muted);
    font-size: 0.86rem;
}

.execution-guide-status-card--done {
    background: #f0fdf4;
}

.execution-guide-status-card--done strong {
    color: #007836;
}

.execution-guide-status-card--open {
    background: #fff7ed;
}

.execution-guide-status-card--open strong {
    color: #b45309;
}

.execution-guide-status-card--scope {
    background: #eff6ff;
}

.execution-guide-status-card--scope strong {
    color: #1d4ed8;
}

.execution-guide-section-grid {
    display: grid;
    gap: 1rem;
}

.execution-guide-subheading {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.95rem 1rem;
    border-radius: 18px;
    background: rgba(15, 23, 42, 0.03);
    border: 1px solid rgba(148, 163, 184, 0.14);
}

.execution-guide-subheading-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--exec-accent) 0%, #38bdf8 100%);
    flex: 0 0 auto;
}

.execution-guide-task-card {
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    gap: 0.9rem;
    align-items: start;
    padding: 1rem 1rem 1rem 1.1rem;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: rgba(255,255,255,0.92);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
}

.execution-guide-task-meta {
    display: grid;
    gap: 0.75rem;
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px solid rgba(148, 163, 184, 0.14);
}

.execution-guide-task-row {
    display: grid;
    grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
    gap: 0.75rem;
}

.execution-guide-task-input,
.execution-guide-task-select,
.execution-guide-task-note {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 14px;
    background: #fff;
    color: var(--exec-ink);
    padding: 0.8rem 0.9rem;
    font-size: 0.95rem;
}

.execution-guide-task-input:focus,
.execution-guide-task-select:focus,
.execution-guide-task-note:focus {
    outline: none;
    border-color: var(--exec-accent);
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
}

.execution-guide-task-note {
    min-height: 74px;
    resize: vertical;
}

.execution-guide-task-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
}

.execution-guide-task-updated {
    color: var(--exec-muted);
    font-size: 0.86rem;
    font-weight: 600;
}

.execution-guide-save-indicator {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 0.7rem;
    border-radius: 999px;
    background: #f8fafc;
    color: var(--exec-muted);
    font-size: 0.78rem;
    font-weight: 800;
}

.execution-guide-save-indicator.is-saving {
    background: #eff6ff;
    color: #1d4ed8;
}

.execution-guide-save-indicator.is-saved {
    background: #f0fdf4;
    color: #007836;
}

.execution-guide-save-indicator.is-error {
    background: #fef2f2;
    color: #b91c1c;
}

@media (max-width: 960px) {
    .execution-guide-kpis {
        grid-template-columns: 1fr 1fr;
    }

    .execution-guide-task-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .execution-guide-kpis {
        grid-template-columns: 1fr;
    }

    .execution-guide-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="execution-guide-shell" style="padding: 150px 0 72px;">
    <div class="container">
        <div style="display: grid; gap: 1.1rem;">
            <div class="execution-guide-hero-card">
                <div style="display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.95fr); gap: 1.25rem; align-items: start;">
                    <div>
                        <span class="section-badge" style="background: rgba(255,255,255,0.12); color: #fff;">
                            Dashboard d execution
                        </span>
                        <h1 style="color: #fff; font-size: clamp(2rem, 5vw, 3.35rem); margin-top: 1rem; margin-bottom: 1rem;">
                            {{ $pageTitle }}
                        </h1>
                        <p style="color: rgba(255,255,255,0.78); font-size: 1.02rem; line-height: 1.85; max-width: 760px; margin: 0;">
                            Vue operatoire de la checklist feature. Chaque point devient une tache actionnable avec suivi, filtrage et progression locale pour piloter l execution sans perdre le detail source.
                        </p>
                    </div>
                    <div class="execution-guide-summary-card" style="padding: 1.2rem 1.2rem 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
                            <div>
                                <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--exec-accent); font-weight: 800;">
                                    Progression globale
                                </div>
                                <div style="display: flex; align-items: baseline; gap: 0.45rem; margin-top: 0.2rem;">
                                    <strong id="executionGuideProgressPercent" style="font-size: clamp(1.8rem, 4vw, 2.4rem); color: var(--exec-ink);">0%</strong>
                                    <span id="executionGuideProgressText" style="color: var(--exec-muted); font-weight: 700;">0 / {{ $checklistCount }} taches completees</span>
                                </div>
                            </div>
                            <div style="width: 54px; height: 54px; border-radius: 18px; background: linear-gradient(135deg, #0f766e 0%, #38bdf8 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; font-weight: 900;">
                                {{ count($sections) }}
                            </div>
                        </div>
                        <div class="execution-guide-progress-bar" style="margin-bottom: 0.9rem;">
                            <span id="executionGuideProgressBar"></span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.65rem;">
                            <button type="button" class="execution-guide-filter is-active" data-guide-filter="all">Tout voir</button>
                            <button type="button" class="execution-guide-filter" data-guide-filter="open">Reste a faire</button>
                            <button type="button" class="execution-guide-filter" data-guide-filter="done">Termine</button>
                        </div>
                            <div class="execution-guide-status-grid">
                            <div class="execution-guide-status-card execution-guide-status-card--scope">
                                <strong id="executionGuideStartedCount">0</strong>
                                <span>taches demarrees</span>
                            </div>
                            <div class="execution-guide-status-card execution-guide-status-card--open">
                                <strong id="executionGuideInProgressCount">0</strong>
                                <span>taches en cours</span>
                            </div>
                            <div class="execution-guide-status-card execution-guide-status-card--done">
                                <strong id="executionGuideDoneCount">0</strong>
                                <span>taches terminees</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="execution-guide-kpis">
                <div class="execution-guide-kpi">
                    <div class="execution-guide-kpi-label">Sections</div>
                    <strong>{{ count($sections) }}</strong>
                    <span>sections pilotees</span>
                </div>
                <div class="execution-guide-kpi">
                    <div class="execution-guide-kpi-label">Actions</div>
                    <strong>{{ $checklistCount }}</strong>
                    <span>actions suivies</span>
                </div>
                <div class="execution-guide-kpi">
                    <div class="execution-guide-kpi-label">Stockage</div>
                    <strong>Serveur</strong>
                    <span>etat partage entre developpeurs</span>
                </div>
                <div class="execution-guide-kpi">
                    <div class="execution-guide-kpi-label">Source</div>
                    <strong>Source</strong>
                    <span style="word-break: break-word;">{{ $guidancePath }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section execution-guide-shell" style="padding-top: 0;">
    <div class="container">
        <div class="execution-guide-topbar">
            <div class="execution-guide-topbar-card">
                <div class="execution-guide-topbar-meta">
                    <div class="execution-guide-topbar-pill"><strong id="executionGuideTopbarPercent">0%</strong> complet</div>
                    <div class="execution-guide-topbar-pill"><span id="executionGuideTopbarDone">0</span> terminees</div>
                    <div class="execution-guide-topbar-pill"><span id="executionGuideTopbarInProgress">0</span> en cours</div>
                    <div class="execution-guide-topbar-pill"><span id="executionGuideTopbarBlocked">0</span> bloquees</div>
                </div>
                <div class="execution-guide-topbar-actions">
                    <button type="button" class="execution-guide-filter is-active" data-guide-filter="all">Vue complete</button>
                    <button type="button" class="execution-guide-filter" data-guide-filter="open">Focus execution</button>
                    <button type="button" class="execution-guide-filter" data-guide-filter="done">Elements valides</button>
                </div>
            </div>
        </div>
        <div class="execution-guide-layout" style="display: grid; grid-template-columns: minmax(260px, 320px) minmax(0, 1fr); gap: 2rem; align-items: start;">
            <aside class="execution-guide-sidebar" style="position: sticky; top: 110px;">
                <div class="execution-guide-summary-card" style="padding: 1.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <h2 style="font-size: 1.1rem; margin: 0;">Sommaire</h2>
                        <button type="button" id="resetExecutionGuide" style="border: none; background: transparent; color: var(--primary); font-weight: 700; cursor: pointer;">
                            Reinitialiser
                        </button>
                    </div>
                    <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border-radius: 18px; background: #f8fafc; border: 1px solid rgba(148, 163, 184, 0.18);">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 0.55rem;">
                            <span style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.08em; color: #0f766e; font-weight: 800;">Avancement</span>
                            <strong id="executionGuideSidebarPercent" style="color: #0f172a;">0%</strong>
                        </div>
                        <div class="execution-guide-progress-bar">
                            <span id="executionGuideSidebarBar"></span>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <input type="search" id="executionGuideSearch" class="execution-guide-search" placeholder="Rechercher un mot-cle dans la checklist">
                    </div>
                    <div style="display: grid; gap: 0.75rem;">
                        @foreach($sections as $section)
                            <a href="#{{ $section['anchor'] }}" data-section-link="{{ $section['anchor'] }}" style="display: block; text-decoration: none; color: #0f172a; border: 1px solid rgba(148, 163, 184, 0.18); border-radius: 18px; padding: 0.9rem 1rem; background: #f8fafc;">
                                <div style="font-weight: 700; line-height: 1.5;">{{ $section['title'] }}</div>
                                <div style="margin-top: 0.3rem; color: #475569; font-size: 0.92rem;" data-section-progress-text="{{ $section['anchor'] }}">
                                    0 / {{ $section['item_count'] }} termine
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div style="display: grid; gap: 1.5rem;">
                @foreach($sections as $sectionIndex => $section)
                    <section id="{{ $section['anchor'] }}" data-guide-section="{{ $section['anchor'] }}" data-section-total="{{ $section['item_count'] }}" class="execution-guide-section-card">
                        <div class="execution-guide-section-toolbar">
                            <div>
                                <div class="execution-guide-section-id">Section {{ $loop->iteration }}</div>
                                <h2 style="margin: 0.35rem 0 0; font-size: clamp(1.3rem, 2vw, 1.9rem); color: #0f172a;">{{ $section['title'] }}</h2>
                            </div>
                            <div data-section-badge="{{ $section['anchor'] }}" class="execution-guide-section-stat">
                                0 / {{ $section['item_count'] }} termine
                            </div>
                        </div>

                        @if($section['item_count'] > 0)
                            <div style="margin-bottom: 1.25rem;">
                                <div class="execution-guide-progress-bar">
                                    <span data-section-progress-bar="{{ $section['anchor'] }}"></span>
                                </div>
                            </div>
                        @endif

                        <div class="execution-guide-section-grid">
                            @foreach($section['blocks'] as $blockIndex => $block)
                                @if($block['type'] === 'subheading')
                                    <div id="{{ $section['anchor'] }}-{{ $block['anchor'] }}" class="execution-guide-subheading">
                                        <span class="execution-guide-subheading-dot" aria-hidden="true"></span>
                                        <h3 style="margin: 0; font-size: 1.08rem; color: #111827;">{{ $block['text'] }}</h3>
                                    </div>
                                @elseif($block['type'] === 'paragraph')
                                    <div class="execution-guide-markdown" style="color: #475569; line-height: 1.8; font-size: 1rem; background: rgba(255,255,255,0.55); border: 1px solid rgba(148, 163, 184, 0.12); padding: 1rem 1.1rem; border-radius: 18px;">
                                        {!! \Illuminate\Support\Str::markdown($block['text']) !!}
                                    </div>
                                @elseif($block['type'] === 'checklist')
                                    <div style="display: grid; gap: 0.75rem;">
                                        @foreach($block['items'] as $itemIndex => $item)
                                            @php
                                                $checkboxId = 'guide-' . $sectionIndex . '-' . $blockIndex . '-' . $itemIndex;
                                                $savedItem = $teamState['items'][$item['key']] ?? [];
                                                $initialStatus = $savedItem['status'] ?? 'todo';
                                                $isComplete = $initialStatus === 'done';
                                            @endphp
                                            <div class="execution-guide-check-item{{ $isComplete ? ' is-complete' : '' }}" data-guide-item data-guide-section-item="{{ $section['anchor'] }}" data-item-key="{{ $item['key'] }}" data-status="{{ $initialStatus }}">
                                                <div class="execution-guide-task-card">
                                                    <label for="{{ $checkboxId }}" style="cursor: pointer;">
                                                        <input
                                                            id="{{ $checkboxId }}"
                                                            class="execution-guide-checkbox"
                                                            type="checkbox"
                                                            data-guide-checkbox="{{ $checkboxId }}"
                                                            data-guide-section="{{ $section['anchor'] }}"
                                                            @checked($isComplete)
                                                        >
                                                        <span class="execution-guide-checkbox-ui" aria-hidden="true"></span>
                                                    </label>
                                                    <div>
                                                        <div class="execution-guide-item" style="color: #0f172a; line-height: 1.72; min-width: 0;">
                                                            {!! \Illuminate\Support\Str::markdown($item['text']) !!}
                                                        </div>
                                                        <div class="execution-guide-task-meta">
                                                            <div class="execution-guide-task-row">
                                                                <select class="execution-guide-task-select" data-guide-status>
                                                                    <option value="todo">A faire</option>
                                                                    <option value="started">Debute</option>
                                                                    <option value="in_progress">En cours</option>
                                                                    <option value="blocked">Bloque</option>
                                                                    <option value="done">Termine</option>
                                                                </select>
                                                                <select class="execution-guide-task-select" data-guide-assignee>
                                                                    @foreach($developerProfiles as $developerProfile)
                                                                        <option value="{{ $developerProfile }}">{{ $developerProfile }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <textarea class="execution-guide-task-note" data-guide-note placeholder="Contexte, blocage, prochain pas"></textarea>
                                                            <div class="execution-guide-task-footer">
                                                                <span class="execution-guide-task-updated" data-guide-updated>Aucune mise a jour</span>
                                                                <span class="execution-guide-save-indicator" data-guide-save-indicator>Non synchronise</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var teamState = @json($teamState['items'] ?? []);
    var updateEndpoint = @json(route('guidance.execution.tasks.update'));
    var resetEndpoint = @json(route('guidance.execution.reset'));
    var csrfToken = @json(csrf_token());
    var statusWeights = {
        todo: 0,
        started: 0.25,
        in_progress: 0.6,
        blocked: 0.15,
        done: 1
    };
    var statusLabels = {
        todo: 'A faire',
        started: 'Debute',
        in_progress: 'En cours',
        blocked: 'Bloque',
        done: 'Termine'
    };
    var checkboxes = document.querySelectorAll('.execution-guide-checkbox');
    var items = document.querySelectorAll('[data-guide-item]');
    var filters = document.querySelectorAll('[data-guide-filter]');
    var resetButton = document.getElementById('resetExecutionGuide');
    var searchInput = document.getElementById('executionGuideSearch');
    var progressPercent = document.getElementById('executionGuideProgressPercent');
    var progressText = document.getElementById('executionGuideProgressText');
    var progressBar = document.getElementById('executionGuideProgressBar');
    var sidebarPercent = document.getElementById('executionGuideSidebarPercent');
    var sidebarBar = document.getElementById('executionGuideSidebarBar');
    var startedCountNode = document.getElementById('executionGuideStartedCount');
    var inProgressCountNode = document.getElementById('executionGuideInProgressCount');
    var doneCountNode = document.getElementById('executionGuideDoneCount');
    var topbarPercent = document.getElementById('executionGuideTopbarPercent');
    var topbarDone = document.getElementById('executionGuideTopbarDone');
    var topbarInProgress = document.getElementById('executionGuideTopbarInProgress');
    var topbarBlocked = document.getElementById('executionGuideTopbarBlocked');
    var activeFilter = 'all';
    var saveTimers = {};

    function formatUpdatedAt(value) {
        if (!value) {
            return 'Aucune mise a jour';
        }

        try {
            var date = new Date(value);
            return 'Mis a jour ' + date.toLocaleString('fr-FR');
        } catch (error) {
            return 'Mis a jour';
        }
    }

    function setSaveIndicator(wrapper, state) {
        var indicator = wrapper.querySelector('[data-guide-save-indicator]');
        if (!indicator) {
            return;
        }

        indicator.classList.remove('is-saving', 'is-saved', 'is-error');

        if (state === 'saving') {
            indicator.textContent = 'Synchronisation...';
            indicator.classList.add('is-saving');
            return;
        }

        if (state === 'saved') {
            indicator.textContent = 'Synchronise';
            indicator.classList.add('is-saved');
            return;
        }

        if (state === 'error') {
            indicator.textContent = 'Erreur sync';
            indicator.classList.add('is-error');
            return;
        }

        indicator.textContent = 'Non synchronise';
    }

    function getItemState(wrapper) {
        return {
            item_key: wrapper.getAttribute('data-item-key'),
            status: wrapper.querySelector('[data-guide-status]')?.value || 'todo',
            assignee: wrapper.querySelector('[data-guide-assignee]')?.value || '',
            note: wrapper.querySelector('[data-guide-note]')?.value || ''
        };
    }

    function applyItemState(wrapper, state) {
        var status = state && state.status ? state.status : 'todo';
        var assignee = state && state.assignee ? state.assignee : '';
        var note = state && state.note ? state.note : '';
        var updatedAt = state && state.updated_at ? state.updated_at : null;
        var checkbox = wrapper.querySelector('.execution-guide-checkbox');
        var statusSelect = wrapper.querySelector('[data-guide-status]');
        var assigneeInput = wrapper.querySelector('[data-guide-assignee]');
        var noteInput = wrapper.querySelector('[data-guide-note]');
        var updatedNode = wrapper.querySelector('[data-guide-updated]');

        wrapper.setAttribute('data-status', status);

        if (statusSelect) {
            statusSelect.value = status;
        }

        if (checkbox) {
            checkbox.checked = status === 'done';
        }

        if (assigneeInput) {
            if (assignee && assigneeInput.tagName === 'SELECT') {
                var hasOption = Array.prototype.some.call(assigneeInput.options, function (option) {
                    return option.value === assignee;
                });

                if (!hasOption) {
                    var customOption = document.createElement('option');
                    customOption.value = assignee;
                    customOption.textContent = assignee;
                    assigneeInput.appendChild(customOption);
                }
            }

            assigneeInput.value = assignee;
        }

        if (noteInput) {
            noteInput.value = note;
        }

        if (updatedNode) {
            updatedNode.textContent = formatUpdatedAt(updatedAt);
        }

        updateCheckboxVisualState(checkbox);
        setSaveIndicator(wrapper, updatedAt ? 'saved' : 'idle');
    }

    function updateSectionProgress(sectionKey) {
        var sectionItems = document.querySelectorAll('[data-guide-item][data-guide-section-item="' + sectionKey + '"]');
        var sectionTotal = sectionItems.length;
        var sectionProgress = 0;
        var sectionDone = 0;

        sectionItems.forEach(function (item) {
            var status = item.getAttribute('data-status') || 'todo';
            sectionProgress += statusWeights[status] || 0;
            if (status === 'done') {
                sectionDone += 1;
            }
        });

        var percent = sectionTotal ? Math.round((sectionProgress / sectionTotal) * 100) : 0;
        var progressTextNode = document.querySelector('[data-section-progress-text="' + sectionKey + '"]');
        var progressBarNode = document.querySelector('[data-section-progress-bar="' + sectionKey + '"]');
        var badgeNode = document.querySelector('[data-section-badge="' + sectionKey + '"]');

        if (progressTextNode) {
            progressTextNode.textContent = sectionDone + ' / ' + sectionTotal + ' termine';
        }

        if (badgeNode) {
            badgeNode.textContent = percent + '% progression';
        }

        if (progressBarNode) {
            progressBarNode.style.width = percent + '%';
        }
    }

    function applyFilter() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        items.forEach(function (wrapper) {
            if (!wrapper) {
                return;
            }

            var text = wrapper.textContent.toLowerCase();
            var searchMismatch = query !== '' && text.indexOf(query) === -1;
            var status = wrapper.getAttribute('data-status') || 'todo';
            var shouldHide = activeFilter === 'open' && status === 'done';
            shouldHide = shouldHide || (activeFilter === 'done' && status !== 'done');
            shouldHide = shouldHide || searchMismatch;
            wrapper.classList.toggle('is-hidden', shouldHide);
        });
    }

    function updateGlobalProgress() {
        var total = items.length;
        var done = 0;
        var started = 0;
        var inProgress = 0;
        var blocked = 0;
        var weighted = 0;

        items.forEach(function (wrapper) {
            var status = wrapper.getAttribute('data-status') || 'todo';
            weighted += statusWeights[status] || 0;

            if (status === 'done') {
                done += 1;
            }
            if (status === 'started') {
                started += 1;
            }
            if (status === 'in_progress') {
                inProgress += 1;
            }
            if (status === 'blocked') {
                blocked += 1;
            }
        });

        var percent = total ? Math.round((weighted / total) * 100) : 0;

        if (progressPercent) {
            progressPercent.textContent = percent + '%';
        }

        if (progressText) {
            progressText.textContent = done + ' / ' + total + ' terminees, progression equipe ' + percent + '%';
        }

        if (progressBar) {
            progressBar.style.width = percent + '%';
        }

        if (sidebarPercent) {
            sidebarPercent.textContent = percent + '%';
        }

        if (sidebarBar) {
            sidebarBar.style.width = percent + '%';
        }

        if (startedCountNode) {
            startedCountNode.textContent = started;
        }

        if (inProgressCountNode) {
            inProgressCountNode.textContent = inProgress;
        }

        if (doneCountNode) {
            doneCountNode.textContent = done;
        }

        if (topbarPercent) {
            topbarPercent.textContent = percent + '%';
        }

        if (topbarDone) {
            topbarDone.textContent = done;
        }

        if (topbarInProgress) {
            topbarInProgress.textContent = inProgress;
        }

        if (topbarBlocked) {
            topbarBlocked.textContent = blocked;
        }

        document.querySelectorAll('[data-guide-section]').forEach(function (section) {
            updateSectionProgress(section.getAttribute('data-guide-section'));
        });
    }

    function updateCheckboxVisualState(checkbox) {
        var wrapper = checkbox.closest('[data-guide-item]');
        if (!wrapper) {
            return;
        }

        wrapper.classList.toggle('is-complete', !!checkbox && checkbox.checked);
    }

    function persistItem(wrapper) {
        var payload = getItemState(wrapper);

        setSaveIndicator(wrapper, 'saving');

        fetch(updateEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('save_failed');
                }

                return response.json();
            })
            .then(function (response) {
                teamState[payload.item_key] = response.item || {
                    status: payload.status,
                    assignee: payload.assignee,
                    note: payload.note,
                    updated_at: new Date().toISOString()
                };
                applyItemState(wrapper, teamState[payload.item_key]);
                updateGlobalProgress();
                applyFilter();
            })
            .catch(function () {
                setSaveIndicator(wrapper, 'error');
            });
    }

    function scheduleSave(wrapper) {
        var key = wrapper.getAttribute('data-item-key');
        if (saveTimers[key]) {
            clearTimeout(saveTimers[key]);
        }

        saveTimers[key] = setTimeout(function () {
            persistItem(wrapper);
        }, 350);
    }

    items.forEach(function (wrapper) {
        var itemKey = wrapper.getAttribute('data-item-key');
        var itemState = teamState[itemKey] || {
            status: 'todo',
            assignee: '',
            note: '',
            updated_at: null
        };
        var checkbox = wrapper.querySelector('.execution-guide-checkbox');
        var statusSelect = wrapper.querySelector('[data-guide-status]');
        var assigneeInput = wrapper.querySelector('[data-guide-assignee]');
        var noteInput = wrapper.querySelector('[data-guide-note]');

        applyItemState(wrapper, itemState);

        checkbox?.addEventListener('change', function () {
            var nextStatus = checkbox.checked ? 'done' : 'todo';
            if (statusSelect) {
                statusSelect.value = nextStatus;
            }
            wrapper.setAttribute('data-status', nextStatus);
            updateCheckboxVisualState(checkbox);
            updateGlobalProgress();
            applyFilter();
            scheduleSave(wrapper);
        });

        statusSelect?.addEventListener('change', function () {
            wrapper.setAttribute('data-status', statusSelect.value);
            if (checkbox) {
                checkbox.checked = statusSelect.value === 'done';
            }
            updateCheckboxVisualState(checkbox);
            updateGlobalProgress();
            applyFilter();
            scheduleSave(wrapper);
        });

        assigneeInput?.addEventListener('change', function () {
            scheduleSave(wrapper);
        });

        noteInput?.addEventListener('input', function () {
            scheduleSave(wrapper);
        });
    });

    filters.forEach(function (filterButton) {
        filterButton.addEventListener('click', function () {
            activeFilter = filterButton.getAttribute('data-guide-filter') || 'all';
            filters.forEach(function (node) {
                node.classList.toggle('is-active', node.getAttribute('data-guide-filter') === activeFilter);
            });
            applyFilter();
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            if (!window.confirm('Reinitialiser tout le suivi partage pour l equipe ?')) {
                return;
            }

            fetch(resetEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('reset_failed');
                    }

                    return response.json();
                })
                .then(function () {
                    teamState = {};
                    items.forEach(function (wrapper) {
                        applyItemState(wrapper, {
                            status: 'todo',
                            assignee: '',
                            note: '',
                            updated_at: null
                        });
                    });
                    updateGlobalProgress();
                    applyFilter();
                })
                .catch(function () {
                    window.alert('La reinitialisation partagee a echoue.');
                });
        });
    }

    searchInput?.addEventListener('input', function () {
        applyFilter();
    });

    updateGlobalProgress();
    applyFilter();
});
</script>
@endsection
