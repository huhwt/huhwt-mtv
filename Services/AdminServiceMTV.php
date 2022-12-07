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

use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Header;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Services;
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
     * @param Tree $tree
     *
     * @return array<string,array<GedcomRecord>>
     */
    public function duplicateRecordsMTV(Tree $tree): array
    {
        // We can't do any reasonable checks using MySQL.
        // Will need to wait for a "repositories" table.
        $repositories = [];

        $sources = DB::table('sources')
            ->where('s_file', '=', $tree->id())
            ->groupBy(['s_name'])
            ->having(new Expression('COUNT(s_id)'), '>', '1')
            ->select([new Expression('GROUP_CONCAT(s_id) AS xrefs')])
            ->pluck('xrefs')
            ->map(static function (string $xrefs) use ($tree): array {
                return array_map(static function (string $xref) use ($tree): Source {
                    return Registry::sourceFactory()->make($xref, $tree);
                }, explode(',', $xrefs));
            })
            ->all();

        $individuals = DB::table('dates')
            ->join('name', static function (JoinClause $join): void {
                $join
                    ->on('d_file', '=', 'n_file')
                    ->on('d_gid', '=', 'n_id');
            })
            ->where('d_file', '=', $tree->id())
            ->where('n_type', '=', 'NAME')          /** EW.H - MOD ... sonst gibts false positives wg. '_MARNM' */
            ->whereIn('d_fact', ['BIRT', 'CHR', 'BAPM', 'DEAT', 'BURI'])
            ->groupBy(['d_year', 'd_month', 'd_day', 'd_type', 'd_fact', 'n_type', 'n_full'])
            ->having(new Expression('COUNT(DISTINCT d_gid)'), '>', '1')
            ->select([new Expression('GROUP_CONCAT(DISTINCT d_gid ORDER BY d_gid) AS xrefs')])
            ->distinct()
            ->pluck('xrefs')
            ->map(static function (string $xrefs) use ($tree): array {
                return array_map(static function (string $xref) use ($tree): Individual {
                    return Registry::individualFactory()->make($xref, $tree);
                }, explode(',', $xrefs));
            })
            ->all();

        $families = DB::table('families')
            ->where('f_file', '=', $tree->id())
            ->groupBy([new Expression('LEAST(f_husb, f_wife)')])
            ->groupBy([new Expression('GREATEST(f_husb, f_wife)')])
            ->having(new Expression('COUNT(f_id)'), '>', '1')
            ->select([new Expression('GROUP_CONCAT(f_id) AS xrefs')])
            ->pluck('xrefs')
            ->map(static function (string $xrefs) use ($tree): array {
                return array_map(static function (string $xref) use ($tree): Family {
                    return Registry::familyFactory()->make($xref, $tree);
                }, explode(',', $xrefs));
            })
            ->all();

        $media = DB::table('media_file')
            ->where('m_file', '=', $tree->id())
            ->where('descriptive_title', '<>', '')
            ->groupBy(['descriptive_title'])
            ->having(new Expression('COUNT(m_id)'), '>', '1')
            ->select([new Expression('GROUP_CONCAT(m_id) AS xrefs')])
            ->pluck('xrefs')
            ->map(static function (string $xrefs) use ($tree): array {
                return array_map(static function (string $xref) use ($tree): Media {
                    return Registry::mediaFactory()->make($xref, $tree);
                }, explode(',', $xrefs));
            })
            ->all();

        return [
            I18N::translate('Repositories')  => $repositories,
            I18N::translate('Sources')       => $sources,
            I18N::translate('List of Individuals')   => $individuals,
            I18N::translate('Families')      => $families,
            I18N::translate('Media objects') => $media,
        ];
    }

}
