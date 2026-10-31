<?php
/**
 * Разметка страницы MarkVault в вёрстке MDShelf.
 *
 * Макет перенесён из _mdshelf/theme (layouts/doc.html + partials):
 *   doc-shell → doc-header + doc-frame(doc-sidebar | doc-main | doc-toc-panel)
 *
 * Ожидает переменные из App\Application::renderTemplate().
 * Логики здесь нет — только HTML, CSS и клиентский JS.
 */
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="generator" content="MarkVault <?= htmlspecialchars($buildVersion ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="copyright" content="MarkVault <?= htmlspecialchars($buildDate ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($buildHash ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        /* ─── Токены темы ──────────────────────────────────────────────── */
        :root {
            color-scheme: dark;
            /* fallback: палитра темы, если переменные не пришли */
            --bg: #09090b;
            --surface: #18181b;
            --surface-hover: #27272a;
            --panel: #18181b;
            --text: #9e9ea8;
            --text-heading: #d4d4d8;
            --muted: #71717a;
            --border: #27272a;
            --border-subtle: #18181b;
            --accent: #10b981;
            --accent-hover: #34d399;
            --accent-soft: rgba(16, 185, 129, 0.15);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.8);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6), 0 2px 4px -1px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.8), 0 10px 10px -5px rgba(0, 0, 0, 0.6);

            /* design tokens — не зависят от темы */
            --radius: 12px;
            --radius-sm: 8px;
            --header-h: 60px;
            --scroll-offset: calc(var(--header-h) + 1.25rem);
            --safe-top: 0px;
            --safe-bottom: 0px;
            --sidebar-w: 260px;
            --toc-w: 240px;
            --font: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --mono: ui-monospace, "SFMono-Regular", SF Mono, Menlo, Consolas, "Liberation Mono", monospace;
        }
        @media (max-width: 1099px) {
            :root {
                --safe-top: env(safe-area-inset-top, 0px);
                --safe-bottom: env(safe-area-inset-bottom, 0px);
            }
        }

        /* Палитра из конфига. Отсутствующие ключи пропускаются. */
        :root {
        <?= \App\Config::toCssVariables($darkVars) ?>
            color-scheme: dark;
        }
        html[data-theme="light"] {
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        <?= \App\Config::toCssVariables($lightVars) ?>
            color-scheme: light;
        }

        /* ─── База ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--text);
            font-family: var(--font);
            font-size: 16px;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        a { color: var(--accent); text-decoration: none; transition: color 0.2s ease; }
        a:hover { color: var(--accent-hover); }

        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }

        /* ─── Иконочные кнопки ─────────────────────────────────────────── */
        .icon-button {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            background: transparent;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            cursor: pointer;
            padding: 0;
            color: var(--muted);
            transition: all 0.2s ease;
        }
        .icon-button:hover { background: var(--surface-hover); color: var(--text-heading); }
        .icon-button-bar {
            display: block;
            width: 16px;
            height: 1.5px;
            background: currentColor;
            border-radius: 1px;
        }
        .icon-button svg { display: block; }
        /* Показываем иконку целевой темы: в тёмной — солнце (переключит
           на светлую), в светлой — луну. Селекторы с .theme-toggle нужны,
           чтобы перебить display: block у .icon-button svg. */
        .theme-toggle .sun-icon { display: block; }
        .theme-toggle .moon-icon { display: none; }
        html[data-theme="light"] .theme-toggle .sun-icon { display: none; }
        html[data-theme="light"] .theme-toggle .moon-icon { display: block; }
        @media (min-width: 1100px) { .nav-toggle { display: none; } }

        /* ─── Шапка ────────────────────────────────────────────────────── */
        .doc-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: calc(var(--header-h) + var(--safe-top));
            padding: var(--safe-top) 16px 0 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            background: color-mix(in srgb, var(--bg) 75%, transparent);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            z-index: 100;
        }
        .doc-header-title {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
        }
        .doc-header-site {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .doc-header-site:hover { color: var(--text-heading); opacity: 0.8; }
        .doc-header-site::before {
            content: "";
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            box-shadow: 0 2px 4px var(--accent-soft);
            flex-shrink: 0;
        }
        .doc-header-page {
            font-size: 0.85rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: none;
            font-weight: 500;
            min-width: 0;
        }
        @media (min-width: 640px) {
            .doc-header-page { display: block; }
            .doc-header-page::before {
                content: "/";
                margin: 0 12px;
                color: var(--border);
                font-weight: 400;
            }
        }
        .doc-header-actions { display: flex; align-items: center; gap: 12px; }
        .toc-toggle.is-active { background: var(--surface-hover); color: var(--text-heading); }

        /* ─── Каркас ───────────────────────────────────────────────────── */
        .doc-shell {
            min-height: 100vh;
            padding-top: calc(var(--header-h) + var(--safe-top));
            display: flex;
            flex-direction: column;
        }
        .doc-frame {
            display: grid;
            grid-template-columns: 1fr;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            flex: 1;
        }
        @media (min-width: 1100px) {
            .doc-frame {
                grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
                align-items: start;
                padding: 0 16px;
            }
        }
        @media (min-width: 1280px) {
            .doc-frame {
                grid-template-columns: var(--sidebar-w) minmax(0, 1fr) var(--toc-w);
                padding: 0 16px;
            }
            html.toc-hidden .doc-frame { grid-template-columns: var(--sidebar-w) minmax(0, 1fr); }
        }

        /* ─── Сайдбар ──────────────────────────────────────────────────── */
        .doc-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: min(85vw, 300px);
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            background: var(--bg);
            border-right: 1px solid var(--border);
            z-index: 50;
            top: calc(var(--header-h) + var(--safe-top));
            box-shadow: var(--shadow-lg);
            display: flex;
        }
        .doc-sidebar-inner {
            flex: 1;
            min-width: 0;
            height: 100%;
            overflow-y: auto;
            padding: 32px 16px calc(24px + var(--safe-bottom));
        }
        .doc-shell.sidebar-open .doc-sidebar { transform: translateX(0); }
        .sidebar-resize-handle { display: none; }
        .doc-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(9, 9, 11, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 45;
            top: calc(var(--header-h) + var(--safe-top));
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        .doc-backdrop[hidden] { opacity: 0; pointer-events: none; display: block; }

        @media (min-width: 1100px) {
            .doc-sidebar {
                position: sticky;
                top: calc(var(--header-h) + var(--safe-top));
                transform: none;
                width: var(--sidebar-w);
                min-width: var(--sidebar-w);
                box-shadow: none;
                background: transparent;
                height: calc(100vh - var(--header-h) - var(--safe-top));
            }
            .doc-sidebar-inner { padding: 32px 24px 32px 0; }
            .doc-sidebar-inner::-webkit-scrollbar { width: 4px; }
            .doc-sidebar-inner::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
            .sidebar-resize-handle { display: none; }
            .doc-backdrop { display: none !important; }
        }

        /* ─── Дерево в сайдбаре ────────────────────────────────────────── */
        .sidebar-nav { display: flex; flex-direction: column; min-height: 0; }
        .sidebar-section {
            display: block;
            padding: 9px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin: 0 0 4px;
        }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-top: 1px; }
        .sidebar-nav ul ul { padding-left: 14px; }

        .sidebar-nav li > a {
            display: block;
            padding: 9px 10px;
            border-radius: 6px;
            color: var(--text);
            font-size: 0.9375rem;
            font-weight: 400;
            text-decoration: none;
            transition: background 0.15s ease, color 0.15s ease;
            line-height: 1.4;
        }
        .sidebar-nav li > a:hover { background: var(--surface-hover); color: var(--text-heading); }
        .sidebar-nav li > a.active {
            color: var(--text-heading);
            font-weight: 600;
            background: var(--surface-hover);
        }
        .sidebar-nav li.locked > a { opacity: 0.75; }

        .sidebar-nav summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 9px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-family: var(--font);
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-heading);
            text-align: left;
            list-style: none;
            user-select: none;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sidebar-nav summary::-webkit-details-marker { display: none; }
        .sidebar-nav summary:hover { background: var(--surface-hover); }
        .sidebar-nav summary::after {
            content: "";
            flex-shrink: 0;
            width: 14px;
            height: 14px;
            background-color: var(--border);
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E") no-repeat center / contain;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E") no-repeat center / contain;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .sidebar-nav details[open] > summary::after { transform: rotate(90deg); }
        .sidebar-nav summary:hover::after { background-color: var(--muted); }
        .sidebar-nav li.locked > summary { opacity: 0.75; }

        .lock-badge { font-size: 11px; margin-left: 4px; opacity: 0.9; }

        /* ─── Форма входа в сайдбаре ───────────────────────────────────── */
        .sidebar-auth { margin-bottom: 14px; }
        .auth-form { display: flex; gap: 6px; }
        .auth-form input {
            flex: 1;
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: var(--radius-sm);
            padding: 7px 10px;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .auth-form input:focus { border-color: var(--accent); }
        .auth-form button {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 7px 12px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .auth-form button:hover { background: var(--accent-hover); }
        .logout-link {
            display: inline-block;
            font-size: 12px;
            color: var(--muted);
            text-decoration: none;
        }
        .logout-link:hover { color: var(--accent); }

        /* ─── Контент ──────────────────────────────────────────────────── */
        .doc-main {
            padding: 24px 20px calc(64px + var(--safe-bottom));
            min-width: 0;
            width: 100%;
        }
        @media (min-width: 1024px) {
            .doc-main { padding: 28px 36px calc(80px + var(--safe-bottom)); }
        }
        @media (min-width: 1280px) {
            .doc-main { padding: 32px 48px calc(80px + var(--safe-bottom)); }
        }

        .doc-notice { color: var(--muted); }

        /* Хлебные крошки */
        .breadcrumbs-list {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 0;
            margin: 0 0 24px;
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 500;
        }
        .breadcrumbs-item a { color: var(--muted); transition: color 0.2s ease; text-decoration: none; }
        .breadcrumbs-item a:hover { color: var(--text-heading); }
        .breadcrumbs-item + .breadcrumbs-item::before {
            content: "";
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-right: 8px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%2371717a'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M9 5l7 7-7 7'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
            vertical-align: middle;
            opacity: 0.6;
        }
        .breadcrumbs-item:last-child,
        .breadcrumbs-item:last-child a { color: var(--text-heading); font-weight: 600; }
        .breadcrumbs-item:last-child a { pointer-events: none; }

        /* ─── Соседние страницы ────────────────────────────────────────── */
        .doc-prev-next { display: grid; gap: 16px; margin-top: 64px; padding-top: 32px; border-top: 1px solid var(--border); }
        .doc-prev-next[hidden] { display: none; }
        @media (min-width: 640px) { .doc-prev-next { grid-template-columns: 1fr 1fr; } }
        .doc-prev-next-link {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
            min-width: 0;
        }
        .doc-prev-next-link:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
            background: var(--bg);
        }
        .doc-prev-next-link .label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .doc-prev-next-link .title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-heading);
            transition: color 0.2s ease;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .doc-prev-next-link:hover .title { color: var(--accent); }
        .doc-prev .label::before { content: "\2190"; font-size: 1.1em; }
        .doc-next { text-align: right; align-items: flex-end; }
        .doc-next .label::after { content: "\2192"; font-size: 1.1em; }

        /* ─── Подвал ───────────────────────────────────────────────────── */
        .site-footer {
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--muted);
            text-align: center;
        }
        .site-footer p { margin: 0 0 6px; }
        .site-footer p:last-child { margin-bottom: 0; }
        .site-footer code {
            font-family: var(--mono);
            font-size: 0.9em;
            background: var(--surface-hover);
            padding: 0.1em 0.35em;
            border-radius: 4px;
            border: 1px solid var(--border-subtle);
            color: var(--text-heading);
        }

        /* ─── Правая колонка: содержание ───────────────────────────────── */
        .doc-toc-panel {
            position: fixed;
            right: 0;
            left: 0;
            bottom: 0;
            max-height: 60vh;
            transform: translateY(102%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            background: var(--bg);
            border-radius: 20px 20px 0 0;
            z-index: 60;
            padding: 24px 20px calc(24px + var(--safe-bottom));
            overflow-y: auto;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
            scrollbar-width: none;
        }
        .doc-toc-panel::-webkit-scrollbar { display: none; }
        .doc-shell.toc-open .doc-toc-panel { transform: translateY(0); }
        .doc-toc-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(9, 9, 11, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 55;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        .doc-toc-backdrop[hidden] { opacity: 0; pointer-events: none; display: block; }

        @media (min-width: 1280px) {
            .doc-toc-panel {
                position: sticky;
                top: calc(var(--header-h) + var(--safe-top));
                transform: none;
                max-height: calc(100vh - var(--header-h) - var(--safe-top));
                border-radius: 0;
                box-shadow: none;
                left: auto;
                width: auto;
                border: none;
                background: transparent;
                padding: 32px 0 32px 24px;
                z-index: auto;
            }
            html.toc-hidden .doc-toc-panel { display: none !important; }
            .doc-toc-inner { max-height: 100%; overflow-y: auto; scrollbar-width: none; }
            .doc-toc-inner::-webkit-scrollbar { display: none; }
            .doc-toc-backdrop { display: none !important; }
        }

        .toc-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 16px;
            color: var(--text-heading);
        }
        .toc-list { list-style: none; padding: 0; margin: 0; border-left: 1px solid var(--border); position: relative; }
        .toc-item { margin: 0; }
        .toc-item a {
            display: block;
            padding: 6px 12px;
            font-size: 0.85rem;
            color: var(--muted);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 1px solid transparent;
            margin-left: -1px;
            font-weight: 500;
            line-height: 1.4;
        }
        .toc-item a:hover { color: var(--text-heading); border-left-color: var(--border); }
        .toc-item a.is-active { font-weight: 600; color: var(--accent); border-left-color: var(--accent); }
        .toc-level-3 a { padding-left: 20px; }

        /* ─── Типографика markdown ─────────────────────────────────────── */
        .prose { color: var(--text); font-size: 1rem; line-height: 1.75; max-width: 1200px; }
        .prose > :first-child { margin-top: 0; }
        .prose > :last-child { margin-bottom: 0; }

        .prose :is(h1, h2, h3, h4, h5, h6) {
            color: var(--text-heading);
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.3;
            position: relative;
            scroll-margin-top: var(--scroll-offset);
        }
        .prose h1 {
            font-size: clamp(1.875rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-top: 0;
            margin-bottom: 1rem;
            line-height: 1.15;
        }
        .prose h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 3rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-subtle);
        }
        .prose h3 { font-size: 1.25rem; margin-top: 2.25rem; margin-bottom: 0.6rem; }
        .prose h4 { font-size: 1.1rem; margin-top: 1.75rem; margin-bottom: 0.5rem; }
        .prose h5, .prose h6 { font-size: 1rem; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .prose h6 { color: var(--muted); }

        .prose p { margin-top: 0; margin-bottom: 1.4em; }
        .prose h1 + p { font-size: 1.075rem; color: var(--text); line-height: 1.8; }

        .prose a {
            color: var(--accent);
            font-weight: 500;
            text-decoration: underline;
            text-decoration-color: color-mix(in srgb, var(--accent) 40%, transparent);
            text-underline-offset: 3px;
            text-decoration-thickness: 1px;
            transition: color 0.15s ease, text-decoration-color 0.15s ease;
        }
        .prose a:hover { color: var(--accent-hover); text-decoration-color: var(--accent-hover); }

        .prose strong { font-weight: 600; color: var(--text-heading); }
        .prose em { font-style: italic; }
        .prose del { color: var(--muted); text-decoration: line-through; text-decoration-color: var(--muted); }
        .prose sup, .prose sub { font-size: 0.75em; line-height: 1; }
        .prose mark {
            background: color-mix(in srgb, var(--accent) 20%, transparent);
            color: var(--text-heading);
            border-radius: 3px;
            padding: 0.1em 0.25em;
        }
        .prose kbd {
            font-family: var(--mono);
            font-size: 0.8em;
            color: var(--text-heading);
            background: var(--surface);
            border: 1px solid var(--border);
            border-bottom-width: 2px;
            border-radius: 5px;
            padding: 0.15em 0.45em;
            white-space: nowrap;
            box-shadow: inset 0 -1px 0 var(--border);
        }
        .prose abbr[title] { text-decoration: underline dotted var(--muted); text-underline-offset: 3px; cursor: help; }
        .prose hr { border: none; border-top: 1px solid var(--border); margin: 2.5rem 0; }

        .prose ul, .prose ol { margin-top: 0; margin-bottom: 1.4em; padding-left: 1.75em; }
        .prose li { margin-bottom: 0.375em; padding-left: 0.25em; }
        .prose li > p { margin-bottom: 0.5em; }
        .prose li::marker { color: var(--muted); }
        .prose ol li::marker { font-size: 0.9em; font-weight: 600; }
        .prose :is(ul, ol) :is(ul, ol) { margin-top: 0.375em; margin-bottom: 0.375em; }

        .prose .task-list-item {
            list-style: none;
            padding-left: 0;
            margin-left: -0.25em;
            display: flex;
            align-items: baseline;
            gap: 0.6em;
        }
        .prose .task-list-item input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 1em;
            height: 1em;
            border: 1.5px solid var(--border);
            border-radius: 4px;
            background: var(--bg);
            cursor: default;
            flex-shrink: 0;
            position: relative;
            top: 0.1em;
            accent-color: var(--accent);
        }

        .prose dl { margin: 1.25em 0; display: grid; grid-template-columns: max-content 1fr; gap: 0.5em 1.5em; align-items: baseline; }
        .prose dt { font-weight: 600; color: var(--text-heading); font-size: 0.925rem; }
        .prose dd { margin: 0; color: var(--text); }

        .prose blockquote {
            border-left: 3px solid var(--accent);
            background: var(--accent-soft);
            margin: 1.5em 0;
            padding: 1rem 1.25rem;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            font-style: italic;
        }
        .prose blockquote > :first-child { margin-top: 0; }
        .prose blockquote > :last-child { margin-bottom: 0; }

        .prose details { border: 1px solid var(--border); border-radius: var(--radius-sm); margin: 1.25em 0; overflow: hidden; }
        .prose summary {
            font-weight: 600;
            color: var(--text-heading);
            cursor: pointer;
            padding: 0.8em 1rem;
            background: var(--surface);
            list-style: none;
            display: flex;
            align-items: center;
            gap: 0.6em;
            user-select: none;
            transition: background 0.15s ease;
        }
        .prose summary::-webkit-details-marker { display: none; }
        .prose summary::before {
            content: "";
            display: inline-block;
            width: 0.85em;
            height: 0.85em;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E") center / contain no-repeat;
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }
        .prose details[open] > summary::before { transform: rotate(90deg); }
        .prose summary:hover { background: var(--surface-hover); }
        .prose details > :not(summary) { padding: 0.75em 1rem 1rem; }
        .prose details > :not(summary) > :first-child { margin-top: 0; }
        .prose details > :not(summary) > :last-child { margin-bottom: 0; }

        .prose code {
            font-family: var(--mono);
            font-size: 0.85em;
            background: var(--surface-hover);
            padding: 0.2em 0.42em;
            border-radius: 5px;
            color: var(--text-heading);
            border: 1px solid var(--border-subtle);
            word-break: break-word;
        }
        .prose pre {
            position: relative;
            margin: 1.4em 0;
            border-radius: var(--radius);
            background: var(--surface);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .prose pre > code {
            display: block;
            overflow-x: auto;
            padding: 1.1rem 1.25rem;
            background: transparent;
            border: none;
            border-radius: 0;
            color: inherit;
            font-size: 0.85rem;
            line-height: 1.65;
            word-break: normal;
            -webkit-overflow-scrolling: touch;
        }
        .prose pre > code::-webkit-scrollbar { height: 5px; }
        .prose pre > code::-webkit-scrollbar-track { background: transparent; }
        .prose pre > code::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .prose pre .code-lang {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.65rem;
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: var(--muted);
            background: var(--border-subtle);
            border-bottom-left-radius: var(--radius-sm);
            border-top-right-radius: var(--radius);
            text-transform: lowercase;
            user-select: none;
            pointer-events: none;
        }

        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: block;
            margin: 1.5em auto;
        }
        .prose figure { margin: 1.75em 0; text-align: center; }
        .prose figure img { margin: 0 auto 0.75em; }
        .prose figcaption { font-size: 0.85rem; color: var(--muted); font-style: italic; }

        .prose .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 1.75em 0; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .prose .table-wrapper::-webkit-scrollbar { height: 5px; }
        .prose .table-wrapper::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .prose table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin: 0; }
        .prose thead { background: var(--surface-hover); }
        .prose th { font-weight: 600; color: var(--text-heading); padding: 10px 16px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .prose td { padding: 10px 16px; border-bottom: 1px solid var(--border-subtle); vertical-align: top; }
        .prose tbody tr:last-child td { border-bottom: none; }
        .prose tbody tr:hover td { background: var(--surface); }

        .prose .footnotes { margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid var(--border); font-size: 0.875rem; color: var(--muted); }
        .prose .footnotes hr { display: none; }
        .prose .footnotes ol { padding-left: 1.5em; margin: 0; }
        .prose .footnotes li { margin-bottom: 0.5em; }
        .prose .footnotes p { margin-bottom: 0.25em; display: inline; }

        .prose [data-math-style] { overflow-x: auto; overflow-y: hidden; }
        .prose [data-math-style="display"] { display: block; text-align: center; margin: 1.5em 0; padding: 0.5em 0; }

        /* ─── Модалка ──────────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(2px);
        }
        .modal-overlay[hidden] { display: none; }
        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            width: 360px;
            max-width: 92vw;
            box-shadow: var(--shadow-lg);
        }
        .modal h3 { margin: 0 0 6px; color: var(--text-heading); }
        .modal-hint { color: var(--muted); font-size: 13px; margin: 0 0 14px; }
        .modal input[type="password"] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .modal input[type="password"]:focus { border-color: var(--accent); }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px; }
        .modal-actions button {
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            border: 1px solid var(--border);
            background: var(--surface-hover);
            color: var(--text);
            transition: background 0.15s ease;
        }
        .modal-actions button:hover { background: var(--border); }
        .modal-actions button[type="submit"] { background: var(--accent); color: #fff; border-color: transparent; }
        .modal-actions button[type="submit"]:hover { background: var(--accent-hover); }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after { transition: none !important; transform: none !important; }
        }
    </style>
    <script>
        (function() {
            var root = document.documentElement;
            try {
                var theme = localStorage.getItem("theme");
                if (theme !== "dark" && theme !== "light") {
                    theme = <?= json_encode($defaultTheme) ?>;
                }
                root.setAttribute("data-theme", theme);
            } catch (e) {}
        })();
    </script>
</head>
<body data-folders-remember="<?= htmlspecialchars($foldersRemember, ENT_QUOTES, 'UTF-8') ?>">
<div class="doc-shell" id="docShell">

    <header class="doc-header" id="docHeader">
        <button type="button" class="icon-button nav-toggle" id="sidebar-open" aria-controls="sidebar-panel" aria-expanded="false" aria-label="Открыть меню">
            <span class="icon-button-bar" aria-hidden="true"></span>
            <span class="icon-button-bar" aria-hidden="true"></span>
            <span class="icon-button-bar" aria-hidden="true"></span>
        </button>

        <div class="doc-header-title">
            <a class="doc-header-site" href="?"><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></a>
            <span class="doc-header-page"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="doc-header-actions">
            <button type="button" class="icon-button toc-toggle" id="toc-open" aria-controls="toc-panel" aria-expanded="false" aria-label="На этой странице" title="На этой странице">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </button>
            <?php if ($protectionEnabled && !$isAuthorized): ?>
                <button type="button" class="icon-button auth-toggle" id="auth-open" aria-label="Войти" title="Войти" aria-haspopup="dialog" aria-controls="authModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                </button>
            <?php endif; ?>
            <button type="button" class="icon-button theme-toggle" id="theme-toggle" aria-label="Переключить тему" title="Переключить тему">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
        </div>
    </header>

    <div class="doc-frame">
        <aside class="doc-sidebar" id="sidebar-panel" aria-label="Навигация">
            <div class="doc-sidebar-inner">
                <?php if ($protectionEnabled && $isAuthorized): ?>
                    <div class="sidebar-auth">
                        <a class="logout-link" href="?logout=1">Выйти 🔓</a>
                    </div>
                <?php endif; ?>

                <p class="sidebar-section"><?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?></p>

                <nav class="sidebar-nav" aria-label="<?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?>">
                    <ul class="sidebar-list" id="sidebar-list">
                        <?php if ($hasDocuments): ?>
                            <?= $tree ?>
                        <?php else: ?>
                            <li style="color: var(--muted); padding: 10px;">Нет .md файлов</li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <div class="sidebar-resize-handle" id="sidebar-resize-handle" aria-hidden="true"></div>
        </aside>
        <div class="doc-backdrop" id="sidebar-backdrop" hidden></div>

        <main class="doc-main">
            <?= $debugInfo ?>

            <?php if ($current): ?>
                <?php if (count($breadcrumbs) > 0): ?>
                    <nav class="breadcrumbs" aria-label="Хлебные крошки">
                        <ol class="breadcrumbs-list">
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <li class="breadcrumbs-item">
                                    <?php if (!empty($crumb['isFile'])): ?>
                                        <span><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <a href="?file=<?= htmlspecialchars($crumb['path'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>

                <article class="doc-article prose" id="docArticle">
                    <?= $content ?>
                </article>

                <nav class="doc-prev-next" id="prevNext" hidden aria-label="Соседние страницы"></nav>

                <footer class="site-footer">
                    <p>Файл: <code><?= htmlspecialchars($current->relative, ENT_QUOTES, 'UTF-8') ?></code> • Обновлён: <?= date('Y-m-d H:i', $current->mtime) ?></p>
                    <p>Собрано с <strong>MarkVault</strong> · <?= htmlspecialchars(isset($build->appVersion) ? $build->appVersion : '', ENT_QUOTES, 'UTF-8') ?></p>
                </footer>
            <?php elseif (!$hasDocuments): ?>
                <p class="doc-notice">Положи .md файлы в эту папку</p>
            <?php else: ?>
                <p class="doc-notice">Файл не выбран</p>
            <?php endif; ?>
        </main>

        <aside class="doc-toc-panel" id="toc-panel" aria-label="На этой странице">
            <div class="doc-toc-inner">
                <div class="toc">
                    <p class="toc-title">На этой странице</p>
                    <ol class="toc-list" id="tocList"></ol>
                </div>
            </div>
        </aside>
        <div class="doc-toc-backdrop" id="toc-backdrop" hidden></div>
    </div>
</div>

<script data-name="theme">
    (function(){
        const THEME_CONFIG = {
            dark:  <?= json_encode($darkVars,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            light: <?= json_encode($lightVars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            default: <?= json_encode($defaultTheme) ?>
        };

        const CSS_MAP = {
            bg: '--bg',
            panel: '--panel',
            surface: '--surface',
            surface_hover: '--surface-hover',
            text: '--text',
            text_heading: '--text-heading',
            muted: '--muted',
            accent: '--accent',
            accent_hover: '--accent-hover',
            accent_soft: '--accent-soft',
            border: '--border',
            border_subtle: '--border-subtle',
            shadow: '--shadow',
            shadow_sm: '--shadow-sm',
            shadow_lg: '--shadow-lg'
        };

        const button = document.getElementById('theme-toggle');
        const html = document.documentElement;

        const current = () => {
            const t = html.getAttribute('data-theme');
            return t === 'light' || t === 'dark' ? t : THEME_CONFIG.default;
        };

        function applyTheme(theme) {
            html.setAttribute('data-theme', theme);
            const style = html.style;

            Object.values(CSS_MAP).forEach(v => style.removeProperty(v));

            const vars = THEME_CONFIG[theme] || {};
            for (const [key, value] of Object.entries(vars)) {
                if (CSS_MAP[key]) style.setProperty(CSS_MAP[key], value);
            }
            try { localStorage.setItem('theme', theme); } catch (e) {}
        }

        let saved = null;
        try { saved = localStorage.getItem('theme'); } catch (e) {}
        applyTheme(saved === 'light' || saved === 'dark' ? saved : THEME_CONFIG.default);

        if (button) {
            button.addEventListener('click', () => {
                applyTheme(current() === 'light' ? 'dark' : 'light');
            });
        }
    })();
</script>

<script data-name="panels">
    (function(){
        const shell = document.getElementById('docShell');
        const open = document.getElementById('sidebar-open');
        const backdrop = document.getElementById('sidebar-backdrop');
        const tocOpen = document.getElementById('toc-open');
        const tocBackdrop = document.getElementById('toc-backdrop');
        if (!shell) return;

        function setSidebar(state) {
            shell.classList.toggle('sidebar-open', state);
            if (open) open.setAttribute('aria-expanded', String(state));
            if (backdrop) backdrop.hidden = !state;
        }
        function setToc(state) {
            shell.classList.toggle('toc-open', state);
            if (tocOpen) {
                tocOpen.setAttribute('aria-expanded', String(state));
                tocOpen.classList.toggle('is-active', state);
            }
            if (tocBackdrop) tocBackdrop.hidden = !state;
        }

        if (open) open.addEventListener('click', () => setSidebar(!shell.classList.contains('sidebar-open')));
        if (backdrop) backdrop.addEventListener('click', () => setSidebar(false));
        if (tocOpen) tocOpen.addEventListener('click', () => setToc(!shell.classList.contains('toc-open')));
        if (tocBackdrop) tocBackdrop.addEventListener('click', () => setToc(false));

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            if (shell.classList.contains('sidebar-open')) setSidebar(false);
            if (shell.classList.contains('toc-open')) setToc(false);
        });

        document.querySelectorAll('.sidebar-nav a[href^="?file="]').forEach(a => {
            a.addEventListener('click', () => setSidebar(false));
        });
    })();
</script>

<script data-name="toc-and-prevnext">
    (function(){
        const article = document.getElementById('docArticle');
        const list = document.getElementById('tocList');
        const panel = document.getElementById('prevNext');
        const tocButton = document.getElementById('toc-open');

        if (article && list) {
            const heads = Array.from(article.querySelectorAll('h2, h3'));
            const used = new Set(['docShell', 'sidebar-panel', 'toc-panel', 'docArticle', 'tocList', 'prevNext']);

            const slugify = (text) => {
                const base = (text || '').toLowerCase().trim()
                    .replace(/[^\p{L}\p{N}\s-]/gu, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                return base || 'section';
            };

            heads.forEach(h => {
                if (!h.id) {
                    let id = slugify(h.textContent);
                    let n = 1;
                    while (used.has(id) || document.getElementById(id)) id = slugify(h.textContent) + '-' + (n++);
                    h.id = id;
                }
                used.add(h.id);

                const li = document.createElement('li');
                li.className = 'toc-item toc-level-' + h.tagName.charAt(1);

                const a = document.createElement('a');
                a.href = '#' + h.id;
                a.textContent = h.textContent;

                li.appendChild(a);
                list.appendChild(li);
            });

            if (heads.length === 0) {
                document.documentElement.classList.add('toc-hidden');
                if (tocButton) tocButton.hidden = true;
            } else {
                const items = Array.from(list.querySelectorAll('a'));
                const sync = () => {
                    let idx = 0;
                    heads.forEach((h, i) => {
                        if (h.getBoundingClientRect().top <= 140) idx = i;
                    });
                    items.forEach((a, i) => a.classList.toggle('is-active', i === idx));
                };
                window.addEventListener('scroll', sync, { passive: true });
                sync();
            }
        }

        if (panel) {
            const links = Array.from(document.querySelectorAll('#sidebar-list a[href^="?file="]'));
            const activeIndex = links.findIndex(a => a.classList.contains('active'));

            const make = (source, direction) => {
                if (!source) return null;
                const link = document.createElement('a');
                link.className = 'doc-prev-next-link ' + (direction === 'prev' ? 'doc-prev' : 'doc-next');
                link.href = source.getAttribute('href');

                const label = document.createElement('span');
                label.className = 'label';
                label.textContent = direction === 'prev' ? 'Назад' : 'Далее';

                const title = document.createElement('span');
                title.className = 'title';
                title.textContent = source.textContent.trim();

                link.append(label, title);
                return link;
            };

            if (activeIndex >= 0) {
                const prev = make(links[activeIndex - 1], 'prev');
                const next = make(links[activeIndex + 1], 'next');
                if (prev) panel.appendChild(prev);
                if (next) panel.appendChild(next);
                if (prev || next) panel.hidden = false;
            }
        }
    })();
</script>

<script data-name="logout-cleanup">
    (function(){
        const params = new URLSearchParams(location.search);

        if (params.get('logged_out') !== '1') return;

        try { localStorage.removeItem('md_docs_auth'); } catch (e) {}

        document.querySelectorAll('input[name="password"]').forEach(inp => {
            inp.value = '';
            inp.setAttribute('autocomplete', 'new-password');
        });

        params.delete('logged_out');
        const cleanUrl = location.pathname
            + (params.toString() ? '?' + params.toString() : '')
            + location.hash;
        history.replaceState(null, '', cleanUrl);
    })();
</script>

<script data-name="nav-details">
(function () {
    const KEY = 'mdvault_folders';
    const REMEMBER = ['per_folder', 'single'];

    const mode = document.body.dataset.foldersRemember;
    const remember = REMEMBER.includes(mode);

    const all = Array.from(document.querySelectorAll('nav details[data-path]'));
    const byPath = new Map();
    all.forEach(d => byPath.set(d.dataset.path, d));

    const siblings = (d) => {
        const list = d.parentElement && d.parentElement.parentElement;
        if (!list) return [];

        return Array.from(list.children)
            .filter(li => li !== d.parentElement)
            .map(li => li.querySelector(':scope > details'))
            .filter(Boolean);
    };

    let state = {};
    if (remember) {
        try {
            const parsed = JSON.parse(localStorage.getItem(KEY) || '{}');
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) state = parsed;
        } catch (e) { state = {}; }

        all.forEach(d => {
            const saved = state[d.dataset.path];
            if (typeof saved === 'boolean') d.open = saved;
        });
    }

    const active = new Set();
    const file = new URLSearchParams(location.search).get('file');
    if (file) {
        const parts = file.split('/');
        parts.pop();

        let acc = '';
        parts.forEach(part => {
            acc = acc ? acc + '/' + part : part;
            active.add(acc);

            const d = byPath.get(acc);
            if (d) d.open = true;
        });
    }

    if (mode === 'single') {
        all.forEach(d => {
            if (!d.open) return;

            const list = d.parentElement && d.parentElement.parentElement;
            if (!list) return;

            const peers = Array.from(list.children)
                .map(li => li.querySelector(':scope > details'))
                .filter(s => s && s.open);

            const winner = peers.find(s => active.has(s.dataset.path)) || peers[0];
            if (winner && winner !== d) d.open = false;
        });
    }

    if (!remember) return;

    const save = () => {
        try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
    };

    all.forEach(d => {
        d.addEventListener('toggle', () => {
            state[d.dataset.path] = d.open;

            if (d.open && mode === 'single') {
                siblings(d).forEach(s => { s.open = false; });
            }

            save();
        });
    });

    all.forEach(d => { state[d.dataset.path] = d.open; });
    save();
})();
</script>

<script data-name="auth">
    (function(){
        // Модалка стоит в разметке ниже этого скрипта, поэтому ищем её после
        // разбора документа: иначе getElementById вернёт null и клик по
        // ключу будет молча ничего не делать.
        const init = () => {
            const modal     = document.getElementById('authModal');
            const redirectF = document.getElementById('redirectFile');
            const modalPwd  = document.getElementById('modalPassword');
            const cancel    = document.getElementById('modalCancel');
            const authOpen  = document.getElementById('auth-open');

            const openModal = (file) => {
                if (!modal) return;
                if (redirectF) redirectF.value = file || '';
                modal.hidden = false;
                setTimeout(() => modalPwd && modalPwd.focus(), 30);
            };

            document.querySelectorAll('nav a[data-protected="1"]').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    openModal(a.dataset.file || '');
                });
            });

            if (authOpen) {
                authOpen.addEventListener('click', () => openModal(''));
            }

            if (cancel) {
                cancel.addEventListener('click', () => { modal.hidden = true; });
            }
            if (modal) {
                modal.addEventListener('click', e => {
                    if (e.target === modal) modal.hidden = true;
                });
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape' && !modal.hidden) modal.hidden = true;
                });
            }

            const saved = localStorage.getItem('md_docs_auth');
            if (saved && modalPwd && !modalPwd.value) {
                modalPwd.value = saved;
            }

            <?php if (!empty($saveToLocalStorage)): ?>
            try { localStorage.setItem('md_docs_auth', <?= json_encode($submittedPassword) ?>); } catch(e) {}
            <?php endif; ?>
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init, { once: true });
        } else {
            init();
        }
    })();
</script>

<?php if ($protectionEnabled): ?>
    <div class="modal-overlay" id="authModal" hidden>
        <div class="modal">
            <h3><?= htmlspecialchars($auth->title(), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="modal-hint"><?= htmlspecialchars($auth->hint(), ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" id="authFormModal">
                <input type="hidden" name="redirect_file" id="redirectFile" value="">
                <input type="password" name="password" id="modalPassword" placeholder="Пароль" autocomplete="current-password" autofocus>
                <div class="modal-actions">
                    <button type="button" id="modalCancel">Отмена</button>
                    <button type="submit">Открыть</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
