<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CRM Puerta Principal' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #eef3f9;
            --bg-accent: #f8fbff;
            --panel: rgba(255, 255, 255, 0.86);
            --panel-solid: #ffffff;
            --surface: #f7faff;
            --surface-strong: #edf4ff;
            --text: #142338;
            --muted: #617286;
            --border: rgba(148, 163, 184, 0.24);
            --border-strong: rgba(148, 163, 184, 0.38);
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-soft: rgba(37, 99, 235, 0.12);
            --success: #027a48;
            --success-soft: rgba(2, 122, 72, 0.1);
            --danger: #b42318;
            --danger-soft: rgba(180, 35, 24, 0.1);
            --warning: #8a4b00;
            --warning-soft: rgba(138, 75, 0, 0.1);
            --shadow-sm: 0 8px 20px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 18px 44px rgba(15, 23, 42, 0.08);
            --radius-xl: 24px;
            --radius-lg: 18px;
            --radius-md: 14px;
            --transition: 180ms ease;
        }

        html.theme-dark {
            color-scheme: dark;
            --bg: #070b12;
            --bg-accent: #0b1220;
            --panel: rgba(11, 18, 32, 0.78);
            --panel-solid: #0b1220;
            --surface: rgba(15, 23, 42, 0.62);
            --surface-strong: rgba(15, 23, 42, 0.82);
            --text: #e6edf7;
            --muted: rgba(226, 232, 240, 0.72);
            --border: rgba(148, 163, 184, 0.18);
            --border-strong: rgba(148, 163, 184, 0.28);
            --primary: #60a5fa;
            --primary-dark: #93c5fd;
            --primary-soft: rgba(96, 165, 250, 0.18);
            --success: #33c189;
            --success-soft: rgba(51, 193, 137, 0.14);
            --danger: #f97066;
            --danger-soft: rgba(249, 112, 102, 0.14);
            --warning: #fdb022;
            --warning-soft: rgba(253, 176, 34, 0.14);
            --shadow-sm: 0 10px 26px rgba(0, 0, 0, 0.42);
            --shadow-md: 0 22px 56px rgba(0, 0, 0, 0.58);
        }

        html.theme-dark body {
            background:
                radial-gradient(circle at top left, rgba(96, 165, 250, 0.16), transparent 34%),
                radial-gradient(circle at top right, rgba(56, 189, 248, 0.12), transparent 30%),
                linear-gradient(180deg, var(--bg-accent) 0%, var(--bg) 100%);
        }

        html.theme-dark body::before {
            background:
                linear-gradient(rgba(7, 11, 18, 0.3), rgba(7, 11, 18, 0.86)),
                radial-gradient(circle at center, rgba(96, 165, 250, 0.08), transparent 62%);
        }

        html.theme-dark .impersonation-banner {
            background: linear-gradient(135deg, rgba(253, 176, 34, 0.14), rgba(253, 176, 34, 0.06));
            border-bottom-color: rgba(253, 176, 34, 0.16);
            color: var(--warning);
        }

        html.theme-dark .app-header-inner {
            border-color: var(--border);
            background: rgba(11, 18, 32, 0.72);
        }

        html.theme-dark input,
        html.theme-dark select,
        html.theme-dark textarea {
            color: var(--text);
            background: rgba(15, 23, 42, 0.72);
            border-color: var(--border-strong);
        }

        html.theme-dark input:hover,
        html.theme-dark select:hover,
        html.theme-dark textarea:hover {
            background: rgba(15, 23, 42, 0.88);
        }

        html.theme-dark input:focus,
        html.theme-dark select:focus,
        html.theme-dark textarea:focus {
            box-shadow: 0 0 0 5px rgba(96, 165, 250, 0.16);
        }

        html.theme-dark .reminder-dropdown {
            border-color: var(--border);
            background: rgba(11, 18, 32, 0.96);
        }

        html.theme-dark .reminder-dropdown-item {
            background: rgba(15, 23, 42, 0.52);
            border-color: var(--border);
        }

        html.theme-dark .reminder-dropdown-item:hover {
            background: rgba(96, 165, 250, 0.08);
        }

        html.theme-dark .reminder-dropdown-item.overdue {
            border-color: rgba(249, 112, 102, 0.28);
            background: rgba(249, 112, 102, 0.12);
        }

        html.theme-dark .reminder-dropdown-empty {
            border-color: rgba(148, 163, 184, 0.24);
            color: rgba(226, 232, 240, 0.78);
        }

        html.theme-dark .card {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(11, 18, 32, 0.96));
            border-color: rgba(148, 163, 184, 0.22);
            box-shadow: var(--shadow-sm);
        }

        html.theme-dark .button {
            background: linear-gradient(135deg, var(--primary), #2563eb);
            box-shadow: 0 12px 24px rgba(96, 165, 250, 0.16);
        }

        html.theme-dark .button:hover {
            box-shadow: 0 18px 34px rgba(96, 165, 250, 0.22);
        }

        html.theme-dark .button-link,
        html.theme-dark .reminder-bell-button,
        html.theme-dark .theme-toggle {
            background: rgba(96, 165, 250, 0.12);
            border-color: rgba(148, 163, 184, 0.22);
            color: var(--primary-dark);
        }

        html.theme-dark .button-link:hover,
        html.theme-dark .reminder-bell[open] .reminder-bell-button,
        html.theme-dark .reminder-bell-button:hover,
        html.theme-dark .theme-toggle:hover {
            background: rgba(96, 165, 250, 0.16);
            border-color: rgba(148, 163, 184, 0.3);
        }

        html.theme-dark thead th {
            color: rgba(226, 232, 240, 0.78);
        }

        html.theme-dark tbody tr:hover {
            background: rgba(96, 165, 250, 0.06);
        }

        html.theme-dark .alert-danger {
            background: rgba(249, 112, 102, 0.12);
            border-color: rgba(249, 112, 102, 0.24);
            color: var(--danger);
        }

        html.theme-dark .alert-success {
            background: rgba(51, 193, 137, 0.12);
            border-color: rgba(51, 193, 137, 0.22);
            color: var(--success);
        }

        html.theme-dark ::placeholder {
            color: rgba(226, 232, 240, 0.42);
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
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 26%),
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.08), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
            color: var(--text);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                linear-gradient(rgba(255, 255, 255, 0.54), rgba(255, 255, 255, 0)),
                radial-gradient(circle at center, rgba(255, 255, 255, 0.22), transparent 64%);
            pointer-events: none;
            z-index: 0;
        }

        a {
            color: inherit;
        }

        form {
            margin: 0;
        }

        .app-shell {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }

        .impersonation-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #fff8ea, #fff4de);
            border-bottom: 1px solid rgba(242, 197, 124, 0.62);
            color: var(--warning);
            font-size: 14px;
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 18px 20px 0;
        }

        .app-header-inner {
            width: min(1260px, 100%);
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, 0.72);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(18px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            box-shadow: 0 0 0 8px rgba(37, 99, 235, 0.12);
            flex-shrink: 0;
        }

        .brand-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .meta {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            min-width: 0;
            flex: 1;
            flex-wrap: wrap;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .toolbar-group-main {
            flex: 1;
        }

        .toolbar-group-user {
            align-items: stretch;
        }

        .reminder-bell {
            position: relative;
        }

        .reminder-bell summary {
            list-style: none;
        }

        .reminder-bell summary::-webkit-details-marker {
            display: none;
        }

        .reminder-bell-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary-dark);
            cursor: pointer;
            transition: transform var(--transition), background var(--transition), border-color var(--transition);
        }

        .reminder-bell[open] .reminder-bell-button,
        .reminder-bell-button:hover {
            transform: translateY(-1px);
            background: rgba(37, 99, 235, 0.12);
            border-color: rgba(37, 99, 235, 0.18);
        }

        .reminder-bell-icon {
            position: relative;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .reminder-bell-icon::before {
            content: '';
            width: 12px;
            height: 11px;
            border: 2px solid currentColor;
            border-bottom: none;
            border-radius: 7px 7px 0 0;
            transform: translateY(-1px);
        }

        .reminder-bell-icon::after {
            content: '';
            position: absolute;
            left: 4px;
            right: 4px;
            bottom: 2px;
            height: 2px;
            background: currentColor;
            border-radius: 999px;
            box-shadow: 0 7px 0 -1px currentColor;
        }

        .reminder-bell-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
        }

        .theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            min-height: 42px;
            padding: 10px;
            border-radius: 999px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary-dark);
            cursor: pointer;
            transition: transform var(--transition), background var(--transition), border-color var(--transition);
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
            background: rgba(37, 99, 235, 0.12);
            border-color: rgba(37, 99, 235, 0.18);
        }

        .theme-toggle-icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle-icon svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .theme-toggle-icon-sun {
            display: none;
        }

        html.theme-dark .theme-toggle-icon-sun {
            display: inline-flex;
        }

        html.theme-dark .theme-toggle-icon-moon {
            display: none;
        }

        .reminder-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: min(420px, calc(100vw - 28px));
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.72);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(16px);
            display: grid;
            gap: 14px;
        }

        .reminder-dropdown-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .reminder-dropdown-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
        }

        .reminder-dropdown-list {
            display: grid;
            gap: 10px;
            max-height: 420px;
            overflow: auto;
            padding-right: 4px;
        }

        .reminder-dropdown-item {
            display: block;
            text-decoration: none;
            color: inherit;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: rgba(248, 251, 255, 0.94);
            transition: transform var(--transition), border-color var(--transition), background var(--transition);
        }

        .reminder-dropdown-item:hover {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, 0.2);
            background: #ffffff;
        }

        .reminder-dropdown-item.overdue {
            border-color: rgba(180, 35, 24, 0.18);
            background: rgba(180, 35, 24, 0.04);
        }

        .reminder-dropdown-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .reminder-dropdown-item-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
        }

        .reminder-dropdown-item-description {
            margin: 0 0 8px;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text);
        }

        .reminder-dropdown-item-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .reminder-dropdown-empty {
            padding: 20px 16px;
            border: 1px dashed var(--border-strong);
            border-radius: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .button,
        .button-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform var(--transition), background var(--transition), border-color var(--transition), color var(--transition), box-shadow var(--transition);
        }

        .button {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: #fff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(37, 99, 235, 0.24);
        }

        .button-link {
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-dark);
        }

        .button-link:hover {
            transform: translateY(-1px);
            background: rgba(37, 99, 235, 0.12);
            border-color: rgba(37, 99, 235, 0.18);
        }

        .button:disabled,
        .button-link:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .app-main {
            padding: 22px 20px 28px;
        }

        .app-container {
            width: min(1260px, 100%);
            margin: 0 auto;
        }

        .card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), var(--panel-solid));
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        h1,
        h2,
        h3 {
            color: var(--text);
            letter-spacing: -0.03em;
        }

        p {
            color: var(--muted);
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
            background: rgba(250, 252, 255, 0.96);
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
            border-color: rgba(37, 99, 235, 0.42);
            background: #ffffff;
            box-shadow: 0 0 0 5px var(--primary-soft);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            color: #304256;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        tbody tr {
            transition: background var(--transition);
        }

        tbody tr:hover {
            background: rgba(37, 99, 235, 0.03);
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
            border-color: rgba(2, 122, 72, 0.16);
            color: var(--success);
        }

        .surface {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .empty-state {
            display: grid;
            place-items: center;
            text-align: center;
            min-height: 180px;
            padding: 24px;
            color: var(--muted);
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.05;
        }

        .page-header p {
            margin: 0;
            font-size: 14px;
        }

        .page-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media (max-width: 960px) {
            .app-header-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .toolbar {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 720px) {
            .app-header,
            .app-main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .app-header-inner,
            .card {
                padding: 18px;
            }

            .impersonation-banner {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-header {
                flex-direction: column;
            }

            .page-header-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    @php
        $currentUser = auth()->user();
        $isImpersonating = session()->has('impersonator_id');
        $reminderInboxCount = $reminderInboxCount ?? 0;
        $reminderInboxItems = $reminderInboxItems ?? collect();
    @endphp

    <div class="app-shell">
        @if ($isImpersonating && $currentUser)
            <div class="impersonation-banner">
                <div>
                    Estas usando la cuenta de <strong>{{ $currentUser->name }}</strong>.
                    Sesion original: <strong>{{ session('impersonator_name') }}</strong>.
                </div>
                <form method="POST" action="{{ route('impersonation.leave') }}">
                    @csrf
                    <button class="button" type="submit">Salir de esta cuenta</button>
                </form>
            </div>
        @endif

        <header class="app-header">
            <div class="app-header-inner">
                <div class="brand">
                    <span class="brand-mark"></span>
                    <div>
                        <div class="brand-title">CRM Puerta Principal</div>
                        @if ($currentUser)
                            <div class="meta">{{ $currentUser->name }} · {{ $currentUser->role }}</div>
                        @endif
                    </div>
                </div>

                <div class="toolbar">
                    <div class="toolbar-group toolbar-group-main">
                        <a class="button-link" href="{{ route('leads.index') }}">Leads</a>
                        <a class="button-link" href="{{ route('leads.create') }}">Nuevo lead</a>
                        <a class="button-link" href="{{ route('leads.import.create') }}">Importar</a>
                        @if ($currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSupervisor()))
                            <a class="button-link" href="{{ route('metrics.index') }}">Metricas</a>
                        @endif
                        @if ($currentUser && $currentUser->role === \App\Models\User::ROLE_SUPER_ADMIN)
                            <a class="button-link" href="{{ route('users.index') }}">Usuarios</a>
                        @endif
                    </div>
                    <div class="toolbar-group toolbar-group-user">
                        @if ($currentUser)
                            <details class="reminder-bell">
                                <summary class="reminder-bell-button">
                                    <span class="reminder-bell-icon" aria-hidden="true"></span>
                                    <span>Recordatorios</span>
                                    <span class="reminder-bell-count">{{ $reminderInboxCount }}</span>
                                </summary>
                                <div class="reminder-dropdown">
                                    <div class="reminder-dropdown-header">
                                        <div>
                                            <div class="reminder-dropdown-title">Pendientes visibles</div>
                                            <div class="meta">
                                                Vencidos primero. Esta bandeja sigue las reglas actuales de visibilidad del CRM.
                                            </div>
                                        </div>
                                        <x-ui.badge>{{ $reminderInboxCount }} total</x-ui.badge>
                                    </div>

                                    @if ($reminderInboxItems->isEmpty())
                                        <div class="reminder-dropdown-empty">
                                            No hay recordatorios pendientes visibles por ahora.
                                        </div>
                                    @else
                                        <div class="reminder-dropdown-list">
                                            @foreach ($reminderInboxItems as $reminder)
                                                @php
                                                    $isOverdue = $reminder->scheduled_at !== null && $reminder->scheduled_at->isPast();
                                                    $lead = $reminder->lead;
                                                @endphp
                                                <a
                                                    class="reminder-dropdown-item{{ $isOverdue ? ' overdue' : '' }}"
                                                    href="{{ route('leads.show', $lead) }}"
                                                >
                                                    <div class="reminder-dropdown-item-header">
                                                        <span class="reminder-dropdown-item-title">{{ $lead?->name ?: 'Lead sin nombre' }}</span>
                                                        <x-ui.badge>{{ $isOverdue ? 'Vencido' : 'Pendiente' }}</x-ui.badge>
                                                    </div>
                                                    <p class="reminder-dropdown-item-description">{{ $reminder->description }}</p>
                                                    <div class="reminder-dropdown-item-meta">
                                                        <span class="meta">{{ $reminder->scheduled_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</span>
                                                        <span class="meta">Estado del lead: {{ $lead?->status ?: 'Sin estado' }}</span>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </details>
                            <button class="theme-toggle" type="button" id="theme-toggle" aria-label="Cambiar modo">
                                <span class="theme-toggle-icon theme-toggle-icon-moon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M21 13.2A8.6 8.6 0 0 1 10.8 3a6.8 6.8 0 1 0 10.2 10.2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="theme-toggle-icon theme-toggle-icon-sun" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 2.5v2.2M12 19.3v2.2M4.5 12h2.2M17.3 12h2.2M5.5 5.5l1.6 1.6M16.9 16.9l1.6 1.6M18.5 5.5l-1.6 1.6M7.1 16.9l-1.6 1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="button" type="submit">Salir</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main">
            <div class="app-container">
                {{ $slot }}
            </div>
        </main>
    </div>
    <script>
        (() => {
            const storageKey = 'crm-theme';
            const root = document.documentElement;
            const apply = (value) => {
                if (value === 'dark') {
                    root.classList.add('theme-dark');
                    return;
                }

                root.classList.remove('theme-dark');
            };

            try {
                const saved = localStorage.getItem(storageKey);

                if (saved === 'dark' || saved === 'light') {
                    apply(saved);
                }
            } catch (error) {}

            const button = document.getElementById('theme-toggle');

            if (!button) {
                return;
            }

            button.addEventListener('click', () => {
                const isDark = root.classList.toggle('theme-dark');

                try {
                    localStorage.setItem(storageKey, isDark ? 'dark' : 'light');
                } catch (error) {}
            });
        })();
    </script>
</body>
</html>
