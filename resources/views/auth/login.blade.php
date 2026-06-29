<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #15803d;
            --primary-dark: #166534;
            --primary-light: #22c55e;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            grid-template-rows: 1fr;
            color: var(--text);
        }
        .hero-panel {
            background: linear-gradient(150deg, var(--primary-dark), var(--primary) 55%, var(--primary-light));
            color: #fff;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            right: -100px;
            top: -100px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .hero-panel::before {
            content: "";
            position: absolute;
            left: -120px;
            bottom: -120px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .hero-panel .brand { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
        .hero-panel .brand .logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }
        .hero-panel .brand .name { font-size: 16px; font-weight: 800; }
        .hero-panel .hero-text { position: relative; z-index: 1; }
        .hero-panel .hero-text h2 { font-size: 30px; font-weight: 800; line-height: 1.25; letter-spacing: -0.5px; }
        .hero-panel .hero-text p { margin-top: 14px; font-size: 15px; opacity: 0.92; max-width: 380px; line-height: 1.6; }
        .hero-panel .foot { font-size: 12.5px; opacity: 0.8; position: relative; z-index: 1; }
        .form-panel {
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }
        .form-card { width: 100%; max-width: 400px; }
        .form-card .mobile-logo {
            display: none;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            margin: 0 auto 18px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }
        .form-card h1 { font-size: 23px; font-weight: 800; }
        .form-card .sub { color: var(--muted); font-size: 14px; margin-top: 6px; margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 7px; }
        .field { margin-bottom: 16px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            font-size: 13px;
            padding: 11px 13px;
            border-radius: 10px;
            margin-bottom: 14px;
        }
        .turnstile-wrap {
            display: flex;
            justify-content: center;
            margin: 4px 0 12px;
            padding: 10px;
            background: #f7faf8;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 4px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 10px 22px rgba(21, 128, 61, 0.25);
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(21, 128, 61, 0.35); }
        .form-foot { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 24px; }
        @media (max-width: 860px) {
            body { grid-template-columns: 1fr; }
            .hero-panel { display: none; }
            .form-card .mobile-logo { display: flex; }
            .form-card { text-align: center; }
            .form-card h1, .form-card .sub { text-align: center; }
            label { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="hero-panel">
        <div class="brand">
            <div class="logo">RQ</div>
            <div class="name">{{ config('app.name') }}</div>
        </div>
        <div class="hero-text">
            <h2>Portal Layanan Digital Terpadu</h2>
            <p>Satu akun untuk mengakses seluruh sistem informasi lembaga: keuangan, pembayaran, dan presensi.</p>
        </div>
        <div class="foot">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
    </div>

    <div class="form-panel">
        <div class="form-card">
            <div class="mobile-logo">RQ</div>
            <h1>Masuk ke Portal</h1>
            <p class="sub">Silakan masuk menggunakan akun Anda</p>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            @if(session('status'))
                <div class="error" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <input type="password" id="password" name="password" required>
                </div>

                @if(config('services.turnstile.site_key'))
                    <div class="turnstile-wrap">
                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ config('services.turnstile.site_key') }}"
                            data-theme="light"
                            data-language="id"
                        ></div>
                    </div>
                @endif

                <button type="submit">Masuk</button>
            </form>

            <p class="form-foot">Sistem Informasi Terpadu &middot; {{ config('app.name') }}</p>
        </div>
    </div>

    @if(config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</body>
</html>
