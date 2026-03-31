@extends('frontend.layouts.app-modern')

@section('title', trans('ui.site.name') . ' — ' . trans('ui.site.subtitle'))
@section('description', trans('ui.home.hero_description'))

@php
    $featuredRestaurants = collect($restaurants)->take(8);
    $featuredProducts = collect($productRecommendations)->take(4);
    if ($featuredProducts->isEmpty()) {
        $featuredProducts = collect($products)->take(8);
    }
    $marqueeRestaurants = $featuredRestaurants->pluck('name')->filter()->take(8);
    if ($marqueeRestaurants->isEmpty()) {
        $marqueeRestaurants = collect(['Africafe', 'Mami Wata', 'Nganda Ya Mboka', 'Espace Malebo']);
    }
    $homeTicker = [
        trans('ui.home.service_cards.restaurants.description'),
        trans('ui.home.service_cards.parcels.description'),
        trans('ui.home.service_cards.transport.description'),
        $featuredRestaurants->count() . ' ' . trans('ui.nav.restaurants') . ' ' . trans('ui.common.results'),
        $featuredProducts->count() . ' ' . trans('ui.common.products_for_you'),
    ];
    $testimonialsFallback = [
        ['tag' => 'Livraison repas', 'quote' => 'La commande arrive chaude, proprement emballee et dans les delais annonces. Je recommande.', 'name' => 'Prisca M.', 'loc' => 'Centre-ville, Brazzaville'],
        ['tag' => 'Service colis', 'quote' => 'Le suivi est clair et la prise en charge rassurante pour les envois du quotidien.', 'name' => 'Cedric N.', 'loc' => 'Littoral congolais'],
        ['tag' => 'Transport', 'quote' => 'Tarif affiche avant confirmation et reservation simple depuis le telephone. Parfait.', 'name' => 'Aimee K.', 'loc' => 'Bacongo, Brazzaville'],
    ];
    if ($featuredRestaurants->count() >= 3) {
        $testimonialsFallback = [
            ['tag' => 'Livraison repas', 'quote' => 'Les commandes arrivent avec un suivi clair. Interface propre, livraison ponctuelle.', 'name' => 'Prisca M.', 'loc' => 'Brazzaville'],
            ['tag' => 'Restaurants partenaires', 'quote' => $featuredRestaurants->count() . ' restaurants disponibles. La selection est variee et la commande intuitive.', 'name' => 'Cedric N.', 'loc' => 'Sud-Congo'],
            ['tag' => 'Plateforme complete', 'quote' => 'Repas, colis et transport dans une seule appli. Vraiment pratique au quotidien.', 'name' => 'Aimee K.', 'loc' => 'Brazzaville'],
        ];
    }
    $socialLinks = [
        ['label' => 'Facebook',  'handle' => '@BantuDelice',     'href' => 'https://www.facebook.com/BantuDelice',           'icon' => 'fab fa-facebook-f', 'tone' => 'facebook',  'is_external' => true],
        ['label' => 'Instagram', 'handle' => '@bantudelice.cg',  'href' => 'https://www.instagram.com/bantudelice.cg/',       'icon' => 'fab fa-instagram',  'tone' => 'instagram', 'is_external' => true],
        ['label' => 'WhatsApp',  'handle' => 'Support client',   'href' => 'https://wa.me/242064000000',                     'icon' => 'fab fa-whatsapp',   'tone' => 'whatsapp',  'is_external' => true],
        ['label' => 'TikTok',    'handle' => '@bantudelice',     'href' => 'https://www.tiktok.com/@bantudelice',             'icon' => 'fab fa-tiktok',     'tone' => 'tiktok',    'is_external' => true],
    ];
    $footerSocials = $socialLinks;
    $paymentMethods = ['Mobile Money', 'Airtel Money', 'MTN MoMo', 'Cash'];
    $opportunityFallbacks = [
        ['title' => 'Devenir coursier',   'body' => "Rejoignez le reseau BantuDelice pour livrer repas et colis. Inscription rapide et flexible.", 'href' => route('driver'),     'cta' => 'Postuler',         'image' => asset('images/home/service-driver.jpg')],
        ['title' => 'Devenir partenaire', 'body' => "Restaurants et commerces : developpez votre visibilite et vos ventes sur la plateforme.",       'href' => route('partner'),    'cta' => "S'inscrire",       'image' => asset('images/home/service-restaurant.jpg')],
        ['title' => 'Rejoindre l\'equipe','body' => "Vous souhaitez rejoindre BantuDelice pour un poste operationnel ou support ? Contactez-nous.", 'href' => route('contact.us'), 'cta' => 'Nous contacter',   'image' => asset('images/home/service-transport.jpg')],
    ];
    $testimonials = collect([1, 2, 3])->map(function ($i) use ($homeContent, $testimonialsFallback) {
        $f = $testimonialsFallback[$i - 1];
        return [
            'tag'   => $homeContent['testimonial_' . $i . '_tag']   ?? $f['tag'],
            'quote' => $homeContent['testimonial_' . $i . '_quote'] ?? $f['quote'],
            'name'  => $homeContent['testimonial_' . $i . '_name']  ?? $f['name'],
            'loc'   => $homeContent['testimonial_' . $i . '_loc']   ?? $f['loc'],
        ];
    })->all();
    $opportunityCards = collect([1, 2, 3])->map(function ($i) use ($homeContent, $opportunityFallbacks) {
        $f = $opportunityFallbacks[$i - 1];
        $image = $homeContent['opportunity_' . $i . '_image'] ?? $f['image'];
        if ($image && strpos($image, 'http') !== 0 && strpos($image, '/') !== 0) $image = asset($image);
        return ['title' => $homeContent['opportunity_' . $i . '_title'] ?? $f['title'], 'body' => $homeContent['opportunity_' . $i . '_body'] ?? $f['body'], 'href' => $homeContent['opportunity_' . $i . '_url'] ?? $f['href'], 'cta' => $homeContent['opportunity_' . $i . '_cta'] ?? $f['cta'], 'image' => $image];
    })->all();
    $congoDepartments = [
        ['name' => 'Sangha','x' => '58%','y' => '11%'],['name' => 'Likouala','x' => '71%','y' => '16%'],
        ['name' => 'Cuvette','x' => '53%','y' => '25%'],['name' => 'Nkeni-Alima','x' => '66%','y' => '29%'],
        ['name' => 'Cuvette-Ouest','x' => '38%','y' => '31%'],['name' => 'Plateaux','x' => '57%','y' => '42%'],
        ['name' => 'Djoue-Lefini','x' => '49%','y' => '53%'],['name' => 'Brazzaville','x' => '58%','y' => '59%'],
        ['name' => 'Pool','x' => '46%','y' => '63%'],['name' => 'Congo-Oubangui','x' => '71%','y' => '56%'],
        ['name' => 'Bouenza','x' => '41%','y' => '73%'],['name' => 'Lekoumou','x' => '55%','y' => '76%'],
        ['name' => 'Niari','x' => '31%','y' => '81%'],['name' => 'Kouilou','x' => '22%','y' => '92%'],
        ['name' => 'Pointe-Noire','x' => '39%','y' => '95%'],
    ];
    $accountLabel = auth()->check() ? trans('ui.nav.account') : trans('ui.nav.login');
    $accountLink  = auth()->check() ? route('user.profile') : route('login');
    $foodEnabled      = (bool) config('bantudelice_modules.food.enabled',      true);
    $colisEnabled     = (bool) config('bantudelice_modules.colis.enabled',     true);
    $transportEnabled = (bool) config('bantudelice_modules.transport.enabled', true);
    $ecosystemPlatforms = collect(config('sites.ecosystem', []))
        ->filter(fn ($platform) => filled($platform['url'] ?? null))
        ->values();
@endphp

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ── Reset & tokens ────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --g:#16a34a;--g2:#22c55e;--g3:#dcfce7;--g4:#f0fdf4;
  --or:#f97316;--or2:#ea580c;--or3:#fff7ed;
  --bg:#ffffff;--bg2:#f8fafc;--bg3:#f1f5f9;
  --tx:#0f172a;--tx2:#475569;--tx3:#94a3b8;
  --bd:#e2e8f0;--bd2:#cbd5e1;
  --r8:8px;--r12:12px;--r16:16px;--r20:20px;--r24:24px;--r99:999px;
  --s1:0 1px 3px rgba(0,0,0,.08);
  --s2:0 4px 16px rgba(0,0,0,.10);
  --s3:0 8px 32px rgba(0,0,0,.14);
  --s4:0 16px 48px rgba(0,0,0,.18);
  --hub-dark:#060f0a;
  font-family:'Inter',sans-serif;
}

/* Hide layout header/footer */
.bd-future-shell > header,
.bd-future-shell > footer,
body > header, body > footer,
.navbar, nav.navbar { display:none!important; }

