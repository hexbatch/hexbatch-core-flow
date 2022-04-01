<?php

namespace app\models\standard\converters;

use app\models\standard\IFlowTagStandardAttribute;
use app\models\standard\RawAttributeData;

Interface IAttributeConverter {

    /**
     * @param RawAttributeData[] $raw_data
     * @return IFlowTagStandardAttribute
     */
    public static function convert(array $raw_data) : object;
}