<?php

namespace HuHwt\WebtreesMods;

use Fisharebest\Webtrees\I18N;

class MoreI18N {

    //functionally same as I18N::translate,
    //different name prevents gettext from picking this up
    //(intention: use where already expected to be translated via main webtrees)
    public static function xlate(string $message, ...$args): string 
    {
        return I18N::translate($message, ...$args);
    }
  
    //functionally same as I18N::translate,
    //different name prevents gettext from picking this up
    //(intention: use where already expected to be translated via main webtrees)
    public static function xlateContext(string $context, string $message, ...$args): string 
    {
        return I18N::translateContext($context, $message, ...$args);
    }
}
