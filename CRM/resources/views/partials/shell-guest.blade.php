@php
    $isLoginView = request()->routeIs('login', 'login.*');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CRM Puerta Principal' }}</title>
    <style>
        :root {
            color-scheme: dark;
            --brand-primary: #373e75;
            --brand-soft: #8fa1bf;
            --brand-fog: #a3b1c6;
            --brand-ink: #0b1e2d;
            --brand-cream: #faf9f6;
            --surface: rgba(250, 249, 246, 0.1);
            --surface-strong: rgba(250, 249, 246, 0.16);
            --panel: rgba(250, 249, 246, 0.9);
            --panel-strong: #faf9f6;
            --text: #132238;
            --muted: #607089;
            --border: rgba(143, 161, 191, 0.26);
            --border-strong: rgba(143, 161, 191, 0.45);
            --focus: rgba(55, 62, 117, 0.18);
            --success: #166534;
            --success-soft: rgba(22, 101, 52, 0.12);
            --danger: #b42318;
            --danger-soft: rgba(180, 35, 24, 0.12);
            --shadow-xl: 0 40px 100px rgba(3, 8, 15, 0.52);
            --shadow-lg: 0 18px 48px rgba(11, 30, 45, 0.18);
            --radius-xl: 32px;
            --radius-lg: 24px;
            --radius-md: 18px;
            --transition: 180ms ease;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--brand-cream);
            background:
                radial-gradient(circle at 18% 16%, rgba(143, 161, 191, 0.16), transparent 24%),
                radial-gradient(circle at 82% 10%, rgba(55, 62, 117, 0.28), transparent 30%),
                radial-gradient(circle at 50% 78%, rgba(143, 161, 191, 0.12), transparent 30%),
                linear-gradient(135deg, #08121d 0%, #10243b 42%, #17385b 100%);
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        body::before {
            background:
                linear-gradient(90deg, transparent 0 14%, rgba(143, 161, 191, 0.16) 15%, transparent 17%),
                linear-gradient(90deg, transparent 0 84%, rgba(143, 161, 191, 0.16) 85%, transparent 87%),
                linear-gradient(rgba(250, 249, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(250, 249, 246, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 56px 56px, 56px 56px;
            opacity: 0.62;
        }

        body::after {
            background:
                linear-gradient(90deg, transparent 0 12%, rgba(163, 177, 198, 0.08) 14%, transparent 16%, transparent 84%, rgba(163, 177, 198, 0.08) 86%, transparent 88%),
                radial-gradient(circle at center, transparent 0%, rgba(8, 19, 29, 0.1) 54%, rgba(8, 19, 29, 0.46) 100%);
        }

        ::selection {
            background: rgba(143, 161, 191, 0.35);
            color: var(--brand-cream);
        }

        .shell {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 22px;
        }

        .ambient {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .ambient-frame {
            position: absolute;
            border: 2px solid rgba(143, 161, 191, 0.18);
            border-radius: 32px;
            width: 230px;
            height: 78vh;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.62;
            box-shadow: inset 0 0 0 1px rgba(250, 249, 246, 0.04);
        }

        .ambient-frame.left {
            left: 5%;
        }

        .ambient-frame.right {
            right: 5%;
        }

        .ambient-frame::before {
            content: '';
            position: absolute;
            inset: 18px;
            border-radius: 24px;
            border: 1px solid rgba(163, 177, 198, 0.14);
        }

        .ambient-glow {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 180px;
            background: radial-gradient(circle at center, rgba(250, 249, 246, 0.12), transparent 68%);
            filter: blur(20px);
            opacity: 0.3;
        }

        .ambient-glow.left {
            left: 2%;
        }

        .ambient-glow.right {
            right: 2%;
        }

        .shell-panel {
            position: relative;
            z-index: 1;
            width: min(576px, 100%);
            padding: 34px 28px 28px;
            border-radius: 20px;
            background: linear-gradient(180deg, #fbfaf7 0%, #f5f3ee 100%);
            border: 1px solid rgba(12, 24, 40, 0.14);
            box-shadow:
                0 28px 80px rgba(6, 12, 20, 0.48),
                0 2px 0 rgba(255, 255, 255, 0.42) inset;
        }

        .content-area {
            color: var(--text);
        }

        .auth-panel {
            display: grid;
            gap: 18px;
        }

        .auth-panel-header {
            display: grid;
            gap: 12px;
            justify-items: start;
        }

        .auth-panel-copy {
            max-width: 56ch;
            margin-bottom: 0;
        }

        .auth-panel-card {
            display: grid;
            gap: 18px;
            padding: 24px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(143, 161, 191, 0.26);
            box-shadow: var(--shadow-lg);
        }

        .auth-form {
            display: grid;
            gap: 20px;
        }

        .auth-note {
            display: grid;
            gap: 8px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(55, 62, 117, 0.06);
            border: 1px solid rgba(55, 62, 117, 0.1);
        }

        .auth-note strong {
            font-size: 14px;
        }

        h1 {
            margin: 0 0 12px;
            color: var(--text);
            font-size: 34px;
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.65;
        }

        strong {
            color: var(--text);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 700;
            color: #1e2d42;
            letter-spacing: 0.01em;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 13px 15px;
            font-size: 14px;
            color: var(--text);
            background: rgba(255, 255, 255, 0.92);
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition), transform var(--transition);
        }

        input:hover,
        select:hover,
        textarea:hover {
            border-color: var(--border-strong);
            background: #ffffff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(55, 62, 117, 0.42);
            box-shadow: 0 0 0 5px var(--focus);
            background: #ffffff;
        }

        input[type="checkbox"] {
            accent-color: var(--brand-primary);
        }

        .checkbox-field {
            flex-direction: row;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(143, 161, 191, 0.18);
        }

        .checkbox-field input {
            width: auto;
            margin: 0;
            flex: 0 0 auto;
        }

        .checkbox-field label {
            margin: 0;
            font-weight: 600;
        }

        .field-error {
            color: var(--danger);
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 14px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 48px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--brand-primary), #2f355f);
            color: var(--brand-cream);
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 14px 28px rgba(55, 62, 117, 0.24);
            transition: transform var(--transition), box-shadow var(--transition), filter var(--transition);
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(55, 62, 117, 0.3);
            filter: brightness(1.02);
        }

        .button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(55, 62, 117, 0.08);
            border: 1px solid rgba(55, 62, 117, 0.12);
            color: var(--brand-primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .login-view {
            display: grid;
            gap: 28px;
            text-align: center;
        }

        .login-brand {
            display: grid;
            justify-items: center;
            gap: 18px;
            padding-top: 0;
        }

        .login-logo {
            display: block;
            width: min(250px, 100%);
        }

        .login-logo-svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .login-logo-svg svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .login-message {
            margin: 0;
            color: #161616;
            font-family: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
            font-size: clamp(20px, 3vw, 25px);
            font-weight: 400;
            line-height: 1.16;
            letter-spacing: -0.025em;
            text-wrap: pretty;
        }

        .login-form-panel {
            position: relative;
            padding: 28px 26px 18px;
            border-radius: 14px;
            border: 1px solid rgba(30, 45, 66, 0.17);
            background: rgba(255, 255, 255, 0.34);
            overflow: hidden;
            text-align: left;
        }

        .login-watermark {
            position: absolute;
            inset: 6px 34px 10px;
            pointer-events: none;
            opacity: 0.115;
        }

        .login-watermark-arch {
            position: absolute;
            left: 50%;
            top: 2px;
            width: 60%;
            height: 88%;
            transform: translateX(-50%);
            border: 16px solid rgba(55, 62, 117, 0.78);
            border-bottom-width: 0;
            border-radius: 220px 220px 0 0;
        }

        .login-watermark-arch::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 18px;
            width: 54%;
            height: 42%;
            transform: translateX(-50%);
            border-radius: 999px;
            background: rgba(163, 177, 198, 0.36);
        }

        .login-watermark-base {
            position: absolute;
            left: 50%;
            bottom: 30px;
            width: 50%;
            height: 18px;
            transform: translateX(-50%);
            background: rgba(55, 62, 117, 0.78);
            border-radius: 10px;
        }

        .login-watermark-base::before,
        .login-watermark-base::after {
            content: '';
            position: absolute;
            bottom: 6px;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: rgba(55, 62, 117, 0.78);
        }

        .login-watermark-base::before {
            left: -18px;
        }

        .login-watermark-base::after {
            right: -18px;
        }

        .login-form-content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 20px;
        }

        .login-field {
            display: grid;
            gap: 9px;
        }

        .login-field label {
            font-size: 15px;
            font-weight: 700;
            color: #121212;
        }

        .login-field input {
            border-radius: 11px;
            padding: 12px 14px;
            border: 2px solid rgba(18, 34, 60, 0.15);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
        }

        .login-field input:focus {
            border-color: rgba(55, 62, 117, 0.92);
            box-shadow: 0 0 0 4px rgba(55, 62, 117, 0.08);
        }

        .login-field-primary input {
            border-width: 3px;
            border-color: #222d5c;
        }

        .login-meta {
            margin: 0;
            color: #2d2d2d;
            font-size: 12px;
            line-height: 1.4;
            max-width: 38ch;
        }

        .login-remember {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2b2b2b;
            font-size: 14px;
        }

        .login-remember input {
            width: 21px;
            height: 21px;
            margin: 0;
            border-radius: 6px;
        }

        .login-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            border-radius: 11px;
            border: 1px solid #222d5c;
            background: linear-gradient(180deg, #3f4a8f, #28366f);
            color: #f7f5f0;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 4px 0 #182247, 0 8px 20px rgba(34, 45, 92, 0.26);
        }

        .login-submit:hover {
            filter: brightness(1.03);
        }

        .login-help {
            color: #353535;
            font-size: 13px;
            line-height: 1.4;
            text-align: center;
            margin-top: 2px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 18px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .alert-danger {
            background: #fff3f2;
            border-color: rgba(180, 35, 24, 0.16);
            color: var(--danger);
        }

        .alert-success {
            background: #effbf4;
            border-color: rgba(22, 101, 52, 0.16);
            color: var(--success);
        }

        .meta {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .list {
            margin: 18px 0 0;
            padding-left: 18px;
            color: var(--muted);
        }

        .list li {
            margin-bottom: 8px;
        }

        @media (max-width: 720px) {
            .shell {
                padding: 20px 14px;
            }

            .shell-panel {
                padding: 22px 18px 24px;
            }

            .ambient-frame {
                display: none;
            }

            .ambient-glow {
                width: 100px;
            }

            .login-form-panel {
                padding: 20px 16px 16px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .login-logo {
                width: min(210px, 100%);
            }

            .login-message {
                font-size: 22px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="ambient" aria-hidden="true">
            <div class="ambient-frame left"></div>
            <div class="ambient-frame right"></div>
            <div class="ambient-glow left"></div>
            <div class="ambient-glow right"></div>
        </div>

        <main class="shell-panel{{ $isLoginView ? ' shell-panel-login' : '' }}">
            <section class="content-area">
                {{ $slot }}
            </section>
        </main>
    </div>
</body>
</html>
