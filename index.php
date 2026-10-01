<?php
declare(strict_types=1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/Parsedown.php')) {
    require __DIR__ . '/Parsedown.php';
} else {
    die('Parsedown не найден. run: composer require erusev/parsedown');
}

$CONTENT_DIR = __DIR__;
$DEFAULT_FILE = 'README.md';
$HIDE_FILES = [
    'index.php',
    'vendor',
    'composer.json',
    'composer.lock',
    '.git',
    '.gitignore',
    '.gitattributes',
    'markvault.phar'
];

/**
 * Проверяет расширение у файла
 * @param string $path
 *
 * @return bool
 */
function isMarkdownFile(string $path): bool {
    return is_file($path) && preg_match('/\.md$/i', $path);
}

/**
 * Рекурсивный поиск файлов в каталоге
 * @param string $dir
 * @param string $basePath
 * @param array $hide
 *
 * @return array
 */
function scanMarkdownFilesRecursive(string $dir, string $basePath = '', array $hide = []): array {
    $files = [];
    $items = @scandir($dir);
    if ($items === false) return $files;

    foreach ($items as $item) {
        if (in_array($item, $hide, true) || str_starts_with($item, '.')) continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $relativePath = $basePath === '' ? $item : $basePath . '/' . $item;

        if (is_dir($fullPath)) {
            $subFiles = scanMarkdownFilesRecursive($fullPath, $relativePath, $hide);
            $files = array_merge($files, $subFiles);
        } elseif (isMarkdownFile($fullPath)) {
            $files[] = [
                'name' => pathinfo($item, PATHINFO_FILENAME),
                'file' => $item,
                'path' => $fullPath,
                'relative' => $relativePath,
                'mtime' => filemtime($fullPath),
            ];
        }
    }

    usort($files, fn($a, $b) => strnatcmp($a['relative'], $b['relative']));
    return $files;
}

function getRequestedFile(array $files, string $default): ?array {
    $requested = $_GET['file'] ?? $default;

    foreach ($files as $f) {
        if ($f['relative'] === $requested || $f['file'] === $requested) {
            return $f;
        }
    }

    return $files[0] ?? null;
}

function buildPathHierarchy(array $files): array {
    $tree = [];
    foreach ($files as $f) {
        $parts = explode('/', $f['relative']);
        $current = &$tree;
        $pathSoFar = '';

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];
            $pathSoFar = $pathSoFar === '' ? $part : $pathSoFar . '/' . $part;

            if (!isset($current[$part])) {
                $current[$part] = [
                    '_type' => 'dir',
                    '_path' => $pathSoFar,
                    '_children' => [],
                ];
            }
            $current = &$current[$part]['_children'];
        }

        $fileName = $parts[count($parts) - 1];
        $current[] = [
            '_type' => 'file',
            '_data' => $f,
        ];
    }
    return $tree;
}

function renderNavTree(array $tree, string $currentRelative, int $depth = 0): string {
    $html = [];
    $indent = str_repeat('  ', $depth);

    foreach ($tree as $key => $node) {
        if ($node['_type'] === 'dir') {
            $dirName = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $html[] = "{$indent}<li class=\"dir\">";
            $html[] = "{$indent}  <details open>";
            $html[] = "{$indent}    <summary>{$dirName}</summary>";
            $html[] = "{$indent}    <ul>";
            $html[] = renderNavTree($node['_children'], $currentRelative, $depth + 2);
            $html[] = "{$indent}    </ul>";
            $html[] = "{$indent}  </details>";
            $html[] = "{$indent}</li>";
        } elseif ($node['_type'] === 'file') {
            $f = $node['_data'];
            $isActive = $currentRelative !== '' && $f['relative'] === $currentRelative;
            $activeClass = $isActive ? ' class="active"' : '';
            $href = '?file=' . htmlspecialchars($f['relative'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8');
            $html[] = "{$indent}<li><a href=\"{$href}\"{$activeClass}>{$label}</a></li>";
        }
    }

    return implode("\n", $html);
}

function convertInternalLinks(string $html, string $currentFile): string {
    return preg_replace_callback(
        '/<a\s+href="([^"]*\.md(?:#[^"]*)?)"([^>]*)>/i',
        function($matches) use ($currentFile) {
            $href = $matches[1];
            $rest = $matches[2];
            
            if (str_contains($href, '?file=')) {
                return $matches[0];
            }
            
            $parts = explode('#', $href, 2);
            $path = $parts[0];
            $anchor = isset($parts[1]) ? '#' . $parts[1] : '';
            
            $currentDir = dirname($currentFile);
            if ($currentDir === '.') {
                $currentDir = '';
            }
            
            if (!str_starts_with($path, '/') && !str_starts_with($path, 'http')) {
                $fullPath = $currentDir === '' ? $path : $currentDir . '/' . $path;
                $fullPath = normalizePath($fullPath);
                $href = '?file=' . $fullPath . $anchor;
            } else {
                $href = '?file=' . ltrim($path, '/') . $anchor;
            }
            
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $rest . '>';
        },
        $html
    );
}

function normalizePath(string $path): string {
    $parts = explode('/', $path);
    $result = [];
    
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($result);
        } else {
            $result[] = $part;
        }
    }
    
    return implode('/', $result);
}

