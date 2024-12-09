<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2020 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * HuH Extensions for webtrees - Multi-Treeview
 * Extensions for webtrees to check and display duplicate Individuals in the database.
 * Copyright (C) 2020-2022 EW.Heinrich
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods\Http\RequestHandlers;

use Fisharebest\Webtrees\Age;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Date;
use Fisharebest\Webtrees\Exceptions\IndividualAccessDeniedException;
use Fisharebest\Webtrees\Exceptions\IndividualNotFoundException;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Http\RequestHandlers\MergeRecordsPage;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use HuHwt\WebtreesMods\Module\InteractiveTree\MultTreeViewMod;

use function assert;
use function redirect;

/**
 * Multiple Treeviews - Interactive check
 * 
 * EW.H - MOD ... derived from webtrees/app/Http/Requesthandlers/MergeRecordsAction.php
 *                        and  webtrees/app/Module/InteractiveTreeModule.php
 */
class MultTreeViewRH implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var string A unique internal name for this module (based on the installation folder). */
    private $name = '';

    /**
     * A unique internal name for this module (based on the installation folder).
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * The title for a specific instance of this chart.
     *
     * @param array<Individual> $INDI_ar
     *
     * @return string
     */
    public function chartTitle($INDI_ar): string
    {
        $htmlTOP_ar = [];
        foreach( $INDI_ar as $individual) {
            // What is (was) the age of the individual
            $bdate = $individual->getBirthDate();
            $ddate = $individual->getDeathDate();

            if ($individual->isDead()) {
                // If dead, show age at death
                $age = (string) new Age($individual->getBirthDate(), $individual->getDeathDate());
            } else {
                // If living, show age today
                $today = new Date(strtoupper(date('d M Y')));
                $age   = (string) new Age($individual->getBirthDate(), $today);
            }
            $htmlTOP = view('modules/interactive-tree/MultTVpageh2', [
                'individual' => $individual,
                'age'        => $age,
            ]);
            $htmlTOP_ar[] = $htmlTOP;
        }
        $htmlTOP = implode('', $htmlTOP_ar);
        $ct = '<div class="d-flex mb-4 mtv-info">' . I18N::translate('Check tree of %s', $htmlTOP) . '</div>'; 
        return $ct;
    }
    /**
     * EW.H - MOD ... die ID mit anzeigen
     * @param Individual $individual
     *
     * @return string
     */
    public function chartSubTitle(Individual $individual): string
    {
        /* I18N: %s is an individual’s name */
        $ct = I18N::translate('Tree of %s', $individual->fullName()); 
        if (!str_ends_with($ct, ")")) {                         // EW.H - MOD ... test if other extension occasionally had added ID
            $ct = $ct  . " (" . $individual->xref() . ")";
        }
        return $ct;
        /* return I18N::translate('Interactive tree of %s', $individual->fullName()); */
    }


    /**
    * EW.H - MOD ... we need root of extension for explicitly referencing styles and scripts in generated HTML
    *
    * Get root of Module
    *       huhwt-mtv/          <- we don't know what to preset here to identify the location in page-hierarchy
    *       - Http/
    *         - RequestHandlers/
    *           - (thisFile)
    *       - resources/        <- here we want to point to later
    */
    private function modRoot(): string
    {
        $file_path = e(asset('snip/'));
        $file_path = str_replace("/public/snip/", "", $file_path) . "/modules_v4/huhwt-mtv";
        return $file_path;
    }

    /**
     * Merge two genealogy records.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/adminMTV';

        $tree = Validator::attributes($request)->tree();

        $xref_s = Validator::queryParams($request)->string('xrefs', '');
        $XREFar = explode(",", $xref_s);
        $xref1 = $XREFar[0];
        $xref2 = $XREFar[1];

        $titleRH = I18N::translate('Interactive check') . ' — ' . e($tree->title());
        $modRoot = $this->modRoot();

        $record1 = Registry::gedcomRecordFactory()->make($xref1, $tree);
        $record2 = Registry::gedcomRecordFactory()->make($xref2, $tree);

        if (
            $record1 === null ||
            $record2 === null ||
            $record1 === $record2 ||
            $record1->tag() !== $record2->tag() ||
            $record1->isPendingDeletion() ||
            $record2->isPendingDeletion()
        ) {
            return redirect(route(MergeRecordsPage::class, [
                'tree'  => $tree->name(),
                'xref1' => $xref1,
                'xref2' => $xref2,
            ]));
        }

        // $tv = new MultTreeViewMod('tv');

        $earmarks = [ "M", "U", "L", "T", "V" ];         // EW.H - MOD ... up to 5 Indi which are viewed as possible duplicates

        $INDI_ar = [];
        $HTML_ar = [];
        $JS_ar = [];
        for ($i = 0, $iE = count($XREFar); $i < $iE ; ++$i) {

            $xref = $XREFar[$i];
            $individual = Registry::individualFactory()->make($xref, $tree);
            $individual = Auth::checkIndividualAccess($individual, false, true);
            if ( $i == 0) { $individual0 = $individual; }

            $htmlTOP = $this->chartSubTitle($individual);
    
            $earmark = $earmarks[$i];
            $tv = new MultTreeViewMod('tv' . $earmark);          // EW.H - MOD ... we need a private instance for each treeview

            [$html, $js] = $tv->drawViewport($individual, $earmark, 3);
    
            $html = $htmlTOP . $html;

            $INDI_ar[] = $individual;
            $HTML_ar[] = $html;
            $JS_ar[] = $js;
        }
        // echo $HTML_ar[1];
        $actLan = Session::get('language', '');

        return $this->viewResponse('modules/interactive-tree/MultTVpage', [
            'html_ar'    => $HTML_ar,
            // 'individual' => $individual,
            'js_ar'      => $JS_ar,
            // 'module'     => $this->name(),
            'title'      => $this->chartTitle($INDI_ar),
            // 'actLan'     => $actLan,
            // 'tree'       => $tree,
            'modRoot'    => $modRoot,       // EW.H - MOD ... root of this module
        ]);
    }
}
