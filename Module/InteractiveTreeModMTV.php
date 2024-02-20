<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2022 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * 
 * HuH Extensions for webtrees - Multi-Treeview
 * Extensions for webtrees to check and display duplicate Individuals in the database.
 * Copyright (C) 2020-2022 EW.Heinrich
 */

declare(strict_types=1);

// namespace Fisharebest\Webtrees\Module;
namespace HuHwt\WebtreesMods\Module\InteractiveTree;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;
// use Fisharebest\Webtrees\Module\InteractiveTree\TreeView;
use Fisharebest\Webtrees\Module\InteractiveTreeModule;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use HuHwt\WebtreesMods\Module\InteractiveTree\MultTreeViewMod;

use HuHwt\WebtreesMods\Exceptions\MTVactionNotFoundException;

use HuHwt\WebtreesMods\ClippingsCartEnhanced\ClippingsCartEnhancedModule;

use function assert;

/**
 * Class InteractiveTreeModMTV
 * Extension for Fisharebest\Webtrees\Module\InteractiveTreeModule, acts as RequestHandler, does overlaying default-Treeview with huhwt-Treeview
 */
class InteractiveTreeModMTV extends InteractiveTreeModule implements 

        RequestHandlerInterface
{

    /**
     * EW.H MOD ... Switch over to 'Details' or 'Individuals'
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = Validator::queryParams($request)->string('action', '');

        if ( $action == 'Details' ) {
            return $this->getDetailsAction($request);
        }

        if ( $action == 'Individuals' ) {
            return $this->getIndividualsAction($request);
        }

        if ( $action == 'CCEadapter' ) {
            return $this->getCCEadapterAction($request);
        }

        throw new MTVactionNotFoundException($action);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * 
     * Feeding missing Individual-Details
     */
    public function getDetailsAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree       = Validator::attributes($request)->tree();
        $pid        = Validator::queryParams($request)->string('pid', '');
        $individual = Registry::individualFactory()->make($pid, $tree);
        $individual = Auth::checkIndividualAccess($individual);
        $instance   = Validator::queryParams($request)->string('instance', '');
        $treeview   = new MultTreeViewMod($instance);                 // EW.H MOD ... set own Treeview-Instance

        return response($treeview->getDetails($individual));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * 
     * Expanding Treeview with up-to-now Indi-Stubs
     */
    public function getIndividualsAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree       = Validator::attributes($request)->tree();
        $q          = Validator::queryParams($request)->string('q', '');
        $instance   = Validator::queryParams($request)->string('instance', '');
        $earmark    = substr($instance, 2);                           // EW.H MOD ... extract Earmark for Treeview-Instance
        $treeview   = new MultTreeViewMod($instance);                  // EW.H MOD ... set own Treeview-Instance

        return response($treeview->getIndividuals($tree, $earmark, $q));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * 
     * perform ClippingsCart
     */
    public function getCCEadapterAction(ServerRequestInterface $request): ResponseInterface
    {

        $tree       = Validator::attributes($request)->tree();
        $XREFindi   = Validator::queryParams($request)->string('XREFindi', '');
        $xrefs = Validator::queryParams($request)->string('xrefs', '');

        $CCEok = class_exists("HuHwt\WebtreesMods\ClippingsCartEnhanced\ClippingsCartEnhancedModule", true);
        if (!$CCEok) {
            $cart = Session::get('cart', []);
            $xrefs = $cart[$tree->name()] ?? [];
            $countXREFcold = count($xrefs);
            return response((string) $countXREFcold);
        }

        $CCEadapter = new ClippingsCartEnhancedModule();

        return response($CCEadapter->clip_mtv($request));
    }

};