$parsedown = new Parsedown();
$parsedown->setMarkupEscaped(false);
$parsedown->setBreaksEnabled(true);

$files = scanMarkdownFilesRecursive($CONTENT_DIR, '', $HIDE_FILES);
$current = getRequestedFile($files, $DEFAULT_FILE);
$tree = buildPathHierarchy($files);

$content = '';
$title = 'Docs';
$breadcrumbs = [];

if ($current) {
    $raw = file_get_contents($current['path']);
    if ($raw !== false) {
        $content = $parsedown->text($raw);
        $content = convertInternalLinks($content, $current['relative']);
        
        $firstLine = trim(explode("\n", $raw)[0] ?? '');
        $title = preg_match('/^#\s+(.*)/', $firstLine, $m) ? trim($m[1]) : $current['name'];

        $parts = explode('/', $current['relative']);
        for ($i = 0; $i < count($parts); $i++) {
            $path = implode('/', array_slice($parts, 0, $i + 1));
            $breadcrumbs[] = [
                'name' => $parts[$i],
                'path' => $path,
                'isFile' => $i === count($parts) - 1,
            ];
        }
    }
}

$debugInfo = '';
if (empty($files)) {
    $debugInfo = '<div style="color: #f59e0b; padding: 20px;">'
        . '⚠️ MD-файлы не найдены в ' . htmlspecialchars($CONTENT_DIR, ENT_QUOTES, 'UTF-8')
        . '<br>Проверь права доступа и наличие .md файлов'
        . '</div>';
}
?>
<!DOCTYPE html>
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

    </style>
</head>
<body>
<nav>
    <h2>Документы</h2>
    <ul>
        <?php if (!empty($tree)): ?>
            <?= renderNavTree($tree, $current ? $current['relative'] : '') ?>
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
            foreach ($breadcrumbs as $i => $crumb) {
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
            Файл: <?= htmlspecialchars($current['relative'], ENT_QUOTES, 'UTF-8') ?> •
            Обновлён: <?= date('Y-m-d H:i', $current['mtime']) ?>
        </div>
        <article>
            <?= $content ?>
        </article>
    <?php elseif (empty($files)): ?>
        <p style="color: var(--muted);">Положи .md файлы в эту папку</p>
    <?php else: ?>
        <p style="color: var(--muted);">Файл не выбран</p>
    <?php endif; ?>
</main>

<script>
(function(){
    const params = new URLSearchParams(location.search);
    const file = params.get('file');
    if (!file) return;

    document.querySelectorAll('nav details').forEach(details => {
        const link = details.parentElement.querySelector('a');
        if (link) {
            const url = new URL(link.href, location.origin);
            const linkPath = url.searchParams.get('file');
            if (linkPath && file.startsWith(linkPath + '/')) {
                details.open = true;
            }
        }
    });
})();
</script>
<script>
(function(){
    const toggle = document.getElementById('themeToggle');
    const darkRoot = {
        '--bg': '#0f1115',
        '--panel': '#161920',
        '--text': '#e6e8eb',
        '--muted': '#9aa0a6',
        '--accent': '#5b9df9',
        '--code-bg': '#1e222a',
        '--border': '#2a2f3a',
    };
    const lightRoot = {
        '--bg': '#faf9f7',
        '--panel': '#f2f0eb',
        '--text': '#232323',
        '--muted': '#6b6b6b',
        '--accent': '#0366d6',
        '--code-bg': '#f6f8fa',
        '--border': '#e1e4e8',
    };

    function setTheme(theme) {
        const root = document.documentElement.style;
        const colors = theme === 'light' ? lightRoot : darkRoot;
        for (const [key, value] of Object.entries(colors)) {
            root.setProperty(key, value);
        }
        toggle.textContent = theme === 'light' ? '☀️' : '🌙';
        localStorage.setItem('theme', theme);
    }

    const saved = localStorage.getItem('theme');
    if (saved) {
        setTheme(saved);
    }

    toggle.addEventListener('click', () => {
        const current = toggle.textContent === '🌙' ? 'dark' : 'light';
        setTheme(current === 'dark' ? 'light' : 'dark');
    });
})();
</script>
</body>
</html>