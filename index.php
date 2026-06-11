<?php

/**
 * Net Auditor IA — Interface principale
 * Agre Agency — Production Build
 */
define('NET_AUDITOR_ACCESS', true);
require_once 'config.php';
session_start();
$_isPremium  = (bool)($_SESSION['is_premium']  ?? false);
$_auditCount = (int) ($_SESSION['audit_count'] ?? 0);
$_username   = htmlspecialchars((string)($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8');
$_showModal  = ($_username === '');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#020617">
    <title>Net Auditor IA — Agre Agency</title>

    <!--
        Anti-FOUC : lit localStorage avant tout rendu pour éviter
        le flash de thème incorrect. Doit rester dans <head>.
    -->
    <script>
        (function() {
            var stored = localStorage.getItem('nai_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        };
    </script>

    <style>
        /* ── Reset & globals ─────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            overflow-x: hidden;
            width: 100%;
        }

        img,
        svg,
        pre,
        code,
        input,
        textarea {
            max-width: 100%;
        }

        /* ── Design tokens (light mode par défaut) ───────────────────── */
        :root {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --bg-subtle: #f1f5f9;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --accent-subtle: #e0f2fe;

            /* Severity — pastel technique */
            --sev-c-bg: #fff1f2;
            --sev-c-text: #9f1239;
            --sev-c-border: #fecdd3;
            --sev-c-accent: #e11d48;
            --sev-w-bg: #fffbeb;
            --sev-w-text: #92400e;
            --sev-w-border: #fde68a;
            --sev-w-accent: #b45309;
            --sev-i-bg: #f8fafc;
            --sev-i-text: #475569;
            --sev-i-border: #e2e8f0;
            --sev-i-accent: #64748b;

            /* Code blocks */
            --code-bg: #0f172a;
            --code-text: #38bdf8;
            --code-border: #1e293b;
            --config-bg: #fefce8;
            --config-text: #854d0e;
            --config-border: #fef08a;
        }

        .dark {
            --bg-page: #020617;
            --bg-card: #0f172a;
            --bg-card-hover: #1e293b;
            --bg-subtle: #1e293b;
            --border: #1e293b;
            --border-strong: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #475569;
            --accent: #38bdf8;
            --accent-hover: #7dd3fc;
            --accent-subtle: rgba(56, 189, 248, .1);

            --sev-c-bg: rgba(136, 19, 55, .25);
            --sev-c-text: #fda4af;
            --sev-c-border: rgba(159, 18, 57, .5);
            --sev-c-accent: #fb7185;
            --sev-w-bg: rgba(120, 53, 15, .25);
            --sev-w-text: #fcd34d;
            --sev-w-border: rgba(146, 64, 14, .5);
            --sev-w-accent: #fbbf24;
            --sev-i-bg: rgba(30, 41, 59, .8);
            --sev-i-text: #94a3b8;
            --sev-i-border: rgba(51, 65, 85, .7);
            --sev-i-accent: #64748b;

            --code-bg: #020617;
            --code-text: #38bdf8;
            --code-border: #1e293b;
            --config-bg: rgba(120, 53, 15, .15);
            --config-text: #fcd34d;
            --config-border: rgba(146, 64, 14, .4);
        }

        /* ── Composants réutilisables ────────────────────────────────── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: .75rem;
            transition: background-color .2s ease;
        }

        .card-hover:hover {
            background: var(--bg-card-hover);
        }

        /* Drop zone */
        .drop-zone {
            border: 2px dashed var(--border-strong);
            border-radius: .625rem;
            transition: border-color .15s ease, background .15s ease, transform .15s ease;
        }

        .drop-zone.is-dragover {
            border-color: var(--accent);
            border-style: solid;
            background: var(--accent-subtle);
            transform: scale(1.005);
        }

        .drop-zone.is-selected {
            border-color: #10b981;
            border-style: solid;
            background: rgba(16, 185, 129, .05);
        }

        .drop-zone.is-error {
            border-color: var(--sev-c-accent);
            border-style: solid;
            background: var(--sev-c-bg);
        }

        /* Severity badges */
        .sev-badge {
            display: inline-flex;
            align-items: center;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .2rem .55rem;
            border-radius: .3rem;
            border-width: 1px;
            border-style: solid;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sev-CRITICAL {
            background: var(--sev-c-bg);
            color: var(--sev-c-text);
            border-color: var(--sev-c-border);
        }

        .sev-WARNING {
            background: var(--sev-w-bg);
            color: var(--sev-w-text);
            border-color: var(--sev-w-border);
        }

        .sev-INFO {
            background: var(--sev-i-bg);
            color: var(--sev-i-text);
            border-color: var(--sev-i-border);
        }

        /* Text helpers */
        .field-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: .375rem;
        }

        .field-value {
            font-size: .875rem;
            line-height: 1.6;
            color: var(--text-primary);
        }

        /* Inline code / config line */
        .config-line {
            display: block;
            background: var(--config-bg);
            color: var(--config-text);
            border: 1px solid var(--config-border);
            border-radius: .3rem;
            padding: .45rem .75rem;
            font-family: ui-monospace, monospace;
            font-size: .75rem;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* Remediation block */
        .remediation-pre {
            display: block;
            background: var(--code-bg);
            color: var(--code-text);
            border: 1px solid var(--code-border);
            border-radius: .375rem;
            padding: .75rem 1rem;
            font-family: ui-monospace, monospace;
            font-size: .75rem;
            white-space: pre-wrap;
            word-break: break-all;
            overflow-x: auto;
        }

        /* Score progress bar */
        .score-bar-track {
            height: 5px;
            background: var(--bg-subtle);
            border-radius: 9999px;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
        }

        /* Spinner */
        .spinner {
            width: 32px;
            height: 32px;
            border: 2.5px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Fade-in cascade */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp .3s ease both;
        }

        /* Language & theme toggles */
        .lang-btn {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .05em;
            padding: .2rem .55rem;
            border-radius: .3rem;
            transition: all .15s ease;
            color: var(--text-secondary);
        }

        .lang-btn.active {
            background: var(--bg-card);
            color: var(--accent);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        }

        /* Button primary */
        .btn-primary {
            background: var(--accent);
            color: #fff;
            border-radius: .5rem;
            font-weight: 600;
            font-size: .875rem;
            transition: background .3s ease-out, transform .3s ease-out, box-shadow .3s ease-out;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--accent-hover);
            box-shadow: 0 4px 14px rgba(2, 132, 199, .3);
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        .dark .btn-primary:hover:not(:disabled) {
            box-shadow: 0 4px 14px rgba(56, 189, 248, .25);
        }

        /* Section divider */
        .section-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Drop zone received state */
        .drop-zone.is-received {
            border-color: var(--accent);
            border-style: solid;
            background: var(--accent-subtle);
        }

        /* ── Stat cards ────────────────────────────────────────────── */
        .stat-card {
            display: flex;
            align-items: center;
            padding: 1rem 1.125rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: .875rem;
            gap: 1rem;
            position: relative;
            overflow: hidden;
            transition: border-color .3s ease-out, box-shadow .3s ease-out;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(260px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                    rgba(6, 182, 212, .1),
                    transparent 70%);
            opacity: 0;
            transition: opacity .3s ease-out;
            pointer-events: none;
            z-index: 1;
        }

        .stat-card:hover {
            border-color: var(--border-strong);
            box-shadow: 0 4px 24px rgba(0, 0, 0, .12);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card>* {
            position: relative;
            z-index: 2;
        }

        .stat-img-wrap {
            width: 3.5rem;
            height: 3.5rem;
            flex-shrink: 0;
            border-radius: .625rem;
            overflow: hidden;
            background: var(--bg-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .stat-content {
            flex: 1;
            min-width: 0;
        }

        .stat-label {
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: .25rem;
        }

        .stat-number {
            font-size: 1.875rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-primary);
        }

        .stat-sub {
            font-size: .65rem;
            color: var(--text-secondary);
            margin-top: .25rem;
            line-height: 1.4;
        }

        /* ── Vuln rows (pleine largeur) ────────────────────────────── */
        .vuln-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .875rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-left: 3px solid transparent;
            border-radius: .625rem;
            cursor: pointer;
            transition: background .3s ease-out, border-color .3s ease-out, box-shadow .3s ease-out;
        }

        .vuln-row:hover {
            background: var(--bg-card-hover);
            border-color: rgba(6, 182, 212, .35);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
        }

        .vuln-row.is-active {
            background: var(--accent-subtle);
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent), 0 4px 16px rgba(56, 189, 248, .08);
        }

        .vuln-row-CRITICAL {
            border-left-color: var(--sev-c-accent);
        }

        .vuln-row-WARNING {
            border-left-color: var(--sev-w-accent);
        }

        .vuln-row-INFO {
            border-left-color: var(--sev-i-accent);
        }

        /* ── Drawers (detail + help) ───────────────────────────────── */
        #detail-overlay,
        #help-overlay {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(3px);
            opacity: 0;
            transition: opacity .25s ease;
            pointer-events: none;
        }

        #detail-overlay.is-open,
        #help-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        #detail-drawer,
        #help-drawer {
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            width: min(480px, 92vw);
            z-index: 50;
            background: rgba(9, 11, 21, .97);
            border-left: 1px solid #1e293b;
            box-shadow: -24px 0 64px rgba(0, 0, 0, .6);
            backdrop-filter: blur(16px);
            transform: translateX(100%);
            transition: transform .3s cubic-bezier(.4, 0, .2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #detail-drawer.is-open,
        #help-drawer.is-open {
            transform: translateX(0);
        }

        #drawer-body,
        #help-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .drawer-field-label {
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: .5rem;
        }

        .drawer-field-value {
            font-size: .875rem;
            line-height: 1.65;
            color: #cbd5e1;
        }

        .drawer-terminal {
            display: block;
            background: #020617;
            color: #38bdf8;
            border: 1px solid #1e293b;
            border-radius: .5rem;
            padding: .875rem 1rem;
            font-family: ui-monospace, 'Cascadia Code', monospace;
            font-size: .75rem;
            white-space: pre-wrap;
            word-break: break-all;
            overflow-x: auto;
            line-height: 1.6;
        }

        .drawer-config {
            display: block;
            background: rgba(120, 53, 15, .12);
            color: #fbbf24;
            border: 1px solid rgba(146, 64, 14, .35);
            border-radius: .5rem;
            padding: .625rem .875rem;
            font-family: ui-monospace, monospace;
            font-size: .75rem;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ── Mobile menu ───────────────────────────────────────────── */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height .28s cubic-bezier(.4, 0, .2, 1), opacity .2s ease;
            opacity: 0;
        }

        #mobile-menu.is-open {
            max-height: 400px;
            opacity: 1;
        }

        .mob-menu-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem .875rem;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 600;
            color: #cbd5e1;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
            width: 100%;
            text-align: left;
            text-decoration: none;
            background: transparent;
            border: none;
        }

        .mob-menu-item:hover {
            background: #1e293b;
            color: #f1f5f9;
        }

        .mob-menu-item svg {
            color: #38bdf8;
            flex-shrink: 0;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-page);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-strong);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        /* ── Background grid ─────────────────────────────────────── */
        #bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image:
                linear-gradient(to right, rgba(148, 163, 184, .055) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(148, 163, 184, .055) 1px, transparent 1px);
            background-size: 44px 44px;
            opacity: 0;
            transition: opacity .4s ease;
        }

        .dark #bg-grid {
            opacity: 1;
        }

        /* ── Glow orbs ───────────────────────────────────────────── */
        .glow-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            transition: opacity .5s ease;
        }

        .dark .glow-orb {
            opacity: 1;
        }

        #glow-cyan {
            width: 700px;
            height: 700px;
            top: -230px;
            left: -190px;
            background: radial-gradient(circle, rgba(6, 182, 212, .08) 0%, transparent 65%);
        }

        #glow-violet {
            width: 580px;
            height: 580px;
            top: 30%;
            right: -180px;
            background: radial-gradient(circle, rgba(124, 58, 237, .07) 0%, transparent 65%);
        }

        /* ── Drop zone dragover glow + float ─────────────────────── */
        @keyframes floatIcon {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .drop-zone.is-dragover {
            box-shadow: 0 0 28px rgba(34, 211, 238, .18), 0 0 0 1px var(--accent);
        }

        .drop-zone.is-dragover .dz-upload-icon {
            animation: floatIcon .7s ease-in-out infinite;
        }

        /* ── Modal username ──────────────────────────────────────── */
        #username-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            background: rgba(2, 6, 23, .88);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            transition: opacity .3s ease;
        }

        #username-overlay.is-visible {
            opacity: 1;
        }

        #username-modal {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 1rem;
            padding: 2rem 1.75rem 1.75rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 32px 80px rgba(0, 0, 0, .6), 0 0 0 1px rgba(56, 189, 248, .08);
            transform: translateY(18px);
            transition: transform .35s cubic-bezier(.4, 0, .2, 1);
        }

        #username-overlay.is-visible #username-modal {
            transform: translateY(0);
        }

        #username-input {
            width: 100%;
            background: #020617;
            border: 1px solid #334155;
            border-radius: .5rem;
            padding: .7rem .9rem;
            font-size: .875rem;
            color: #f1f5f9;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        #username-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .12);
        }

        /* ── Status panel terminal cursor ────────────────────────── */
        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        .terminal-cursor {
            display: inline-block;
            width: 7px;
            height: 12px;
            background: #22d3ee;
            border-radius: 1px;
            vertical-align: text-bottom;
            margin-left: 3px;
            animation: blink 1.1s step-end infinite;
        }

        /* ── Status panel stat-card override ─────────────────────── */
        .status-panel.stat-card {
            flex-direction: column;
            align-items: stretch;
            padding: 1.25rem;
            gap: 1.125rem;
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden w-full">

    <!-- Background décor (grid + glow — dark mode only) -->
    <div id="bg-grid" aria-hidden="true"></div>
    <div id="glow-cyan" class="glow-orb" aria-hidden="true"></div>
    <div id="glow-violet" class="glow-orb" aria-hidden="true"></div>

    <!-- ═══════════════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════════════════ -->
    <nav class="sticky top-0 z-50 border-b"
        style="background: var(--bg-card); border-color: var(--border);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center gap-3 overflow-hidden">

            <!-- Logo + brand -->
            <div class="flex items-center gap-2.5 flex-shrink-0 min-w-0">
                <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg"
                    style="background: var(--accent-subtle); border: 1px solid var(--border);">
                    <!-- Shield SVG -->
                    <svg viewBox="0 0 20 20" fill="none" stroke-width="1.5" class="w-4 h-4"
                        style="stroke: var(--accent);">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10 2L3 5v5c0 4.418 3.134 7.674 7 8 3.866-.326 7-3.582 7-8V5l-7-3z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 10l2 2 4-4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold leading-tight truncate" style="color: var(--text-primary);">
                        Net Auditor <span style="color: var(--accent);">IA</span>
                    </p>
                    <p class="text-xs leading-tight hidden sm:block" style="color: var(--text-muted);"
                        data-i18n="appSubtitle">Analyseur de Vulnérabilités Cisco</p>
                </div>
            </div>

            <div class="flex-1"></div>

            <!-- ── Desktop nav group (md+) ─────────────────────────────── -->
            <div class="hidden md:flex items-center gap-2 flex-shrink-0">

                <button onclick="openHelp()"
                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg
                           transition-colors duration-150 flex-shrink-0"
                    style="color: var(--text-secondary); border: 1px solid var(--border);
                           background: var(--bg-subtle); cursor: pointer;"
                    onmouseover="this.style.color='var(--accent)'; this.style.borderColor='var(--accent)';"
                    onmouseout="this.style.color='var(--text-secondary)'; this.style.borderColor='var(--border)';">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.061-1.061 3 3 0 112.871 5.026v.345a.75.75 0 01-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 108.94 6.94zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span data-i18n="helpNavBtn">Comment ça marche ?</span>
                </button>

                <a href="qui-suis-je.php"
                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg
                      transition-colors duration-150 flex-shrink-0"
                    style="color: var(--text-secondary); border: 1px solid var(--border); background: var(--bg-subtle);"
                    onmouseover="this.style.color='var(--accent)'; this.style.borderColor='var(--accent)';"
                    onmouseout="this.style.color='var(--text-secondary)'; this.style.borderColor='var(--border)';">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                        <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                    </svg>
                    <span data-i18n="navAbout">Qui suis-je ?</span>
                </a>

                <div class="flex items-center rounded-md p-0.5 gap-0.5"
                    style="background: var(--bg-subtle);">
                    <button id="btn-lang-fr" class="lang-btn" onclick="setLang('fr')">FR</button>
                    <button id="btn-lang-en" class="lang-btn" onclick="setLang('en')">EN</button>
                </div>
            </div>

            <!-- ── Theme toggle (toujours visible) ──────────────────────── -->
            <button onclick="toggleTheme()" aria-label="Changer de thème"
                class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg transition-colors duration-150"
                style="background: var(--bg-subtle); border: 1px solid var(--border);">
                <svg id="icon-moon" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 dark:hidden"
                    style="color: var(--text-secondary);">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                </svg>
                <svg id="icon-sun" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 hidden dark:block"
                    style="color: #fbbf24;">
                    <path fill-rule="evenodd"
                        d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.22 1.78a1 1 0 011.42 1.42l-.71.7a1 1 0 11-1.42-1.41l.71-.71zM18 9a1 1 0 110 2h-1a1 1 0 110-2h1zM5.05 13.536l-.71.71a1 1 0 01-1.42-1.42l.71-.7a1 1 0 011.42 1.41zM4 10a6 6 0 1112 0 6 6 0 01-12 0zm-2 0a1 1 0 110 2H1a1 1 0 110-2h1zm13.66 3.536a1 1 0 011.41 1.42l-.7.7a1 1 0 01-1.42-1.41l.71-.71zM11 17a1 1 0 11-2 0v-1a1 1 0 112 0v1zM6.34 5.05a1 1 0 01-1.41-1.42l.7-.7A1 1 0 017.05 4.34l-.71.71z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <!-- ── Burger (mobile seulement) ─────────────────────────────── -->
            <button id="burger-btn" onclick="toggleMobileMenu()"
                class="flex md:hidden items-center justify-center w-8 h-8 rounded-lg flex-shrink-0
                       transition-colors duration-150"
                style="background: var(--bg-subtle); border: 1px solid var(--border);"
                aria-label="Menu" aria-expanded="false">
                <svg id="icon-burger" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"
                    style="color: var(--text-secondary);">
                    <path fill-rule="evenodd"
                        d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <svg id="icon-burger-close" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 hidden"
                    style="color: var(--text-secondary);">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </nav>


    <!-- ═══════════════════════════════════════════════════════════════
     MENU MOBILE (burger)
════════════════════════════════════════════════════════════════ -->
    <div id="mobile-menu" class="md:hidden sticky top-14 z-40"
        style="background: rgba(9,11,21,.97); border-bottom: 1px solid #1e293b;
            backdrop-filter: blur(14px);">
        <div class="max-w-5xl mx-auto px-4 py-2.5 space-y-0.5">

            <!-- Comment ça marche -->
            <button onclick="openHelp(); closeMobileMenu();" class="mob-menu-item">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.061-1.061 3 3 0 112.871 5.026v.345a.75.75 0 01-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 108.94 6.94zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <span data-i18n="helpNavBtn">Comment ça marche ?</span>
            </button>

            <!-- Qui suis-je -->
            <a href="qui-suis-je.php" class="mob-menu-item" style="text-decoration:none;">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;">
                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                </svg>
                <span>Qui suis-je ?</span>
            </a>

            <!-- Séparateur + Langue -->
            <div style="height:1px;background:#1e293b;margin:.5rem 0;"></div>
            <div style="display:flex;align-items:center;gap:.875rem;padding:.5rem .875rem;">
                <span style="font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#475569;">
                    Langue
                </span>
                <div style="display:flex;align-items:center;background:#0f172a;border:1px solid #1e293b;
                        border-radius:.375rem;padding:.15rem;gap:.15rem;">
                    <button id="btn-lang-fr-m" class="lang-btn"
                        onclick="setLang('fr'); closeMobileMenu();">FR</button>
                    <button id="btn-lang-en-m" class="lang-btn"
                        onclick="setLang('en'); closeMobileMenu();">EN</button>
                </div>
            </div>
            <div style="height:.375rem;"></div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
     MAIN
════════════════════════════════════════════════════════════════ -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6 overflow-x-hidden w-full">

        <!-- Hero -->
        <div class="text-center space-y-1.5 py-2">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: var(--text-primary);"
                data-i18n="heroTitle">Audit de Configuration Cisco</h1>
            <p class="text-sm max-w-md mx-auto" style="color: var(--text-secondary);"
                data-i18n="heroSubtitle">
                Déposez un fichier de configuration IOS/IOS-XE. L'analyse détecte les vulnérabilités et génère les commandes de remédiation.
            </p>
        </div>

        <!-- ── Bi-column cockpit layout ───────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

            <!-- LEFT: Status & Live Monitor ─────────────────────────── -->
            <div class="lg:col-span-1">
                <div class="stat-card status-panel">

                    <!-- System status header -->
                    <div class="flex items-center justify-between w-full">
                        <p class="section-label" style="margin-bottom:0;" data-i18n="sysStatusLabel">STATUT DU SYSTÈME</p>
                        <div class="flex items-center gap-1.5">
                            <span class="relative flex" style="width:.625rem;height:.625rem;flex-shrink:0;">
                                <span class="animate-ping absolute inline-flex w-full h-full rounded-full"
                                    style="background:#4ade80;opacity:.75;"></span>
                                <span class="relative inline-flex rounded-full"
                                    style="width:.625rem;height:.625rem;background:#22c55e;"></span>
                            </span>
                            <span class="text-xs font-semibold" style="color:#22c55e;"
                                data-i18n="sysOnline">Moteur IA opérationnel</span>
                        </div>
                    </div>

                    <!-- Operator display -->
                    <div class="flex items-center justify-between w-full"
                        style="border-top:1px solid var(--border); padding-top:.875rem; margin-top:-.25rem;">
                        <p style="font-size:.56rem;letter-spacing:.1em;text-transform:uppercase;color:#475569;"
                            data-i18n="operatorLabel">EXPLOITANT</p>
                        <p id="operator-name" class="text-xs font-semibold font-mono truncate"
                            style="color:#38bdf8;max-width:65%;text-align:right;"><?= $_username ?: '—' ?></p>
                    </div>

                    <!-- Pre-flight terminal -->
                    <div class="w-full rounded-lg"
                        style="background:#020617; border:1px solid #1e293b; padding:.875rem 1rem;
                            font-family:ui-monospace,'Cascadia Code',monospace; font-size:.72rem; line-height:1.8;">
                        <p style="color:#334155; font-size:.58rem; letter-spacing:.1em; text-transform:uppercase; margin-bottom:.625rem;">
                            <span style="color:#22d3ee;">▶</span>&nbsp;VÉRIFICATIONS PRÉ-VOL
                        </p>
                        <p style="display:flex;justify-content:space-between;align-items:center;width:100%;"><span><span style="color:#334155;">01</span>&nbsp;<span style="color:#38bdf8;">CIS Cisco IOS Metrics</span></span><span style="color:#4ade80;white-space:nowrap;">✓ Ready</span></p>
                        <p style="display:flex;justify-content:space-between;align-items:center;width:100%;"><span><span style="color:#334155;">02</span>&nbsp;<span style="color:#38bdf8;">NIST SP 800-115 Rules</span></span><span style="color:#4ade80;white-space:nowrap;">✓ Ready</span></p>
                        <p style="display:flex;justify-content:space-between;align-items:center;width:100%;"><span><span style="color:#334155;">03</span>&nbsp;<span style="color:#38bdf8;">Remediations CLI Engine</span></span><span style="color:#4ade80;white-space:nowrap;">✓ Ready</span></p>
                        <p style="display:flex;justify-content:space-between;align-items:center;width:100%;"><span><span style="color:#334155;">04</span>&nbsp;<span style="color:#38bdf8;">Severity Scoring Matrix</span></span><span style="color:#4ade80;white-space:nowrap;">✓ Ready</span></p>
                        <p style="display:flex;justify-content:space-between;align-items:center;width:100%;"><span><span style="color:#334155;">05</span>&nbsp;<span style="color:#38bdf8;">JSON Report Builder</span></span><span style="color:#4ade80;white-space:nowrap;">✓ Ready</span></p>
                        <p style="margin-top:.5rem;">
                            <span style="color:#22d3ee;">$</span><span style="color:#64748b;"
                                data-i18n="sysAwaiting">&nbsp;En attente du fichier config...</span><span class="terminal-cursor"></span>
                        </p>
                    </div>

                    <!-- Engine info pills -->
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <div class="rounded-md text-center"
                            style="background:#0f172a; border:1px solid #1e293b; padding:.6rem .5rem;">
                            <p style="font-size:.56rem;letter-spacing:.08em;text-transform:uppercase;color:#475569;margin-bottom:.2rem;"
                                data-i18n="engineLabel">MOTEUR</p>
                            <p style="font-size:.78rem;font-weight:700;color:#38bdf8;font-family:ui-monospace,monospace;">Claude AI</p>
                        </div>
                        <div class="rounded-md text-center"
                            style="background:#0f172a; border:1px solid #1e293b; padding:.6rem .5rem;">
                            <p style="font-size:.56rem;letter-spacing:.08em;text-transform:uppercase;color:#475569;margin-bottom:.2rem;"
                                data-i18n="rulesLabel">RÈGLES</p>
                            <p style="font-size:.78rem;font-weight:700;color:#38bdf8;font-family:ui-monospace,monospace;">14 / CIS L2</p>
                        </div>
                    </div>

                    <!-- Compliance badges -->
                    <div class="w-full" style="border-top:1px solid var(--border); padding-top:1rem;">
                        <p style="font-size:.58rem;letter-spacing:.1em;text-transform:uppercase;color:#475569;margin-bottom:.5rem;"
                            data-i18n="complianceLabel">CONFORMITÉ</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="sev-badge" style="background:rgba(56,189,248,.08);color:#38bdf8;border-color:rgba(56,189,248,.25);font-size:.58rem;">CIS L1/L2</span>
                            <span class="sev-badge" style="background:rgba(56,189,248,.08);color:#38bdf8;border-color:rgba(56,189,248,.25);font-size:.58rem;">NIST 800-115</span>
                            <span class="sev-badge" style="background:rgba(56,189,248,.08);color:#38bdf8;border-color:rgba(56,189,248,.25);font-size:.58rem;">PCI-DSS</span>
                            <span class="sev-badge" style="background:rgba(56,189,248,.08);color:#38bdf8;border-color:rgba(56,189,248,.25);font-size:.58rem;">NSA Guide</span>
                        </div>
                    </div>

                    <!-- Trial counter & Premium unlock -->
                    <div class="w-full" style="border-top:1px solid var(--border); padding-top:1rem;">

                        <!-- Counter row -->
                        <div id="trial-counter" class="flex items-center justify-between">
                            <p style="font-size:.58rem;letter-spacing:.1em;text-transform:uppercase;color:#475569;"
                                data-i18n="trialLabel">ESSAIS GRATUITS</p>
                            <div class="flex items-center gap-2">
                                <div style="display:flex;gap:.3rem;align-items:center;">
                                    <span id="trial-dot-1" style="width:.55rem;height:.55rem;border-radius:50%;background:#1e293b;border:1px solid #334155;transition:background .3s ease;display:inline-block;"></span>
                                    <span id="trial-dot-2" style="width:.55rem;height:.55rem;border-radius:50%;background:#1e293b;border:1px solid #334155;transition:background .3s ease;display:inline-block;"></span>
                                </div>
                                <span id="trial-count-text" style="font-size:.7rem;font-weight:700;color:#64748b;font-family:ui-monospace,monospace;">0 / 2</span>
                            </div>
                        </div>

                        <!-- Premium activation form (shown when limit reached) -->
                        <div id="premium-form" class="hidden" style="margin-top:.875rem;">
                            <p id="trial-lock-msg" class="text-xs" style="color:#fbbf24;line-height:1.6;margin-bottom:.625rem;">
                                Limite atteinte. Activez la version Premium pour continuer.
                            </p>
                            <!-- LinkedIn contact button -->
                            <a id="trial-linkedin-btn"
                                href="https://www.linkedin.com/in/ange-kevin-agre-a03b3a386"
                                target="_blank" rel="noopener noreferrer"
                                style="display:flex;align-items:center;justify-content:center;gap:.5rem;
                                  width:100%;margin-bottom:.5rem;padding:.45rem .75rem;
                                  background:rgba(10,102,194,.15);border:1px solid rgba(10,102,194,.4);
                                  border-radius:.375rem;font-size:.7rem;font-weight:600;color:#60a5fa;
                                  text-decoration:none;transition:background .15s ease,border-color .15s ease;"
                                onmouseover="this.style.background='rgba(10,102,194,.25)';this.style.borderColor='rgba(10,102,194,.6)';"
                                onmouseout="this.style.background='rgba(10,102,194,.15)';this.style.borderColor='rgba(10,102,194,.4)';"
                                data-i18n="contactLinkedIn">Contacter sur LinkedIn</a>
                            <!-- Gmail contact button -->
                            <a id="trial-gmail-btn"
                                href="mailto:agrekevin09@gmail.com?subject=Demande de clé d'activation Net Auditor"
                                style="display:flex;align-items:center;justify-content:center;gap:.5rem;
                                  width:100%;margin-bottom:.625rem;padding:.45rem .75rem;
                                  background:rgba(234,67,53,.15);border:1px solid rgba(234,67,53,.4);
                                  border-radius:.375rem;font-size:.7rem;font-weight:600;color:#f87171;
                                  text-decoration:none;transition:background .15s ease,border-color .15s ease;"
                                onmouseover="this.style.background='rgba(234,67,53,.25)';this.style.borderColor='rgba(234,67,53,.6)';"
                                onmouseout="this.style.background='rgba(234,67,53,.15)';this.style.borderColor='rgba(234,67,53,.4)';">
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                </svg>
                                Contacter par Gmail
                            </a>
                            <div style="display:flex;gap:.4rem;align-items:center;">
                                <input id="premium-key-input" type="text"
                                    placeholder="Entrez votre clé Premium"
                                    autocomplete="off" spellcheck="false"
                                    style="flex:1;min-width:0;background:#0f172a;border:1px solid #334155;
                                          border-radius:.375rem;padding:.45rem .65rem;font-size:.7rem;
                                          color:#f1f5f9;outline:none;font-family:ui-monospace,monospace;
                                          transition:border-color .15s ease;"
                                    onfocus="this.style.borderColor='var(--accent)'"
                                    onblur="this.style.borderColor='#334155'"
                                    onkeydown="if(event.key==='Enter')activatePremium();" />
                                <button id="premium-activate-btn" onclick="activatePremium()"
                                    style="background:var(--accent);color:#fff;border:none;border-radius:.375rem;
                                           padding:.45rem .75rem;font-size:.7rem;font-weight:700;cursor:pointer;
                                           white-space:nowrap;transition:background .15s ease,opacity .15s ease;"
                                    onmouseover="this.style.background='var(--accent-hover)'"
                                    onmouseout="this.style.background='var(--accent)'"
                                    data-i18n="trialActivateBtn">Activer</button>
                            </div>
                            <p id="premium-error" class="hidden" style="font-size:.68rem;color:#f87171;margin-top:.4rem;"></p>
                        </div>

                        <!-- Premium active badge (shown after activation) -->
                        <div id="premium-badge" class="<?= $_isPremium ? '' : 'hidden' ?>"
                            style="display:<?= $_isPremium ? 'flex' : 'none' ?>;align-items:center;gap:.5rem;margin-top:.5rem;">
                            <svg viewBox="0 0 20 20" fill="currentColor"
                                style="width:.875rem;height:.875rem;color:#22c55e;flex-shrink:0;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-xs font-semibold" style="color:#22c55e;"
                                data-i18n="trialPremiumBadge">Version Premium activée</span>
                        </div>

                    </div>

                </div>
            </div><!-- /left col -->

            <!-- RIGHT: Dynamic Action Zone ────────────────────────────────── -->
            <div class="lg:col-span-2" id="dynamic-right-col">

                <!-- ── Section Upload (ÉTAPE 1) ─────────────────────────────────── -->
                <section id="upload-section" class="card p-5 sm:p-6">
                    <p class="section-label mb-5" data-i18n="uploadSectionLabel">Soumission du fichier</p>

                    <form id="uploadForm" action="process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="lang" id="langInput" value="fr">

                        <!-- Drop Zone -->
                        <div id="dropZone" class="drop-zone p-8 sm:p-12 text-center cursor-pointer mb-4"
                            role="button" tabindex="0" aria-label="Zone de dépôt de fichier">

                            <input type="file" id="fileInput" name="configFile" accept=".txt" class="hidden" required>

                            <!-- État : idle -->
                            <div id="dz-idle" class="space-y-3">
                                <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                                    style="background: var(--accent-subtle); border: 1px solid var(--border);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" class="w-6 h-6 dz-upload-icon"
                                        style="stroke: var(--accent);">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm" style="color: var(--text-primary);" data-i18n="dropText">
                                        Glissez-déposez votre fichier ici
                                    </p>
                                    <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                                        <span data-i18n="dropOr">ou</span>
                                        <span class="underline underline-offset-2 cursor-pointer"
                                            style="color: var(--accent);" data-i18n="dropBrowse">cliquez pour parcourir</span>
                                    </p>
                                </div>
                                <p class="text-xs" style="color: var(--text-muted);" data-i18n="dropHint">
                                    Format .txt — Configuration Cisco IOS/IOS-XE — 500 Ko max
                                </p>
                            </div>

                            <!-- État : dragover -->
                            <div id="dz-dragover" class="hidden space-y-2">
                                <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                                    style="border: 2px solid var(--accent); background: var(--accent-subtle);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" class="w-6 h-6"
                                        style="stroke: var(--accent);">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-sm" style="color: var(--accent);" data-i18n="dropRelease">
                                    Relâchez pour déposer
                                </p>
                            </div>

                            <!-- État : fichier sélectionné -->
                            <div id="dz-selected" class="hidden space-y-2">
                                <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                                    style="border: 2px solid #10b981; background: rgba(16,185,129,.08);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" class="w-6 h-6"
                                        stroke="#10b981">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <p id="dz-filename" class="font-semibold text-sm truncate max-w-xs mx-auto"
                                    style="color: #10b981;"></p>
                                <p id="dz-filesize" class="text-xs" style="color: var(--text-muted);"></p>
                                <p class="text-xs" style="color: var(--text-muted);" data-i18n="dropChange">
                                    Cliquez pour changer de fichier
                                </p>
                            </div>

                            <!-- État : erreur -->
                            <div id="dz-error" class="hidden space-y-2">
                                <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                                    style="border: 2px solid var(--sev-c-accent); background: var(--sev-c-bg);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" class="w-6 h-6"
                                        style="stroke: var(--sev-c-accent);">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <p id="dz-error-msg" class="font-semibold text-sm" style="color: var(--sev-c-accent);"></p>
                                <p class="text-xs" style="color: var(--text-muted);" data-i18n="dropRetry">
                                    Cliquez pour réessayer
                                </p>
                            </div>

                            <!-- État : received (auto-submit) -->
                            <div id="dz-received" class="hidden space-y-3">
                                <div class="spinner mx-auto"></div>
                                <p id="dz-received-msg" class="font-semibold text-sm" style="color: var(--accent);"></p>
                                <p id="dz-received-name" class="text-xs truncate max-w-xs mx-auto" style="color: var(--text-muted);"></p>
                                <button type="button" onclick="cancelAutoSubmit()"
                                    class="text-xs underline underline-offset-2" style="color: var(--text-muted);"
                                    data-i18n="dropCancel">Annuler</button>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" id="submitBtn" class="btn-primary w-full py-3 px-5" disabled>
                            <span data-i18n="btnAnalyze">Lancer l'analyse de sécurité</span>
                        </button>
                    </form>
                </section>

                <!-- ── Section Loading (ÉTAPE 2) ─────────────────────────────────── -->
                <section id="loading-section" class="hidden card p-8">
                    <div class="flex flex-col items-center gap-4 text-center">
                        <div class="spinner"></div>
                        <div>
                            <p id="loading-step" class="text-sm font-semibold" style="color: var(--accent);"></p>
                            <p class="text-xs mt-1" style="color: var(--text-muted);" data-i18n="loadingHint">
                                L'analyse approfondie peut prendre 20–45 secondes
                            </p>
                        </div>
                        <div class="score-bar-track w-full max-w-xs">
                            <div id="loading-bar" class="score-bar-fill" style="width:0%; background: var(--accent);"></div>
                        </div>
                    </div>
                </section>

                <!-- ── Section Results Summary (ÉTAPE 3) ─────────────────────────── -->
                <section id="results-summary-section" class="hidden space-y-5">
                    <!-- En-tête rapport -->
                    <div class="card p-5 fade-up" id="report-header" data-pdf-id="report-header">
                        <p class="section-label mb-4" data-i18n="reportTitle">Rapport d'audit de sécurité</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="min-w-0">
                                <p class="field-label" data-i18n="metaHost">Hôte</p>
                                <p id="meta-hostname" class="text-sm font-semibold font-mono truncate"
                                    style="color: var(--text-primary);"></p>
                            </div>
                            <div class="min-w-0">
                                <p class="field-label" data-i18n="metaFile">Fichier</p>
                                <p id="meta-filename" class="text-sm font-semibold font-mono truncate"
                                    style="color: var(--text-primary);"></p>
                            </div>
                            <div class="min-w-0">
                                <p class="field-label" data-i18n="metaDate">Date d'analyse</p>
                                <p id="meta-date" class="text-sm font-semibold font-mono"
                                    style="color: var(--text-primary);"></p>
                            </div>
                            <div>
                                <p class="field-label" data-i18n="metaFindings">Findings</p>
                                <p id="meta-total" class="text-sm font-semibold" style="color: var(--text-primary);"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Score + compteurs -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        <!-- Score -->
                        <div class="stat-card fade-up" id="score-card" data-pdf-id="score-card">
                            <div class="stat-img-wrap">
                                <img src="assets/img/img1.png" alt="" onerror="this.closest('.stat-img-wrap').style.background='#1e293b'">
                            </div>
                            <div class="stat-content">
                                <p class="stat-label" data-i18n="scoreTitle">Score de Sécurité</p>
                                <div class="flex items-baseline gap-1">
                                    <p id="score-value" class="stat-number">—</p>
                                    <span style="font-size:.75rem;color:#475569;">/100</span>
                                </div>
                                <div class="score-bar-track mt-2 mb-1.5">
                                    <div id="score-bar" class="score-bar-fill" style="width:0%;"></div>
                                </div>
                                <span id="score-label-badge" class="sev-badge" style="font-size:.6rem;"></span>
                            </div>
                        </div>

                        <!-- Critical -->
                        <div class="stat-card fade-up" data-pdf-id="score-card">
                            <div class="stat-img-wrap">
                                <img src="assets/img/img2.png" alt="" onerror="this.closest('.stat-img-wrap').style.background='#1e293b'">
                            </div>
                            <div class="stat-content">
                                <p class="stat-label" data-i18n="sevCritical">Critique</p>
                                <p id="count-critical" class="stat-number" style="color:var(--sev-c-text);">0</p>
                                <p class="stat-sub" data-i18n="sevCriticalSub">vulnérabilités critiques</p>
                            </div>
                        </div>

                        <!-- Warning -->
                        <div class="stat-card fade-up">
                            <div class="stat-img-wrap">
                                <img src="assets/img/img3.png" alt="" onerror="this.closest('.stat-img-wrap').style.background='#1e293b'">
                            </div>
                            <div class="stat-content">
                                <p class="stat-label" data-i18n="sevWarning">Avertissement</p>
                                <p id="count-warning" class="stat-number" style="color:var(--sev-w-text);">0</p>
                                <p class="stat-sub" data-i18n="sevWarningSub">avertissements</p>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="stat-card fade-up">
                            <div class="stat-img-wrap">
                                <img src="assets/img/img4.png" alt="" onerror="this.closest('.stat-img-wrap').style.background='#1e293b'">
                            </div>
                            <div class="stat-content">
                                <p class="stat-label" data-i18n="sevInfo">Information</p>
                                <p id="count-info" class="stat-number" style="color:var(--sev-i-text);">0</p>
                                <p class="stat-sub" data-i18n="sevInfoSub">points d'attention</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ── Section Liste des Vulnérabilités (ÉTAPE 3 suite) ─────────────── -->
                <section id="audit-results" class="hidden space-y-5" data-pdf-target="true">

                    <!-- Liste vulnérabilités -->
                    <div>
                        <p class="section-label mb-4">
                            <span data-i18n="vulnListTitle">Détail des vulnérabilités</span>
                            <span style="color: var(--text-muted); font-weight:400; text-transform:none; letter-spacing:0;"
                                data-i18n="vulnListSorted">(triées par sévérité)</span>
                        </p>
                        <div id="vuln-list" class="space-y-2.5" data-pdf-id="vuln-list"></div>
                    </div>

                    <!-- Bouton nouvelle analyse -->
                    <div class="flex justify-center pt-2">
                        <button id="newAnalysisBtn" class="btn-primary px-6 py-2.5 text-sm">
                            <span data-i18n="btnNewAnalysis">Nouvelle analyse</span>
                        </button>
                    </div>

                </section>

            </div><!-- /right col (DYNAMIC) -->
        </div><!-- /grid -->

        <!-- ── Section : Erreur globale ───────────────────────────────────── -->
        <section id="error-section" class="hidden card p-5"
            style="border-color: var(--sev-c-border);">
            <div class="flex items-start gap-3">
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5"
                    style="color: var(--sev-c-accent);">
                    <path fill-rule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd" />
                </svg>
                <div class="min-w-0">
                    <p class="font-semibold text-sm" style="color: var(--sev-c-text);" data-i18n="errorTitle">
                        Erreur d'analyse
                    </p>
                    <p id="error-message" class="text-sm mt-0.5" style="color: var(--text-secondary);"></p>
                    <p id="error-code" class="text-xs mt-0.5 font-mono" style="color: var(--text-muted);"></p>
                </div>
            </div>
        </section>
    </main>


    <!-- ═══════════════════════════════════════════════════════════════
     DETAIL DRAWER
════════════════════════════════════════════════════════════════ -->
    <div id="detail-overlay" onclick="closeDrawer()"></div>
    <div id="help-overlay" onclick="closeHelp()"></div>

    <aside id="detail-drawer" role="dialog" aria-modal="true" aria-label="Détails de la vulnérabilité">
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;
                padding:1.25rem 1.5rem;border-bottom:1px solid #1e293b;flex-shrink:0;">
            <div id="drawer-header-content" style="flex:1;min-width:0;"></div>
            <button onclick="closeDrawer()" aria-label="Fermer"
                style="flex-shrink:0;display:flex;align-items:center;gap:.4rem;
                       padding:.4rem .75rem;border-radius:.375rem;
                       background:#1e293b;border:1px solid #334155;
                       color:#94a3b8;font-size:.75rem;font-weight:600;
                       cursor:pointer;transition:all .15s ease;white-space:nowrap;"
                onmouseover="this.style.background='#334155';this.style.color='#f1f5f9';"
                onmouseout="this.style.background='#1e293b';this.style.color='#94a3b8';">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
                Fermer
            </button>
        </div>
        <!-- Scrollable body -->
        <div id="drawer-body"></div>
    </aside>

    <!-- HELP DRAWER -->
    <aside id="help-drawer" role="dialog" aria-modal="true" aria-label="Documentation">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;
                padding:1.25rem 1.5rem;border-bottom:1px solid #1e293b;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:.625rem;">
                <div style="width:2rem;height:2rem;border-radius:.5rem;background:rgba(56,189,248,.1);
                        border:1px solid rgba(56,189,248,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg viewBox="0 0 20 20" fill="#38bdf8" style="width:14px;height:14px;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.061-1.061 3 3 0 112.871 5.026v.345a.75.75 0 01-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 108.94 6.94zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 id="help-drawer-title" style="font-size:.9375rem;font-weight:700;color:#f1f5f9;margin:0;"
                    data-help-i18n="helpDrawerTitle">À propos de Net Auditor IA</h2>
            </div>
            <button onclick="closeHelp()" aria-label="Fermer"
                style="flex-shrink:0;display:flex;align-items:center;gap:.4rem;
                       padding:.4rem .75rem;border-radius:.375rem;
                       background:#1e293b;border:1px solid #334155;
                       color:#94a3b8;font-size:.75rem;font-weight:600;
                       cursor:pointer;transition:all .15s ease;white-space:nowrap;"
                onmouseover="this.style.background='#334155';this.style.color='#f1f5f9';"
                onmouseout="this.style.background='#1e293b';this.style.color='#94a3b8';">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
                Fermer
            </button>
        </div>
        <div id="help-body"></div>
    </aside>

    <!-- Footer -->
    <footer class="border-t mt-12 py-5" style="border-color: var(--border);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="text-xs" style="color: var(--text-muted);">&copy; <?= date('Y') ?> Agre Agency</p>
            <p class="text-xs font-mono" style="color: var(--border-strong);">Net Auditor IA v<?= APP_VERSION ?></p>
        </div>
    </footer>


    <!-- ═══════════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════════ -->
    <script>
        'use strict';

        /* ─── État Premium / Quota / Exploitant (injecté par PHP) ───────────────── */
        const TRIAL_LIMIT = 2;
        const LINKEDIN_URL = 'https://www.linkedin.com/in/ange-kevin-agre-a03b3a386';
        let isPremium = <?= json_encode($_isPremium)  ?>;
        let auditCount = <?= json_encode($_auditCount) ?>;
        let username = <?= json_encode($_username)   ?>;
        const showModal = <?= json_encode($_showModal)  ?>;
        let trialLocked = !isPremium && auditCount >= TRIAL_LIMIT;

        /* ─── Dictionnaire i18n ──────────────────────────────────────────────────── */
        const i18n = {
            fr: {
                appSubtitle: 'Analyseur de Vulnérabilités Cisco',
                heroTitle: 'Audit de Configuration Cisco',
                heroSubtitle: 'Déposez un fichier de configuration IOS/IOS-XE. L\'analyse détecte les vulnérabilités et génère les commandes de remédiation.',
                uploadSectionLabel: 'Soumission du fichier',
                dropText: 'Glissez-déposez votre fichier ici',
                dropOr: 'ou',
                dropBrowse: 'cliquez pour parcourir',
                dropHint: 'Format .txt — Configuration Cisco IOS/IOS-XE — 500 Ko max',
                dropRelease: 'Relâchez pour déposer',
                dropChange: 'Cliquez pour changer de fichier',
                dropRetry: 'Cliquez pour réessayer',
                dropCancel: 'Annuler',
                btnAnalyze: 'Lancer l\'analyse de sécurité',
                btnNewAnalysis: 'Nouvelle analyse',
                loadingHint: 'L\'analyse approfondie peut prendre 20–45 secondes',
                step1: '[ 1/3 ]  Envoi du fichier...',
                step2: '[ 2/3 ]  Analyse de la configuration...',
                step3: '[ 3/3 ]  Génération du rapport...',
                step4: 'Finalisation...',
                reportTitle: 'Rapport d\'audit de sécurité',
                metaHost: 'Hôte',
                metaFile: 'Fichier',
                metaDate: 'Date d\'analyse',
                metaFindings: 'Findings',
                scoreTitle: 'Score de Sécurité',
                sevCritical: 'Critique',
                sevCriticalSub: 'vulnérabilités critiques',
                sevWarning: 'Avertissement',
                sevWarningSub: 'avertissements',
                sevInfo: 'Information',
                sevInfoSub: 'points d\'attention',
                vulnListTitle: 'Détail des vulnérabilités',
                vulnListSorted: '(triées par sévérité)',
                fieldConfigLine: 'Ligne de configuration',
                fieldDescription: 'Description',
                fieldImpact: 'Impact',
                fieldRemediation: 'Commande(s) de remédiation Cisco CLI',
                noVulns: 'Aucune vulnérabilité détectée.',
                noVulnsSub: 'La configuration semble conforme aux bonnes pratiques.',
                vulnSelectHint: 'Sélectionnez une ligne pour afficher les détails',
                errorTitle: 'Erreur d\'analyse',
                scoreLabels: {
                    CRITICAL_RISK: 'Risque Critique',
                    HIGH_RISK: 'Risque Élevé',
                    MODERATE_RISK: 'Risque Modéré',
                    LOW_RISK: 'Risque Faible',
                    SECURE: 'Sécurisé',
                },
                sevBadges: {
                    CRITICAL: 'Critique',
                    WARNING: 'Avertissement',
                    INFO: 'Info'
                },
                profileTitle: 'Informaticien — Développement d\'Applications & Sécurité Réseaux',
                profileBio: 'Passionné par l\'architecture logicielle et la sécurisation des infrastructures réseau. J\'allie expertise technique en développement full-stack et audit de configuration pour concevoir des solutions robustes, fiables et hautement sécurisées.',
                profileLinkedIn: 'Profil LinkedIn',
                sysStatusLabel: 'STATUT DU SYSTÈME',
                sysOnline: 'Moteur IA opérationnel',
                sysAwaiting: ' En attente du fichier config...',
                engineLabel: 'MOTEUR',
                rulesLabel: 'RÈGLES',
                complianceLabel: 'CONFORMITÉ',
                operatorLabel: 'EXPLOITANT',
                trialLabel: 'ESSAIS GRATUITS',
                trialKeyPlaceholder: 'Entrez votre clé Premium',
                trialActivateBtn: 'Activer',
                trialKeyInvalid: 'Clé invalide. Réessayez.',
                trialPremiumBadge: 'Version Premium activée',
                contactLinkedIn: 'Contacter sur LinkedIn',
                modalTitle: 'Initialisation du Cockpit',
                modalSubtitle: 'Entrez votre nom ou votre entreprise pour personnaliser votre accès.',
                modalBtn: 'Initialiser le cockpit',
                navAbout: 'Qui suis-je ?',
                helpNavBtn: 'Comment ça marche ?',
                helpDrawerTitle: 'À propos de Net Auditor IA',
                helpWhat: 'C\'est quoi ?',
                helpWhatText: 'Un outil d\'audit intelligent qui analyse instantanément les fichiers de configuration des routeurs et commutateurs Cisco pour détecter les failles de sécurité avant qu\'elles ne soient exploitées.',
                helpHow: 'Comment ça marche ?',
                helpStep1Title: 'Déposez votre fichier',
                helpStep1Text: 'Glissez-déposez ou sélectionnez un fichier de configuration Cisco (.txt) dans la zone prévue.',
                helpStep2Title: 'L\'IA analyse',
                helpStep2Text: 'L\'IA d\'Agre Agency analyse chaque ligne et calcule un score de sécurité global sur 100.',
                helpStep3Title: 'Explorez les résultats',
                helpStep3Text: 'Parcourez la liste des failles détectées et appliquez les commandes de correction fournies dans le panneau de détails.',
                helpLegend: 'Légende des alertes',
                helpCritical: 'Critique',
                helpCriticalText: 'Vulnérabilités graves (ex : mots de passe visibles, protocoles non sécurisés). À corriger immédiatement.',
                helpWarning: 'Avertissement',
                helpWarningText: 'Défauts de configuration ou manque de restrictions d\'accès.',
                helpInfo: 'Information',
                helpInfoText: 'Bonnes pratiques et optimisations de configuration système.',
            },
            en: {
                appSubtitle: 'Cisco Vulnerability Analyzer',
                heroTitle: 'Cisco Configuration Audit',
                heroSubtitle: 'Drop an IOS/IOS-XE configuration file. The engine detects vulnerabilities and generates remediation commands.',
                uploadSectionLabel: 'File submission',
                dropText: 'Drag & drop your file here',
                dropOr: 'or',
                dropBrowse: 'click to browse',
                dropHint: '.txt format — Cisco IOS/IOS-XE Configuration — 500 KB max',
                dropRelease: 'Release to drop',
                dropChange: 'Click to change file',
                dropRetry: 'Click to retry',
                dropCancel: 'Cancel',
                btnAnalyze: 'Run security analysis',
                btnNewAnalysis: 'New analysis',
                loadingHint: 'Deep analysis may take 20–45 seconds',
                step1: '[ 1/3 ]  Sending file...',
                step2: '[ 2/3 ]  Analyzing configuration...',
                step3: '[ 3/3 ]  Generating report...',
                step4: 'Finalizing...',
                reportTitle: 'Security Audit Report',
                metaHost: 'Host',
                metaFile: 'File',
                metaDate: 'Analysis date',
                metaFindings: 'Findings',
                scoreTitle: 'Security Score',
                sevCritical: 'Critical',
                sevCriticalSub: 'critical vulnerabilities',
                sevWarning: 'Warning',
                sevWarningSub: 'warnings',
                sevInfo: 'Information',
                sevInfoSub: 'attention points',
                vulnListTitle: 'Vulnerability details',
                vulnListSorted: '(sorted by severity)',
                fieldConfigLine: 'Configuration line',
                fieldDescription: 'Description',
                fieldImpact: 'Impact',
                fieldRemediation: 'Cisco CLI remediation command(s)',
                noVulns: 'No vulnerabilities detected.',
                noVulnsSub: 'The configuration appears to comply with best practices.',
                vulnSelectHint: 'Select a row to view details',
                errorTitle: 'Analysis error',
                scoreLabels: {
                    CRITICAL_RISK: 'Critical Risk',
                    HIGH_RISK: 'High Risk',
                    MODERATE_RISK: 'Moderate Risk',
                    LOW_RISK: 'Low Risk',
                    SECURE: 'Secure',
                },
                sevBadges: {
                    CRITICAL: 'Critical',
                    WARNING: 'Warning',
                    INFO: 'Info'
                },
                profileTitle: 'Computer Scientist — Application Development & Network Security',
                profileBio: 'Passionate about software architecture and network infrastructure security. I combine technical expertise in full-stack development and configuration auditing to build robust, reliable, and highly secure solutions.',
                profileLinkedIn: 'LinkedIn Profile',
                sysStatusLabel: 'SYSTEM STATUS',
                sysOnline: 'AI Engine operational',
                sysAwaiting: ' Awaiting config file...',
                engineLabel: 'ENGINE',
                rulesLabel: 'RULES',
                complianceLabel: 'COMPLIANCE',
                operatorLabel: 'OPERATOR',
                trialLabel: 'FREE TRIALS',
                trialKeyPlaceholder: 'Enter your Premium key',
                trialActivateBtn: 'Activate',
                trialKeyInvalid: 'Invalid key. Please try again.',
                trialPremiumBadge: 'Premium version activated',
                contactLinkedIn: 'Contact on LinkedIn',
                modalTitle: 'Cockpit Initialization',
                modalSubtitle: 'Enter your name or company to personalize your access.',
                modalBtn: 'Initialize cockpit',
                navAbout: 'About me',
                helpNavBtn: 'How does it work?',
                helpDrawerTitle: 'About Net Auditor IA',
                helpWhat: 'What is it?',
                helpWhatText: 'An intelligent audit tool that instantly analyzes Cisco router and switch configuration files to detect security vulnerabilities before they can be exploited.',
                helpHow: 'How does it work?',
                helpStep1Title: 'Drop your file',
                helpStep1Text: 'Drag & drop or select a Cisco configuration file (.txt) in the upload area.',
                helpStep2Title: 'AI analysis',
                helpStep2Text: 'Agre Agency\'s AI analyzes every line and computes a global security score out of 100.',
                helpStep3Title: 'Explore the results',
                helpStep3Text: 'Browse the list of detected vulnerabilities and apply the ready-to-use remediation commands provided in the detail panel.',
                helpLegend: 'Alert legend',
                helpCritical: 'Critical',
                helpCriticalText: 'Severe vulnerabilities (e.g. visible passwords, insecure protocols). Fix immediately.',
                helpWarning: 'Warning',
                helpWarningText: 'Configuration flaws or missing access restrictions.',
                helpInfo: 'Information',
                helpInfoText: 'Best practices and system configuration optimizations.',
            },
        };


        /* ─── Module Langue ──────────────────────────────────────────────────────── */
        let currentLang = 'fr';

        function initLang() {
            currentLang = localStorage.getItem('nai_lang') || 'fr';
            applyLang(currentLang);
        }

        function setLang(lang) {
            currentLang = lang;
            localStorage.setItem('nai_lang', lang);
            applyLang(lang);
        }

        function applyLang(lang) {
            const t = i18n[lang] || i18n.fr;
            document.documentElement.lang = lang;
            document.getElementById('langInput').value = lang;

            // Traduction des éléments statiques via data-i18n
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (t[key] !== undefined) el.textContent = t[key];
            });

            // Boutons langue (desktop + mobile)
            ['btn-lang-fr', 'btn-lang-fr-m'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('active', lang === 'fr');
            });
            ['btn-lang-en', 'btn-lang-en-m'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('active', lang === 'en');
            });

            // Si le drawer d'aide est ouvert, mettre à jour son contenu
            if (document.getElementById('help-drawer').classList.contains('is-open')) {
                document.getElementById('help-drawer-title').textContent = t.helpDrawerTitle;
                document.getElementById('help-body').innerHTML = renderHelpContent(t);
            }

            // Si le drawer de détail est ouvert, mettre à jour les labels i18n
            if (_openVuln && document.getElementById('detail-drawer').classList.contains('is-open')) {
                _paintDrawer();
            }

            // Rafraîchit le panneau trial (textes dynamiques)
            updateTrialUI();
        }

        /* ─── Menu Burger Mobile ─────────────────────────────────────────────────── */
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const burger = document.getElementById('burger-btn');
            const isOpen = menu.classList.contains('is-open');
            if (isOpen) {
                closeMobileMenu();
            } else {
                menu.classList.add('is-open');
                document.getElementById('icon-burger').classList.add('hidden');
                document.getElementById('icon-burger-close').classList.remove('hidden');
                burger.setAttribute('aria-expanded', 'true');
            }
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (!menu.classList.contains('is-open')) return;
            menu.classList.remove('is-open');
            document.getElementById('icon-burger').classList.remove('hidden');
            document.getElementById('icon-burger-close').classList.add('hidden');
            document.getElementById('burger-btn').setAttribute('aria-expanded', 'false');
        }

        // Ferme le menu burger si clic en dehors
        document.addEventListener('click', e => {
            const menu = document.getElementById('mobile-menu');
            const burger = document.getElementById('burger-btn');
            if (menu.classList.contains('is-open') &&
                !menu.contains(e.target) &&
                !burger.contains(e.target)) {
                closeMobileMenu();
            }
        });

        /* ─── Module Thème ───────────────────────────────────────────────────────── */
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('nai_theme', isDark ? 'dark' : 'light');
        }


        /* ─── Modal Username ────────────────────────────────────────────────────── */
        function showUsernameModal() {
            const overlay = document.getElementById('username-overlay');
            overlay.style.display = 'flex';
            requestAnimationFrame(() => {
                requestAnimationFrame(() => overlay.classList.add('is-visible'));
            });

            // Placeholder aléatoire à chaque ouverture
            const examples = currentLang === 'en' ? ['Ex: Airbus, Security Dept.', 'Ex: Cisco Analyst', 'Ex: TechCorp', 'Ex: Guest User', 'Ex: Network Team'] : ['Ex : Airbus, Dept. Sécurité', 'Ex : TechCorp', 'Ex : Analyste Réseau', 'Ex : Équipe IT', 'Ex : Recruteur Tech'];
            const input = document.getElementById('username-input');
            input.placeholder = examples[Math.floor(Math.random() * examples.length)];

            setTimeout(() => input.focus(), 350);
        }

        function hideUsernameModal() {
            const overlay = document.getElementById('username-overlay');
            overlay.classList.remove('is-visible');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        }

        async function submitUsername() {
            const input = document.getElementById('username-input');
            const errEl = document.getElementById('modal-error');
            const name = input.value.trim();

            errEl.classList.add('hidden');
            if (!name) {
                input.focus();
                return;
            }

            try {
                const fd = new FormData();
                fd.append('username', name);
                const res = await fetch('setname.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    username = data.username;
                    document.getElementById('operator-name').textContent = username;
                    hideUsernameModal();
                    updateTrialUI();
                } else {
                    errEl.textContent = data.error || (currentLang === 'en' ? 'Invalid name.' : 'Nom invalide.');
                    errEl.classList.remove('hidden');
                    input.focus();
                }
            } catch (e) {
                errEl.textContent = currentLang === 'en' ? 'Network error.' : 'Erreur réseau.';
                errEl.classList.remove('hidden');
            }
        }

        /* ─── Premium / Trial ───────────────────────────────────────────────────── */
        function updateTrialUI() {
            const t = i18n[currentLang];
            const counterEl = document.getElementById('trial-counter');
            const formEl = document.getElementById('premium-form');
            const badgeEl = document.getElementById('premium-badge');
            const dot1 = document.getElementById('trial-dot-1');
            const dot2 = document.getElementById('trial-dot-2');
            const countTxt = document.getElementById('trial-count-text');

            if (isPremium) {
                trialLocked = false;
                counterEl.classList.add('hidden');
                formEl.classList.add('hidden');
                badgeEl.style.display = 'flex';
                unlockDropzone();
                return;
            }

            badgeEl.style.display = 'none';
            counterEl.classList.remove('hidden');

            // Dots: red when used, dark when free
            dot1.style.background = auditCount >= 1 ? '#f43f5e' : '#1e293b';
            dot2.style.background = auditCount >= 2 ? '#f43f5e' : '#1e293b';
            countTxt.textContent = auditCount + ' / ' + TRIAL_LIMIT;
            countTxt.style.color = auditCount >= TRIAL_LIMIT ? '#f43f5e' : '#64748b';

            if (auditCount >= TRIAL_LIMIT) {
                trialLocked = true;
                formEl.classList.remove('hidden');

                // Message personnalisé avec nom + bouton LinkedIn
                const lockMsg = document.getElementById('trial-lock-msg');
                if (lockMsg) {
                    const nameStr = username ?
                        (currentLang === 'en' ?
                            `for <strong style="color:#f1f5f9;">${esc(username)}</strong>` :
                            `pour <strong style="color:#f1f5f9;">${esc(username)}</strong>`) :
                        '';
                    const bodyFr = `Limite d'essais gratuits atteinte ${nameStr}. Pour débloquer votre accès illimité, veuillez contacter Agre Agency afin d'obtenir votre clé d'activation dédiée.`;
                    const bodyEn = `Free trial limit reached ${nameStr}. To unlock unlimited access, please contact Agre Agency to obtain your dedicated activation key.`;
                    lockMsg.innerHTML = (currentLang === 'en' ? bodyEn : bodyFr);
                }

                // Bouton LinkedIn
                const linkedInContainer = document.getElementById('trial-linkedin-btn');
                if (linkedInContainer) {
                    linkedInContainer.textContent = t.contactLinkedIn;
                }

                // Champ clé
                const activBtn = document.getElementById('premium-activate-btn');
                const keyInput = document.getElementById('premium-key-input');
                if (activBtn) activBtn.textContent = t.trialActivateBtn;
                if (keyInput) keyInput.placeholder = t.trialKeyPlaceholder;

                lockDropzone();
            } else {
                trialLocked = false;
                formEl.classList.add('hidden');
                unlockDropzone();
            }
        }

        function lockDropzone() {
            const dz = document.getElementById('dropZone');
            dz.style.pointerEvents = 'none';
            dz.style.opacity = '0.38';
            dz.style.cursor = 'not-allowed';
            document.getElementById('submitBtn').disabled = true;
        }

        function unlockDropzone() {
            const dz = document.getElementById('dropZone');
            dz.style.pointerEvents = '';
            dz.style.opacity = '';
            dz.style.cursor = '';
            // submitBtn re-enable only when a valid file is present (handled by processFile/cancelAutoSubmit)
        }

        async function activatePremium() {
            const t = i18n[currentLang];
            const input = document.getElementById('premium-key-input');
            const errEl = document.getElementById('premium-error');
            const btn = document.getElementById('premium-activate-btn');
            const key = input.value.trim();

            errEl.classList.add('hidden');
            if (!key) {
                input.focus();
                return;
            }

            btn.disabled = true;
            btn.style.opacity = '.55';
            btn.textContent = '...';

            try {
                const fd = new FormData();
                fd.append('key', key);
                const res = await fetch('activate.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    isPremium = true;
                    input.value = '';
                    updateTrialUI();
                } else {
                    errEl.textContent = t.trialKeyInvalid;
                    errEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.style.opacity = '';
                    btn.textContent = t.trialActivateBtn;
                    input.focus();
                    input.select();
                }
            } catch (e) {
                errEl.textContent = currentLang === 'en' ? 'Network error.' : 'Erreur réseau.';
                errEl.classList.remove('hidden');
                btn.disabled = false;
                btn.style.opacity = '';
                btn.textContent = t.trialActivateBtn;
            }
        }

        /* ─── Drop Zone ──────────────────────────────────────────────────────────── */
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const submitBtn = document.getElementById('submitBtn');

        function dzState(state, msg) {
            ['idle', 'dragover', 'selected', 'error', 'received'].forEach(s =>
                document.getElementById('dz-' + s).classList.add('hidden')
            );
            dropZone.classList.remove('is-dragover', 'is-selected', 'is-error', 'is-received');
            document.getElementById('dz-' + state).classList.remove('hidden');
            if (state !== 'idle') dropZone.classList.add('is-' + state);
            if (state === 'error' && msg) document.getElementById('dz-error-msg').textContent = msg;
        }

        dropZone.addEventListener('click', () => {
            if (autoSubmitTimer) {
                cancelAutoSubmit();
                return;
            }
            fileInput.click();
        });
        dropZone.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                if (autoSubmitTimer) cancelAutoSubmit();
                else fileInput.click();
            }
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            dropZone.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
            });
            document.body.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        dropZone.addEventListener('dragenter', () => dzState('dragover'));
        dropZone.addEventListener('dragover', () => dzState('dragover'));
        dropZone.addEventListener('dragleave', e => {
            if (!dropZone.contains(e.relatedTarget)) dzState('idle');
        });
        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer?.files;
            if (files?.length) processFile(files[0]);
            else dzState('idle');
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) processFile(fileInput.files[0]);
        });

        let autoSubmitTimer = null;
        let autoSubmitCountdown = 0;

        function processFile(file) {
            if (trialLocked) {
                lockDropzone();
                document.getElementById('premium-form').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }
            if (autoSubmitTimer) {
                clearInterval(autoSubmitTimer);
                autoSubmitTimer = null;
            }
            if (!file.name.toLowerCase().endsWith('.txt')) {
                dzState('error', currentLang === 'en' ?
                    'Invalid extension. Only .txt files are accepted.' :
                    'Extension invalide. Seuls les fichiers .txt sont acceptés.');
                submitBtn.disabled = true;
                return;
            }
            if (file.size > 500 * 1024) {
                dzState('error', currentLang === 'en' ?
                    `File too large: ${(file.size/1024).toFixed(1)} KB (limit: 500 KB).` :
                    `Fichier trop volumineux : ${(file.size/1024).toFixed(1)} Ko (limite : 500 Ko).`);
                submitBtn.disabled = true;
                return;
            }
            if (file !== fileInput.files[0]) {
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            }
            document.getElementById('dz-filename').textContent = file.name;
            document.getElementById('dz-filesize').textContent = fmtBytes(file.size);

            // Auto-submit countdown
            autoSubmitCountdown = 2;
            const msgEl = document.getElementById('dz-received-msg');
            const nameEl = document.getElementById('dz-received-name');
            nameEl.textContent = file.name + ' — ' + fmtBytes(file.size);
            const updateMsg = () => {
                msgEl.textContent = currentLang === 'en' ?
                    `File received — launching in ${autoSubmitCountdown}s…` :
                    `Fichier reçu — lancement dans ${autoSubmitCountdown}s…`;
            };
            updateMsg();
            dzState('received');
            submitBtn.disabled = true;

            autoSubmitTimer = setInterval(() => {
                autoSubmitCountdown--;
                if (autoSubmitCountdown <= 0) {
                    clearInterval(autoSubmitTimer);
                    autoSubmitTimer = null;
                    document.getElementById('uploadForm').requestSubmit();
                } else {
                    updateMsg();
                }
            }, 1000);
        }

        function cancelAutoSubmit() {
            if (autoSubmitTimer) {
                clearInterval(autoSubmitTimer);
                autoSubmitTimer = null;
            }
            dzState('selected');
            submitBtn.disabled = trialLocked;
        }

        function fmtBytes(b) {
            if (b < 1024) return b + ' o';
            if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' Ko';
            return (b / (1024 * 1024)).toFixed(2) + ' Mo';
        }


        /* ─── Soumission AJAX ────────────────────────────────────────────────────── */
        document.getElementById('uploadForm').addEventListener('submit', async e => {
            e.preventDefault();
            const t = i18n[currentLang];

            document.getElementById('error-section').classList.add('hidden');
            document.getElementById('audit-results').classList.add('hidden');
            document.getElementById('upload-section').classList.add('hidden');
            document.getElementById('loading-section').classList.remove('hidden');

            // Barre de progression simulée
            const bar = document.getElementById('loading-bar');
            const stepEl = document.getElementById('loading-step');
            const steps = [{
                    pct: 15,
                    msg: t.step1
                },
                {
                    pct: 50,
                    msg: t.step2
                },
                {
                    pct: 80,
                    msg: t.step3
                },
                {
                    pct: 93,
                    msg: t.step4
                },
            ];
            let idx = 0;
            bar.style.width = steps[0].pct + '%';
            stepEl.textContent = steps[0].msg;
            const timer = setInterval(() => {
                idx = Math.min(idx + 1, steps.length - 1);
                bar.style.width = steps[idx].pct + '%';
                stepEl.textContent = steps[idx].msg;
            }, 9000);

            try {
                const resp = await fetch('process.php', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await resp.json();
                clearInterval(timer);
                bar.style.width = '100%';
                await sleep(250);
                document.getElementById('loading-section').classList.add('hidden');

                if (data.error) {
                    if (data.code === 'TRIAL_LIMIT') {
                        auditCount = TRIAL_LIMIT;
                        updateTrialUI();
                    }
                    showError(data.error, data.code || '');
                    document.getElementById('upload-section').classList.remove('hidden');
                    return;
                }
                // Synchronise le compteur côté client avec l'état serveur
                if (typeof data.trial_count === 'number') {
                    auditCount = data.trial_count;
                    isPremium = !!data.is_premium;
                }
                updateTrialUI();
                renderResults(data);
            } catch (err) {
                clearInterval(timer);
                document.getElementById('loading-section').classList.add('hidden');
                showError(currentLang === 'en' ?
                    'Network error: ' + err.message :
                    'Erreur réseau : ' + err.message, 'NETWORK_ERROR');
                document.getElementById('upload-section').classList.remove('hidden');
            }
        });

        function showError(msg, code) {
            const t = i18n[currentLang];
            document.getElementById('error-message').textContent = msg;
            document.getElementById('error-code').textContent = code ? (currentLang === 'en' ? 'Code: ' : 'Code : ') + code : '';
            const sec = document.getElementById('error-section');
            sec.classList.remove('hidden');
            sec.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function sleep(ms) {
            return new Promise(r => setTimeout(r, ms));
        }


        /* ─── Rendu des résultats ────────────────────────────────────────────────── */
        function renderResults(data) {
            const t = i18n[currentLang];
            const audit = data.audit;
            if (!audit) {
                showError('Données d\'audit absentes.', 'MISSING_DATA');
                return;
            }

            const meta = audit.scan_metadata || {};
            const vulns = audit.vulnerabilities || [];
            const score = Number(audit.security_score) || 0;
            const label = audit.score_label || 'CRITICAL_RISK';

            // Métadonnées
            document.getElementById('meta-hostname').textContent = meta.hostname || 'Unknown';
            document.getElementById('meta-filename').textContent = data.filename || meta.file_analyzed || '—';
            document.getElementById('meta-date').textContent = meta.scan_date || '—';
            document.getElementById('meta-total').textContent = (meta.total_findings ?? vulns.length) + ' finding(s)';

            // Score
            const scoreColors = {
                CRITICAL_RISK: 'var(--sev-c-accent)',
                HIGH_RISK: 'var(--sev-w-accent)',
                MODERATE_RISK: '#eab308',
                LOW_RISK: 'var(--accent)',
                SECURE: '#10b981',
            };
            const color = scoreColors[label] || 'var(--text-secondary)';
            const scoreValEl = document.getElementById('score-value');
            scoreValEl.textContent = score;
            scoreValEl.style.color = color;
            const scoreBar = document.getElementById('score-bar');
            scoreBar.style.background = color;
            setTimeout(() => {
                scoreBar.style.width = score + '%';
            }, 80);

            const badge = document.getElementById('score-label-badge');
            badge.textContent = t.scoreLabels[label] || label;
            // Choix de la classe badge selon la catégorie de score
            badge.className = 'sev-badge ' + (
                label === 'SECURE' ? 'sev-INFO' :
                label === 'LOW_RISK' ? 'sev-INFO' :
                label === 'MODERATE_RISK' ? 'sev-WARNING' : 'sev-CRITICAL'
            );

            // Compteurs
            document.getElementById('count-critical').textContent = meta.critical_count ?? vulns.filter(v => v.severity === 'CRITICAL').length;
            document.getElementById('count-warning').textContent = meta.warning_count ?? vulns.filter(v => v.severity === 'WARNING').length;
            document.getElementById('count-info').textContent = meta.info_count ?? vulns.filter(v => v.severity === 'INFO').length;

            // Cartes vulnérabilités (triées CRITICAL > WARNING > INFO)
            const order = {
                CRITICAL: 0,
                WARNING: 1,
                INFO: 2
            };
            const sorted = [...vulns].sort((a, b) =>
                (order[a.severity] ?? 9) - (order[b.severity] ?? 9)
            );

            const list = document.getElementById('vuln-list');
            list.innerHTML = '';

            if (!sorted.length) {
                list.innerHTML = `
            <div class="card p-6 text-center" style="border-color: #10b981;">
                <p class="font-semibold text-sm" style="color: #10b981;">${esc(t.noVulns)}</p>
                <p class="text-xs mt-1" style="color: var(--text-muted);">${esc(t.noVulnsSub)}</p>
            </div>`;
            } else {
                buildVulnLayout(sorted, t, list);
            }

            // Sauvegarde pour résistance au refresh
            try {
                sessionStorage.setItem('nai_audit', JSON.stringify(data));
            } catch (e) {}

            // Affichage
            document.getElementById('results-summary-section').classList.remove('hidden');
            document.getElementById('audit-results').classList.remove('hidden');
            document.getElementById('audit-results').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        /* ─── Help Drawer ────────────────────────────────────────────────────────── */
        function renderHelpContent(t) {
            const steps = [{
                    title: t.helpStep1Title,
                    text: t.helpStep1Text
                },
                {
                    title: t.helpStep2Title,
                    text: t.helpStep2Text
                },
                {
                    title: t.helpStep3Title,
                    text: t.helpStep3Text
                },
            ];
            const stepsHTML = steps.map((s, i) => `
        <div style="display:flex;gap:.875rem;align-items:flex-start;">
            <div style="width:1.625rem;height:1.625rem;border-radius:50%;background:#0284c7;
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;font-size:.65rem;font-weight:800;color:#fff;margin-top:.1rem;">${i + 1}</div>
            <div>
                <p style="font-size:.8125rem;font-weight:700;color:#f1f5f9;margin:0 0 .2rem;">${esc(s.title)}</p>
                <p style="font-size:.8125rem;line-height:1.65;color:#94a3b8;margin:0;">${esc(s.text)}</p>
            </div>
        </div>`).join('');

            const legends = [{
                    color: '#e11d48',
                    bg: 'rgba(136,19,55,.15)',
                    border: 'rgba(159,18,57,.3)',
                    label: t.helpCritical,
                    text: t.helpCriticalText,
                    labelColor: '#fda4af'
                },
                {
                    color: '#f59e0b',
                    bg: 'rgba(120,53,15,.15)',
                    border: 'rgba(146,64,14,.3)',
                    label: t.helpWarning,
                    text: t.helpWarningText,
                    labelColor: '#fcd34d'
                },
                {
                    color: '#475569',
                    bg: 'rgba(30,41,59,.45)',
                    border: '#1e293b',
                    label: t.helpInfo,
                    text: t.helpInfoText,
                    labelColor: '#94a3b8'
                },
            ];
            const legendHTML = legends.map(l => `
        <div style="padding:.75rem 1rem;background:${l.bg};
                    border:1px solid ${l.border};border-left:3px solid ${l.color};
                    border-radius:.5rem;">
            <p style="font-size:.75rem;font-weight:700;color:${l.labelColor};margin:0 0 .25rem;">${esc(l.label)}</p>
            <p style="font-size:.75rem;line-height:1.55;color:#64748b;margin:0;">${esc(l.text)}</p>
        </div>`).join('');

            return `<div style="display:flex;flex-direction:column;gap:2rem;">
        <div style="padding:.875rem 1rem;background:rgba(56,189,248,.06);
                    border:1px solid rgba(56,189,248,.15);border-radius:.625rem;">
            <p style="font-size:.6rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;
                      color:#38bdf8;margin:0 0 .5rem;">${esc(t.helpWhat)}</p>
            <p style="font-size:.875rem;line-height:1.7;color:#cbd5e1;margin:0;">${esc(t.helpWhatText)}</p>
        </div>
        <div>
            <p style="font-size:.6rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;
                      color:#38bdf8;margin:0 0 1rem;">${esc(t.helpHow)}</p>
            <div style="display:flex;flex-direction:column;gap:.875rem;">${stepsHTML}</div>
        </div>
        <div>
            <p style="font-size:.6rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;
                      color:#38bdf8;margin:0 0 .875rem;">${esc(t.helpLegend)}</p>
            <div style="display:flex;flex-direction:column;gap:.5rem;">${legendHTML}</div>
        </div>
    </div>`;
        }

        function openHelp() {
            closeDrawer(); // ferme le détail si ouvert
            closeMobileMenu(); // ferme le burger si ouvert
            const t = i18n[currentLang];
            document.getElementById('help-drawer-title').textContent = t.helpDrawerTitle;
            document.getElementById('help-body').innerHTML = renderHelpContent(t);
            document.getElementById('help-overlay').classList.add('is-open');
            document.getElementById('help-drawer').classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeHelp() {
            document.getElementById('help-overlay').classList.remove('is-open');
            document.getElementById('help-drawer').classList.remove('is-open');
            document.body.style.overflow = '';
        }

        /* ─── Detail Drawer — état courant ──────────────────────────────────────── */
        let _openVuln = null;
        let _openSev = null;

        function _paintDrawer() {
            const t = i18n[currentLang];
            const vuln = _openVuln;
            const sev = _openSev;
            const badgeLabel = (t.sevBadges || {})[sev] || sev;
            const id = esc(vuln.vuln_id || '');

            document.getElementById('drawer-header-content').innerHTML = `
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem;flex-wrap:wrap;">
            ${id ? `<code style="font-size:.65rem;color:#64748b;font-family:ui-monospace,monospace;">${id}</code>` : ''}
            <span class="sev-badge sev-${sev}" style="font-size:.6rem;">${esc(badgeLabel)}</span>
        </div>
        <h2 style="font-size:.9375rem;font-weight:700;color:#f1f5f9;line-height:1.4;margin:0;">${esc(vuln.title || 'Vulnérabilité')}</h2>`;

            let body = '<div style="display:flex;flex-direction:column;gap:1.5rem;">';
            if (vuln.affected_line) body += `
        <div>
            <p class="drawer-field-label">${esc(t.fieldConfigLine)}</p>
            <code class="drawer-config">${esc(vuln.affected_line)}</code>
        </div>`;
            if (vuln.description) body += `
        <div>
            <p class="drawer-field-label">${esc(t.fieldDescription)}</p>
            <p class="drawer-field-value">${esc(vuln.description)}</p>
        </div>`;
            if (vuln.impact) body += `
        <div>
            <p class="drawer-field-label">${esc(t.fieldImpact)}</p>
            <p class="drawer-field-value">${esc(vuln.impact)}</p>
        </div>`;
            if (vuln.remediation) body += `
        <div>
            <p class="drawer-field-label">${esc(t.fieldRemediation)}</p>
            <pre class="drawer-terminal">${esc(vuln.remediation)}</pre>
        </div>`;
            body += '</div>';
            document.getElementById('drawer-body').innerHTML = body;
        }

        function openDrawer(vuln, validSev) {
            closeHelp();
            _openVuln = vuln;
            _openSev = validSev;
            _paintDrawer();
            document.getElementById('detail-overlay').classList.add('is-open');
            document.getElementById('detail-drawer').classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            document.getElementById('detail-overlay').classList.remove('is-open');
            document.getElementById('detail-drawer').classList.remove('is-open');
            document.body.style.overflow = '';
            document.querySelectorAll('.vuln-row').forEach(r => r.classList.remove('is-active'));
            _openVuln = null;
            _openSev = null;
        }

        // Escape closes whichever drawer is open
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeDrawer();
                closeHelp();
            }
        });

        /* ─── Layout liste des vulnérabilités (pleine largeur) ───────────────────── */
        function buildVulnLayout(vulns, t, container) {
            const list = document.createElement('div');
            list.style.cssText = 'display:flex;flex-direction:column;gap:.5rem;';

            vulns.forEach((vuln, i) => {
                const sev = (vuln.severity || 'INFO').toUpperCase();
                const validSev = ['CRITICAL', 'WARNING', 'INFO'].includes(sev) ? sev : 'INFO';
                const badgeLabel = (t.sevBadges || {})[validSev] || validSev;
                const id = esc(vuln.vuln_id || 'VULN-' + String(i + 1).padStart(3, '0'));

                const row = document.createElement('div');
                row.className = `vuln-row vuln-row-${validSev} fade-up`;
                row.style.animationDelay = (i * 30) + 'ms';
                row.innerHTML = `
            <code style="font-size:.65rem;color:var(--text-secondary);font-family:ui-monospace,monospace;flex-shrink:0;">${id}</code>
            <span style="flex:1;min-width:0;font-size:.875rem;font-weight:600;color:var(--text-primary);
                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${esc(vuln.title || 'Vulnérabilité')}
            </span>
            <span class="sev-badge sev-${validSev}" style="font-size:.6rem;flex-shrink:0;">${esc(badgeLabel)}</span>
            <svg viewBox="0 0 20 20" fill="currentColor"
                 style="width:15px;height:15px;color:var(--text-muted);flex-shrink:0;transition:color .15s ease;">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>`;

                row.addEventListener('click', () => {
                    document.querySelectorAll('.vuln-row').forEach(r => r.classList.remove('is-active'));
                    row.classList.add('is-active');
                    openDrawer(vuln, validSev);
                });

                list.appendChild(row);
            });

            container.appendChild(list);
        }

        /* ─── Nouvelle analyse ───────────────────────────────────────────────────── */
        document.getElementById('newAnalysisBtn').addEventListener('click', () => {
            sessionStorage.removeItem('nai_audit');
            closeDrawer();
            document.getElementById('results-summary-section').classList.add('hidden');
            document.getElementById('audit-results').classList.add('hidden');
            document.getElementById('error-section').classList.add('hidden');
            document.getElementById('uploadForm').reset();
            dzState('idle');
            submitBtn.disabled = true;
            const sec = document.getElementById('upload-section');
            sec.classList.remove('hidden');
            sec.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });

        /* ─── Utilitaire XSS ────────────────────────────────────────────────────── */
        function esc(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /* ─── Spotlight sur les stat-cards ──────────────────────────────────────── */
        (function initSpotlight() {
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mousemove', e => {
                    const r = card.getBoundingClientRect();
                    card.style.setProperty('--mouse-x', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
                    card.style.setProperty('--mouse-y', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
                });
            });
        })();

        /* ─── Initialisation ─────────────────────────────────────────────────────── */
        initLang(); // calls updateTrialUI() internally via applyLang()

        // Restauration depuis sessionStorage (résistance au refresh)
        (function restoreAudit() {
            let saved;
            try {
                saved = sessionStorage.getItem('nai_audit');
            } catch (e) {}
            if (!saved) return;
            let data;
            try {
                data = JSON.parse(saved);
            } catch (e) {
                sessionStorage.removeItem('nai_audit');
                return;
            }
            if (!data || !data.audit) {
                sessionStorage.removeItem('nai_audit');
                return;
            }
            document.getElementById('upload-section').classList.add('hidden');
            renderResults(data);
        })();

        /* ─── Initialisation finale (après DOM complet) ──────────────────────────── */
        window.addEventListener('DOMContentLoaded', () => {
            if (showModal) showUsernameModal();
        });
    </script>

    <!-- ═══════════════════════════════════════════════════════════════
     MODAL : Initialisation de l'exploitant
     Doit rester AVANT </body> mais APRÈS le <script> principal
     est inutile — on le place ICI pour que l'élément existe
     dans le DOM quand le script s'exécute via DOMContentLoaded.
