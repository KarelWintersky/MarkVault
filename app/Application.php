<?php
declare(strict_types=1);

namespace App;

use App\Auth\Authenticator;
use App\Auth\Protection;
use App\Config\ConfigFactory;
use App\Config\Theme;
use App\Content\Document;
use App\Content\DocumentScanner;
use App\Content\DocumentSelector;
use App\Content\NavTreeBuilder;
use App\Content\SortRules;
use App\Content\TitleResolver;
use App\Http\Request;
use App\Markdown\MarkdownRenderer;
use App\Support\Path;
use App\View\Layout;
use App\View\NavRenderer;
use App\View\Page;
use Arris\AppConfig;

/**
 * Точка сборки MarkVault: читает конфиг, разбирает запрос, собирает страницу.
 *
 * Здесь же обрабатываются редиректы (вход/выход) — они меняют поток запроса,
 * поэтому живут в приложении, а не в отдельном классе.
 */
final class Application
{
    private AppConfig $config;
    /** @var array<string,mixed> */
    private array $settings;
    private Theme $theme;
    private Protection $protection;
    private Authenticator $auth;
    private Request $request;

    /**
     * @param string $baseDir корень приложения — каталог с index.php
     * @param Request|null $request подменяется в тестах
     * @param string|null $configFile путь к конфигу, по умолчанию «$baseDir/config.yaml»
     */
    public function __construct(
        private readonly string $baseDir,
        ?Request $request = null,
        ?string $configFile = null,
    ) {
        $this->request = $request ?? Request::fromGlobals();
        $this->config = ConfigFactory::load($baseDir, $configFile);
        $this->settings = $this->config->get('*');

        $this->theme = Theme::fromConfig($this->settings);
        $this->protection = Protection::fromConfig($this->settings);
        $this->auth = new Authenticator($this->request, $this->protection);
    }

    /**
     * Обрабатывает запрос и печатает страницу.
     */
    public function run(): void
    {
        echo $this->handle();
    }

    /**
     * То же, что run(), но возвращает HTML — удобно для тестов.
     */
    public function handle(): string
    {
        $isAuthorized = $this->auth->isAuthorized();

        // Успешный вход с формы: уводим на запрошенный документ.
        if ($isAuthorized && $this->request->isPost()) {
            $redirectFile = $this->request->post('redirect_file');

            if ($redirectFile !== null && $redirectFile !== '') {
                $this->redirect('?file=' . rawurlencode($redirectFile));
            }
        }

        // Запоминаем пароль только если он реально пришёл формой.
        $savePassword = $isAuthorized && $this->auth->isPasswordSubmitted();
        if ($savePassword) {
            $this->auth->remember();
        }

        if ($this->auth->isLogoutRequested()) {
            $this->auth->forget();

            // Метка logged_out=1 нужна клиентскому JS, чтобы почистить localStorage.
            $this->redirect($this->request->uriPath() . '?logged_out=1');
        }

        return $this->render($isAuthorized, $savePassword);
    }

    private function render(bool $isAuthorized, bool $savePassword): string
    {
        $titles = TitleResolver::fromConfig($this->settings);

        $scanner = new DocumentScanner($this->contentDir(), $titles, $this->hiddenNames());
        $documents = SortRules::fromConfig($this->settings)->apply($scanner->scan());

        $current = (new DocumentSelector($this->defaultFile()))
            ->select($documents, $this->request->query('file'));

        [$title, $content, $breadcrumbs] = $this->buildDocumentView($current, $titles, $isAuthorized);

        return (new Layout(new NavRenderer($this->protection, $isAuthorized)))->render(new Page(
            $this->theme,
            $this->protection,
            $this->stringSetting('site.title', 'Docs'),
            $this->stringSetting('site.nav_title', 'Документы'),
            $title,
            $documents === [] ? $this->notice() : '',
            $documents !== [],
            (new NavTreeBuilder($titles))->build($documents),
            $current,
            $content,
            $breadcrumbs,
            $isAuthorized,
            $savePassword,
            $this->auth->submittedPassword(),
        ));
    }

    /**
     * Заголовок, HTML и «хлебные крошки» открытого документа.
     *
     * @return array{0:string,1:string,2:array<int,array{name:string,path:string,isFile:bool}>}
     */
    private function buildDocumentView(
        ?Document $document,
        TitleResolver $titles,
        bool $isAuthorized,
    ): array {
        $title = 'Docs';
        $content = '';
        $breadcrumbs = [];

        if ($document === null) {
            return [$title, $content, $breadcrumbs];
        }

        if ($this->protection->protects($document->relative) && !$isAuthorized) {
            return ['Файл защищён', $content, $breadcrumbs];
        }

        $raw = file_get_contents($document->path);
        if ($raw === false) {
            return [$title, $content, $breadcrumbs];
        }

        $renderer = new MarkdownRenderer();

        return [
            $renderer->title($raw, $document->name),
            $renderer->render($raw, $document->relative),
            $this->buildBreadcrumbs($document, $titles),
        ];
    }

    /**
     * @return array<int,array{name:string,path:string,isFile:bool}>
     */
    private function buildBreadcrumbs(Document $document, TitleResolver $titles): array
    {
        $parts = explode('/', $document->relative);
        $lastIndex = count($parts) - 1;

        $breadcrumbs = [];

        foreach ($parts as $index => $part) {
            $path = implode('/', array_slice($parts, 0, $index + 1));
            $isFile = $index === $lastIndex;

            $breadcrumbs[] = [
                'name' => $isFile ? $document->name : $titles->resolve($path . '/', $part),
                'path' => $path,
                'isFile' => $isFile,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Каталог с документами. Относительный «content» разрешается от корня
     * приложения, поэтому конфиг не зависит от текущего каталога процесса.
     */
    private function contentDir(): string
    {
        $content = $this->stringSetting('content', $this->baseDir);

        if ($content === '') {
            return $this->baseDir;
        }

        if (!Path::isAbsolute($content)) {
            $content = $this->baseDir . '/' . $content;
        }

        return Path::normalize($content);
    }

    private function defaultFile(): string
    {
        return $this->stringSetting('site.default_file', 'README.md');
    }

    /**
     * Имена файлов и папок, которые не показываем в навигации.
     *
     * @return array<int,string>
     */
    private function hiddenNames(): array
    {
        $hide = $this->config->get('hide', []);

        if (!is_array($hide)) {
            return [];
        }

        return array_values(array_map(strval(...), $hide));
    }

    /**
     * Предупреждение вместо навигации, если markdown-файлов не нашлось.
     */
    private function notice(): string
    {
        $dir = htmlspecialchars($this->contentDir(), ENT_QUOTES, 'UTF-8');

        return '<div style="color: #f59e0b; padding: 20px;">'
            . '⚠️ MD-файлы не найдены в ' . $dir
            . '<br>Проверь права доступа и наличие .md файлов'
            . '</div>';
    }

    private function stringSetting(string $key, string $default): string
    {
        $value = $this->config->get($key, $default);

        return is_array($value) ? $default : (string)$value;
    }

    /**
     * Редирект с немедленным выходом — дальше рендера уже не будет.
     */
    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}