<?php
/**
 * Разметка страницы MarkVault.
 *
 * Шаблон: ожидает переменные, которые готовит App\Application::renderTemplate()
 * ($title, $navTitle, $tree, $current, $content, $breadcrumbs, $auth,
 * $protectionEnabled, $isAuthorized, $darkVars, $lightVars, $defaultTheme,
 * $hasDocuments, $saveToLocalStorage, $submittedPassword, $debugInfo, $foldersRemember,
 * $buildVersion, $buildDate, $buildHash).
 *
 * Логики здесь нет — только HTML, CSS и клиентский JS.
 */
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="MarkVault <?= htmlspecialchars($buildVersion ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="copyright" content="MarkVault <?= htmlspecialchars($buildDate ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($buildHash ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            /* fallback: палитра темы, если переменные не пришли */
            --bg: #09090b;
            --panel: #18181b;
            --surface: #18181b;
            --surface-hover: #27272a;
            --text: #9e9ea8;
            --text-heading: #d4d4d8;
            --muted: #71717a;
            --accent: #10b981;
            --accent-hover: #34d399;
            --accent-soft: rgba(16, 185, 129, 0.15);
            --border: #27272a;
            --border-subtle: #18181b;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.8);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.8),
                         0 10px 10px -5px rgba(0, 0, 0, 0.6);

            /* design tokens — не зависят от темы */
            --radius: 12px;
            --radius-sm: 8px;
            --sidebar-w: 260px;
            --font: "Inter", system-ui, -apple-system, "Segoe UI", Roboto,
                    Ubuntu, "Helvetica Neue", sans-serif;
            --mono: ui-monospace, "SFMono-Regular", SF Mono, Menlo, Consolas,
                    "Liberation Mono", monospace;
        }
        :root {
        <?= \App\Config::toCssVariables($darkVars) ?>
            color-scheme: dark;
        }
        html[data-theme="light"] {
        <?= \App\Config::toCssVariables($lightVars) ?>
            color-scheme: light;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            font-family: var(--font);
            font-size: 16px;
            line-height: 1.7;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
        }
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
        }

        /* ─── Навигация ─────────────────────────────────── */
        nav {
            background: var(--panel);
            border-right: 1px solid var(--border);
            padding: 32px 16px 24px;
            overflow-y: auto;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        nav h2 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--muted);
            margin: 0 0 12px;
            padding: 0 10px;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        nav li { margin: 2px 0; }
        nav li.dir { margin: 8px 0 4px; }
        nav a {
            display: block;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            line-height: 1.45;
            transition: background 0.15s ease, color 0.15s ease;
        }
        nav a:hover {
            background: var(--surface-hover);
            color: var(--text-heading);
        }
        nav a.active {
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 600;
        }
        nav details > summary {
            cursor: pointer;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
            user-select: none;
            list-style: none;
            transition: background 0.15s ease, color 0.15s ease;
        }
        nav details > summary::-webkit-details-marker { display: none; }
        nav details > summary:hover {
            background: var(--surface-hover);
            color: var(--text-heading);
        }
        nav details > summary::before {
            content: "📁 ";
        }
        nav details[open] > summary::before {
            content: "📂 ";
        }
        nav ul ul {
            margin: 2px 0 4px;
            padding-left: 12px;
            border-left: 1px solid var(--border-subtle);
        }
        nav li.locked a { opacity: 0.75; }
        .lock-badge {
            font-size: 11px;
            margin-left: 4px;
            opacity: 0.9;
        }

        /* ─── Контент ───────────────────────────────────── */
        main {
            padding: 24px 20px 64px;
            min-width: 0;
            max-width: 900px;
        }
        @media (min-width: 1024px) {
            main { padding: 28px 36px 80px; }
        }
        @media (min-width: 1280px) {
            main { padding: 32px 48px 80px; }
        }

        main > h1, main > h2, main > h3,
        main > h4, main > h5, main > h6 {
            color: var(--text-heading);
            line-height: 1.3;
            letter-spacing: -0.02em;
            font-weight: 700;
        }
        main > h1 { margin-top: 0; }
        main > p { line-height: 1.7; margin: 0.9em 0; }
        main > a { color: var(--accent); text-decoration: none; }
        main > a:hover { text-decoration: underline; }

        .meta {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 18px;
        }
        .breadcrumbs {
            margin-bottom: 16px;
            font-size: 14px;
        }
        .breadcrumbs a { color: var(--muted); }
        .breadcrumbs a:hover { color: var(--accent); }
        .breadcrumbs span { color: var(--muted); margin: 0 6px; }

        input[type="checkbox"] { margin-right: 8px; }

        /* ─── Кнопка темы ───────────────────────────────── */
        .theme-toggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            width: 40px;
            height: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            font-size: 16px;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .theme-toggle:hover {
            background: var(--surface-hover);
            color: var(--text-heading);
        }

        /* ─── Форма входа ───────────────────────────────── */
        .auth-form {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }
        .auth-form input {
            flex: 1;
            min-width: 0;
            background: var(--surface-hover);
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
            margin-bottom: 14px;
            text-decoration: none;
        }
        .logout-link:hover { color: var(--accent); }

        /* ─── Модалка ───────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(2px);
        }
        .modal-overlay[hidden] { display: none; }
        .modal {
            background: var(--panel);
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
            background: var(--surface-hover);
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
        .modal-actions {
            display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px;
        }
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
        .modal-actions button[type="submit"] {
            background: var(--accent);
            color: #fff;
            border-color: transparent;
        }
        .modal-actions button[type="submit"]:hover { background: var(--accent-hover); }

        /* ─── Типографика markdown ──────────────────────── */
        .prose { color: var(--text); }
        .prose > :first-child { margin-top: 0; }

        .prose h1 {
            font-size: clamp(1.875rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-top: 0;
            margin-bottom: 1rem;
            line-height: 1.15;
            color: var(--text-heading);
        }
        .prose h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 2.5rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-heading);
        }
        .prose h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 0.6rem;
            color: var(--text-heading);
        }
        .prose h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--text-heading);
        }
        .prose h5, .prose h6 {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-heading);
        }
        .prose h6 { color: var(--muted); }

        .prose p {
            margin-top: 0;
            margin-bottom: 1.4em;
        }
        .prose h1 + p {
            font-size: 1.075rem;
            line-height: 1.8;
            color: var(--text);
        }

        .prose a {
            color: var(--accent);
            font-weight: 500;
            text-decoration: underline;
            text-decoration-color: var(--accent-soft);
            text-underline-offset: 3px;
            text-decoration-thickness: 1px;
            transition: color 0.15s ease, text-decoration-color 0.15s ease;
        }
        .prose a:hover {
            color: var(--accent-hover);
            text-decoration-color: var(--accent-hover);
        }

        .prose strong { font-weight: 600; color: var(--text-heading); }
        .prose em { font-style: italic; }
        .prose del { color: var(--muted); text-decoration: line-through; }

        .prose mark {
            background: var(--accent-soft);
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

        .prose hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 2.5rem 0;
        }

        .prose ul, .prose ol {
            margin-top: 0;
            margin-bottom: 1.4em;
            padding-left: 1.75em;
        }
        .prose li {
            margin-bottom: 0.375em;
            padding-left: 0.25em;
        }
        .prose li > p { margin-bottom: 0.5em; }
        .prose li::marker { color: var(--muted); }
        .prose ol li::marker { font-size: 0.9em; font-weight: 600; }
        .prose :is(ul, ol) :is(ul, ol) {
            margin-top: 0.375em;
            margin-bottom: 0.375em;
        }
        .prose .task-list-item {
            list-style: none;
            padding-left: 0;
            margin-left: -0.25em;
        }

        .prose blockquote {
            margin: 1.5em 0;
            padding: 0.25em 0 0.25em 1.1em;
            border-left: 3px solid var(--accent);
            color: var(--muted);
        }
        .prose blockquote > :last-child { margin-bottom: 0; }

        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-sm);
        }

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
        }
        .prose pre > code::-webkit-scrollbar { height: 5px; }
        .prose pre > code::-webkit-scrollbar-track { background: transparent; }
        .prose pre > code::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .prose table {
            border-collapse: collapse;
            width: 100%;
            margin: 1.5em 0;
            font-size: 0.95em;
        }
        .prose th, .prose td {
            border: 1px solid var(--border);
            padding: 10px 12px;
            text-align: left;
        }
        .prose th {
            background: var(--surface);
            font-weight: 600;
            color: var(--text-heading);
        }
        .prose tr:nth-child(even) { background: var(--surface); }

        .prose input[type="checkbox"] {
            accent-color: var(--accent);
            margin-right: 8px;
        }
    </style>
