<?php
/**
 * Разметка страницы MarkVault.
 *
 * Шаблон: ожидает переменные, которые готовит App\Application::renderTemplate()
 * ($title, $navTitle, $tree, $current, $content, $breadcrumbs, $auth,
 * $protectionEnabled, $isAuthorized, $darkVars, $lightVars, $defaultTheme,
 * $hasDocuments, $saveToLocalStorage, $submittedPassword, $debugInfo, $foldersRemember).
 *
 * Логики здесь нет — только HTML, CSS и клиентский JS.
 */
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg: #0f1115;
            --panel: #161920;
            --text: #e6e8eb;
            --muted: #9aa0a6;
            --accent: #5b9df9;
            --code-bg: #1e222a;
            --border: #2a2f3a;
        }
        :root {
        <?= \App\Config::toCssVariables($darkVars) ?>
            color-scheme: dark;
        }
        html[data-theme="light"] {
        <?= \App\Config::toCssVariables($lightVars) ?>
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
        }
        nav {
            background: var(--panel);
            border-right: 1px solid var(--border);
            padding: 16px;
            overflow-y: auto;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        nav h2 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            margin: 0 0 12px;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        nav li { margin: 4px 0; }
        nav li.dir { margin: 8px 0 4px; }
        nav a {
            display: block;
            padding: 6px 10px;
            border-radius: 6px;
            color: var(--text);
            text-decoration: none;
            transition: background 0.15s ease;
        }
        nav a:hover { background: rgba(255,255,255,0.06); }
        nav a.active {
            background: rgba(91,157,249,0.15);
            color: var(--accent);
        }
        nav details > summary {
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            color: var(--muted);
            font-weight: 600;
            user-select: none;
        }
        nav details > summary:hover {
            background: rgba(255,255,255,0.04);
            color: var(--text);
        }
        nav details > summary::before {
            content: "📁 ";
        }
        nav details[open] > summary::before {
            content: "📂 ";
        }
        main {
            padding: 24px 32px;
            max-width: 900px;
        }
        h1, h2, h3, h4, h5, h6 {
            margin: 1.2em 0 0.6em;
            line-height: 1.3;
        }
        h1 { margin-top: 0; }
        p { line-height: 1.65; margin: 0.9em 0; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        code {
            background: var(--code-bg);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: ui-monospace, SFMono, Menlo, Consolas, monospace;
            font-size: 0.92em;
        }
        pre {
            background: var(--code-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 16px;
            overflow-x: auto;
        }
        pre code {
            background: none;
            padding: 0;
            display: block;
            white-space: pre;
        }
        ul, ol { padding-left: 1.6em; }
        li { margin: 0.4em 0; }
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

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 1.2em 0;
            font-size: 0.95em;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background: rgba(255,255,255,0.03);
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: rgba(255,255,255,0.02);
        }

        input[type="checkbox"] { margin-right: 8px; }

        blockquote {
            border-left: 3px solid var(--accent);
            margin: 1em 0;
            padding: 0.5em 0 0.5em 1em;
            color: var(--muted);
        }

        .theme-toggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: var(--panel);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .theme-toggle:hover {
            background: rgba(0,0,0,0.05);
        }

        /* Auth */

        .auth-form {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }
        .auth-form input {
            flex: 1;
            min-width: 0;
            background: var(--code-bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            outline: none;
        }
        .auth-form input:focus { border-color: var(--accent); }
        .auth-form button {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 13px;
            cursor: pointer;
        }
        .auth-form button:hover { filter: brightness(1.1); }

        .logout-link {
            display: inline-block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 14px;
            text-decoration: none;
        }
        .logout-link:hover { color: var(--accent); }

        nav li.locked a { opacity: 0.75; }
        .lock-badge {
            font-size: 11px;
            margin-left: 4px;
            opacity: 0.9;
        }

        /* Модалка */
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
            border-radius: 12px;
            padding: 22px 24px;
            width: 360px;
            max-width: 92vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .modal h3 { margin: 0 0 6px; }
        .modal-hint { color: var(--muted); font-size: 13px; margin: 0 0 14px; }
        .modal input[type="password"] {
            width: 100%;
            background: var(--code-bg);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        .modal input[type="password"]:focus { border-color: var(--accent); }
        .modal-actions {
            display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px;
        }
        .modal-actions button {
            padding: 8px 14px; border-radius: 8px; cursor: pointer;
            font-size: 13px; border: 1px solid var(--border);
            background: var(--code-bg); color: var(--text);
        }
        .modal-actions button[type="submit"] {
            background: var(--accent); color: #fff; border-color: transparent;
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
        <article>
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
            text: '--text',
            muted: '--muted',
            accent: '--accent',
            code_bg: '--code-bg',
            border: '--border'
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