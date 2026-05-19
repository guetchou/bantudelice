<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') — BantuDelice</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8f8f6;color:#1a1a1a;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
.bd-err{text-align:center;max-width:480px;width:100%}
.bd-err-logo{display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:40px}
.bd-err-logo-circle{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#ff8c00,#e85d04);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:#fff}
.bd-err-logo-name{font-size:1.1rem;font-weight:900;color:#1a1a1a}
.bd-err-code{font-size:5rem;font-weight:900;line-height:1;letter-spacing:-.04em;background:linear-gradient(135deg,#ff8c00,#e85d04);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:12px}
.bd-err-title{font-size:1.35rem;font-weight:800;color:#1a1a1a;margin-bottom:8px}
.bd-err-msg{font-size:.95rem;color:#667085;line-height:1.6;margin-bottom:32px}
.bd-err-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;background:linear-gradient(135deg,#ff8c00,#e85d04);color:#fff;font-weight:700;font-size:.9rem;text-decoration:none;box-shadow:0 4px 16px rgba(232,93,4,.3);transition:opacity .15s}
.bd-err-btn:hover{opacity:.88}
.bd-err-divider{margin:20px 0;color:#e0e0e0}
.bd-err-link{font-size:.85rem;color:#e85d04;text-decoration:none;font-weight:600}
.bd-err-link:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="bd-err">
    <a href="/" class="bd-err-logo">
        <div class="bd-err-logo-circle">B</div>
        <span class="bd-err-logo-name">BantuDelice</span>
    </a>
    @yield('body')
</div>
</body>
</html>
