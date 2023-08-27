<?php

/**
 * HuH Extensions for webtrees - Multi-Treeview
 * Extensions for webtrees to check and display duplicate Individuals in the database.
 * Copyright (C) 2020-2022 EW.Heinrich
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods;

use Aura\Router\RouterContainer;
use Aura\Router\Map;
// use Fig\Http\Message\RequestMethodInterface;
use fisharebest\Localization\Translation;
use Fisharebest\webtrees\module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
// use Fisharebest\Webtrees\Module\ModuleChartInterface;
// use Fisharebest\Webtrees\Module\ModuleChartTrait;
// use Fisharebest\Localization\Locale\LocaleInterface;
// use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\Middleware\AuthManager;
// use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
// use Fisharebest\Webtrees\Registry;
// use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\View;
// use Fisharebest\Webtrees\Individual;
// use Fisharebest\Webtrees\Services;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;

// use Fisharebest\Webtrees\Services\LocalizationService;
// use Fisharebest\Webtrees\Session;
// use Fisharebest\Webtrees\Tree;
// use Illuminate\Database\Capsule\Manager as DB;
// use Illuminate\Database\Query\Builder;
// use Illuminate\Database\Query\Expression;
// use Illuminate\Database\Query\JoinClause;
// use Psr\Http\Message\ResponseInterface;
// use Psr\Http\Message\ServerRequestInterface;
// use Psr\Http\Server\RequestHandlerInterface;

use Fisharebest\Webtrees\Module\ModuleConfigTrait;

use HuHwt\WebtreesMods\Http\RequestHandlers\MultTreeViewRH;
use HuHwt\WebtreesMods\Http\RequestHandlers\FindDuplicateRecordsMTV;
use HuHwt\WebtreesMods\Module\InteractiveTree\InteractiveTreeModMTV;
use HuHwt\WebtreesMods\Traits\MultTVconfigTrait;

use function app;
// use function array_keys;
use function assert;
// use function e;
// use function implode;
// use function in_array;
// use function ob_get_clean;
// use function ob_start;
// use function redirect;
// use function route;
// use function usort;
use function view;

/**
 * Class MultTreeView
 */
class MultTreeView extends AbstractModule implements ModuleCustomInterface, ModuleInterface, ModuleGlobalInterface, ModuleConfigInterface
{
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ViewResponseTrait;
    use ModuleConfigTrait;

    use MultTVconfigTrait;

    private const ROUTE_DEFAULT = 'huhwt-mult-treeview';
    private const ROUTE_URL = '/tree/{tree}/mult-treeview&xrefs={xrefs}';

    private $huh;

    public function __construct() {
      $this->huh = json_decode('"\u210D"') . "&" . json_decode('"\u210D"') . "wt";
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     *
     * @return string
     */
    public function customModuleAuthorName(): string {

        return 'EW.Heinrich';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     *
     * @return string
     */
    public function customModuleVersion(): string {
        return '2.1.17.2';
    }

    /**
     * {@inheritDoc}
     * A URL that will provide the latest stable version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string {
        return 'https://github.com/huhwt/huhwt-mtv/blob/master/latest-version.txt';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     *
     * @return string
     */
    public function customModuleSupportUrl(): string {
        return 'https://github.com/huhwt/huhwt-mtv/issues';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     *
     * @return string
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
        // no differentiation according to language variants
        $_language = substr($language, 0, 2);
        $ret = [];
        $languageFile = $this->resourcesFolder() . 'lang/' . $_language . '/messages.po';
        if (file_exists($languageFile)) {
            $ret = (new Translation($languageFile))->asArray();
        }
        return $ret;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     *
     * @return string
     */
    public function title(): string 
    {
        $title = I18N::translate('MultTreeView');
        return $this->huh . ' ' . $title;
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
    /**
     * Raw content, to be added at the end of the <head> element.
     * Typically, this will be <link> and <meta> elements.
     *
     * @return string
     */
    public function headContent(): string
    {
        return view("{$this->name()}::style", [
            'path' => $this->assetUrl('css/huhwt.min.css'),
        ]);
    }

    /**
     * Raw content, to be added at the end of the <body> element.
     * Typically, this will be <script> elements.
     * EW.H - MOD ... - Script element will be mapped by explicit customized adminMTV.phtml
     * @return string
     */
    public function bodyContent(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::boot()
     */
    public function boot(): void 
    {
        $router_container = app(RouterContainer::class);
        assert($router_container instanceof RouterContainer);
        $router = $router_container->getMap();

        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [
                    AuthManager::class,
                ],
            ]);

            // $all_routes = $router->getRoutes();
            // // $allkeys = array_keys($all_routes);
            // foreach ($all_routes as $key => $aroute) {
            //     if ($aroute->name == 'Fisharebest\Webtrees\Http\RequestHandlers\FindDuplicateRecords') {
            //         unset($all_routes[$key]);
            //     }
            // }
            // $router->setRoutes($all_routes);

            $router->get(MultTreeViewRH::class, '/mult-treeview'); 

            $router->get(FindDuplicateRecordsMTV::class, '/duplicatesMTV');    // EW.H MOD - redefining AdminService for enhancing Individuals

            $router->get(InteractiveTreeModMTV::class, '/treeMTV'); 

        });

        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        View::registerCustomView('::admin/trees', $this->name() . '::admin/trees');

        View::registerCustomView('::layouts/adminMTV', $this->name() . '::layouts/adminMTV');
        View::registerCustomView('::admin/trees-duplicates', $this->name() . '::admin/trees-duplicates');

        View::registerCustomView('::modules/interactive-tree/MultTVchart', $this->name() . '::modules/interactive-tree/MultTVchart');
        View::registerCustomView('::modules/interactive-tree/MultTVpage', $this->name() . '::modules/interactive-tree/MultTVpage');
        View::registerCustomView('::modules/interactive-tree/MultTVpageh2', $this->name() . '::modules/interactive-tree/MultTVpageh2');
    }

}