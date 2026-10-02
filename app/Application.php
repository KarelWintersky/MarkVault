<?php
declare(strict_types=1);

namespace App;

use App\DTO\Document;
use App\DTO\Page;
use App\Render\Content;
use App\Render\Markdown;
use App\Render\Navigation;
use App\Units\Path;
use App\Units\Request;

/**
 * Точка сборки MarkVault: читает конфиг, разбирает запрос, собирает страницу.
 *
 * Здесь же обрабатываются редиректы (вход/выход) — они меняют поток запроса,
 * поэтому живут в приложении, а не в отдельном классе.
 */
final class Application
{
    private const TEMPLATE = __DIR__ . '/template.php';

    private Config $config;
    private Content $content;
    private Auth $auth;
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
        $this->config = Config::load($baseDir, $configFile);
        $this->content = new Content($this->config, $this->contentDir());
        $this->auth = new Auth($this->config, $this->request);
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

        // Запоминаем пароль только если он реально пришёл формой. И делаем
        // это ДО редиректа: redirect() завершает процесс, поэтому кука,
        // поставленная после него, просто не ушла бы браузеру — вход
        // выглядел бы как «ничего не произошло»: страница открывается, но
        // файл снова заперт.
        $savePassword = $isAuthorized && $this->auth->isPasswordSubmitted();
        if ($savePassword) {
            $this->auth->remember();
        }

        // Успешный вход с формы: уводим на запрошенный документ.
        if ($isAuthorized && $this->request->isPost()) {
            $redirectFile = $this->request->post('redirect_file');

            if ($redirectFile !== null && $redirectFile !== '') {
                $this->redirect('?file=' . rawurlencode($redirectFile));
            }
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
        $documents = $this->content->all();
        $current = $this->content->select($this->request->query('file'));

        [$title, $content, $breadcrumbs] = $this->buildDocumentView($current, $isAuthorized);

        $nav = new Navigation($this->auth, $this->content, $isAuthorized, $this->config->foldersCollapsed());

        return $this->renderTemplate(new Page(
            $this->config,
            $this->auth,
            $this->config->string('site.title', 'Docs'),
            $this->config->string('site.nav_title', 'Документы'),
            $title,
            $documents === [] ? $this->notice() : '',
            $documents !== [],
            $nav->build($documents),
            $current,
            $content,
            $breadcrumbs,
            $isAuthorized,
            $savePassword,
            $this->auth->submittedPassword(),
        ), $nav);
    }

    /**
     * Сборка страницы целиком: готовит переменные для разметки и подключает
     * шаблон template.php. Шаблон — единственное место, где есть HTML.
     */
    private function renderTemplate(Page $page, Navigation $nav): string
    {
        $auth = $page->auth;
        $protectionEnabled = $auth->isEnabled();
        $isAuthorized = $page->isAuthorized;

        $siteTitle = $page->siteTitle;
        $navTitle = $page->navTitle;
        $title = $page->title !== '' ? $page->title : $siteTitle;
        $debugInfo = $page->notice;
        $hasDocuments = $page->hasDocuments;

        $current = $page->current;
        $content = $page->content;
        $breadcrumbs = $page->breadcrumbs;

        $tree = $nav->render(
            $page->tree,
            $current !== null ? $current->relative : '',
        );

        $darkVars = $page->config->dark();
        $lightVars = $page->config->light();
        $defaultTheme = $page->config->defaultTheme();

        $saveToLocalStorage = $page->savePasswordToStorage;
        $submittedPassword = $page->submittedPassword;

        ob_start();
        require self::TEMPLATE;

        return (string)ob_get_clean();
    }

    /**
     * Заголовок, HTML и «хлебные крошки» открытого документа.
     *
     * @return array{0:string,1:string,2:array<int,array{name:string,path:string,isFile:bool}>}
     */
    private function buildDocumentView(?Document $document, bool $isAuthorized): array
    {
        $title = 'Docs';
        $content = '';
        $breadcrumbs = [];

        if ($document === null) {
            return [$title, $content, $breadcrumbs];
        }

        if ($this->auth->protects($document->relative) && !$isAuthorized) {
            return ['Файл защищён', $content, $breadcrumbs];
        }

        $raw = file_get_contents($document->path);
        if ($raw === false) {
            return [$title, $content, $breadcrumbs];
        }

        $markdown = new Markdown();

        return [
            $markdown->title($raw, $document->name),
            $markdown->render($raw, $document->relative),
            $this->buildBreadcrumbs($document),
        ];
    }

    /**
     * @return array<int,array{name:string,path:string,isFile:bool}>
     */
    private function buildBreadcrumbs(Document $document): array
    {
        $parts = explode('/', $document->relative);
        $lastIndex = count($parts) - 1;

        $breadcrumbs = [];

        foreach ($parts as $index => $part) {
            $path = implode('/', array_slice($parts, 0, $index + 1));
            $isFile = $index === $lastIndex;

            $breadcrumbs[] = [
                'name' => $isFile
                    ? $document->name
                    : $this->content->resolveTitle($path . '/', $part),
                'path' => $path,
                'isFile' => $isFile,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Каталог с документами. Относительный «content.path» разрешается от
     * корня приложения, поэтому конфиг не зависит от текущего каталога
     * процесса.
     */
    private function contentDir(): string
    {
        $content = $this->config->string('content.path', $this->baseDir);

        if ($content === '') {
            return $this->baseDir;
        }

        if (!Path::isAbsolute($content)) {
            $content = $this->baseDir . '/' . $content;
        }

        return Path::normalize($content);
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

    /**
     * Редирект с немедленным выходом — дальше рендера уже не будет.
     */
    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