════════════════════════════════════════════════════════════════ -->
    <div id="username-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display:none;">
        <div id="username-modal">

            <!-- Icône -->
            <div class="flex justify-center mb-4">
                <div style="width:3rem;height:3rem;border-radius:.75rem;background:rgba(56,189,248,.1);
                        border:1px solid rgba(56,189,248,.2);display:flex;align-items:center;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"
                        style="width:1.375rem;height:1.375rem;stroke:#38bdf8;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                </div>
            </div>

            <!-- Titre -->
            <h2 id="modal-title" class="text-center font-bold text-base mb-1"
                style="color:#f1f5f9;" data-i18n="modalTitle">Initialisation du Cockpit</h2>
            <p class="text-center text-xs mb-5" style="color:#64748b;line-height:1.6;"
                data-i18n="modalSubtitle">Entrez votre nom ou votre entreprise pour personnaliser votre accès.</p>

            <!-- Input -->
            <input id="username-input" type="text" maxlength="80"
                placeholder="Ex : Airbus, TechCorp, John Doe…"
                autocomplete="name" spellcheck="false"
                onkeydown="if(event.key==='Enter')submitUsername();" />

            <!-- Bouton -->
            <button onclick="submitUsername()"
                class="btn-primary w-full mt-3" style="padding:.7rem 1rem;">
                <span data-i18n="modalBtn">Initialiser le cockpit</span>
            </button>

            <p id="modal-error" class="hidden text-center text-xs mt-2" style="color:#f87171;"></p>
        </div>
    </div>
</body>

</html>