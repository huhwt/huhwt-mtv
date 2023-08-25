<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2023 webtrees development team
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
 * Copyright (C) 2020-2023 EW.Heinrich
 */

declare(strict_types=1);

// namespace Fisharebest\Webtrees\Http\RequestHandlers;
namespace HuHwt\WebtreesMods\Http\RequestHandlers;

use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\AdminService;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Illuminate\Database\Capsule\Manager as DB;

use HuHwt\WebtreesMods\Services\AdminServiceMTV;
use HuHwt\WebtreesMods\Traits\MultTVconfigTrait;

use function e;

/**
 * Find potential duplicate records in a tree.
 */
class FindDuplicateRecordsMTV implements RequestHandlerInterface
{
    use ViewResponseTrait;
    use MultTVconfigTrait;

    private AdminService $admin_service;

    private AdminServiceMTV $admin_serviceMTV;

    /**
     * Get a module setting. Return a default if the setting is not set.
     *
     * @param string $setting_name
     *
     * @return string
     */
    private function getPreference(string $setting_name): string
    {

        return DB::table('module_setting')
            ->where('module_name', '=', '_huhwt-mtv_')
            ->where('setting_name', '=', $setting_name)
            ->value('setting_value') ?? '-1';
    }

    /**
     * @param AdminService $admin_service
     */
    public function __construct(AdminService $admin_service)
    {
        $this->admin_service = $admin_service;

        $this->admin_serviceMTV = new AdminServiceMTV();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $tree = Validator::attributes($request)->tree();

        /** EW.H - MOD ... first run: default module */
        $duplicates = $this->admin_service->duplicateRecords($tree);

        /** EW.H - MOD ... check preferences */
        $ntypeOption = (int) $this->getPreference('ntype_Option');
        $dfactOption = (int) $this->getPreference('dfact_Option');

        /** EW.H - MOD ... we have relevant preferences ... */
        if ($ntypeOption > 0 || $dfactOption > 0) {
            $ntype_Options = $this->ntypeConfigOptions();
            $dfact_Options = $this->dfactConfigOptions();

            $ntOpt = $ntypeOption > 0 ? $ntype_Options[$ntypeOption] : '';
            $dfOpt = $dfactOption > 0 ? $dfact_Options[$dfactOption] : '';

            /** EW.H - MOD ... second run: check for duplicate individuals with constraints */
            $duplicates[I18N::translate('Individuals')] = $this->admin_serviceMTV->duplicateRecordsMTV($tree, $ntOpt, $dfOpt);
        }

        $title      = I18N::translate('Find duplicates') . ' — ' . e($tree->title());

        return $this->viewResponse('admin/trees-duplicates', [
            'duplicates' => $duplicates,
            'title'      => $title,
            'tree'       => $tree,
        ]);
    }
}
