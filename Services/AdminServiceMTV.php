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

namespace HuHwt\WebtreesMods\Services;

use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Header;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services;
use Fisharebest\Webtrees\Services\AdminService;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use League\Flysystem\FilesystemInterface;

use function array_map;
use function explode;
use function fclose;
use function fread;
use function preg_match;

/**
 * Utilities for the control panel.
 */
class AdminServiceMTV
{

    /**
     * @param Tree      $tree
     * @param string    $ntOpt
     * @param string    $dfOpt
     *
     * @return array<string,array<GedcomRecord>>
     */
    public function duplicateRecordsMTV(Tree $tree, string $ntOpt, string $dfOpt): array
    {

        $individuals = DB::table('dates')
            ->join('name', static function (JoinClause $join): void {
                $join
                    ->on('d_file', '=', 'n_file')
                    ->on('d_gid', '=', 'n_id');
            })
            ->where('d_file', '=', $tree->id())
            ;
        /** EW.H - MOD ... sonst gibts false positives wg. '_MARNM' */
        if ($ntOpt > '') {
            if (strpos($ntOpt, '>') > 0) {
                $ntCO_ = substr($ntOpt,strpos($ntOpt,'>')+1);
                $ntCO_ = str_replace("'","",$ntCO_);

                $individuals = $individuals
                    ->where('n_type', '=', $ntCO_)
                    ;
                }
        }
        if ($dfOpt > '') {
            if (strpos($dfOpt, '>') > 0) {
                $dfCO_ = substr($dfOpt,strpos($dfOpt,'>')+1);
                $dfCO_ = str_replace("'","",$dfCO_);
                $dfCO_ar = explode(',', $dfCO_);

                $individuals = $individuals
                    ->whereIn('d_fact', $dfCO_ar)
                    ;
            }
        }

        $individuals = $individuals
            ->groupBy(['d_year', 'd_month', 'd_day', 'd_type', 'd_fact', 'n_type', 'n_full'])
            ->having(new Expression('COUNT(DISTINCT d_gid)'), '>', '1')
            ->select([new Expression('GROUP_CONCAT(DISTINCT d_gid ORDER BY d_gid) AS xrefs')])
            ->distinct()
            ->orderBy('xrefs')
            ->pluck('xrefs')
            ;
        $individualsr = $individuals
            ->map(static function (string $xrefs) use ($tree): array {
                return array_map(static function (string $xref) use ($tree): Individual {
                    return Registry::individualFactory()->make($xref, $tree);
                }, explode(',', $xrefs));
            })
            ->all()
            ;


        return $individualsr;
    }

}
