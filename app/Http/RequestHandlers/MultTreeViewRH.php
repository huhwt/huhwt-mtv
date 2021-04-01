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
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods\Http\RequestHandlers;

use Fisharebest\Webtrees\Age;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Date;
use Fisharebest\Webtrees\Exceptions\IndividualAccessDeniedException;
use Fisharebest\Webtrees\Exceptions\IndividualNotFoundException;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use HuHwt\WebtreesMods\Module\InteractiveTree\MultTreeViewMod;

use function assert;
use function redirect;
use function app;

/**
 * Multiple Treeviews - Interactive check
 * 
 * EW.H - MOD ... derived from webtrees/app/Http/Requesthandlers/MergeRecordsPage.php
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
     * @param Individual $individual
     *
     * @return string
     */
    public function chartTitle(Individual $individual): string
    {
        // What is (was) the age of the individual
        $bdate = $individual->getBirthDate();
        $ddate = $individual->getDeathDate();

        if ($individual->isDead()) {
            // If dead, show age at death
            $age = (new Age($bdate, $ddate))->ageAtEvent(false);
        } else {
            // If living, show age today
            $today = strtoupper(date('d M Y'));
            $age   = (new Age($bdate, new Date($today)))->ageAtEvent(true);
        }

        $htmlTOP = view('modules/interactive-tree/MultTVpageh2', [
            'individual' => $individual,
            'age'        => $age,
        ]);
        $ct = I18N::translate('Check tree of %s', $htmlTOP); 
        return $ct;
    }

    public function chartSubTitle(Individual $individual): string
    {
        /* I18N: %s is an individual’s name */
        $ct = I18N::translate('Tree of %s', $individual->fullName()) . " (" . $individual->xref() . ")"; 
        return $ct;         /* EW.H - MOD ... die ID mit anzeigen */
        /* return I18N::translate('Interactive tree of %s', $individual->fullName()); */
    }


    /**
     * Get root of Module
     */
    private function modRoot(): string
    {
        if (isset($_SERVER['HTTPS'])) {
            $protocol = ($_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        }
        else {
            $protocol = 'http';
        }

        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        $file_path = realpath(dirname(__FILE__, 3));            // EW.H - MOD ... actually 3 levels lower than root
        $file_path = substr($file_path, strlen($doc_root));

        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $file_path;
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

        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $xref_s = $request->getQueryParams()['xrefs'] ?? '';
        $xrefs = explode(",", $xref_s);
        $xref1 = $xrefs[0];
        $xref2 = $xrefs[1];

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

        $tv = new MultTreeViewMod('tv');

        $rlevels = [ "M", "U", "L", "T", "V" ];

        $htmlAr = [];
        $jsAr = [];
        for ($i = 0, $iE = count($xrefs); $i < $iE ; ++$i) {

            $xref = $xrefs[$i];
            $individual = Registry::individualFactory()->make($xref, $tree);
            $individual = Auth::checkIndividualAccess($individual, false, true);
            if ( $i == 0) { $individual0 = $individual; }

            $htmlTOP = $this->chartSubTitle($individual);
    
            $rlevel = $rlevels[$i];

            [$html, $js] = $tv->drawViewport($individual, $rlevel, 4);
    
            $html = $htmlTOP . $html;

            $htmlAr[] = $html;
            $jsAr[] = $js;
        }
        // echo $htmlAr[1];
        $actLan = Session::get('language', '');

        return $this->viewResponse('modules/interactive-tree/MultTVpage', [
            'htmls'      => $htmlAr,
            // 'individual' => $individual,
            'jss'        => $jsAr,
            // 'module'     => $this->name(),
            'title'      => $this->chartTitle($individual0),
            'actLan'     => $actLan,
            'tree'       => $tree,
            'modRoot'    => $modRoot,       // EW.H - MOD ... root of this module
        ]);
    }
}