</head>
<body data-folders-remember="<?= htmlspecialchars($foldersRemember, ENT_QUOTES, 'UTF-8') ?>">
<nav>
    <h2><?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if ($protectionEnabled): ?>
        <?php if (!$isAuthorized): ?>
            <form method="post" class="auth-form" id="authFormTop">
                <input type="password" name="password" placeholder="<?= htmlspecialchars($auth->passwordFieldPlaceholder(), ENT_QUOTES, 'UTF-8') ?>" autocomplete="current-password">
                <button type="submit">Войти</button>
            </form>
        <?php else: ?>
            <a class="logout-link" href="?logout=1">Выйти 🔓</a>
        <?php endif; ?>
    <?php endif; ?>

    <ul>
        <?php if ($hasDocuments): ?>
            <?= $tree ?>
        <?php else: ?>
            <li style="color: var(--muted); padding: 10px;">Нет .md файлов</li>
        <?php endif; ?>
    </ul>
</nav>
<button class="theme-toggle" id="themeToggle" title="Переключить тему">🌙</button>
<main>
    <?= $debugInfo ?>
    <?php if ($current): ?>
        <div class="breadcrumbs" style="display:none">
            <?php
            $crumbs = [];
            foreach ($breadcrumbs as $crumb) {
                if ($crumb['isFile']) {
                    $crumbs[] = htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8');
                } else {
                    $crumbs[] = '<a href="?file=' . htmlspecialchars($crumb['path'], ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') . '</a>';
                }
            }
            echo implode('<span>/</span>', $crumbs);
            ?>
        </div>

        <h1 style="display:none"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="meta" style="display:none">
            Файл: <?= htmlspecialchars($current->relative, ENT_QUOTES, 'UTF-8') ?> •
            Обновлён: <?= date('Y-m-d H:i', $current->mtime) ?>
        </div>
        <article class="prose">
            <?= $content ?>
        </article>
    <?php elseif (!$hasDocuments): ?>
        <p style="color: var(--muted);">Положи .md файлы в эту папку</p>
    <?php else: ?>
        <p style="color: var(--muted);">Файл не выбран</p>
    <?php endif; ?>
</main>

<script data-name="logout-cleanup">
    (function(){
        const params = new URLSearchParams(location.search);

        if (params.get('logged_out') !== '1') return;

        // 1. Чистим пароль из localStorage
        try { localStorage.removeItem('md_docs_auth'); } catch (e) {}

        // 2. На всякий случай чистим все поля с паролем на странице
        document.querySelectorAll('input[name="password"]').forEach(inp => {
            inp.value = '';
            inp.setAttribute('autocomplete', 'new-password');
        });

        // 3. Убираем ?logged_out=1 из адресной строки,
        //    чтобы F5 не запускал очистку повторно и URL был красивым
        params.delete('logged_out');
        const cleanUrl = location.pathname
            + (params.toString() ? '?' + params.toString() : '')
            + location.hash;
        history.replaceState(null, '', cleanUrl);
    })();
</script>
<script data-name="Nav Details">
(function () {
    const KEY = 'mdvault_folders';
    const REMEMBER = ['per_folder', 'single'];

    const mode = document.body.dataset.foldersRemember;
    const remember = REMEMBER.includes(mode);

    const all = Array.from(document.querySelectorAll('nav details[data-path]'));
    const byPath = new Map();
    all.forEach(d => byPath.set(d.dataset.path, d));

    // Соседи по уровню: те же <li> в том же списке. Вложенные папки не трогаем —
    // «одна открытая папка на уровень» не значит «одна во всём дереве».
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

        // Сохранённое состояние важнее того, что нарисовал сервер: папка,
        // которую пользователь закрыл в прошлый раз, не должна распахиваться
        // снова сама.
        all.forEach(d => {
            const saved = state[d.dataset.path];
            if (typeof saved === 'boolean') d.open = saved;
        });
    }

    // Ветка с открытым документом раскрывается всегда — иначе активный пункт
    // не был бы виден. Путь строится из ?file=, поэтому вложенные папки тоже.
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

    // «Не больше одной открытой папки на уровне» держим и при загрузке, а не
    // только по клику: состояние могло остаться от режима per_folder. Побеждает
    // папка из активной ветки, иначе — первая по порядку в разметке.
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

    // Пишем фактическое состояние, а не «намерение»: событие toggle срабатывает
    // и на клике, и на программной установке open, поэтому в localStorage
    // попадает ровно то, что видит пользователь.
    all.forEach(d => {
        d.addEventListener('toggle', () => {
            state[d.dataset.path] = d.open;

            if (d.open && mode === 'single') {
                siblings(d).forEach(s => { s.open = false; });
            }

            save();
        });
    });

    // Итог загрузки записываем сразу: иначе в localStorage осталось бы то, что
    // было до правок (например, две открытые папки одного уровня), и следующая
    // загрузка снова разбирала бы его заново.
    all.forEach(d => { state[d.dataset.path] = d.open; });
    save();
})();
</script>
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
            shadow_sm: '--shadow-sm',
            shadow_lg: '--shadow-lg'
        };

        const toggle = document.getElementById('themeToggle');

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const root = document.documentElement.style;

            // Сбрасываем возможные inline-значения
            Object.values(CSS_MAP).forEach(v => root.removeProperty(v));

            const vars = THEME_CONFIG[theme] || {};
            for (const [key, value] of Object.entries(vars)) {
                if (CSS_MAP[key]) {
                    root.setProperty(CSS_MAP[key], value);
                }
            }
            toggle.textContent = theme === 'light' ? '☀️' : '🌙';
            localStorage.setItem('theme', theme);
        }

        const saved = localStorage.getItem('theme');
        applyTheme(saved === 'light' || saved === 'dark' ? saved : THEME_CONFIG.default);

        toggle.addEventListener('click', () => {
            const next = toggle.textContent === '🌙' ? 'light' : 'dark';
            applyTheme(next);
        });
    })();
</script>
<script data-name="auth">
    (function(){
        const modal     = document.getElementById('authModal');
        const modalForm = document.getElementById('authFormModal');
        const redirectF = document.getElementById('redirectFile');
        const modalPwd  = document.getElementById('modalPassword');
        const cancel    = document.getElementById('modalCancel');

        // Перехват кликов по защищённым ссылкам
        document.querySelectorAll('nav a[data-protected="1"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                if (!modal) return;
                redirectF.value = a.dataset.file || '';
                modal.hidden = false;
                setTimeout(() => modalPwd && modalPwd.focus(), 30);
            });
        });

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

        // Автозаполнение формы из localStorage (fallback, если куки отключены)
        const saved = localStorage.getItem('md_docs_auth');
        if (saved) {
            const topInput = document.querySelector('#authFormTop input[name="password"]');
            if (topInput && !topInput.value) topInput.value = saved;
        }

        // Если сервер только что сохранил пароль — дублируем в localStorage
        <?php if (!empty($saveToLocalStorage)): ?>
        try { localStorage.setItem('md_docs_auth', <?= json_encode($submittedPassword) ?>); } catch(e) {}
        <?php endif; ?>
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