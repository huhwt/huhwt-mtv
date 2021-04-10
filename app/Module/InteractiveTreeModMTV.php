<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2021 webtrees development team
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
 */

declare(strict_types=1);

// namespace Fisharebest\Webtrees\Module;
namespace HuHwt\WebtreesMods\Module\InteractiveTree;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Exceptions\IndividualAccessDeniedException;
use Fisharebest\Webtrees\Exceptions\IndividualNotFoundException;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;
// use Fisharebest\Webtrees\Module\InteractiveTree\TreeView;
use Fisharebest\Webtrees\Module\InteractiveTreeModule;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use HuHwt\WebtreesMods\Module\InteractiveTree\MultTreeViewMod;

use HuHwt\WebtreesMods\Exceptions\MTVactionNotFoundException;

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
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $action = $request->getQueryParams()['action'];

        if ( $action == 'Details' ) {
            return $this->getDetailsAction($request);
        }

        if ( $action == 'Individuals' ) {
            return $this->getIndividualsAction($request);
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
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $pid        = $request->getQueryParams()['pid'];
        $individual = Registry::individualFactory()->make($pid, $tree);

        if ($individual === null) {
            throw new IndividualNotFoundException();
        }

        if (!$individual->canShow()) {
            throw new IndividualAccessDeniedException();
        }

        $instance = $request->getQueryParams()['instance'];
        $treeview = new MultTreeViewMod($instance);                 // EW.H MOD ... set own Treeview-Instance

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
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $q        = $request->getQueryParams()['q'];
        $instance = $request->getQueryParams()['instance'];
        $earmark   = substr($instance, 2);                           // EW.H MOD ... extract Earmark for Treeview-Instance
        $treeview = new MultTreeViewMod($instance);                 // EW.H MOD ... set own Treeview-Instance

        return response($treeview->getIndividuals($tree, $earmark, $q));
    }

};