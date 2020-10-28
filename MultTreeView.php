<?php

declare(strict_types=1);

namespace HuHwt\WebtreesMods;

use Aura\Router\RouterContainer;
use Aura\Router\Map;
use Fig\Http\Message\RequestMethodInterface;
use fisharebest\Localization\Translation;
use Fisharebest\webtrees\module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleChartInterface;
use Fisharebest\Webtrees\Module\ModuleChartTrait;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\Middleware\AuthManager;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\ModuleInterface;

use Fisharebest\Webtrees\Services\LocalizationService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


use HuHwt\WebtreesMods\Http\RequestHandlers\MultTreeViewRH;
use HuHwt\WebtreesMods\Http\RequestHandlers\FindDuplicateRecordsRH;

use function app;
use function array_keys;
use function assert;
use function e;
use function implode;
use function in_array;
use function ob_get_clean;
use function ob_start;
use function redirect;
use function route;
use function usort;
use function view;

/**
 * Class MultTreeView
 */
class MultTreeView extends AbstractModule implements ModuleCustomInterface, ModuleInterface, ModuleChartInterface
{
    use ModuleCustomTrait;
    use ViewResponseTrait;
    use ModuleChartTrait;

    private const ROUTE_DEFAULT = 'huhwt-mult-treeview';
    private const ROUTE_URL = '/tree/{tree}/mult-treeview&xrefs={xrefs}';

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string {

        return 'EW.Heinrich';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     *
     * We use a system where the version number is equal to the latest version of webtrees
     * Interim versions get an extra sub number
     *
     * The dev version is always one step above the latest stable version of this module
     * The subsequent stable version depends on the version number of the latest stable version of webtrees
     *
     */
    public function customModuleVersion(): string {
        return file_get_contents(__DIR__ . '/latest-version.txt');
    }

    // EW.H - Mod ... wird eigentlich in der Basisklasse über die ...VersionUrl gezogen
    // Dient zur Plausibilisierung, dass die installierte Version zum Core kompatibel ist
    public function customModuleLatestVersion(): string {
        return file_get_contents(__DIR__ . '/latest-version.txt');
    }

    /**
     * A URL that will provide the latest stable version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string {
        return '';      // https://raw.githubusercontent.com/vesta-webtrees-2-custom-modules/vesta_common/master/latest-version.txt';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string {
        return '';      // https://huh-netz.de';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string {
        return __DIR__ . '/resources/';
    }

    /**
     * Additional/updated translations.
     *
     * @param string $language
     *
     * @return array<string,string>
     */
    public function customTranslations(string $language): array
    {
        switch ($language) {
            case 'de':
            case 'de_DE':
            // Arrays are preferred, and faster.
            // If your module uses .MO files, then you can convert them to arrays like this.
            $Tpath = __DIR__ . '/resources/lang/de/messages.po';
            return (new Translation($Tpath))->asArray();

        default:
            return [];
            }
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     */
    public function title(): string 
    {
        $title = I18N::translate('MultTreeView');
        return $title;
    }

    public function description(): string 
    {
        return I18N::translate('An interactive tree, showing all the ancestors and descendants of an individual.');
    }

    /**
     * CSS class for the URL.
     *
     * @return string
     */
    public function chartMenuClass(): string
    {
        return 'menu-chart-tree';
    }

    /**
     * Return a menu item for this chart - for use in individual boxes.
     *
     * @param Individual $individual
     *
     * @return Menu|null
     */
    public function chartBoxMenu(Individual $individual): ?Menu
    {
        return $this->chartMenu($individual);
    }

    /**
     * The title for a specific instance of this chart.
     *
     * @param Individual $individual
     *
     * @return string
     */
    public function chartTitle(Individual $individual): string
    {
        /* I18N: %s is an individual’s name */
        return I18N::translate('Interactive tree of %s', $individual->fullName());
    }

    /**
     * The URL for this chart.
     *
     * @param Individual $individual
     * @param mixed[]    $parameters
     *
     * @return string
     */
    public function chartUrl(Individual $individual, array $parameters = []): string
    {
        return route('module', [
                'module' => $this->name(),
                'action' => 'Chart',
                'xref'   => $individual->xref(),
                'tree'    => $individual->tree()->name(),
            ] + $parameters);
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::boot()
     */
    public function boot(): void 
    {
        $router_container = app(RouterContainer::class);
        assert($router_container instanceof RouterContainer);

        $router_container = app(RouterContainer::class);
        assert($router_container instanceof RouterContainer);
        $router = $router_container->getMap();

        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [
                    AuthManager::class,
                ],
            ]);

            $router->get(MultTreeViewRH::class, '/mult-treeview'); 
            // $router->get(FindDuplicateRecordsRH::class, '/duplicates'); 
            });

        // var_dump($router_container);

        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        View::registerCustomView('::layouts/adminMTV', $this->name() . '::layouts/adminMTV');
        View::registerCustomView('::layouts/defaultMTV', $this->name() . '::layouts/defaultMTV');
        View::registerCustomView('::admin/trees-duplicates', $this->name() . '::admin/trees-duplicates');

        View::registerCustomView('::modules/interactive-tree/MultTVchart', $this->name() . '::modules/interactive-tree/MultTVchart');
        View::registerCustomView('::modules/interactive-tree/MultTVpage', $this->name() . '::modules/interactive-tree/MultTVpage');
        View::registerCustomView('::modules/interactive-tree/MultTVpageh2', $this->name() . '::modules/interactive-tree/MultTVpageh2');
    }

}