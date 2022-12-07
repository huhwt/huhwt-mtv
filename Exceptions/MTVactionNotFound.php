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

namespace HuHwt\WebtreesMods\Exceptions;

use Exception;
use Fisharebest\Webtrees\I18N;

/**
 * Application level exceptions.
 */
class MTVactionNotFoundException extends Exception
{
    /**
     * @param string|null $message
     */
    public function __construct(string $action = null)
    {
        $message = I18N::translate('Action unknown:') . '<pre>' . e($action) . '</pre>';

        parent::__construct($message);
    }
}
