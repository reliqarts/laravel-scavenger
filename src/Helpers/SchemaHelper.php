<?php

namespace ReliQArts\Scavenger\Helpers;

use Config;

class SchemaHelper extends CoreHelper
{
    /**
     * Get scavenger scraps table.
     */
    public static function getScrapsTable()
    {
        return Config::get('scavenger.database.scraps_table', 'scavenger_scraps');
    }
}
