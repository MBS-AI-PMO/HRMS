@php
    $general_setting = $general_setting ?? \App\Models\GeneralSetting::latest()->first();
    $siteTitle = $general_setting->site_title ?? 'HRMS';
    $logoUrl = ! empty($general_setting->site_logo)
        ? asset('/images/logo/'.$general_setting->site_logo)
        : null;
    $footerText = $general_setting->footer ?? '';
    $footerLink = $general_setting->footer_link ?? '#';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (! empty($general_setting->site_logo))
        <link rel="icon" type="image/png" href="{{ asset('/images/logo/'.$general_setting->site_logo) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.min.css') }}">
    <style>
        :root {
            --auth-violet: #2d1848;
            --auth-violet-mid: #4a3580;
            --auth-violet-light: #6b5b9e;
            --auth-violet-glow: #8b7cc4;
            --auth-ink: #0f172a;
            --auth-muted: #64748b;
            --auth-border: #e8edf4;
            --auth-bg: #f0f4f9;
            --auth-surface: #ffffff;
            --auth-radius: 20px;
        }

        * { box-sizing: border-box; }

        body.auth-page {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--auth-bg);
            color: var(--auth-ink);
            -webkit-font-smoothing: antialiased;
        }

        .auth-shell {
            display: grid;
            grid-template-columns: 60% 40%;
            min-height: 100vh;
            position: relative;
        }

        /* ── Brand panel ── */
        .auth-brand {
            background:
                radial-gradient(ellipse 80% 60% at 10% 90%, rgba(139, 124, 196, 0.35) 0%, transparent 55%),
                radial-gradient(ellipse 50% 40% at 85% 15%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                linear-gradient(160deg, #1e0f35 0%, var(--auth-violet) 35%, var(--auth-violet-mid) 70%, #5a4a8a 100%);
            color: #fff;
            padding: 48px 56px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .auth-brand__grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.6) 0%, transparent 90%);
            pointer-events: none;
        }

        .auth-brand__orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-brand__orb--1 {
            width: 420px;
            height: 420px;
            top: -120px;
            right: -80px;
            background: radial-gradient(circle, rgba(255,255,255,.09) 0%, transparent 70%);
        }

        .auth-brand__orb--2 {
            width: 280px;
            height: 280px;
            bottom: -60px;
            left: -40px;
            background: radial-gradient(circle, rgba(139,124,196,.25) 0%, transparent 70%);
        }

        .auth-brand__inner {
            position: relative;
            z-index: 2;
            max-width: 520px;
        }

        .auth-brand__logo img {
            max-height: 52px;
            max-width: 180px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .auth-brand__logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 40px;
            display: block;
        }

        .auth-brand__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 20px;
            backdrop-filter: blur(8px);
        }

        .auth-brand__badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 8px rgba(74, 222, 128, 0.6);
        }

        .auth-brand__headline {
            font-size: clamp(1.75rem, 3vw, 2.35rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.035em;
            margin: 0 0 16px;
        }

        .auth-brand__tagline {
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.78);
            margin: 0;
            max-width: 440px;
        }

        .auth-brand__features {
            list-style: none;
            padding: 0;
            margin: 40px 0 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .auth-brand__features li {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(6px);
            transition: background 0.2s;
        }

        .auth-brand__features li:hover {
            background: rgba(255, 255, 255, 0.11);
        }

        .auth-brand__features i {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .auth-brand__footer {
            position: relative;
            z-index: 2;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.45);
            padding-top: 24px;
        }

        /* ── Form side ── */
        .auth-main {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 48px 40px 40px 0;
            position: relative;
            z-index: 10;
            background: var(--auth-bg);
        }

        .auth-card {
            width: 100%;
            max-width: 480px;
            background: var(--auth-surface);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: var(--auth-radius);
            box-shadow:
                0 0 0 1px rgba(55, 32, 91, 0.04),
                0 24px 60px rgba(45, 24, 72, 0.2),
                0 8px 24px rgba(15, 23, 42, 0.08);
            padding: 0;
            position: relative;
            z-index: 10;
            overflow: hidden;
            /* half card sits on blue panel, half on white */
            transform: translateX(-50%);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--auth-violet), var(--auth-violet-mid), var(--auth-violet-glow));
        }

        .auth-card__body {
            padding: 36px 36px 32px;
        }

        .auth-card--wide { max-width: 820px; }

        .auth-card__eyebrow {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--auth-violet-mid);
            margin-bottom: 10px;
        }

        .auth-card__title {
            font-size: 1.625rem;
            font-weight: 800;
            color: var(--auth-ink);
            margin: 0 0 8px;
            letter-spacing: -0.03em;
        }

        .auth-card__subtitle {
            font-size: 0.875rem;
            color: var(--auth-muted);
            margin: 0 0 28px;
            line-height: 1.55;
        }

        .auth-field { margin-bottom: 18px; }

        .auth-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .auth-field .form-control {
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid var(--auth-border);
            background: #f8fafc;
            padding: 0 16px;
            font-size: 0.9375rem;
            color: var(--auth-ink);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .auth-field .form-control::placeholder { color: #94a3b8; }

        .auth-field .form-control:focus {
            background: #fff;
            border-color: var(--auth-violet-mid);
            box-shadow: 0 0 0 4px rgba(74, 53, 128, 0.12);
            outline: none;
        }

        .auth-field .invalid-feedback { font-size: 0.8rem; margin-top: 5px; }

        .auth-password-wrap { position: relative; }

        .auth-password-wrap .form-control { padding-right: 48px; }

        .auth-password-toggle {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px 10px;
            line-height: 1;
            border-radius: 8px;
            transition: color 0.15s, background 0.15s;
        }

        .auth-password-toggle:hover {
            color: var(--auth-violet-mid);
            background: rgba(74, 53, 128, 0.06);
        }

        .auth-btn {
            display: block;
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--auth-violet) 0%, var(--auth-violet-mid) 60%, #5a4a8a 100%);
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 6px;
            box-shadow: 0 4px 14px rgba(45, 24, 72, 0.3);
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(45, 24, 72, 0.35);
            color: #fff;
        }

        .auth-btn:active { transform: translateY(0); }

        .auth-btn:focus { outline: none; box-shadow: 0 0 0 4px rgba(74, 53, 128, 0.25); }

        .auth-divider {
            height: 1px;
            background: var(--auth-border);
            margin: 0 0 24px;
        }

        .auth-links {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--auth-border);
            font-size: 0.8125rem;
        }

        .auth-links a {
            color: var(--auth-violet-mid);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s;
        }

        .auth-links a:hover { color: var(--auth-violet); text-decoration: underline; }

        .auth-links--split {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .auth-alert {
            border-radius: 12px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            border: none;
        }

        .auth-copyright {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            z-index: 20;
            margin: 0;
            padding: 0 16px;
            pointer-events: none;
        }

        .auth-copyright a {
            color: var(--auth-violet-mid);
            font-weight: 600;
            text-decoration: none;
            pointer-events: auto;
        }

        .auth-copyright a:hover { text-decoration: underline; }

        @media (max-width: 991px) {
            .auth-shell {
                display: flex;
                flex-direction: column;
            }

            .auth-brand {
                width: 100%;
                padding: 36px 28px 56px;
            }

            .auth-brand__headline { font-size: 1.6rem; }

            .auth-brand__features { display: none; }

            .auth-main {
                width: 100%;
                margin-left: 0;
                margin-top: -44px;
                padding: 0 20px 40px;
                align-items: center;
            }

            .auth-card {
                max-width: 460px;
                transform: none;
            }

            .auth-copyright {
                position: static;
                margin-top: 32px;
                padding-bottom: 24px;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="auth-page">
<div class="auth-shell">
    <aside class="auth-brand">
        <div class="auth-brand__grid"></div>
        <div class="auth-brand__orb auth-brand__orb--1"></div>
        <div class="auth-brand__orb auth-brand__orb--2"></div>
        <div class="auth-brand__inner">
            <div class="auth-brand__logo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteTitle }}">
                @else
                    <span class="auth-brand__logo-text">{{ $siteTitle }}</span>
                @endif
            </div>
            <div class="auth-brand__badge">
                <span class="auth-brand__badge-dot"></span>
                {{ __('Secure HR Platform') }}
            </div>
            <h1 class="auth-brand__headline">@yield('brand_headline', __('Human Resource Management'))</h1>
            <p class="auth-brand__tagline">@yield('brand_tagline', __('Manage your workforce, attendance, leave, and projects in one secure platform.'))</p>
            <ul class="auth-brand__features">
                <li><i class="fa fa-users"></i> {{ __('Employee & team management') }}</li>
                <li><i class="fa fa-clock-o"></i> {{ __('Attendance & timesheets') }}</li>
                <li><i class="fa fa-line-chart"></i> {{ __('Reports & analytics') }}</li>
            </ul>
        </div>
        <div class="auth-brand__footer">&copy; {{ date('Y') }} {{ $siteTitle }}</div>
    </aside>

    <main class="auth-main">
        <div class="auth-card @yield('card_class')">
            <div class="auth-card__body">
                @hasSection('card_eyebrow')
                    <div class="auth-card__eyebrow">@yield('card_eyebrow')</div>
                @endif
                @hasSection('card_title')
                    <h2 class="auth-card__title">@yield('card_title')</h2>
                @endif
                @hasSection('card_subtitle')
                    <p class="auth-card__subtitle">@yield('card_subtitle')</p>
                @endif

                @yield('content')
            </div>
        </div>
    </main>

    @if ($footerText)
        <div class="auth-copyright">
            {{ __('Developed by') }}
            <a href="{{ $footerLink }}" class="external" target="_blank" rel="noopener">{{ $footerText }}</a>
        </div>
    @endif
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
@stack('scripts')
</body>
</html>
