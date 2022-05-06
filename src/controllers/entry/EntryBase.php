<?php
namespace app\controllers\entry;

use app\controllers\base\BasePages;
use app\helpers\EntryHelper;



class EntryBase extends BasePages
{


    protected function get_entry_helper() : EntryHelper {
        return EntryHelper::get_entry_helper();
    }





} //end class