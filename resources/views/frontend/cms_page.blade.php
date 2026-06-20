@extends('frontend.layouts.app-modern')
@php
    $cmsBrand = app(\App\Services\AuthBrandingService::class)->resolve(request());
    $cmsBrandName = $cmsBrand['name'] ?? 'la plateforme';
@endphp

@php
    $cmsPageRawTitle = $page->seo_title ?: $page->title;
    $cmsPageTitle = str_contains($cmsPageRawTitle, $cmsBrandName)
        ? $cmsPageRawTitle
        : $cmsPageRawTitle . ' | ' . $cmsBrandName;
@endphp
@section('title', $cmsPageTitle)
@section('description', $page->seo_description ?: $page->excerpt)

@section('content')
<section style="background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%); padding: 150px 0 80px; color: white;">
    <div class="container" style="max-width: 980px;">
        <span class="section-badge" style="background: rgba(255,255,255,0.12); color: white;">Contenu</span>
        <h1 style="font-size: clamp(2.2rem, 5vw, 3.6rem); margin-top: 1rem;">{{ $page->title }}</h1>
        @if(!empty($page->excerpt))
            <p style="color: rgba(255,255,255,0.9); max-width: 760px; margin-top: 1rem; font-size: 1.125rem;">
                {{ $page->excerpt }}
            </p>
        @endif
    </div>
</section>

<section style="padding: 56px 0 80px; background: #f8fafc;">
    <div class="container" style="max-width: 980px;">
        <div style="background: #fff; border-radius: 28px; box-shadow: 0 18px 48px rgba(15,23,42,0.08); overflow: hidden;">
            @if(!empty($pageImage))
                <div style="height: 300px; overflow: hidden;">
                    <img src="{{ asset($pageImage) }}" alt="{{ $page->title }}" style="width:100%;height:100%;object-fit:cover;">
                </div>
            @endif

            <div style="padding: 32px 28px;">
                <div class="cms-page-content" style="color:#334155; line-height:1.8; font-size:1.02rem;">
                    {!! $pageBody !!}
                </div>

                @if(!empty($pageCtaLabel) && !empty($pageCtaUrl))
                    <div style="margin-top: 32px;">
                        <a href="{{ $pageCtaUrl }}" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.75rem 1.5rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">{{ $pageCtaLabel }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<style>
    .cms-page-content h2,
    .cms-page-content h3,
    .cms-page-content h4 {
        color: #0f172a;
        margin-top: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .cms-page-content p {
        margin-bottom: 1rem;
    }
    .cms-page-content ul,
    .cms-page-content ol {
        padding-left: 1.25rem;
        margin-bottom: 1rem;
    }
</style>
@endsection
