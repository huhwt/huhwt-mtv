<?php

/**
 * JustLight Theme
 *
 * JustCarmen webtrees modules
 * Copyright (C) 2009-2020 Carmen Pijpers-Knegt
 *
 * Based on webtrees: online genealogy
 * Copyright (C) 2020 webtrees development team
 *
 * This file is part of JustCarmen webtrees modules
 *
 * JustCarmen webtrees modules is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * JustCarmen webtrees modules is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with JustCarmen webtrees modules. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods;

use Fisharebest\Webtrees\Webtrees;

//webtrees major version switch
if (defined("WT_VERSION"))
    {
    //this is a webtrees 2.x module. it cannot be used with webtrees 1.x. See README.md.
    return;
    } else {
    $version = Webtrees::VERSION;
    }

require_once __DIR__ . '/autoload.php';

require __DIR__ . '/MultTreeView.php';

return app(MultTreeView::class);