<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir une application</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: #0a0f1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 24px;
        }

        /* Subtle animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(0,149,67,.12), transparent),
                radial-gradient(ellipse 60% 40% at 80% 60%, rgba(29,78,216,.10), transparent),
                radial-gradient(ellipse 50% 40% at 50% 10%, rgba(194,65,12,.08), transparent);
            pointer-events: none;
        }

        /* WINDOW */
        .portal-window {
            position: relative;
            width: 100%;
            max-width: 860px;
            background: rgba(15, 23, 42, 0.92);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 20px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,.04),
                0 32px 80px rgba(0,0,0,.6),
                0 8px 24px rgba(0,0,0,.4);
            overflow: hidden;
            backdrop-filter: blur(24px);
        }

        /* Title bar */
        .portal-titlebar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            background: rgba(255,255,255,.03);
        }
        .portal-titlebar__dots { display: flex; gap: 6px; }
        .portal-titlebar__dot {
            width: 12px; height: 12px; border-radius: 50%;
        }
        .portal-titlebar__dot--red   { background: #ff5f57; }
        .portal-titlebar__dot--yellow { background: #febc2e; }
        .portal-titlebar__dot--green  { background: #28c840; }
        .portal-titlebar__label {
            margin-left: 10px;
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255,255,255,.35);
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* Header */
        .portal-header {
            padding: 32px 40px 24px;
            text-align: center;
        }
        .portal-header__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            font-size: .7rem;
            font-weight: 700;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-bottom: 16px;
        }
        @if($isSuperAdmin)
        .portal-header__eyebrow { background: rgba(0,149,67,.15); border-color: rgba(0,149,67,.3); color: #4ade80; }
        @endif
        .portal-header__title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
        }
        .portal-header__sub {
            margin-top: 8px;
            font-size: .85rem;
            color: rgba(255,255,255,.4);
        }

        /* App grid */
        .portal-apps {
            display: grid;
            grid-template-columns: repeat({{ count($apps) }}, minmax(0,1fr));
            gap: 16px;
            padding: 0 32px 32px;
        }

        .portal-app {
            position: relative;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            padding: 24px 20px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: border-color .2s, background .2s, transform .15s;
            cursor: pointer;
        }
        .portal-app:hover {
            background: rgba(255,255,255,.07);
            border-color: rgba(255,255,255,.18);
            transform: translateY(-2px);
        }
        .portal-app::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 14px;
            background: radial-gradient(circle at top left, var(--app-color), transparent 60%);
            opacity: 0;
            transition: opacity .2s;
            pointer-events: none;
        }
        .portal-app:hover::before { opacity: .07; }

        .portal-app__icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #fff;
            background: var(--app-color);
            box-shadow: 0 6px 20px rgba(0,0,0,.3);
            flex-shrink: 0;
        }
        .portal-app__label {
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
        }
        .portal-app__tagline {
            font-size: .75rem;
            font-weight: 500;
            color: rgba(255,255,255,.45);
            margin-top: 2px;
        }
        .portal-app__desc {
            font-size: .78rem;
            color: rgba(255,255,255,.35);
            line-height: 1.55;
        }
        .portal-app__cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
            height: 36px;
            padding: 0 16px;
            border-radius: 8px;
            background: var(--app-color);
            color: #fff;
            font-size: .8rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            width: fit-content;
            transition: opacity .15s;
        }
        .portal-app__cta:hover { opacity: .88; }

        /* Footer */
        .portal-footer {
            padding: 16px 32px;
            border-top: 1px solid rgba(255,255,255,.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .portal-footer__user {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .portal-footer__avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
            font-weight: 800;
            color: rgba(255,255,255,.6);
        }
        .portal-footer__name {
            font-size: .78rem;
            font-weight: 600;
            color: rgba(255,255,255,.45);
        }
        .portal-footer__logout {
            font-size: .75rem;
            color: rgba(255,255,255,.3);
            text-decoration: none;
            font-weight: 600;
            transition: color .15s;
        }
        .portal-footer__logout:hover { color: rgba(255,255,255,.6); }

        @media (max-width: 640px) {
            .portal-apps { grid-template-columns: 1fr; }
            .portal-header { padding: 24px 24px 20px; }
            .portal-footer { padding: 12px 24px; }
        }
        @media (max-width: 440px) {
            .portal-window { border-radius: 14px; }
        }
    </style>
</head>
<body>
    <div class="portal-window">

        <div class="portal-titlebar">
            <div class="portal-titlebar__dots">
                <span class="portal-titlebar__dot portal-titlebar__dot--red"></span>
                <span class="portal-titlebar__dot portal-titlebar__dot--yellow"></span>
                <span class="portal-titlebar__dot portal-titlebar__dot--green"></span>
            </div>
            <span class="portal-titlebar__label">Admin — Choisir une application</span>
        </div>

        <div class="portal-header">
            <div class="portal-header__eyebrow">
                @if($isSuperAdmin)
                    <i class="fas fa-shield-alt" style="font-size:.65rem;"></i> Super administrateur
                @else
                    <i class="fas fa-user-shield" style="font-size:.65rem;"></i> Administrateur
                @endif
            </div>
            <h1 class="portal-header__title">Quelle application ?</h1>
            <p class="portal-header__sub">Choisissez l'espace de travail pour cette session.</p>
        </div>

        <div class="portal-apps">
            @foreach($apps as $app)
                <a href="{{ route($app['url']) }}" class="portal-app" style="--app-color: {{ $app['color'] }};">
                    <span class="portal-app__icon"><i class="{{ $app['icon'] }}"></i></span>
                    <div>
                        <div class="portal-app__label">{{ $app['label'] }}</div>
                        <div class="portal-app__tagline">{{ $app['tagline'] }}</div>
                    </div>
                    <p class="portal-app__desc">{{ $app['desc'] }}</p>
                    <span class="portal-app__cta">
                        <i class="fas fa-sign-in-alt" style="font-size:.7rem;"></i>
                        Se connecter
                    </span>
                </a>
            @endforeach
        </div>

        <div class="portal-footer">
            <div class="portal-footer__user">
                <div class="portal-footer__avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <span class="portal-footer__name">{{ auth()->user()->name ?? auth()->user()->email }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="portal-footer__logout" style="background:none;border:none;cursor:pointer;">
                    <i class="fas fa-sign-out-alt" style="margin-right:4px;font-size:.7rem;"></i>Deconnexion
                </button>
            </form>
        </div>

    </div>
</body>
</html>
