<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #1f6b45;
            --green-dark: #185a3a;
            --green-soft: #e8f5ee;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #d1e7d8;
            --bg: #eef4f0;
            --card: #ffffff;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .portal-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .portal-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(31, 107, 69, 0.08);
            padding: 28px 28px 24px;
        }
        .portal-card.wide { max-width: 720px; }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .brand img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--green-soft);
        }
        .brand-name {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--green);
            line-height: 1.2;
        }
        .portal-title {
            text-align: center;
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 4px;
        }
        .portal-sub {
            text-align: center;
            color: var(--muted);
            font-size: 0.95rem;
            margin-bottom: 22px;
        }
        .field { margin-bottom: 14px; }
        .field label {
            display: block;
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--green);
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            height: 46px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(31, 107, 69, 0.12);
        }
        .error-text {
            color: var(--danger);
            font-size: 0.82rem;
            margin-top: 5px;
        }
        .btn-primary {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 10px;
            background: var(--green);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: background .15s, transform .1s;
        }
        .btn-primary:hover { background: var(--green-dark); }
        .btn-primary:active { transform: translateY(1px); }
        .portal-note {
            margin-top: 18px;
            padding: 14px 16px;
            background: #f7faf8;
            border-radius: 12px;
            font-size: 0.82rem;
            color: #4b5563;
            line-height: 1.55;
        }
        .portal-note strong {
            display: block;
            color: var(--green);
            margin-bottom: 6px;
            font-size: 0.88rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 14px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88rem;
        }
        .back-link:hover { color: var(--green); }
        .alert {
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 14px;
        }
        .alert-error { background: #fef2f2; color: #b91c1c; }
        .alert-info { background: #eff6ff; color: #1d4ed8; }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 8px;
        }
        .module-card {
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 16px;
            text-align: center;
            background: #fff;
            transition: transform .15s, box-shadow .15s, border-color .15s;
        }
        .module-card:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(31, 107, 69, 0.12);
            border-color: var(--green);
        }
        .module-card.disabled {
            opacity: 0.55;
            cursor: not-allowed;
            background: #f9fafb;
        }
        .module-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 12px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: var(--green-soft);
            color: var(--green);
        }
        .module-card h3 {
            font-size: 1rem;
            margin-bottom: 4px;
            color: var(--green);
        }
        .module-card p {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .portal-user {
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 18px;
        }
        .portal-user strong { color: var(--text); }
        .portal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            gap: 12px;
        }
        .turnstile-wrap {
            display: flex;
            justify-content: center;
            margin: 4px 0 14px;
            padding: 10px;
            background: #f7faf8;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .btn-link {
            background: none;
            border: none;
            color: var(--green);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
        }
    </style>
    @yield('style')
</head>
<body>
    @yield('content')
</body>
</html>
