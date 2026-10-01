<?php
declare(strict_types=1);

if (!defined("PHAR_PATH")) { define("PHAR_PATH", __DIR__ . '/markvault.phar'); }

if (is_file(PHAR_PATH)) {
    require_once PHAR_PATH;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class MarkVault
{
    /**
     * Конфиг по-умолчанию
     * @return array
     */
    protected static function getDefaultConfig():array
    {
        return [
            'site' => [
                'title' => 'Docs',
                'nav_title' => 'Документы',
                'default_file' => 'README.md',
            ],
            'content'   =>  __DIR__,
            'hide' => [
                'index.php', 'vendor', 'composer.json', 'composer.lock',
                '.git', '.gitignore', 'test.php', 'config.yaml',
            ],
            'theme' => [
                'default' => 'dark',
                'dark' => [
                    'bg' => '#0f1115',
                    'panel' => '#161920',
                    'text' => '#e6e8eb',
                    'muted' => '#9aa0a6',
                    'accent' => '#5b9df9',
                    'code_bg' => '#1e222a',
                    'border' => '#2a2f3a',
                ],
                'light' => [
                    'bg' => '#faf9f7',
                    'panel' => '#f2f0eb',
                    'text' => '#232323',
                    'muted' => '#6b6b6b',
                    'accent' => '#0366d6',
                    'code_bg' => '#f6f8fa',
                    'border' => '#e1e4e8',
                ],
            ],
        ];
    }
}

/**
 * Загрузка конфигурации из config.yaml с дефолтами.
 */
function loadConfig(string $path): array {
    $defaults = [
        'site' => [
            'title' => 'Docs',
            'nav_title' => 'Документы',
            'default_file' => 'README.md',
        ],
        'content'   =>  __DIR__,
        'hide' => [
            'index.php', 'vendor', 'composer.json', 'composer.lock',
            '.git', '.gitignore', 'test.php', 'config.yaml',
        ],
        'theme' => [
            'default' => 'dark',
            'dark' => [
                'bg' => '#0f1115',
                'panel' => '#161920',
                'text' => '#e6e8eb',
                'muted' => '#9aa0a6',
                'accent' => '#5b9df9',
                'code_bg' => '#1e222a',
                'border' => '#2a2f3a',
            ],
            'light' => [
                'bg' => '#faf9f7',
                'panel' => '#f2f0eb',
                'text' => '#232323',
                'muted' => '#6b6b6b',
                'accent' => '#0366d6',
                'code_bg' => '#f6f8fa',
                'border' => '#e1e4e8',
            ],
        ],
    ];

    if (!is_file($path)) {
        return $defaults;
    }

    try {
        $loaded = Yaml::parseFile($path);
    } catch (ParseException $e) {
        // При ошибке парсинга — используем дефолты
        return $defaults;
    }

    if (!is_array($loaded)) {
        return $defaults;
    }

    return array_replace_recursive($defaults, $loaded);
}

/**
 * Защита включена, если есть секция protected и в ней непустой пароль.
 * Пустая строка, "0", пробелы, null, отсутствие ключа — выключено.
 */
function protectionEnabled(array $config): bool {
    if (empty($config['protected']) || !is_array($config['protected'])) {
        return false;
    }

    if (!array_key_exists('password', $config['protected'])) {
        return false;
    }

    $password = $config['protected']['password'];

    // Разрешаем только строку/число, всё остальное — выключено
    if (!is_string($password) && !is_int($password) && !is_float($password)) {
        return false;
    }

    // Приводим к строке и обрезаем пробелы — пустая строка = выключено.
    // Явно: "0" — это валидный пароль, "" и "   " — нет.
    return trim((string)$password) !== '';
}

/**
 * Читает секцию sort и нормализует в:
 * [
 *   'before' => ['README.md' => 0, '00-overview.md' => 1],
 *   'after'  => ['QUESTIONS.md' => 0, '99-changelog.md' => 1],
 * ]
 *
 * Если файл встречается в обеих секциях — он остаётся в 'before'
 * и удаляется из 'after'.
 */
function getSortRules(array $config): array {
    $sort = $config['sort'] ?? [];
    if (!is_array($sort)) {
        return ['before' => [], 'after' => []];
    }

    $normalize = static function ($list): array {
        if (!is_array($list)) return [];
        $out = [];
        $i = 0;
        foreach ($list as $path) {
            $path = ltrim(str_replace('\\', '/', (string)$path), '/');
            if ($path === '') continue;
            if (!isset($out[$path])) {
                $out[$path] = $i++;
            }
        }
        return $out;
    };

    $before = $normalize($sort['before_all'] ?? []);
    $after  = $normalize($sort['after_all']  ?? []);

    // before_all приоритетнее: удаляем пересечения из after
    foreach (array_keys($before) as $path) {
        unset($after[$path]);
    }

    return ['before' => $before, 'after' => $after];
}

/**
 * Возвращает «вес» элемента для сортировки.
 * Меньше = выше в списке.
 *
 * Диапазоны:
 *   before_all:  0 … 999        (в порядке перечисления)
 *   обычные:     1_000_000 …    (по алфавиту)
 *   after_all:   10_000_000 …   (в порядке перечисления)
 */
function getSortWeight(string $relative, array $rules): array {
    $relative = ltrim(str_replace('\\', '/', $relative), '/');

    // before_all — приоритетнее
    if (isset($rules['before'][$relative])) {
        return [0, $rules['before'][$relative]];
    }

    // Папка: "guides/" может быть задана и как "guides"
    if (isset($rules['before'][$relative . '/'])) {
        return [0, $rules['before'][$relative . '/']];
    }

    if (isset($rules['after'][$relative])) {
        return [2, $rules['after'][$relative]];
    }
    if (isset($rules['after'][$relative . '/'])) {
        return [2, $rules['after'][$relative . '/']];
    }

    return [1, 0]; // обычная группа
}

function sortFilesByRules(array $files, array $rules): array {
    usort($files, function ($a, $b) use ($rules) {
        [$groupA, $idxA] = getSortWeight($a['relative'], $rules);
        [$groupB, $idxB] = getSortWeight($b['relative'], $rules);

        if ($groupA !== $groupB) {
            return $groupA <=> $groupB;
        }

        // Внутри before_all/after_all — порядок как в конфиге
        if ($groupA !== 1) {
            return $idxA <=> $idxB;
        }

        // Внутри обычной группы — natural sort по относительному пути
        return strnatcmp($a['relative'], $b['relative']);
    });
    return $files;
}

/**
 * Проверяет, защищён ли конкретный файл (по относительному пути).
 * Поддерживает как точное совпадение файла, так и префикс папки.
 */
function isProtectedFile(string $relative, array $config): bool {
    if (!protectionEnabled($config)) {
        return false;
    }

    $files = $config['protected']['files'] ?? [];
    if (!is_array($files) || $files === []) {
        return false;
    }

    $relative = ltrim(str_replace('\\', '/', $relative), '/');

    foreach ($files as $pattern) {
        $pattern = ltrim(str_replace('\\', '/', (string)$pattern), '/');

        if ($pattern === '') {
            continue; // пустые элементы пропускаем
        }

        if (str_ends_with($pattern, '/')) {
            if (str_starts_with($relative, $pattern)) {
                return true;
            }
            continue;
        }

        if ($relative === $pattern) {
            return true;
        }

        if (str_starts_with($relative, $pattern . '/')) {
            return true;
        }
    }

    return false;
}

/**
 * Проверяет корректность введённого пароля.
 */
function checkPassword(string $input, array $config): bool {
    if (!protectionEnabled($config)) {
        return true;
    }

    $expected = trim((string)$config['protected']['password']);

    return hash_equals($expected, $input);
}

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
 *
 * @param string $dir
 * @param string $basePath
 * @param array $hide
 * @param array $titlesMap
 *
 * @return array
 */
function scanMarkdownFilesRecursive(
    string $dir,
    string $basePath = '',
    array $hide = [],
    array $titlesMap = []
): array {
    $files = [];
    $items = @scandir($dir);
    if ($items === false) return $files;

    foreach ($items as $item) {
        if (in_array($item, $hide, true) || str_starts_with($item, '.')) continue;

        $fullPath     = $dir . DIRECTORY_SEPARATOR . $item;
        $relativePath = $basePath === '' ? $item : $basePath . '/' . $item;

        if (is_dir($fullPath)) {
            $subFiles = scanMarkdownFilesRecursive($fullPath, $relativePath, $hide, $titlesMap);
            $files = array_merge($files, $subFiles);
        } elseif (isMarkdownFile($fullPath)) {
            $fallback = pathinfo($item, PATHINFO_FILENAME);
            $name     = resolveTitle($relativePath, $titlesMap, $fallback);

            $files[] = [
                'name'     => $name,
                'file'     => $item,
                'path'     => $fullPath,
                'relative' => $relativePath,
                'mtime'    => filemtime($fullPath),
            ];
        }
    }

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

function buildPathHierarchy(array $files, array $titlesMap = []): array {
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
                    '_type'     => 'dir',
                    '_path'     => $pathSoFar,
                    '_label'    => resolveTitle($pathSoFar . '/', $titlesMap, $part),
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

function renderNavTree(array $tree, string $currentRelative, array $config, bool $isAuthorized, int $depth = 0): string {
    $html = [];
    $indent = str_repeat('  ', $depth);
    $lockIcon = (string)($config['protected']['lock_icon'] ?? '🔒');

    foreach ($tree as $key => $node) {
        if ($node['_type'] === 'dir') {
            $dirName = htmlspecialchars($node['_label'] ?? $key, ENT_QUOTES, 'UTF-8');
            $html[] = "{$indent}<li class=\"dir\">";
            $html[] = "{$indent}  <details open>";
            $html[] = "{$indent}    <summary>{$dirName}</summary>";
            $html[] = "{$indent}    <ul>";
            $html[] = renderNavTree($node['_children'], $currentRelative, $config, $isAuthorized, $depth + 2);
            $html[] = "{$indent}    </ul>";
            $html[] = "{$indent}  </details>";
            $html[] = "{$indent}</li>";
        } elseif ($node['_type'] === 'file') {
            $f = $node['_data'];
            $isActive = $currentRelative !== '' && $f['relative'] === $currentRelative;
            $activeClass = $isActive ? ' class="active"' : '';

            $isProtected = isProtectedFile($f['relative'], $config);
            $locked = $isProtected && !$isAuthorized;

            $href = '?file=' . htmlspecialchars($f['relative'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8');

            $liClass = $locked ? ' class="locked"' : '';

            // data-protected нужен JS для перехвата клика
            $dataAttr = $locked
                ? ' data-protected="1" data-file="' . htmlspecialchars($f['relative'], ENT_QUOTES, 'UTF-8') . '"'
                : '';

            $lockBadge = $locked ? ' <span class="lock-badge" title="Защищено">' . $lockIcon . '</span>' : '';

            $html[] = "{$indent}<li{$liClass}><a href=\"{$href}\"{$activeClass}{$dataAttr}>{$label}{$lockBadge}</a></li>";
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

/**
 * Готовим CSS-переменные для обеих тем
 * @param array $vars
 * @return string
 */
function themeVarsToCss(array $vars): string {
    $map = [
        'bg'      => '--bg',
        'panel'   => '--panel',
        'text'    => '--text',
        'muted'   => '--muted',
        'accent'  => '--accent',
        'code_bg' => '--code-bg',
        'border'  => '--border',
    ];
    $out = [];
    foreach ($map as $key => $cssVar) {
        if (isset($vars[$key])) {
            $out[] = "            {$cssVar}: " . htmlspecialchars((string)$vars[$key], ENT_QUOTES, 'UTF-8') . ";";
        }
    }
    return implode("\n", $out);
}

/**
 * Достаёт карту названий из конфига. Ключи нормализуются
 * (убираем ведущие слэши, выравниваем разделители).
 */
function getTitlesMap(array $config): array {
    $titles = $config['titles'] ?? [];
    if (!is_array($titles)) {
        return [];
    }

    $map = [];
    foreach ($titles as $path => $label) {
        $path  = ltrim(str_replace('\\', '/', (string)$path), '/');
        $label = trim((string)$label);
        if ($path === '' || $label === '') {
            continue;
        }
        $map[$path] = $label;
    }
    return $map;
}

/**
 * Возвращает название для файла/папки.
 *
 * @param string $relative Относительный путь: "guides/install.md" или "guides/"
 * @param array  $titlesMap Результат getTitlesMap()
 * @param string $fallback Имя по умолчанию (для файла — без .md, для папки — имя папки)
 */
function resolveTitle(string $relative, array $titlesMap, string $fallback): string {
    $relative = ltrim(str_replace('\\', '/', $relative), '/');

    // 1. Точное совпадение (файл или папка со слэшем)
    if (isset($titlesMap[$relative])) {
        return $titlesMap[$relative];
    }

    // 2. Для файла пробуем вариант с завершающим слэшем (на случай, если
    //    в конфиге папка записана как "guides" без слэша, — но это уже покрыто ниже)
    // 3. Для папки — добавляем слэш и ищем
    if (isset($titlesMap[$relative . '/'])) {
        return $titlesMap[$relative . '/'];
    }

    return $fallback;
}

# =====================================================================================================================
$config = loadConfig(__DIR__ . '/config.yaml');

$DEFAULT_FILE = (string)($config['site']['default_file'] ?? 'README.md');
$HIDE_FILES   = (array)($config['hide'] ?? []);
$SITE_TITLE   = (string)($config['site']['title'] ?? 'Docs');
$NAV_TITLE    = (string)($config['site']['nav_title'] ?? 'Документы');
$THEME_CFG    = $config['theme'] ?? [];
$CONTENT_DIR  = $config['content'] ?? __DIR__;

$darkVars  = $THEME_CFG['dark']  ?? [];
$lightVars = $THEME_CFG['light'] ?? [];
$defaultTheme = in_array(($THEME_CFG['default'] ?? 'dark'), ['dark', 'light'], true)
    ? $THEME_CFG['default']
    : 'dark';
# === SECURITY ===

$PROTECTED_CONFIG = $config['protected'] ?? [];
$COOKIE_NAME = 'md_docs_auth';
$authCookieValue = $_COOKIE[$COOKIE_NAME] ?? '';

$submittedPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $submittedPassword = (string)$_POST['password'];
} elseif ($authCookieValue !== '') {
    // кука хранит сам пароль (base64), т.к. требование — plain text
    $decoded = base64_decode($authCookieValue, true);
    $submittedPassword = $decoded !== false ? $decoded : '';
} elseif (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $decoded = base64_decode((string)$_SERVER['HTTP_X_AUTH_TOKEN'], true);
    $submittedPassword = $decoded !== false ? $decoded : '';
}

$isAuthorized = checkPassword($submittedPassword, $config);

if ($isAuthorized && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['redirect_file'])) {
    $target = (string)$_POST['redirect_file'];
    header('Location: ?file=' . rawurlencode($target));
    exit;
}

// Если пароль пришёл через POST и верный — сохраняем куку
if ($isAuthorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    setcookie($COOKIE_NAME, base64_encode($submittedPassword), [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // и в localStorage через JS-инициализацию
    $saveToLocalStorage = true;
} else {
    $saveToLocalStorage = false;
}

// Если пользователь нажал "выйти"
if (isset($_GET['logout'])) {
    setcookie($COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Оставляем маркер logged_out=1 — его поймает JS и почистит localStorage
    $target = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $target . '?logged_out=1');
    exit;
}

# =====================================================================================================================

$parsedown = new Parsedown();
$parsedown->setMarkupEscaped(false);
$parsedown->setBreaksEnabled(true);

$TITLES_MAP = getTitlesMap($config);
$SORT_RULES = getSortRules($config);

$files = scanMarkdownFilesRecursive($CONTENT_DIR, '', $HIDE_FILES, $TITLES_MAP);
$files = sortFilesByRules($files, $SORT_RULES);
$tree = buildPathHierarchy($files, $TITLES_MAP);

$current = getRequestedFile($files, $DEFAULT_FILE);

$content = '';
$title = 'Docs';
$breadcrumbs = [];

$contentBlocked = false;

if ($current) {
    if (isProtectedFile($current['relative'], $config) && !$isAuthorized) {
        $contentBlocked = true;
        $title = 'Файл защищён';
    } else {
        $raw = file_get_contents($current['path']);
        if ($raw !== false) {
            $content = $parsedown->text($raw);
            $content = convertInternalLinks($content, $current['relative']);

            $firstLine = trim(explode("\n", $raw)[0] ?? '');
            $title = preg_match('/^#\s+(.*)/', $firstLine, $m)
                ? trim($m[1])
                : $current['name'];  // ← $current['name'] уже красивое из titles

            $parts = explode('/', $current['relative']);
            for ($i = 0; $i < count($parts); $i++) {
                $path = implode('/', array_slice($parts, 0, $i + 1));
                $isFile = $i === count($parts) - 1;

                if ($isFile) {
                    $label = $current['name']; // уже красивое
                } else {
                    $label = resolveTitle($path . '/', $TITLES_MAP, $parts[$i]);
                }

                $breadcrumbs[] = [
                    'name'   => $label,
                    'path'   => $path,
                    'isFile' => $isFile,
                ];
            }
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
    <title><?= htmlspecialchars($title !== '' ? $title : $SITE_TITLE, ENT_QUOTES, 'UTF-8') ?></title>
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
        <?= themeVarsToCss($darkVars) ?>
            color-scheme: dark;
        }
        html[data-theme="light"] {
        <?= themeVarsToCss($lightVars) ?>
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
<body>
<nav>
    <h2><?= htmlspecialchars($NAV_TITLE, ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if (protectionEnabled($config)): ?>
        <?php if (!$isAuthorized): ?>
            <form method="post" class="auth-form" id="authFormTop">
                <input type="password" name="password" placeholder="<?= htmlspecialchars($config['protected']['hint'] ?? 'Пароль', ENT_QUOTES, 'UTF-8') ?>" autocomplete="current-password">
                <button type="submit">Войти</button>
            </form>
        <?php else: ?>
            <a class="logout-link" href="?logout=1">Выйти 🔓</a>
        <?php endif; ?>
    <?php endif; ?>

    <ul>
        <?php if (!empty($tree)): ?>
            <?= renderNavTree($tree, $current ? $current['relative'] : '', $config, $isAuthorized) ?>
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

<?php if (protectionEnabled($config)): ?>
    <div class="modal-overlay" id="authModal" hidden>
        <div class="modal">
            <h3><?= htmlspecialchars($config['protected']['title'] ?? 'Файл защищён', ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="modal-hint"><?= htmlspecialchars($config['protected']['hint'] ?? 'Введите пароль для доступа', ENT_QUOTES, 'UTF-8') ?></p>
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