body{background:#fff;color:var(--tx);overflow-x:hidden}

/* ── Scroll progress bar ───────────────────────────────────── */
.scroll-prog{
  position:fixed;top:0;left:0;height:3px;width:0%;z-index:9999;
  background:linear-gradient(90deg,var(--g),var(--g2),var(--or));
  transition:width .1s linear;
}

/* ── Announcement ticker ───────────────────────────────────── */
.ticker2{
  background:var(--g);color:#fff;font-family:'Poppins',sans-serif;
  font-size:.75rem;font-weight:600;letter-spacing:.04em;
  padding:8px 0;overflow:hidden;white-space:nowrap;position:relative;z-index:102;
}
.ticker2__track{
  display:inline-flex;animation:tickerScroll 28s linear infinite;
}
.ticker2__track:hover{animation-play-state:paused}
.ticker2__item{padding:0 48px}
.ticker2__item::before{content:'●';margin-right:12px;opacity:.6;font-size:.5rem;vertical-align:middle}
@keyframes tickerScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

/* ── Navigation ────────────────────────────────────────────── */
.nav2{
  position:sticky;top:0;z-index:100;background:#fff;
  border-bottom:1.5px solid var(--bd);
  padding:0 24px;display:flex;align-items:center;gap:0;
  height:68px;transition:box-shadow .25s;
}
.nav2.scrolled{box-shadow:0 4px 24px rgba(0,0,0,.08)}
.nav2__brand{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:32px;flex-shrink:0}
.nav2__logo{height:38px;width:auto;object-fit:contain}
.nav2__brand-name{font-family:'Poppins',sans-serif;font-size:1.15rem;font-weight:700;color:var(--tx)}
.nav2__links{display:flex;align-items:center;gap:2px;flex:1}
.nav2__link{
  display:flex;align-items:center;gap:6px;
  padding:8px 14px;border-radius:var(--r8);
  text-decoration:none;color:var(--tx2);
  font-family:'Inter',sans-serif;font-size:.875rem;font-weight:500;
  transition:background .18s,color .18s;white-space:nowrap;
}
.nav2__link:hover{background:var(--bg3);color:var(--tx)}
.nav2__link i{font-size:.8rem;color:var(--g)}
/* Platforms dropdown */
.nav2__dd{position:relative}
.nav2__ddbtn{
  display:flex;align-items:center;gap:6px;
  padding:8px 14px;border-radius:var(--r8);
  background:none;border:none;cursor:pointer;
  color:var(--tx2);font-family:'Inter',sans-serif;font-size:.875rem;font-weight:500;
  transition:background .18s,color .18s;white-space:nowrap;
}
.nav2__ddbtn:hover,.nav2__dd:hover .nav2__ddbtn{background:var(--bg3);color:var(--tx)}
.nav2__ddbtn i.fa-chevron-down{font-size:.65rem;transition:transform .2s}
.nav2__dd.open .nav2__ddbtn i.fa-chevron-down{transform:rotate(180deg)}
.nav2__ddmenu{
  position:absolute;top:calc(100% + 8px);left:0;
  background:#fff;border:1.5px solid var(--bd);border-radius:var(--r16);
  box-shadow:var(--s4);padding:8px;min-width:240px;
  display:none;z-index:200;
  animation:ddFade .18s ease;
}
.nav2__dd.open .nav2__ddmenu{display:block}
@keyframes ddFade{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.nav2__dditem{
  display:flex;align-items:center;gap:12px;
  padding:10px 14px;border-radius:var(--r12);
  text-decoration:none;color:var(--tx);
  font-family:'Inter',sans-serif;font-size:.875rem;font-weight:500;
  transition:background .15s;
}
.nav2__dditem:hover{background:var(--bg3)}
.nav2__dditem-ic{width:34px;height:34px;border-radius:var(--r8);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.nav2__dditem-ic.gr{background:var(--g3);color:var(--g)}
.nav2__dditem-ic.or{background:var(--or3);color:var(--or)}
.nav2__dditem-ic.pu{background:#f3e8ff;color:#7c3aed}
.nav2__dditem-ic.te{background:#ccfbf1;color:#0d9488}
.nav2__dditem-body{flex:1}
.nav2__dditem-name{font-weight:600;font-size:.82rem}
.nav2__dditem-desc{font-size:.73rem;color:var(--tx3);margin-top:1px}
.nav2__dditem-badge{
  font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:var(--r99);
  background:var(--bg3);color:var(--tx3);white-space:nowrap;
}
.nav2__dditem-badge.active-badge{background:var(--g3);color:var(--g)}
.nav2__dditem-badge.coming-badge{background:#fef9c3;color:#92400e}
.nav2__actions{display:flex;align-items:center;gap:10px;margin-left:auto;flex-shrink:0}
.nav2__search{
  display:flex;align-items:center;gap:8px;
  padding:8px 16px;border-radius:var(--r99);
  background:var(--bg3);border:1.5px solid transparent;
  color:var(--tx3);font-size:.875rem;cursor:pointer;
  transition:border-color .18s,background .18s;
}
.nav2__search:hover{border-color:var(--bd2);background:#fff}
.nav2__cart{
  width:40px;height:40px;border-radius:var(--r8);border:1.5px solid var(--bd);
  display:flex;align-items:center;justify-content:center;
  color:var(--tx2);font-size:.95rem;text-decoration:none;
  transition:border-color .18s,color .18s,background .18s;position:relative;
}
.nav2__cart:hover{border-color:var(--g);color:var(--g);background:var(--g4)}
.nav2__mobile-toggle{
  display:none;background:none;border:none;cursor:pointer;
  padding:8px;border-radius:var(--r8);color:var(--tx);font-size:1.2rem;
}
.btn-green{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:var(--r99);
  background:var(--g);color:#fff;font-family:'Poppins',sans-serif;
  font-size:.875rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;
  transition:background .18s,transform .15s,box-shadow .18s;
  box-shadow:0 4px 14px rgba(22,163,74,.35);
}
.btn-green:hover{background:#15803d;transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.45);color:#fff}
.btn-glass-outline{
  display:inline-flex;align-items:center;gap:8px;
  padding:12px 28px;border-radius:var(--r99);
  background:rgba(255,255,255,0.10);
  border:1.5px solid rgba(255,255,255,0.40);
  color:#fff;font-family:'Poppins',sans-serif;font-size:.9rem;font-weight:600;
  text-decoration:none;cursor:pointer;
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
  transition:background .18s,transform .15s;
}
.btn-glass-outline:hover{background:rgba(255,255,255,0.22);transform:translateY(-1px);color:#fff}
.btn-outline{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:var(--r99);
  background:transparent;border:1.5px solid var(--g);
  color:var(--g);font-family:'Poppins',sans-serif;font-size:.875rem;font-weight:600;
  text-decoration:none;transition:background .18s,color .18s;
}
.btn-outline:hover{background:var(--g);color:#fff}

/* ── HERO ──────────────────────────────────────────────────── */
.hero2{
  position:relative;min-height:100vh;
  background-image:url('{{ asset('images/home/service-restaurant.jpg') }}');
  background-size:cover;background-position:center top;
  display:flex;align-items:center;overflow:hidden;
}
.hero2__overlay{
  position:absolute;inset:0;z-index:0;
  background:linear-gradient(135deg,
    rgba(5,20,10,0.92) 0%,
    rgba(8,32,16,0.85) 50%,
    rgba(22,163,74,0.20) 100%);
}
.hero2__inner{
  position:relative;z-index:2;
  max-width:1240px;margin:0 auto;padding:100px 24px 80px;
  width:100%;display:grid;grid-template-columns:1fr 440px;gap:60px;align-items:center;
}
.hero2__left{display:flex;flex-direction:column;gap:28px}
.hero2__pill{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(22,163,74,0.22);border:1px solid rgba(22,163,74,0.45);
  color:#86efac;font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:600;
  padding:6px 16px;border-radius:var(--r99);backdrop-filter:blur(8px);
  width:fit-content;
}
.hero2__pill span{display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;animation:pulse2 1.4s ease infinite}
@keyframes pulse2{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
.hero2__h1{
  font-family:'Poppins',sans-serif;font-size:clamp(2.4rem,5vw,3.8rem);
  font-weight:800;line-height:1.1;color:#fff;letter-spacing:-.02em;
}
.hero2__h1 em{font-style:normal;color:var(--g2)}
.hero2__sub{font-size:1.1rem;color:rgba(255,255,255,.72);line-height:1.7;max-width:520px}
.hero2__chips{display:flex;flex-wrap:wrap;gap:10px}
.hero2__chip{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:var(--r99);
  background:rgba(255,255,255,0.09);border:1px solid rgba(255,255,255,0.22);
  color:rgba(255,255,255,.85);font-size:.82rem;font-weight:500;cursor:pointer;
  backdrop-filter:blur(8px);transition:background .18s,border-color .18s;
}
.hero2__chip:hover,.hero2__chip.active{
  background:rgba(22,163,74,0.30);border-color:rgba(22,163,74,.6);color:#fff;
}
.hero2__chip i{font-size:.75rem;color:var(--g2)}
.hero2__ctas{display:flex;flex-wrap:wrap;gap:14px;align-items:center}
.hero2__proof{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.hero2__avs{display:flex}
.hero2__av{
  width:34px;height:34px;border-radius:50%;border:2.5px solid rgba(255,255,255,0.5);
  background:var(--g3);display:flex;align-items:center;justify-content:center;
  font-size:.7rem;font-weight:700;color:var(--g);margin-left:-10px;
}
.hero2__av:first-child{margin-left:0}
.hero2__proof-txt{color:rgba(255,255,255,.7);font-size:.8rem}
.hero2__proof-txt strong{color:#fff}
.hero2__clock{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.20);
  color:rgba(255,255,255,.7);font-size:.78rem;font-weight:500;
  padding:4px 12px;border-radius:var(--r99);backdrop-filter:blur(8px);
}

/* Hero right card */
.hero2__card{
  background:rgba(255,255,255,0.10);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid rgba(255,255,255,0.20);border-radius:var(--r24);
  padding:24px;
}
.hero2__card-h{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:18px;padding-bottom:14px;
  border-bottom:1px solid rgba(255,255,255,0.15);
}
.hero2__card-title{font-family:'Poppins',sans-serif;font-size:.9rem;font-weight:700;color:#fff}
.hero2__card-badge{
  background:rgba(22,163,74,0.30);border:1px solid rgba(22,163,74,.5);
  color:#86efac;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:var(--r99);
}
.hero2__card-row{
  display:flex;align-items:center;gap:12px;
  padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.08);
}
.hero2__card-row:last-child{border-bottom:none}
.hero2__card-img{
  width:46px;height:46px;border-radius:var(--r12);overflow:hidden;flex-shrink:0;
  background:rgba(255,255,255,.1);
}
.hero2__card-img img{width:100%;height:100%;object-fit:cover}
.hero2__card-name{flex:1;font-size:.82rem;font-weight:600;color:#fff}
.hero2__card-meta{font-size:.72rem;color:rgba(255,255,255,.5);margin-top:2px}
.hero2__card-time{
  font-size:.72rem;font-weight:700;color:var(--g2);
  background:rgba(22,163,74,.18);padding:3px 8px;border-radius:var(--r99);
}
.hero2__card-cta{
  margin-top:18px;display:block;text-align:center;
  background:var(--g);color:#fff;font-family:'Poppins',sans-serif;
  font-size:.82rem;font-weight:700;padding:12px;border-radius:var(--r12);
  text-decoration:none;transition:background .18s;
}
.hero2__card-cta:hover{background:#15803d;color:#fff}

/* Bubbles & rain */
.hero-bubble{
  position:absolute;border-radius:50%;
  background:radial-gradient(circle,rgba(34,197,94,.18),rgba(22,163,74,.05));
  border:1px solid rgba(34,197,94,.12);
  animation:bubbleRise linear infinite;pointer-events:none;z-index:1;
}
@keyframes bubbleRise{
  0%{transform:translateY(100vh) scale(0);opacity:0}
  10%{opacity:.35}
  90%{opacity:.2}
  100%{transform:translateY(-120px) scale(1.2);opacity:0}
}
.rain-drop{
  position:absolute;width:1px;border-radius:2px;
  background:rgba(255,255,255,0.07);
  animation:rainFall linear infinite;pointer-events:none;z-index:1;
}
@keyframes rainFall{
  0%{transform:translateY(-60px);opacity:0}
  20%{opacity:.5}
  100%{transform:translateY(110vh);opacity:0}
}

/* Hero scroll hint */
.hero-scroll-hint{
  position:absolute;bottom:32px;left:50%;transform:translateX(-50%);
  z-index:3;display:flex;flex-direction:column;align-items:center;gap:6px;
  color:rgba(255,255,255,.5);font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;
  animation:scrollHint 2.2s ease-in-out infinite;
}
.hero-scroll-hint i{font-size:1rem;color:rgba(255,255,255,.4)}
@keyframes scrollHint{
  0%,100%{transform:translateX(-50%) translateY(0);opacity:.5}
  50%{transform:translateX(-50%) translateY(10px);opacity:1}
}

/* ── How it works ──────────────────────────────────────────── */
.sec{padding:80px 0}
.sec.bg2{background:var(--bg2)}
.mx{max-width:1240px;margin:0 auto;padding:0 24px}
.sec__head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:48px;gap:20px}
.sec__tag{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--g3);color:var(--g);
  font-family:'Poppins',sans-serif;font-size:.72rem;font-weight:700;
  letter-spacing:.08em;text-transform:uppercase;
  padding:5px 14px;border-radius:var(--r99);margin-bottom:10px;
}
.sec__tag i{font-size:.7rem}
.sec h2{font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;line-height:1.15;color:var(--tx)}
.sec h2 em{font-style:normal;color:var(--g)}
.sec__sub{color:var(--tx2);margin-top:10px;font-size:1rem;max-width:560px}
.sec__more{
  display:flex;align-items:center;gap:6px;color:var(--g);
  font-size:.875rem;font-weight:600;text-decoration:none;white-space:nowrap;
  transition:gap .18s;flex-shrink:0;
}
.sec__more:hover{gap:10px;color:var(--g)}

/* Steps */
.steps-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
.step{
  padding:28px 22px;border-radius:var(--r20);background:#fff;
  border:1.5px solid var(--bd);transition:transform .2s,box-shadow .2s,border-color .2s;
  position:relative;overflow:hidden;
}
.step::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--g),var(--g2));
  transform:scaleX(0);transform-origin:left;transition:transform .3s;
}
.step:hover::before{transform:scaleX(1)}
.step:hover{transform:translateY(-4px);box-shadow:var(--s3);border-color:var(--g3)}
.step__num{
  width:52px;height:52px;border-radius:var(--r16);background:var(--g4);
  display:flex;align-items:center;justify-content:center;margin-bottom:20px;
  font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:800;color:var(--g);
}
.step__title{font-family:'Poppins',sans-serif;font-size:1rem;font-weight:700;color:var(--tx);margin-bottom:8px}
.step__body{font-size:.875rem;color:var(--tx2);line-height:1.65}

/* ── Restaurants ───────────────────────────────────────────── */
.resto-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.rc{background:#fff;border-radius:var(--r20);border:1.5px solid var(--bd);overflow:hidden;transition:transform .2s,box-shadow .2s}
.rc:hover{transform:translateY(-5px);box-shadow:var(--s3)}
.rc__img{position:relative;height:160px;overflow:hidden;background:var(--bg3)}
.rc__img img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.rc:hover .rc__img img{transform:scale(1.06)}
.rc__badge{
  position:absolute;top:12px;left:12px;
  background:rgba(15,23,42,.72);backdrop-filter:blur(6px);
  color:#fff;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:var(--r99);
}
.rc__time{
  position:absolute;bottom:10px;right:10px;
  background:rgba(255,255,255,.92);color:var(--tx);
  font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:var(--r99);
}
.rc__body{padding:16px}
.rc__cat{font-size:.72rem;color:var(--tx3);font-weight:500;margin-bottom:4px}
.rc__name{font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;color:var(--tx);margin-bottom:8px}
.rc__meta{display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--tx2);margin-bottom:14px}
.rc__stars{color:#f59e0b;font-weight:600}
.rc__btn{
  display:flex;align-items:center;justify-content:center;gap:6px;
  background:var(--g4);color:var(--g);border:1.5px solid var(--g3);
  padding:9px 0;border-radius:var(--r12);font-size:.82rem;font-weight:600;
  text-decoration:none;transition:background .18s,color .18s;font-family:'Poppins',sans-serif;
}
.rc__btn:hover{background:var(--g);color:#fff}

/* ── Products ──────────────────────────────────────────────── */
.prod-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.pc{background:#fff;border-radius:var(--r20);border:1.5px solid var(--bd);overflow:hidden;transition:transform .2s,box-shadow .2s}
.pc:hover{transform:translateY(-5px);box-shadow:var(--s3)}
.pc__img{position:relative;height:180px;overflow:hidden;background:var(--bg3)}
.pc__img img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.pc:hover .pc__img img{transform:scale(1.06)}
.pc__price{
  position:absolute;bottom:10px;right:10px;
  background:var(--g);color:#fff;font-family:'Poppins',sans-serif;
  font-size:.78rem;font-weight:700;padding:4px 12px;border-radius:var(--r99);
}
.pc__body{padding:16px}
.pc__name{font-family:'Poppins',sans-serif;font-size:.92rem;font-weight:700;color:var(--tx);margin-bottom:6px}
.pc__desc{font-size:.8rem;color:var(--tx2);line-height:1.55;margin-bottom:14px}
.pc__btn{
  display:inline-flex;align-items:center;gap:6px;
  color:var(--g);font-size:.82rem;font-weight:600;text-decoration:none;transition:gap .18s;
}
.pc__btn:hover{gap:10px}

/* ── Food orbs section ─────────────────────────────────────── */
.food-gallery-sec{padding:80px 0;background:var(--bg2)}
.food-gallery-inner{max-width:1240px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.food-orbs{position:relative;height:380px}
.food-orb{
  position:absolute;border-radius:50%;overflow:hidden;
  border:4px solid #fff;box-shadow:0 8px 32px rgba(0,0,0,0.18);
  transition:transform .35s ease;
}
.food-orb:hover{transform:scale(1.06)}
.food-orb img{width:100%;height:100%;object-fit:cover}
.fo-1{width:180px;height:180px;top:20px;left:30px}
.fo-2{width:145px;height:145px;top:10px;left:170px;z-index:2}
.fo-3{width:160px;height:160px;top:170px;left:80px;z-index:3}
.fo-4{width:130px;height:130px;top:160px;left:240px}
.food-gallery-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:#fff;border:1.5px solid var(--g3);border-radius:var(--r99);
  padding:6px 14px;font-size:.72rem;font-weight:700;color:var(--g);
  box-shadow:var(--s2);
}
.food-gallery-text .sec__tag{margin-bottom:12px}
.food-gallery-text h2{margin-bottom:16px}
.food-gallery-text p{color:var(--tx2);line-height:1.75;margin-bottom:28px;font-size:1rem}

/* ── Impact band ───────────────────────────────────────────── */
.impact-band{
  padding:100px 24px;
  background-image:
    linear-gradient(135deg,rgba(5,20,10,0.93) 0%,rgba(8,30,16,0.90) 100%),
    url('{{ asset('images/home/service-driver.jpg') }}');
  background-size:cover;background-position:center;background-attachment:fixed;
}
.impact-inner{max-width:1240px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center}
.impact-left h2{font-family:'Poppins',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:800;color:#fff;line-height:1.15;margin-bottom:18px}
.impact-left h2 em{font-style:normal;color:var(--g2)}
.impact-left p{color:rgba(255,255,255,.68);line-height:1.75;margin-bottom:32px;font-size:1rem}
.impact-stats{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.istat{
  background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);
  border-radius:var(--r16);padding:24px 20px;text-align:center;
  backdrop-filter:blur(8px);
}
.istat__num{
  font-family:'Poppins',sans-serif;font-size:2.2rem;font-weight:800;
  color:var(--g2);line-height:1;margin-bottom:8px;
}
.istat__lbl{color:rgba(255,255,255,.6);font-size:.82rem}

/* ── Hub section ───────────────────────────────────────────── */
.hub-section{
  padding:100px 0;
  background:linear-gradient(135deg,#060f0a 0%,#0a1a0e 50%,#060f0a 100%);
}
.hub-intro{text-align:center;max-width:660px;margin:0 auto 64px;padding:0 24px}
.hub-intro .sec__tag{justify-content:center;background:rgba(22,163,74,.20);border:1px solid rgba(22,163,74,.3);color:#86efac}
.hub-intro h2{font-family:'Poppins',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:800;color:#fff;line-height:1.15;margin:14px 0}
.hub-intro h2 em{font-style:normal;color:var(--g2)}
.hub-intro p{color:rgba(255,255,255,.55);line-height:1.75;font-size:.975rem}
.hub-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;max-width:1240px;margin:0 auto;padding:0 24px}
.hub-card{
  padding:28px 22px;border-radius:var(--r20);
  border:1px solid rgba(255,255,255,0.08);
  background:rgba(255,255,255,0.05);
  transition:transform .2s,box-shadow .2s;
  position:relative;overflow:hidden;
}
.hub-card::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--g),transparent);
  opacity:0;transition:opacity .3s;
}
.hub-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(0,0,0,.4)}
.hub-card--active{
  background:linear-gradient(135deg,rgba(22,163,74,.25) 0%,rgba(21,128,61,.15) 100%);
  border-color:rgba(22,163,74,.4);
  box-shadow:0 0 0 1px rgba(22,163,74,.2),0 8px 32px rgba(22,163,74,.15);
}
.hub-card--active::after{opacity:1}
.hub-card__ic{
  width:52px;height:52px;border-radius:var(--r16);display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;margin-bottom:18px;
}
.hub-card__ic.gr{background:rgba(22,163,74,.25);color:#4ade80}
.hub-card__ic.or{background:rgba(249,115,22,.25);color:#fb923c}
.hub-card__ic.pu{background:rgba(124,58,237,.25);color:#a78bfa}
.hub-card__ic.te{background:rgba(13,148,136,.25);color:#2dd4bf}
.hub-badge{
  display:inline-flex;align-items:center;gap:4px;
  font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:var(--r99);
  margin-bottom:14px;
}
.hub-badge.active-b{background:rgba(22,163,74,.3);color:#4ade80;border:1px solid rgba(22,163,74,.4)}
.hub-badge.coming-b{background:rgba(255,255,255,.08);color:rgba(255,255,255,.45);border:1px solid rgba(255,255,255,.1)}
.hub-badge.progress-b{background:rgba(249,115,22,.2);color:#fb923c;border:1px solid rgba(249,115,22,.3)}
.hub-card__name{font-family:'Poppins',sans-serif;font-size:1.05rem;font-weight:700;color:#fff;margin-bottom:10px}
.hub-card__desc{font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:22px}
.hub-card__link{
  display:inline-flex;align-items:center;gap:6px;
  font-size:.8rem;font-weight:600;color:var(--g2);text-decoration:none;
  transition:gap .18s;
}
.hub-card__link:hover{gap:10px}
.hub-card__link.disabled{color:rgba(255,255,255,.25);pointer-events:none}

/* ── Testimonials ──────────────────────────────────────────── */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.tc{
  padding:28px;border-radius:var(--r20);background:#fff;
  border:1.5px solid var(--bd);transition:transform .2s,box-shadow .2s;
  display:flex;flex-direction:column;gap:14px;
}
.tc:hover{transform:translateY(-4px);box-shadow:var(--s3)}
.tc--feat{background:var(--g);border-color:var(--g)}
.tc--feat .tc__stars,.tc--feat .tc__tag,.tc--feat .tc__quote,.tc--feat .tc__name,.tc--feat .tc__loc,.tc--feat .tc__sep{color:#fff!important}
.tc--feat .tc__sep{background:rgba(255,255,255,.3)!important}
.tc--feat .tc__tag{background:rgba(255,255,255,.18)!important;color:#fff!important}
.tc__stars{color:#fbbf24;font-size:.9rem;letter-spacing:2px}
.tc__sep{height:1px;background:var(--bd);margin:4px 0}
.tc__tag{
  display:inline-block;background:var(--g3);color:var(--g);
  font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:var(--r99);
  width:fit-content;
}
.tc__quote{font-size:.9rem;color:var(--tx2);line-height:1.65;flex:1;font-style:italic}
.tc__author{display:flex;align-items:center;gap:12px;margin-top:4px}
.tc__avatar{
  width:40px;height:40px;border-radius:50%;background:var(--g);
  color:#fff;font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.tc--feat .tc__avatar{background:rgba(255,255,255,.25)}
.tc__name{font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:700;color:var(--tx)}
.tc__loc{font-size:.75rem;color:var(--tx3);margin-top:2px}
.tc__loc i{margin-right:3px}

/* ── Opportunities ─────────────────────────────────────────── */
.opp-intro{text-align:center;max-width:600px;margin:0 auto 48px;display:flex;flex-direction:column;align-items:center}
.opp-intro h2{font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:var(--tx)}
.opp-intro p{color:var(--tx2);line-height:1.7;margin-top:12px}
.opp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.oc{background:#fff;border-radius:var(--r20);border:1.5px solid var(--bd);overflow:hidden;transition:transform .2s,box-shadow .2s}
.oc:hover{transform:translateY(-5px);box-shadow:var(--s3)}
.oc__img{height:200px;overflow:hidden;background:var(--bg3)}
.oc__img img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.oc:hover .oc__img img{transform:scale(1.06)}
.oc__body{padding:22px}
.oc__title{font-family:'Poppins',sans-serif;font-size:1rem;font-weight:700;color:var(--tx);margin-bottom:8px}
.oc__txt{font-size:.85rem;color:var(--tx2);line-height:1.65;margin-bottom:18px}
.oc__cta{
  display:inline-flex;align-items:center;gap:6px;
  color:var(--g);font-size:.82rem;font-weight:600;text-decoration:none;transition:gap .18s;
}
.oc__cta:hover{gap:10px}

/* ── Congo map ─────────────────────────────────────────────── */
.congo-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px;gap:16px}
.congo-meta{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--g3);color:var(--g);font-size:.82rem;font-weight:600;
  padding:8px 16px;border-radius:var(--r99);
}
.congo-wrap{background:#fff;border-radius:var(--r24);border:1.5px solid var(--bd);overflow:hidden;box-shadow:var(--s2)}
.congo-map{position:relative;max-width:520px;margin:0 auto;padding:32px}
.congo-pin{
  position:absolute;font-size:.65rem;font-weight:600;color:var(--tx2);
  background:#fff;border:1px solid var(--bd);padding:2px 8px;
  border-radius:var(--r99);white-space:nowrap;transform:translate(-50%,-50%);
  box-shadow:var(--s1);transition:background .18s,color .18s;cursor:default;
}
.congo-pin:hover{background:var(--g);color:#fff;border-color:var(--g)}
.congo-foot{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 24px;background:var(--bg2);border-top:1px solid var(--bd);gap:16px;flex-wrap:wrap;
}
.congo-foot__note{font-size:.8rem;color:var(--tx3)}
.congo-foot__tag{display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600;color:var(--g)}

/* ── App section ───────────────────────────────────────────── */
.app2__grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.app2__phones{display:flex;align-items:flex-start;gap:16px;justify-content:center}
.app2__phone{
  width:200px;background:#fff;border-radius:20px;border:1.5px solid var(--bd);
  box-shadow:var(--s3);overflow:hidden;
}
.app2__phone.sm{width:160px;margin-top:40px;border-color:var(--g3)}
.app2__phone-h{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 16px;background:var(--g4);font-size:.8rem;font-weight:700;color:var(--tx);
  border-bottom:1px solid var(--bd);
}
.app2__phone-b{padding:14px;display:flex;flex-direction:column;gap:10px}
.app2__mini{
  background:var(--bg3);border-radius:var(--r8);padding:8px 10px;
  font-size:.72rem;color:var(--tx2);
}
.app2__text .sec__tag{margin-bottom:12px}
.app2__text h2{font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;line-height:1.2;margin-bottom:14px;color:var(--tx)}
.app2__text h2 em{font-style:normal;color:var(--g)}
.app2__text p{color:var(--tx2);line-height:1.75;margin-bottom:24px;font-size:.975rem}
.app2__stores{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px}
.app2__store{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--tx);color:#fff;font-family:'Poppins',sans-serif;
  font-size:.82rem;font-weight:600;padding:10px 18px;border-radius:var(--r12);
  cursor:pointer;transition:background .18s;
}
.app2__store:hover{background:#1e293b}
.app2__feats{display:flex;flex-direction:column;gap:10px}
.app2__feat{display:flex;align-items:center;gap:10px;font-size:.875rem;color:var(--tx2)}
.app2__check{
  width:22px;height:22px;border-radius:50%;background:var(--g3);
  color:var(--g);display:flex;align-items:center;justify-content:center;
  font-size:.65rem;flex-shrink:0;
}

/* ── Social ────────────────────────────────────────────────── */
.soc2__intro{text-align:center;max-width:540px;margin:0 auto 40px}
.soc2__intro h2{font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:var(--tx);margin:12px 0}
.soc2__intro h2 em{font-style:normal;color:var(--g)}
.soc2__intro p{color:var(--tx2);line-height:1.7}
.soc2__grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.soc2__card{
  padding:24px 18px;border-radius:var(--r20);background:#fff;
  border:1.5px solid var(--bd);display:flex;flex-direction:column;align-items:center;gap:8px;
  text-decoration:none;transition:transform .2s,box-shadow .2s;text-align:center;
}
.soc2__card:hover{transform:translateY(-4px);box-shadow:var(--s3)}
.soc2__ic{width:48px;height:48px;border-radius:var(--r12);display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.soc2__ic.facebook{background:#e7f0fd;color:#1877f2}
.soc2__ic.instagram{background:#fce4ec;color:#e1306c}
.soc2__ic.whatsapp{background:#e8f5e9;color:#25d366}
.soc2__ic.tiktok{background:#f3e8ff;color:#010101}
.soc2__name{font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:700;color:var(--tx)}
.soc2__handle{font-size:.75rem;color:var(--tx3)}
.soc2__open{font-size:.72rem;color:var(--g);font-weight:600;margin-top:4px}

/* ── Stats ─────────────────────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
.stat-cell{
  text-align:center;padding:32px 20px;border-radius:var(--r20);
  background:#fff;border:1.5px solid var(--bd);transition:transform .2s,box-shadow .2s;
}
.stat-cell:hover{transform:translateY(-4px);box-shadow:var(--s2)}
.stat-num{
  font-family:'Poppins',sans-serif;font-size:2.6rem;font-weight:800;
  color:var(--g);line-height:1;margin-bottom:10px;
}
.stat-lbl{font-size:.85rem;color:var(--tx2)}

/* ── Footer ────────────────────────────────────────────────── */
.ft2{background:#0f172a;padding:64px 24px 32px}
.ft2__grid{max-width:1240px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr;gap:40px;margin-bottom:40px}
.ft2__brand{display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:16px}
.ft2__logo{height:36px;width:auto;object-fit:contain;filter:brightness(0) invert(1)}
.ft2__brand-name{font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:700;color:#fff}
.ft2__desc{font-size:.82rem;color:#64748b;line-height:1.7;margin-bottom:22px}
.ft2__socs{display:flex;gap:10px}
.ft2__soc{
  width:38px;height:38px;border-radius:var(--r8);background:rgba(255,255,255,.07);
  border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;
  color:#94a3b8;text-decoration:none;transition:background .18s,color .18s;font-size:.9rem;
}
.ft2__soc:hover{background:var(--g);color:#fff;border-color:var(--g)}
.ft2__col h4{font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px}
.ft2__links{display:flex;flex-direction:column;gap:10px}
.ft2__links a{font-size:.82rem;color:#64748b;text-decoration:none;transition:color .18s}
.ft2__links a:hover{color:#fff}
.ft2__bot{
  max-width:1240px;margin:0 auto;
  border-top:1px solid rgba(255,255,255,.07);padding-top:24px;
  display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
}
.ft2__copy{font-size:.78rem;color:#475569}
.ft2__pays{display:flex;gap:8px;flex-wrap:wrap}
.ft2__pay{
  font-size:.72rem;color:#475569;background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.08);padding:3px 10px;border-radius:var(--r99);
}
.ft2__legal{display:flex;gap:16px}
.ft2__legal a{font-size:.75rem;color:#475569;text-decoration:none;transition:color .18s}
.ft2__legal a:hover{color:#fff}

/* ── fadeUp keyframe (used by testimonials) ─────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}

/* ── Delivery journey section ──────────────────────────────── */
.journey-section{padding:100px 0;background:#fff;position:relative;overflow:hidden}
.journey-section::before{
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--g),var(--g2),var(--or),var(--g2),var(--g));
  background-size:200% 100%;animation:gradShift 4s linear infinite;
}
@keyframes gradShift{0%{background-position:0% 0}100%{background-position:200% 0}}

.journey-intro{text-align:center;max-width:620px;margin:0 auto 64px;padding:0 24px}
.journey-intro h2{font-family:'Poppins',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:800;color:var(--tx);line-height:1.15;margin:12px 0}
.journey-intro h2 em{font-style:normal;color:var(--g)}
.journey-intro p{color:var(--tx2);line-height:1.75;font-size:1rem}

.journey-timeline{
  max-width:1100px;margin:0 auto;padding:0 24px;
  display:grid;grid-template-columns:repeat(5,1fr);
  position:relative;gap:0;
}
.journey-timeline::before{
  content:'';position:absolute;top:52px;left:10%;right:10%;height:3px;
  background:linear-gradient(90deg,var(--g3),var(--g),var(--g2),var(--or),var(--g3));
  background-size:200% 100%;animation:gradShift 3s linear infinite;
  border-radius:3px;z-index:0;
}

.jstep{
  display:flex;flex-direction:column;align-items:center;gap:18px;
  padding:0 8px;position:relative;z-index:1;
  opacity:0;transform:translateY(30px);
  transition:opacity .55s ease,transform .55s ease;
}
.jstep.on{opacity:1;transform:none}

.jstep__bubble{
  width:104px;height:104px;border-radius:50%;
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;
  border:3px solid #fff;box-shadow:0 4px 20px rgba(0,0,0,0.10);
  position:relative;overflow:hidden;
  cursor:default;transition:transform .25s,box-shadow .25s;
}
.jstep__bubble:hover{transform:scale(1.08);box-shadow:0 8px 32px rgba(0,0,0,0.18)}
.jstep__bubble::after{
  content:'';position:absolute;inset:0;border-radius:50%;
  background:rgba(255,255,255,0.08);
  animation:bubblePulse 2.5s ease-in-out infinite;
}
@keyframes bubblePulse{
  0%,100%{transform:scale(1);opacity:.4}
  50%{transform:scale(1.12);opacity:0}
}

.jstep__icon{font-size:1.8rem;position:relative;z-index:1;line-height:1}
.jstep__step-label{
  font-family:'Poppins',sans-serif;font-size:.58rem;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase;
  color:rgba(255,255,255,0.8);position:relative;z-index:1;
}

/* Bubble color per step */
.jstep:nth-child(1) .jstep__bubble{background:linear-gradient(135deg,#1d4ed8,#3b82f6)}
.jstep:nth-child(2) .jstep__bubble{background:linear-gradient(135deg,#7c3aed,#a78bfa)}
.jstep:nth-child(3) .jstep__bubble{background:linear-gradient(135deg,#d97706,#f59e0b)}
.jstep:nth-child(4) .jstep__bubble{background:linear-gradient(135deg,var(--g),var(--g2))}
.jstep:nth-child(5) .jstep__bubble{background:linear-gradient(135deg,#dc2626,#f97316)}

/* Animated ring around active step */
.jstep:nth-child(3) .jstep__bubble::before,
.jstep:nth-child(4) .jstep__bubble::before{
  content:'';position:absolute;inset:-6px;border-radius:50%;
  border:2px solid rgba(255,255,255,0.35);
  animation:ringPulse 1.8s ease-in-out infinite;
}
@keyframes ringPulse{
  0%,100%{transform:scale(1);opacity:.6}
  50%{transform:scale(1.15);opacity:0}
}

.jstep__content{text-align:center}
.jstep__num{
  width:24px;height:24px;border-radius:50%;background:var(--g);color:#fff;
  font-family:'Poppins',sans-serif;font-size:.68rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 10px;
}
.jstep__title{font-family:'Poppins',sans-serif;font-size:.9rem;font-weight:700;color:var(--tx);margin-bottom:6px}
.jstep__body{font-size:.78rem;color:var(--tx2);line-height:1.55}

/* Status badge below each step */
.jstep__status{
  display:inline-flex;align-items:center;gap:5px;
  padding:4px 12px;border-radius:var(--r99);
  font-size:.68rem;font-weight:700;
}
.status--done{background:var(--g3);color:var(--g)}
.status--progress{background:#fef3c7;color:#92400e;animation:statusBlink 1.2s ease-in-out infinite}
.status--pending{background:var(--bg3);color:var(--tx3)}
@keyframes statusBlink{0%,100%{opacity:1}50%{opacity:.6}}

/* ── Journey phone mockup ──────────────────────────────────── */
.journey-mockup{
  max-width:360px;margin:56px auto 0;padding:0 24px;
  position:relative;
}
.jmock{
  background:#fff;border-radius:28px;border:2px solid var(--bd);
  box-shadow:var(--s4);overflow:hidden;
}
.jmock__bar{
  background:var(--tx);padding:16px 18px 12px;
  display:flex;align-items:center;justify-content:space-between;
}
.jmock__bar span{font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:700;color:#fff}
.jmock__bar i{color:rgba(255,255,255,.6);font-size:.9rem}
.jmock__body{padding:16px}
.jmock__tracker{
  background:var(--bg3);border-radius:var(--r16);padding:16px;margin-bottom:14px;
}
.jmock__trk-title{font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:700;color:var(--tx);margin-bottom:12px}
.jmock__step{
  display:flex;align-items:center;gap:10px;padding:8px 0;
  border-bottom:1px solid var(--bd);
}
.jmock__step:last-child{border-bottom:none;padding-bottom:0}
.jmock__step-ic{
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:.75rem;
}
.jmock__step-ic.done{background:var(--g);color:#fff}
.jmock__step-ic.active{background:var(--or);color:#fff;animation:statusBlink 1s infinite}
.jmock__step-ic.wait{background:var(--bg3);color:var(--tx3);border:1.5px solid var(--bd)}
.jmock__step-txt{flex:1;font-size:.78rem;font-weight:600;color:var(--tx)}
.jmock__step-t{font-size:.68rem;color:var(--tx3)}
.jmock__eta{
  display:flex;align-items:center;justify-content:space-between;
  background:var(--g);color:#fff;border-radius:var(--r12);padding:12px 16px;
  margin-top:6px;
}
.jmock__eta-label{font-size:.8rem;opacity:.85}
.jmock__eta-val{font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:800}

/* Responsive journey */
@media(max-width:900px){
  .journey-timeline{grid-template-columns:1fr 1fr;gap:32px;justify-items:center}
  .journey-timeline::before{display:none}
}
@media(max-width:600px){
  .journey-timeline{grid-template-columns:1fr;gap:24px}
}

/* ── Custom cursor ─────────────────────────────────────────── */
#bdHomeCursor{position:fixed;top:0;left:0;pointer-events:none;z-index:9000;display:none}
.bd-home-cursor-ready #bdHomeCursor{display:block}
.bd-home-cursor-ready *{cursor:none!important}
#bdHomeCursorDot{position:fixed;width:8px;height:8px;border-radius:50%;background:var(--g);transform:translate(-50%,-50%);transition:width .1s,height .1s,background .1s;pointer-events:none}
#bdHomeCursorRing{position:fixed;width:36px;height:36px;border-radius:50%;border:2px solid var(--g);opacity:.5;transform:translate(-50%,-50%);transition:width .15s,height .15s,opacity .15s;pointer-events:none}
.bd-home-cursor-hover #bdHomeCursorDot{width:14px;height:14px;background:var(--or)}
.bd-home-cursor-hover #bdHomeCursorRing{width:52px;height:52px;opacity:.3}

/* ── Scroll reveal ─────────────────────────────────────────── */
.rev,.rev-l{opacity:0;transform:translateY(28px);transition:opacity .55s ease,transform .55s ease}
.rev-l{transform:translateX(-28px)}
.rev.on,.rev-l.on{opacity:1;transform:none}

/* ── Responsive ────────────────────────────────────────────── */
@media(max-width:1100px){
  .hero2__inner{grid-template-columns:1fr;gap:40px}
  .hero2__card{display:none}
  .steps-grid{grid-template-columns:repeat(2,1fr)}
  .resto-grid{grid-template-columns:repeat(2,1fr)}
  .prod-grid{grid-template-columns:repeat(2,1fr)}
  .hub-grid{grid-template-columns:repeat(2,1fr)}
  .food-gallery-inner{grid-template-columns:1fr}
  .impact-inner{grid-template-columns:1fr;gap:40px}
}
@media(max-width:768px){
  .nav2__links,.nav2__search{display:none}
  .nav2__mobile-toggle{display:flex}
  .steps-grid,.resto-grid,.prod-grid,.hub-grid,.opp-grid,.stats-grid,.soc2__grid,.testi-grid{grid-template-columns:1fr}
  .ft2__grid{grid-template-columns:1fr 1fr}
  .app2__grid{grid-template-columns:1fr}
  .congo-head{flex-direction:column;align-items:flex-start}
  .sec__head{flex-direction:column;align-items:flex-start}
  .impact-stats{grid-template-columns:1fr 1fr}
  .food-orbs{height:280px}
  .fo-1{width:130px;height:130px}
  .fo-2{width:110px;height:110px;left:120px}
  .fo-3{width:120px;height:120px;top:130px;left:60px}
  .fo-4{width:100px;height:100px;top:125px;left:180px}
}
@media(max-width:480px){
  .ft2__grid{grid-template-columns:1fr}
  .ft2__bot{flex-direction:column;text-align:center}
  .hero2__h1{font-size:2rem}
}
</style>
@endsection

@section('content')
<div id="bdHomeCursor">
  <div id="bdHomeCursorDot"></div>
  <div id="bdHomeCursorRing"></div>
</div>

<div class="scroll-prog" id="scrollProg"></div>

<div class="bd2">

{{-- ── Ticker ────────────────────────────────────────────────── --}}
<div class="ticker2">
  <div class="ticker2__track" id="tickerTrack">
    @foreach($marqueeRestaurants as $rn)
      <span class="ticker2__item">{{ $rn }}</span>
    @endforeach
    <span class="ticker2__item">🍽️ Commandez maintenant</span>
    <span class="ticker2__item">⚡ Livraison en 20–40 min</span>
    <span class="ticker2__item">🌍 Brazzaville · Pointe-Noire</span>
    @foreach($marqueeRestaurants as $rn)
      <span class="ticker2__item">{{ $rn }}</span>
    @endforeach
    <span class="ticker2__item">🍽️ Commandez maintenant</span>
    <span class="ticker2__item">⚡ Livraison en 20–40 min</span>
    <span class="ticker2__item">🌍 Brazzaville · Pointe-Noire</span>
  </div>
</div>

{{-- ── Navigation ───────────────────────────────────────────── --}}
<nav class="nav2" id="mainNav">
  <a href="{{ route('home') }}" class="nav2__brand">
    <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice" class="nav2__logo"
         onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
    <span style="display:none" class="nav2__brand-name">BantuDelice</span>
  </a>
  <div class="nav2__links">
    @if($foodEnabled)
      <a href="{{ route('restaurants.all') }}" class="nav2__link">
        <i class="fas fa-bowl-food"></i> Restaurants
      </a>
    @endif
    <a href="{{ route('offers') }}" class="nav2__link">
      <i class="fas fa-tag"></i> Offres
    </a>
    {{-- Plateformes dropdown --}}
    <div class="nav2__dd" id="navPlatformsDD">
      <button class="nav2__ddbtn" id="navPlatformsBtn" type="button">
        <i class="fas fa-th-large"></i> Nos Plateformes <i class="fas fa-chevron-down"></i>
      </button>
      <div class="nav2__ddmenu">
        @foreach($ecosystemPlatforms as $platform)
          <a href="{{ $platform['url'] }}" class="nav2__dditem" target="_blank" rel="noopener">
            <span class="nav2__dditem-ic {{ ($platform['name'] ?? '') === 'Salisa' ? 'pu' : (($platform['name'] ?? '') === 'Kosunga' ? 'te' : (($platform['name'] ?? '') === 'Memela' ? 'or' : 'gr')) }}"><i class="fas {{ $platform['icon'] ?? 'fa-circle' }}"></i></span>
            <span class="nav2__dditem-body">
              <span class="nav2__dditem-name">{{ $platform['name'] }}</span>
              <span class="nav2__dditem-desc">{{ $platform['description'] }}</span>
            </span>
            <span class="nav2__dditem-badge {{ ($platform['badge'] ?? '') === 'Disponible' ? 'active-b' : 'coming-b' }}">{{ $platform['badge'] ?? 'Disponible' }}</span>
          </a>
        @endforeach
      </div>
    </div>
    <a href="{{ route('partner') }}" class="nav2__link">
      <i class="fas fa-handshake"></i> Partenaires
    </a>
  </div>
  <div class="nav2__actions">
    <span class="nav2__search">
      <i class="fas fa-search" style="font-size:.8rem"></i>
      <span>Rechercher…</span>
    </span>
    @if($foodEnabled)
    <a href="{{ route('cart') }}" class="nav2__cart" aria-label="Panier">
      <i class="fas fa-shopping-basket"></i>
    </a>
    @endif
    <a href="{{ $accountLink }}" class="btn-green">
      <i class="fas fa-user fa-sm"></i> {{ $accountLabel }}
    </a>
    <button class="nav2__mobile-toggle" type="button" aria-label="Menu">
      <i class="fas fa-bars"></i>
    </button>
  </div>
</nav>

{{-- ── HERO ──────────────────────────────────────────────────── --}}
@if($foodEnabled)
<section class="hero2" id="hero">
  <div class="hero2__overlay"></div>
  {{-- Bubbles & rain generated by JS --}}
  <div class="hero2__inner">
    <div class="hero2__left">
      <div class="hero2__pill">
        <span></span>
        Livraison de repas · Congo
      </div>
      <h1 class="hero2__h1">
        Tout ce dont vous avez besoin,<br>
        <em>livré chez vous.</em>
      </h1>
      <p class="hero2__sub">
        Explorez les meilleurs restaurants de Brazzaville et Pointe-Noire.<br>
        Commandez en quelques secondes, suivez en temps réel.
      </p>
      <div class="hero2__chips">
        <span class="hero2__chip active"><i class="fas fa-fire-flame-curved"></i> Cuisine locale</span>
        <span class="hero2__chip"><i class="fas fa-temperature-hot"></i> Plats chauds</span>
        <span class="hero2__chip"><i class="fas fa-star"></i> Offres du jour</span>
        <span class="hero2__chip"><i class="fas fa-clock"></i> Livraison rapide</span>
      </div>
      <div class="hero2__ctas">
        <a href="{{ route('restaurants.all') }}" class="btn-green" style="padding:14px 32px;font-size:1rem">
          <i class="fas fa-bowl-food"></i> Commander maintenant
        </a>
        <a href="{{ route('partner') }}" class="btn-glass-outline">
          Devenir partenaire
        </a>
      </div>
      <div class="hero2__proof">
        <div class="hero2__avs">
          <div class="hero2__av">PM</div>
          <div class="hero2__av">CN</div>
          <div class="hero2__av">AK</div>
          <div class="hero2__av">LB</div>
        </div>
        <span class="hero2__proof-txt">
          <strong>{{ $featuredRestaurants->count() }}+ restaurants</strong> disponibles
        </span>
        <span class="hero2__clock">
          <i class="fas fa-clock fa-xs"></i>
          <span id="heroClock">--:--</span>
        </span>
      </div>
    </div>
    <div class="hero2__card rev">
      <div class="hero2__card-h">
        <span class="hero2__card-title"><i class="fas fa-bowl-food" style="color:var(--g2);margin-right:6px"></i>Restaurants du moment</span>
        <span class="hero2__card-badge">En ligne</span>
      </div>
      @foreach($featuredRestaurants->take(4) as $r)
        @php
          $rMedia = $r->cover_image ?: $r->logo;
          $rImg = $rMedia ? (strpos($rMedia,'http')===0 ? $rMedia : asset('images/restaurant_images/'.$rMedia)) : asset('images/home/service-restaurant.jpg');
          $rFee = number_format((float)($r->delivery_charges ?? 0),0,',',' ');
        @endphp
        <div class="hero2__card-row">
          <div class="hero2__card-img">
            <img src="{{ $rImg }}" alt="{{ $r->name }}"
                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
          </div>
          <div>
            <div class="hero2__card-name">{{ $r->name }}</div>
            <div class="hero2__card-meta">{{ $rFee }} FCFA livraison</div>
          </div>
          <span class="hero2__card-time">~30 min</span>
        </div>
      @endforeach
      <a href="{{ route('restaurants.all') }}" class="hero2__card-cta">
        <i class="fas fa-bowl-food fa-sm"></i> Voir tous les restaurants
      </a>
    </div>
  </div>
  <div class="hero-scroll-hint">
    <span>Découvrir</span>
    <i class="fas fa-chevron-down"></i>
  </div>
</section>
@endif

{{-- ── Comment ça marche ─────────────────────────────────────── --}}
@if($foodEnabled)
<section class="sec bg2" id="how">
  <div class="mx">
    <div class="sec__head rev">
      <div>
        <div class="sec__tag"><i class="fas fa-circle-play"></i> {{ $homeContent['how_it_works_tag'] ?? 'Comment ça marche' }}</div>
        <h2>{{ $homeContent['how_it_works_title'] ?? 'Commander, c\'est <em>simple</em>' }}</h2>
        <p class="sec__sub">{{ $homeContent['how_it_works_subtitle'] ?? 'En 4 étapes, recevez votre repas sans effort.' }}</p>
      </div>
    </div>
    <div class="steps-grid">
      @php
        $steps = [
          ['n'=>'01','icon'=>'fas fa-search','t'=>$homeContent['step_1_title']??'Choisissez','b'=>$homeContent['step_1_body']??'Parcourez les restaurants et les plats disponibles près de chez vous.'],
          ['n'=>'02','icon'=>'fas fa-cart-shopping','t'=>$homeContent['step_2_title']??'Composez','b'=>$homeContent['step_2_body']??'Ajoutez vos plats au panier, personnalisez votre commande.'],
          ['n'=>'03','icon'=>'fas fa-mobile-screen-button','t'=>$homeContent['step_3_title']??'Payez','b'=>$homeContent['step_3_body']??'Réglez en Mobile Money, Airtel Money, MTN MoMo ou cash.'],
          ['n'=>'04','icon'=>'fas fa-motorcycle','t'=>$homeContent['step_4_title']??'Recevez','b'=>$homeContent['step_4_body']??'Votre livreur récupère la commande et vous la remet en main propre.'],
        ];
      @endphp
      @foreach($steps as $i => $s)
        <div class="step rev" style="transition-delay:{{ $i * 0.08 }}s">
          <div class="step__num">{{ $s['n'] }}</div>
          <div class="step__title">{{ $s['t'] }}</div>
          <div class="step__body">{{ $s['b'] }}</div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ── Restaurants populaires ────────────────────────────────── --}}
@if($foodEnabled)
<section class="sec" id="restos">
  <div class="mx">
    <div class="sec__head rev">
      <div>
        <div class="sec__tag"><i class="fas fa-store"></i> {{ $homeContent['restaurants_tag'] ?? 'Nos partenaires' }}</div>
        <h2>{{ $homeContent['restaurants_title'] ?? 'Restaurants populaires' }}</h2>
        @if(!empty($homeContent['restaurants_subtitle']))
          <p class="sec__sub">{{ $homeContent['restaurants_subtitle'] }}</p>
        @endif
      </div>
      <a href="{{ route('restaurants.all') }}" class="sec__more">
        Voir tout <i class="fas fa-arrow-right fa-xs"></i>
      </a>
    </div>
    <div class="resto-grid">
      @forelse($featuredRestaurants as $restaurant)
        @php
          $deliveryFee = number_format((float)($restaurant->delivery_charges ?? 0), 0, ',', ' ');
          $rating      = number_format((float)($restaurant->avg_rating ?? 4.0), 1);
          $media       = $restaurant->cover_image ?: $restaurant->logo;
          $logo        = $media ? (strpos($media, 'http') === 0 ? $media : asset('images/restaurant_images/' . $media)) : asset('images/home/service-restaurant.jpg');
          $cuisines    = $restaurant->cuisines->pluck('name')->take(3)->implode(' · ') ?: 'Cuisine congolaise';
        @endphp
        <article class="rc rev">
          <div class="rc__img">
            <img src="{{ $logo }}" alt="{{ $restaurant->name }}"
                 onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
            <span class="rc__badge">Populaire</span>
            <span class="rc__time">20–40 min</span>
          </div>
          <div class="rc__body">
            <div class="rc__cat">{{ $cuisines }}</div>
            <div class="rc__name">{{ $restaurant->name }}</div>
            <div class="rc__meta">
              <span class="rc__stars">★ {{ $rating }}</span>
              <span>·</span>
              <span>{{ $deliveryFee }} FCFA livraison</span>
            </div>
            <a href="{{ route('resturant.detail', $restaurant->id) }}" class="rc__btn">
              <i class="fas fa-bowl-food fa-xs"></i> Commander
            </a>
          </div>
        </article>
      @empty
        <article class="rc">
          <div class="rc__body" style="padding:24px">
            <div class="rc__name">Restaurants bientôt disponibles</div>
          </div>
        </article>
      @endforelse
    </div>
  </div>
</section>
@endif

{{-- ── Plats a decouvrir ─────────────────────────────────────── --}}
@if($foodEnabled)
<section class="sec bg2" id="plats">
  <div class="mx">
    <div class="sec__head rev">
      <div>
        <div class="sec__tag"><i class="fas fa-fire"></i> {{ $homeContent['popular_products_tag'] ?? 'Sélection du moment' }}</div>
        <h2>{{ $homeContent['popular_products_title'] ?? 'Plats à découvrir' }}</h2>
      </div>
      <a href="{{ route('restaurants.all') }}" class="sec__more">
        Explorer <i class="fas fa-arrow-right fa-xs"></i>
      </a>
    </div>
    <div class="prod-grid">
      @foreach($featuredProducts as $product)
        @php
          $image = $product->image ? (strpos($product->image, 'http') === 0 ? $product->image : asset('images/product_images/' . $product->image)) : asset('images/product_images/default-food.jpg');
          $price = number_format((float)(($product->discount_price ?? 0) > 0 ? $product->discount_price : $product->price), 0, ',', ' ');
        @endphp
        <article class="pc rev">
          <div class="pc__img">
            <img src="{{ $image }}" alt="{{ $product->name }}"
                 onerror="this.src='{{ asset('images/product_images/default-food.jpg') }}'">
            <span class="pc__price">{{ $price }} FCFA</span>
          </div>
          <div class="pc__body">
            <div class="pc__name">{{ $product->name }}</div>
            <div class="pc__desc">{{ \Illuminate\Support\Str::limit($product->description ?: 'Plat recommandé sur BantuDelice.', 90) }}</div>
            <a href="{{ route('pro.detail', $product->id) }}" class="pc__btn">
              Voir le plat <i class="fas fa-arrow-right fa-xs"></i>
            </a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ── Parcours de commande animé ────────────────────────────── --}}
@if($foodEnabled)
<section class="journey-section" id="journey">
  <div class="journey-intro">
    <div class="sec__tag" style="justify-content:center;margin:0 auto 10px"><i class="fas fa-route"></i> De la commande à votre porte</div>
    <h2>Comment fonctionne la <em>livraison</em>&nbsp;?</h2>
    <p>En quelques minutes, votre repas passe du restaurant à votre domicile. Suivez chaque étape en temps réel.</p>
  </div>

  <div class="journey-timeline" id="journeyTimeline">

    {{-- Étape 1 : Choisir --}}
    <div class="jstep">
      <div class="jstep__bubble">
        <span class="jstep__icon">🔍</span>
        <span class="jstep__step-label">Étape 1</span>
      </div>
      <div class="jstep__content">
        <div class="jstep__num">01</div>
        <div class="jstep__title">Choisissez votre restaurant</div>
        <div class="jstep__body">Parcourez les menus, les notes et les délais de livraison estimés.</div>
        <span class="jstep__status status--done"><i class="fas fa-check"></i> Simple</span>
      </div>
    </div>

    {{-- Étape 2 : Commander --}}
    <div class="jstep">
      <div class="jstep__bubble">
        <span class="jstep__icon">🛒</span>
        <span class="jstep__step-label">Étape 2</span>
      </div>
      <div class="jstep__content">
        <div class="jstep__num">02</div>
        <div class="jstep__title">Composez votre panier</div>
        <div class="jstep__body">Ajoutez vos plats, personnalisez et confirmez votre adresse de livraison.</div>
        <span class="jstep__status status--done"><i class="fas fa-check"></i> Rapide</span>
      </div>
    </div>

    {{-- Étape 3 : Paiement --}}
    <div class="jstep">
      <div class="jstep__bubble">
        <span class="jstep__icon">💳</span>
        <span class="jstep__step-label">Étape 3</span>
      </div>
      <div class="jstep__content">
        <div class="jstep__num">03</div>
        <div class="jstep__title">Payez en sécurité</div>
        <div class="jstep__body">Mobile Money, Airtel, MTN MoMo ou cash — choisissez votre mode de paiement.</div>
        <span class="jstep__status status--progress"><i class="fas fa-spinner fa-spin"></i> En cours</span>
      </div>
    </div>

    {{-- Étape 4 : Préparation --}}
    <div class="jstep">
      <div class="jstep__bubble">
        <span class="jstep__icon">👨‍🍳</span>
        <span class="jstep__step-label">Étape 4</span>
      </div>
      <div class="jstep__content">
        <div class="jstep__num">04</div>
        <div class="jstep__title">Le restaurant prépare</div>
        <div class="jstep__body">Le cuisinier reçoit votre commande et prépare vos plats avec soin.</div>
        <span class="jstep__status status--pending"><i class="fas fa-clock"></i> ~15 min</span>
      </div>
    </div>

    {{-- Étape 5 : Livraison --}}
    <div class="jstep">
      <div class="jstep__bubble">
        <span class="jstep__icon">🛵</span>
        <span class="jstep__step-label">Étape 5</span>
      </div>
      <div class="jstep__content">
        <div class="jstep__num">05</div>
        <div class="jstep__title">Livreur à votre porte</div>
        <div class="jstep__body">Suivez votre livreur en temps réel sur la carte jusqu'à la livraison.</div>
        <span class="jstep__status status--pending"><i class="fas fa-location-dot"></i> GPS actif</span>
      </div>
    </div>

  </div>

  {{-- Mockup téléphone avec tracker --}}
  <div class="journey-mockup rev">
    <div class="jmock">
      <div class="jmock__bar">
        <span><i class="fas fa-bowl-food" style="margin-right:6px;color:var(--g2)"></i>BantuDelice</span>
        <i class="fas fa-bell"></i>
      </div>
      <div class="jmock__body">
        <div class="jmock__tracker">
          <div class="jmock__trk-title">📦 Suivi de votre commande</div>
          <div class="jmock__step">
            <span class="jmock__step-ic done"><i class="fas fa-check"></i></span>
            <span class="jmock__step-txt">Commande confirmée</span>
            <span class="jmock__step-t">12:04</span>
          </div>
          <div class="jmock__step">
            <span class="jmock__step-ic done"><i class="fas fa-check"></i></span>
            <span class="jmock__step-txt">Restaurant a accepté</span>
            <span class="jmock__step-t">12:06</span>
          </div>
          <div class="jmock__step">
            <span class="jmock__step-ic active"><i class="fas fa-fire-flame-curved"></i></span>
            <span class="jmock__step-txt" style="color:var(--or);font-weight:700">En préparation…</span>
            <span class="jmock__step-t">~12 min</span>
          </div>
          <div class="jmock__step">
            <span class="jmock__step-ic wait"><i class="fas fa-motorcycle"></i></span>
            <span class="jmock__step-txt">Livreur assigné</span>
            <span class="jmock__step-t">—</span>
          </div>
          <div class="jmock__step">
            <span class="jmock__step-ic wait"><i class="fas fa-home"></i></span>
            <span class="jmock__step-txt">Livraison</span>
            <span class="jmock__step-t">—</span>
          </div>
        </div>
        <div class="jmock__eta">
          <span class="jmock__eta-label">Livraison estimée</span>
          <span class="jmock__eta-val" id="jmockCountdown">28:00</span>
        </div>
      </div>
    </div>
  </div>

  <div style="text-align:center;margin-top:40px">
    <a href="{{ route('restaurants.all') }}" class="btn-green" style="padding:14px 36px;font-size:1rem">
      <i class="fas fa-bowl-food"></i> Commander maintenant
    </a>
  </div>
</section>
@endif

{{-- ── Food gallery / orbs ───────────────────────────────────── --}}
@if($foodEnabled)
<div class="food-gallery-sec">
  <div class="food-gallery-inner mx rev">
    <div class="food-gallery-text">
      <div class="food-gallery-badge">
        <i class="fas fa-leaf" style="color:var(--g)"></i> Fait avec passion
      </div>
      <div class="sec__tag" style="margin-top:14px"><i class="fas fa-utensils"></i> Notre cuisine</div>
      <h2 style="font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;line-height:1.15;color:var(--tx);margin:12px 0">
        Des saveurs <em style="font-style:normal;color:var(--g)">authentiques</em><br>livrées chez vous
      </h2>
      <p>Chaque restaurant partenaire est sélectionné pour la qualité de ses plats. Cuisine locale, grillades, plats mijotés — la richesse gastronomique du Congo dans votre assiette.</p>
      <a href="{{ route('restaurants.all') }}" class="btn-green" style="width:fit-content">
        <i class="fas fa-bowl-food"></i> Explorer les restaurants
      </a>
    </div>
    <div class="food-orbs">
      @php
        $orbImages = [
          asset('images/home/service-restaurant.jpg'),
          asset('images/home/service-driver.jpg'),
          asset('images/home/service-restaurant.jpg'),
          asset('images/home/service-transport.jpg'),
        ];
        $ri = 0;
        foreach($featuredRestaurants->take(4) as $ro) {
          $rmedia = $ro->cover_image ?: $ro->logo;
          if($rmedia) $orbImages[$ri] = strpos($rmedia,'http')===0 ? $rmedia : asset('images/restaurant_images/'.$rmedia);
          $ri++;
        }
      @endphp
      <div class="food-orb fo-1">
        <img src="{{ $orbImages[0] }}" alt="Plat" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
      <div class="food-orb fo-2">
        <img src="{{ $orbImages[1] }}" alt="Plat" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
      <div class="food-orb fo-3">
        <img src="{{ $orbImages[2] }}" alt="Plat" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
      <div class="food-orb fo-4">
        <img src="{{ $orbImages[3] }}" alt="Plat" onerror="this.src='{{ asset('images/home/service-restaurant.jpg') }}'">
      </div>
    </div>
  </div>
</div>
@endif

{{-- ── Impact band ───────────────────────────────────────────── --}}
<div class="impact-band">
  <div class="impact-inner">
    <div class="impact-left rev-l">
      <h2>La livraison <em>congolaise</em><br>à la hauteur de vos attentes</h2>
      <p>BantuDelice connecte les meilleurs restaurants de Brazzaville et Pointe-Noire à des milliers de clients. Chaque commande est suivie en temps réel, chaque livreur est formé, chaque plat arrive chaud.</p>
      <a href="{{ route('restaurants.all') }}" class="btn-green" style="width:fit-content;padding:14px 32px">
        <i class="fas fa-bowl-food"></i> Commander maintenant
      </a>
    </div>
    <div class="impact-stats rev">
      <div class="istat">
        <div class="istat__num" id="statResto">{{ $featuredRestaurants->count() }}+</div>
        <div class="istat__lbl">Restaurants partenaires</div>
      </div>
      <div class="istat">
        <div class="istat__num">~30 min</div>
        <div class="istat__lbl">Délai moyen de livraison</div>
      </div>
      <div class="istat">
        <div class="istat__num">2</div>
        <div class="istat__lbl">Villes actives au Congo</div>
      </div>
      <div class="istat">
        <div class="istat__num">4.2★</div>
        <div class="istat__lbl">Satisfaction client moyenne</div>
      </div>
    </div>
  </div>
</div>

{{-- ── Avis clients ──────────────────────────────────────────── --}}
<section class="sec bg2" id="testi">
  <div class="mx">
    <div class="sec__head">
      <div>
        <div class="sec__tag"><i class="fas fa-star"></i> {{ $homeContent['testimonials_tag'] ?? 'Avis clients' }}</div>
        <h2>{{ $homeContent['testimonials_title'] ?? 'Ils nous <em>font confiance</em>' }}</h2>
        <p class="sec__sub">Des milliers de clients satisfaits à Brazzaville et Pointe-Noire</p>
      </div>
    </div>
    <div class="testi-grid">
      @foreach($testimonials as $idx => $item)
        @php $initials = mb_strtoupper(mb_substr($item['name'],0,2)); @endphp
        <article class="tc {{ $idx === 1 ? 'tc--feat' : '' }}" style="animation:fadeUp .5s ease both;animation-delay:{{ $idx * 0.15 }}s">
          <span class="tc__stars">★★★★★</span>
          <div class="tc__sep"></div>
          <span class="tc__tag">{{ $item['tag'] }}</span>
          <div class="tc__quote">"{{ $item['quote'] }}"</div>
          <div class="tc__author">
            <div class="tc__avatar">{{ $initials }}</div>
            <div>
              <div class="tc__name">{{ $item['name'] }}</div>
              <div class="tc__loc"><i class="fas fa-location-dot fa-xs"></i> {{ $item['loc'] }}</div>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Écosystème hub ────────────────────────────────────────── --}}
<div class="hub-section">
  <div class="hub-intro rev">
    <div class="sec__tag"><i class="fas fa-th-large"></i> Écosystème digital</div>
    <h2>L'écosystème <em>congolais</em></h2>
    <p>BantuDelice reste la porte d'entree food. Les autres plateformes de l'ecosysteme sont accessibles directement via leurs domaines dedies.</p>
  </div>
  <div class="hub-grid">
    {{-- BantuDelice --}}
    <div class="hub-card hub-card--active rev">
      <div class="hub-card__ic gr"><i class="fas fa-bowl-food"></i></div>
      <span class="hub-badge active-b"><i class="fas fa-circle fa-xs"></i> Actif</span>
      <div class="hub-card__name">BantuDelice</div>
      <div class="hub-card__desc">Livraison de repas depuis les meilleurs restaurants de Brazzaville et Pointe-Noire. Commander en quelques secondes.</div>
      @if($foodEnabled)
        <a href="{{ route('restaurants.all') }}" class="hub-card__link">
          Commander maintenant <i class="fas fa-arrow-right fa-xs"></i>
        </a>
      @endif
    </div>
    @foreach($ecosystemPlatforms as $index => $platform)
      <div class="hub-card rev" style="transition-delay:{{ number_format(0.08 * ($index + 1), 2) }}s">
        <div class="hub-card__ic {{ ($platform['name'] ?? '') === 'Salisa' ? 'pu' : (($platform['name'] ?? '') === 'Kosunga' ? 'te' : (($platform['name'] ?? '') === 'Memela' ? 'or' : 'gr')) }}"><i class="fas {{ $platform['icon'] ?? 'fa-circle' }}"></i></div>
        <span class="hub-badge {{ ($platform['badge'] ?? '') === 'Disponible' ? 'active-b' : 'coming-b' }}"><i class="fas fa-arrow-up-right-from-square fa-xs"></i> {{ $platform['badge'] ?? 'Disponible' }}</span>
        <div class="hub-card__name">{{ $platform['name'] }}</div>
        <div class="hub-card__desc">{{ $platform['description'] }}</div>
        <a href="{{ $platform['url'] }}" class="hub-card__link" target="_blank" rel="noopener">
          Ouvrir {{ $platform['name'] }} <i class="fas fa-arrow-right fa-xs"></i>
        </a>
      </div>
    @endforeach
  </div>
</div>

{{-- ── Opportunités ──────────────────────────────────────────── --}}
<section class="sec" id="opportunities">
  <div class="mx">
    <div class="opp-intro rev">
      <div class="sec__tag" style="justify-content:center">{{ $homeContent['opportunities_tag'] ?? 'Opportunités' }}</div>
      <h2>{{ $homeContent['opportunities_title'] ?? 'Grandissez avec BantuDelice' }}</h2>
      <p>{{ $homeContent['opportunities_subtitle'] ?? "Que vous soyez coursier, enseigne ou candidat, BantuDelice ouvre des relais de croissance concrets au Congo." }}</p>
    </div>
    <div class="opp-grid">
      @foreach($opportunityCards as $card)
        <article class="oc rev">
          <div class="oc__img">
            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}">
          </div>
          <div class="oc__body">
            <div class="oc__title">{{ $card['title'] }}</div>
            <p class="oc__txt">{{ $card['body'] }}</p>
            <a href="{{ $card['href'] }}" class="oc__cta">
              {{ $card['cta'] }} <i class="fas fa-arrow-right fa-xs"></i>
            </a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Carte du Congo ────────────────────────────────────────── --}}
<section class="sec bg2" id="congo">
  <div class="mx rev">
    <div class="congo-head">
      <div>
        <div class="sec__tag"><i class="fas fa-map-location-dot"></i> Couverture nationale</div>
        <h2>Présents dans <em>tout le Congo</em></h2>
      </div>
      <span class="congo-meta"><i class="fas fa-location-dot"></i> 15 départements</span>
    </div>
    <div class="congo-wrap">
      <div class="congo-map">
        <svg viewBox="0 0 700 980" role="img" aria-label="Carte des 15 départements du Congo">
          <defs>
            <linearGradient id="cgFill" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#bbf7d0"/>
              <stop offset="100%" stop-color="#86efac"/>
            </linearGradient>
          </defs>
          <path d="M391 35 C459 64,533 118,548 191 C558 240,539 292,561 345 C585 403,648 463,641 528 C635 586,589 630,559 678 C523 735,507 806,468 863 C430 918,376 955,323 943 C279 932,258 886,221 859 C170 822,101 802,79 742 C54 673,89 602,101 539 C114 470,83 410,99 351 C118 280,182 240,222 188 C270 125,307 74,391 35 Z"
                fill="url(#cgFill)" stroke="rgba(22,163,74,.35)" stroke-width="8" stroke-linejoin="round"/>
          <path d="M248 170 C305 230,418 221,489 191 M196 292 C286 318,418 311,522 280 M171 430 C283 453,437 439,556 396 M150 584 C273 615,436 602,572 548 M146 721 C250 751,384 760,500 713 M259 116 C246 221,233 328,241 437 M345 80 C340 193,338 304,348 423 M433 111 C423 228,428 355,445 500 M514 245 C505 352,493 461,472 586 M309 472 C306 594,315 704,340 851"
                fill="none" stroke="rgba(22,163,74,.12)" stroke-width="4" stroke-linecap="round"/>
        </svg>
        @foreach($congoDepartments as $dept)
          <span class="congo-pin" style="left:{{ $dept['x'] }};top:{{ $dept['y'] }};">{{ $dept['name'] }}</span>
        @endforeach
      </div>
      <div class="congo-foot">
        <span class="congo-foot__note">15 départements du Congo couverts par la plateforme BantuDelice.</span>
        <span class="congo-foot__tag"><i class="fas fa-check-circle fa-xs"></i> Couverture nationale</span>
      </div>
    </div>
  </div>
</section>

{{-- ── Application mobile ────────────────────────────────────── --}}
<section class="sec" id="app">
  <div class="mx">
    <div class="app2__grid rev">
      <div class="app2__phones">
        <div class="app2__phone">
          <div class="app2__phone-h">
            <strong>BantuDelice</strong>
            <i class="fas fa-bars" style="color:var(--tx3)"></i>
          </div>
          <div class="app2__phone-b">
            <div class="app2__mini">Rechercher un restaurant ou un plat…</div>
            @foreach($featuredRestaurants->take(3) as $r)
              <div class="app2__mini">{{ $r->name }} · {{ number_format((float)($r->delivery_charges ?? 0), 0, ',', ' ') }} FCFA</div>
            @endforeach
            <div class="btn-green" style="min-height:38px;padding:0 14px;font-size:.82rem;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:6px;">
              <i class="fas fa-bowl-food fa-xs"></i> Commander
            </div>
          </div>
        </div>
        <div class="app2__phone sm">
          <div class="app2__phone-h">
            <strong>Suivi repas</strong>
            <i class="fas fa-clock" style="color:var(--or)"></i>
          </div>
          <div class="app2__phone-b">
            <div class="app2__mini">En préparation · 12 min</div>
            <div class="app2__mini">Livreur assigné · en route</div>
            <div class="app2__mini" style="height:72px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-map-location-dot fa-2x" style="color:var(--g);opacity:.4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="app2__text">
        <div class="sec__tag"><i class="fas fa-mobile-screen-button"></i> Application mobile</div>
        <h2>BantuDelice,<br>partout au <em>Congo.</em></h2>
        <p>Commandez et suivez votre livraison depuis votre téléphone. Une interface claire, rapide, disponible 7j/7.</p>
        <div class="app2__stores">
          <span class="app2__store"><i class="fab fa-apple"></i> App Store</span>
          <span class="app2__store"><i class="fab fa-google-play"></i> Google Play</span>
        </div>
        <div class="app2__feats">
          <div class="app2__feat"><span class="app2__check"><i class="fas fa-check"></i></span>Suivi GPS de votre commande en temps réel</div>
          <div class="app2__feat"><span class="app2__check"><i class="fas fa-check"></i></span>Notifications à chaque étape de la livraison</div>
          <div class="app2__feat"><span class="app2__check"><i class="fas fa-check"></i></span>Mobile Money, Airtel Money, MTN MoMo, Cash</div>
          <div class="app2__feat"><span class="app2__check"><i class="fas fa-check"></i></span>Vos commandes favorites en un clic</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ── Réseaux sociaux ───────────────────────────────────────── --}}
<section class="sec bg2" id="social">
  <div class="mx rev">
    <div class="soc2__intro">
      <div class="sec__tag" style="justify-content:center;margin:0 auto 10px"><i class="fas fa-users"></i> Communauté</div>
      <h2>Rejoignez la <em>communauté</em></h2>
      <p>Offres exclusives, nouveaux restaurants et actualités BantuDelice directement dans votre fil.</p>
    </div>
    <div class="soc2__grid">
      @foreach($socialLinks as $s)
        <a href="{{ $s['href'] }}" class="soc2__card"
           @if(!empty($s['is_external'])) target="_blank" rel="noopener noreferrer" @endif>
          <span class="soc2__ic {{ $s['tone'] }}"><i class="{{ $s['icon'] }}"></i></span>
          <strong class="soc2__name">{{ $s['label'] }}</strong>
          <small class="soc2__handle">{{ $s['handle'] }}</small>
          <span class="soc2__open">Voir <i class="fas fa-external-link-alt fa-xs"></i></span>
        </a>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Chiffres clés ─────────────────────────────────────────── --}}
<section class="sec" id="trust">
  <div class="mx rev">
    <div style="text-align:center;margin-bottom:40px">
      <div class="sec__tag" style="justify-content:center;margin:0 auto 10px"><i class="fas fa-chart-line"></i> En chiffres</div>
      <h2>La confiance <em>se prouve</em></h2>
    </div>
    <div class="stats-grid">
      <div class="stat-cell">
        <div class="stat-num">{{ $featuredRestaurants->count() }}+</div>
        <div class="stat-lbl">Restaurants partenaires référencés</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">~30 min</div>
        <div class="stat-lbl">Délai moyen de livraison</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">2 villes</div>
        <div class="stat-lbl">Zones urbaines actives</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">4.2★</div>
        <div class="stat-lbl">Satisfaction client moyenne</div>
      </div>
    </div>
  </div>
</section>

{{-- ── Footer ────────────────────────────────────────────────── --}}
<footer class="ft2">
  <div class="ft2__grid">
    <div>
      <a href="{{ route('home') }}" class="ft2__brand">
        <img src="{{ asset('frontend/images/BuntuDelice.png') }}" alt="BantuDelice" class="ft2__logo"
             onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
        <span style="display:none;" class="ft2__brand-name">BantuDelice</span>
      </a>
      <p class="ft2__desc">Plateforme congolaise de livraison de repas. Commandez, suivez et recevez vos plats favoris depuis les meilleurs restaurants de Brazzaville et Pointe-Noire.</p>
      <div class="ft2__socs">
        @foreach($footerSocials as $s)
          <a class="ft2__soc" href="{{ $s['href'] }}" aria-label="{{ $s['label'] }}"
             @if(!empty($s['is_external'])) target="_blank" rel="noopener noreferrer" @endif>
            <i class="{{ $s['icon'] }}"></i>
          </a>
        @endforeach
      </div>
    </div>
    <div class="ft2__col">
      <h4>Livraison repas</h4>
      <div class="ft2__links">
        @if($foodEnabled)
          <a href="{{ route('restaurants.all') }}">Explorer les restaurants</a>
          <a href="{{ route('track.order') }}">Suivi de commande</a>
        @endif
        <a href="{{ route('offers') }}">Offres et promotions</a>
        <a href="{{ route('faq') }}">Questions fréquentes</a>
      </div>
    </div>
    <div class="ft2__col">
      <h4>Partenaires</h4>
      <div class="ft2__links">
        <a href="{{ route('partner') }}">Devenir restaurant partenaire</a>
        <a href="{{ route('driver') }}">Devenir livreur</a>
        <a href="{{ route('contact.us') }}">Contacter l'équipe</a>
      </div>
    </div>
    <div class="ft2__col">
      <h4>Informations</h4>
      <div class="ft2__links">
        <a href="{{ route('terms.conditions') }}">Conditions générales</a>
        <a href="{{ route('privacy.policy') }}">Confidentialité</a>
        <a href="{{ route('help') }}">Centre d'aide</a>
        <a href="{{ route('contact.us') }}">Nous contacter</a>
        <a href="{{ route('site.map') }}">Plan du site</a>
      </div>
    </div>
  </div>
  <div class="ft2__bot">
    <span class="ft2__copy">&copy; {{ date('Y') }} BantuDelice. Tous droits réservés.</span>
    <span class="ft2__pays">
      @foreach($paymentMethods as $pm)<span class="ft2__pay">{{ $pm }}</span>@endforeach
    </span>
    <span class="ft2__legal">
      <a href="{{ route('legal.notices') }}">Mentions légales</a>
      <a href="{{ route('cookies.policy') }}">Cookies</a>
      <a href="{{ route('site.map') }}">Plan du site</a>
    </span>
  </div>
</footer>

</div>{{-- .bd2 --}}
@endsection

@section('scripts')
<script>
(function(){
  // ── Scroll progress bar
  var prog=document.getElementById('scrollProg');
  if(prog){
    window.addEventListener('scroll',function(){
      var s=document.documentElement;
      var p=(s.scrollTop||document.body.scrollTop)/(s.scrollHeight-s.clientHeight)*100;
      prog.style.width=Math.min(p,100)+'%';
    },{passive:true});
  }
})();

(function(){
  // ── Horloge hero
  var c=document.getElementById('heroClock');
  function t(){if(c)c.textContent=new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}
  t();setInterval(t,1000);
})();

(function(){
  // ── Nav shadow on scroll
  var nav=document.getElementById('mainNav');
  if(!nav)return;
  window.addEventListener('scroll',function(){
    nav.classList.toggle('scrolled',window.scrollY>10);
  },{passive:true});
})();

(function(){
  // ── Platforms dropdown
  var dd=document.getElementById('navPlatformsDD');
  var btn=document.getElementById('navPlatformsBtn');
  if(!dd||!btn)return;
  btn.addEventListener('click',function(e){
    e.stopPropagation();
    dd.classList.toggle('open');
  });
  document.addEventListener('click',function(e){
    if(!dd.contains(e.target))dd.classList.remove('open');
  });
})();

(function(){
  // ── Generate bubbles in hero
  var hero=document.getElementById('hero');
  if(!hero)return;
  for(var i=0;i<18;i++){
    var b=document.createElement('div');
    b.className='hero-bubble';
    var sz=20+Math.random()*60;
    b.style.cssText=[
      'width:'+sz+'px',
      'height:'+sz+'px',
      'left:'+Math.random()*100+'%',
      'bottom:'+(Math.random()*30-10)+'%',
      'animation-duration:'+(8+Math.random()*10)+'s',
      'animation-delay:'+(Math.random()*12)+'s',
    ].join(';');
    hero.appendChild(b);
  }
})();

(function(){
  // ── Generate rain drops in hero
  var hero=document.getElementById('hero');
  if(!hero)return;
  for(var i=0;i<30;i++){
    var r=document.createElement('div');
    r.className='rain-drop';
    var h=15+Math.random()*30;
    r.style.cssText=[
      'height:'+h+'px',
      'left:'+Math.random()*100+'%',
      'top:'+(Math.random()*20-20)+'px',
      'animation-duration:'+(0.6+Math.random()*1.2)+'s',
      'animation-delay:'+(Math.random()*4)+'s',
    ].join(';');
    hero.appendChild(r);
  }
})();

(function(){
  // ── Hero chips active state
  document.querySelectorAll('.hero2__chip').forEach(function(chip){
    chip.addEventListener('click',function(){
      document.querySelectorAll('.hero2__chip').forEach(function(c){c.classList.remove('active')});
      this.classList.add('active');
    });
  });
})();

(function(){
  // ── Journey steps reveal with staggered delay
  var steps=document.querySelectorAll('.jstep');
  if(!steps.length||!('IntersectionObserver' in window)){
    steps.forEach(function(s){s.classList.add('on')});
  } else {
    var jio=new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){
          var idx=Array.from(steps).indexOf(en.target);
          setTimeout(function(){en.target.classList.add('on')},idx*120);
          jio.unobserve(en.target);
        }
      });
    },{threshold:0.15});
    steps.forEach(function(s){jio.observe(s)});
  }
})();

(function(){
  // ── Journey mockup countdown timer
  var el=document.getElementById('jmockCountdown');
  if(!el)return;
  var secs=28*60;
  setInterval(function(){
    if(secs>0)secs--;
    var m=Math.floor(secs/60);
    var s=secs%60;
    el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
  },1000);
})();

(function(){
  // ── Scroll reveal
  var els=document.querySelectorAll('.rev,.rev-l');
  if(!els.length||!('IntersectionObserver' in window)){
    els.forEach(function(e){e.classList.add('on')});return;
  }
  var io=new IntersectionObserver(function(entries){
    entries.forEach(function(en){
      if(en.isIntersecting){en.target.classList.add('on');io.unobserve(en.target)}
    });
  },{threshold:0.10});
  els.forEach(function(e){io.observe(e)});
})();

(function(){
  // ── Custom cursor (pointer device only)
  if(!window.matchMedia||!window.matchMedia('(pointer:fine)').matches)return;
  var cur=document.getElementById('bdHomeCursor'),
      dot=document.getElementById('bdHomeCursorDot'),
      ring=document.getElementById('bdHomeCursorRing');
  if(!cur||!dot||!ring)return;
  document.body.classList.add('bd-home-cursor-ready');
  var x=window.innerWidth/2,y=window.innerHeight/2,rx=x,ry=y;
  document.addEventListener('mousemove',function(e){
    x=e.clientX;y=e.clientY;
    dot.style.left=x+'px';dot.style.top=y+'px';
  });
  document.addEventListener('mouseover',function(e){
    document.body.classList.toggle('bd-home-cursor-hover',!!e.target.closest('a,button'));
  });
  (function ani(){rx+=(x-rx)*.16;ry+=(y-ry)*.16;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(ani)})();
})();
</script>
@endsection
