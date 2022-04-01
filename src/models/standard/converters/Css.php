<?php

namespace app\models\standard\converters;

use app\models\standard\FlowTagStandardAttribute;
use app\models\standard\IFlowTagStandardAttribute;
use app\models\standard\RawAttributeData;

class Css implements IAttributeConverter {

    /**
     * @param RawAttributeData[] $raw_data
     * @return IFlowTagStandardAttribute
     */
    public static function convert(array $raw_data): object
    {
        $ret = null ;
        return $ret;
    }
}