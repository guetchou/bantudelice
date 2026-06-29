<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Administration')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
@yield('head_extra')
@stack('head')
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}:root{--adm-sidebar-w:252px;--adm-sidebar-collapsed:68px;--adm-topbar-h:58px;--adm-bg:#f3f6f9;--adm-sidebar-bg:#0a1710;--adm-accent:#009543;--adm-text:#111827;--adm-muted:#6b7280}html,body{min-height:100%}.adm-body{min-height:100vh;background:var(--adm-bg);color:var(--adm-text);font-family:'Poppins',system-ui,sans-serif;overflow-x:hidden}.adm-sidebar{position:fixed;inset:0 auto 0 0;z-index:120;width:var(--adm-sidebar-w);display:flex;flex-direction:column;background:var(--adm-sidebar-bg);color:#fff;border-right:1px solid rgba(255,255,255,.07);transition:width .2s ease,transform .22s ease}.adm-brand{min-height:68px;display:flex;align-items:center;gap:11px;padding:14px 16px;color:#fff;text-decoration:none;border-bottom:1px solid rgba(255,255,255,.07)}.adm-brand-mark{width:38px;height:38px;flex:0 0 38px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:var(--adm-accent);font-weight:900;box-shadow:0 0 0 4px rgba(0,149,67,.18)}.adm-brand-copy{min-width:0}.adm-brand-name{display:block;font-size:.82rem;font-weight:800;line-height:1.15}.adm-brand-sub{display:block;margin-top:2px;color:#8ca395;font-size:.59rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.adm-workspaces{display:flex;gap:5px;padding:10px 12px;overflow-x:auto;border-bottom:1px solid rgba(255,255,255,.07)}.adm-workspace{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid transparent;border-radius:7px;color:rgba(255,255,255,.48);text-decoration:none;font-size:.63rem;font-weight:800;white-space:nowrap}.adm-workspace:hover{color:#fff;background:rgba(255,255,255,.06)}.adm-workspace.is-active{color:#fff;border-color:rgba(255,255,255,.18);background:rgba(255,255,255,.09)}.adm-workspace-dot{width:6px;height:6px;border-radius:50%;background:currentColor}.adm-nav{flex:1;min-height:0;padding:8px 8px 14px;overflow-y:auto;overflow-x:hidden}.adm-nav::-webkit-scrollbar{width:4px}.adm-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.14);border-radius:999px}.adm-nav-section{margin:4px 0 9px}.adm-nav-section+.adm-nav-section{margin-top:12px}.adm-nav-label,.adm-nav-details>summary{min-height:28px;display:flex;align-items:center;gap:7px;padding:5px 9px;color:#708278;font-size:.58rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;user-select:none}.adm-nav-details>summary{cursor:pointer;list-style:none;border-radius:7px}.adm-nav-details>summary::-webkit-details-marker{display:none}.adm-nav-details>summary:hover{color:#b8c8be;background:rgba(255,255,255,.04)}.adm-nav-arrow{margin-left:auto;font-size:.55rem;transition:transform .16s}.adm-nav-details[open] .adm-nav-arrow{transform:rotate(90deg)}.adm-nav-items{display:flex;flex-direction:column;gap:2px}.adm-nav-details .adm-nav-items{margin-top:3px}.adm-nav-item{min-height:38px;display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:9px;color:#91a499;text-decoration:none;font-size:.74rem;font-weight:600;border:1px solid transparent;transition:.14s}.adm-nav-item:hover{color:#fff;background:rgba(255,255,255,.055)}.adm-nav-item.is-active{color:#fff;background:var(--adm-accent);border-color:rgba(255,255,255,.13);box-shadow:0 5px 15px rgba(0,0,0,.16)}.adm-nav-icon{width:18px;flex:0 0 18px;text-align:center;font-size:.8rem}.adm-nav-text{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.adm-user{display:grid;grid-template-columns:34px minmax(0,1fr) 30px;gap:9px;align-items:center;padding:12px 14px;border-top:1px solid rgba(255,255,255,.07)}.adm-avatar{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:var(--adm-accent);color:#fff;font-size:.72rem;font-weight:900}.adm-user-copy{min-width:0}.adm-user-name{color:#fff;font-size:.7rem;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.adm-user-role{margin-top:2px;color:#71867a;font-size:.59rem}.adm-logout{width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;color:#829388;text-decoration:none;background:rgba(255,255,255,.04)}.adm-logout:hover{color:#fca5a5;background:rgba(239,68,68,.12)}.adm-main{min-height:100vh;margin-left:var(--adm-sidebar-w);transition:margin-left .2s}.adm-topbar{position:sticky;top:0;z-index:80;height:var(--adm-topbar-h);display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 22px;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid #e5e7eb}.adm-topbar-left,.adm-topbar-right{display:flex;align-items:center;gap:10px;min-width:0}.adm-toggle{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:1px solid #dfe6ec;border-radius:9px;background:#fff;color:#52606d;cursor:pointer}.adm-toggle:hover{color:var(--adm-accent)}.adm-page-title{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#111827;font-size:.87rem;font-weight:850}.adm-workspace-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#fff;color:var(--adm-accent);border:1px solid #dfe6ec;font-size:.63rem;font-weight:800}.adm-topbar-avatar{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:2px solid rgba(0,0,0,.06);background:var(--adm-accent);color:#fff;font-size:.71rem;font-weight:900;cursor:pointer}.adm-content{padding:22px}.adm-overlay{position:fixed;inset:0;z-index:110;display:none;background:rgba(15,23,42,.55)}body.sidebar-collapsed .adm-sidebar{width:var(--adm-sidebar-collapsed)}body.sidebar-collapsed .adm-main{margin-left:var(--adm-sidebar-collapsed)}body.sidebar-collapsed .adm-brand-copy,body.sidebar-collapsed .adm-workspaces,body.sidebar-collapsed .adm-nav-label,body.sidebar-collapsed .adm-nav-details>summary span,body.sidebar-collapsed .adm-nav-arrow,body.sidebar-collapsed .adm-nav-text,body.sidebar-collapsed .adm-user-copy,body.sidebar-collapsed .adm-logout{display:none}body.sidebar-collapsed .adm-brand{justify-content:center;padding-inline:8px}body.sidebar-collapsed .adm-nav-details .adm-nav-items{display:none}body.sidebar-collapsed .adm-nav-item{justify-content:center;padding-inline:8px}body.sidebar-collapsed .adm-user{grid-template-columns:1fr;justify-items:center;padding-inline:8px}@media(max-width:1024px){.adm-sidebar{transform:translateX(-100%);width:var(--adm-sidebar-w)}.adm-sidebar.is-open{transform:translateX(0)}.adm-main,body.sidebar-collapsed .adm-main{margin-left:0}body.sidebar-collapsed .adm-sidebar{width:var(--adm-sidebar-w)}body.sidebar-collapsed .adm-brand-copy,body.sidebar-collapsed .adm-nav-label,body.sidebar-collapsed .adm-nav-details>summary span,body.sidebar-collapsed .adm-nav-arrow,body.sidebar-collapsed .adm-nav-text,body.sidebar-collapsed .adm-user-copy,body.sidebar-collapsed .adm-logout{display:initial}body.sidebar-collapsed .adm-workspaces{display:flex}body.sidebar-collapsed .adm-nav-details .adm-nav-items{display:flex}body.sidebar-collapsed .adm-user{grid-template-columns:34px minmax(0,1fr) 30px;justify-items:stretch}body.sidebar-collapsed .adm-nav-item{justify-content:flex-start;padding-inline:10px}.adm-overlay.is-visible{display:block}}@media(max-width:640px){.adm-topbar{padding:0 14px}.adm-content{padding:14px}.adm-workspace-pill{display:none}}
</style>
@include('admin.partials._ops_compat_styles')
@yield('style')
</head>
@php
$adminUser=auth()->user();
$allowedWorkspaces=$adminUser->adminWorkspaces();
$routeName=(string)optional(request()->route())->getName();
$requestedWorkspace=(string)request('workspace','');
if(str_starts_with($routeName,'admin.transport.')||str_starts_with($routeName,'vehicle.')){$activeWorkspace='kende';}
elseif(str_starts_with($routeName,'admin.colis.')||str_starts_with($routeName,'admin.relay-points.')){$activeWorkspace='mema';}
elseif($requestedWorkspace!==''&&in_array($requestedWorkspace,$allowedWorkspaces,true)){$activeWorkspace=$requestedWorkspace;}
elseif(in_array('bantudelice',$allowedWorkspaces,true)){$activeWorkspace='bantudelice';}
else{$activeWorkspace=$allowedWorkspaces[0]??'bantudelice';}
$workspaceConfig=config('admin_navigation.workspaces.'.$activeWorkspace,[]);
$platformConfig=config('admin_navigation.platform',[]);
$activeNav=trim($__env->yieldContent('nav_active'));
$accent=$workspaceConfig['color']??'#009543';
@endphp
<body class="adm-body" style="--adm-accent:{{ $accent }}" data-nav-active="{{ $activeNav }}">
<div class="adm-overlay" id="admOverlay" onclick="admCloseSidebar()"></div>
<aside class="adm-sidebar" id="admSidebar" aria-label="Navigation d’administration">
<a href="{{ route('admin.portal',['workspace'=>$activeWorkspace]) }}" class="adm-brand">
<span class="adm-brand-mark">B</span>
<span class="adm-brand-copy"><span class="adm-brand-name">BantuDelice</span><span class="adm-brand-sub">Portail administrateur</span></span>
</a>
@if(count($allowedWorkspaces)>1)
<div class="adm-workspaces" aria-label="Changer d’espace">
@foreach($allowedWorkspaces as $workspaceKey)
@php $switchConfig=config('admin_navigation.workspaces.'.$workspaceKey);$switchRoute=$switchConfig['dashboard_route']??'admin.portal'; @endphp
@if($switchConfig&&app('router')->has($switchRoute))
<a href="{{ route($switchRoute,['workspace'=>$workspaceKey]) }}" class="adm-workspace {{ $workspaceKey===$activeWorkspace?'is-active':'' }}" title="{{ $switchConfig['label'] }}">
<span class="adm-workspace-dot" style="color:{{ $switchConfig['color']??'#fff' }}"></span>{{ $switchConfig['short_label']??$switchConfig['label'] }}
</a>
@endif
@endforeach
</div>
@endif
<nav class="adm-nav">
@foreach(($workspaceConfig['sections']??[]) as $section)
@php
$sectionItems=collect($section['items']??[])->filter(fn($item)=>app('router')->has($item['route']))->values();
$sectionIsActive=$sectionItems->contains(fn($item)=>($item['nav']??'')===$activeNav);
$isCollapsible=(bool)($section['collapsible']??false);
@endphp
@if($sectionItems->isNotEmpty())
<section class="adm-nav-section">
@if($isCollapsible)
<details class="adm-nav-details" @if($sectionIsActive) open @endif>
<summary><span>{{ $section['label'] }}</span><i class="fas fa-chevron-right adm-nav-arrow"></i></summary>
<div class="adm-nav-items">
@foreach($sectionItems as $item)
<a href="{{ route($item['route'],array_merge(['workspace'=>$activeWorkspace],$item['params']??[])) }}" class="adm-nav-item {{ ($item['nav']??'')===$activeNav?'is-active':'' }}" title="{{ $item['label'] }}">
<span class="adm-nav-icon"><i class="{{ $item['icon'] }}"></i></span><span class="adm-nav-text">{{ $item['label'] }}</span>
</a>
@endforeach
</div>
</details>
@else
<div class="adm-nav-label">{{ $section['label'] }}</div>
<div class="adm-nav-items">
@foreach($sectionItems as $item)
<a href="{{ route($item['route'],array_merge(['workspace'=>$activeWorkspace],$item['params']??[])) }}" class="adm-nav-item {{ ($item['nav']??'')===$activeNav?'is-active':'' }}" title="{{ $item['label'] }}">
<span class="adm-nav-icon"><i class="{{ $item['icon'] }}"></i></span><span class="adm-nav-text">{{ $item['label'] }}</span>
</a>
@endforeach
</div>
@endif
</section>
@endif
@endforeach
@yield('sidebar_extra')
@php
$platformItems=collect($platformConfig['items']??[])->filter(fn($item)=>app('router')->has($item['route']))->values();
$platformIsActive=$platformItems->contains(fn($item)=>($item['nav']??'')===$activeNav);
@endphp
@if($platformItems->isNotEmpty())
<section class="adm-nav-section">
<details class="adm-nav-details" @if($platformIsActive) open @endif>
<summary><span>{{ $platformConfig['label']??'Plateforme' }}</span><i class="fas fa-chevron-right adm-nav-arrow"></i></summary>
<div class="adm-nav-items">
@foreach($platformItems as $item)
<a href="{{ route($item['route'],['workspace'=>$activeWorkspace]) }}" class="adm-nav-item {{ ($item['nav']??'')===$activeNav?'is-active':'' }}" title="{{ $item['label'] }}">
<span class="adm-nav-icon"><i class="{{ $item['icon'] }}"></i></span><span class="adm-nav-text">{{ $item['label'] }}</span>
</a>
@endforeach
</div>
</details>
</section>
@endif
</nav>
<div class="adm-user">
<div class="adm-avatar">{{ strtoupper(substr($adminUser->name??'A',0,1)) }}</div>
<div class="adm-user-copy"><div class="adm-user-name">{{ $adminUser->name??'Administrateur' }}</div><div class="adm-user-role">{{ $adminUser->isSuperAdmin()?'Super administrateur':'Administrateur' }}</div></div>
<a href="{{ route('logout') }}" class="adm-logout" title="Déconnexion" onclick="event.preventDefault();document.getElementById('admLogoutForm').submit();"><i class="fas fa-sign-out-alt"></i></a>
</div>
<form id="admLogoutForm" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
</aside>
<div class="adm-main">
<header class="adm-topbar">
<div class="adm-topbar-left"><button type="button" class="adm-toggle" onclick="admToggleSidebar()" aria-label="Ouvrir ou réduire le menu"><i class="fas fa-bars"></i></button><span class="adm-page-title">@yield('page_title','Tableau de bord')</span></div>
<div class="adm-topbar-right"><span class="adm-workspace-pill"><i class="fas fa-circle" style="font-size:.42rem"></i>{{ $workspaceConfig['label']??ucfirst($activeWorkspace) }}</span><button type="button" class="adm-topbar-avatar" title="Mon profil" onclick="admProfileOpen()">{{ strtoupper(substr($adminUser->name??'A',0,1)) }}</button></div>
</header>
<main class="adm-content">@yield('content')</main>
@include('admin.partials._admin_profile_drawer')
</div>
<script>
function admToggleSidebar(){if(window.innerWidth<=1024){var s=document.getElementById('admSidebar'),o=document.getElementById('admOverlay'),open=s.classList.toggle('is-open');o.classList.toggle('is-visible',open);return}var c=document.body.classList.toggle('sidebar-collapsed');try{localStorage.setItem('adm-sidebar-collapsed',c?'1':'0')}catch(e){}}
function admCloseSidebar(){document.getElementById('admSidebar').classList.remove('is-open');document.getElementById('admOverlay').classList.remove('is-visible')}
function admSyncSidebarMode(){if(window.innerWidth<=1024){document.body.classList.remove('sidebar-collapsed');admCloseSidebar();return}try{document.body.classList.toggle('sidebar-collapsed',localStorage.getItem('adm-sidebar-collapsed')==='1')}catch(e){}}
admSyncSidebarMode();window.addEventListener('resize',admSyncSidebarMode);
</script>
@yield('scripts')
@yield('script')
@stack('scripts')
</body>
</html>
