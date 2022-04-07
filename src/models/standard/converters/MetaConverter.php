<?php

namespace app\models\standard\converters;

use app\helpers\Utilities;
use app\models\standard\IFlowTagStandardAttribute;
use Carbon\Carbon;

class MetaConverter extends BaseConverter {

    const STANDARD_NAME = IFlowTagStandardAttribute::STD_ATTR_NAME_META;

    /**
     * @param string $key
     * @return object
     */
    protected function getFinalOfKey(string $key): ?string
    {
        if ($key === IFlowTagStandardAttribute::META_KEY_DATETIME) {
            $me = Utilities::get_utilities();
            $tz = $me->get_program_timezone();
            $raws = $this->getRawOfKey(IFlowTagStandardAttribute::META_KEY_DATETIME);
            $reversed_raws =array_reverse($raws);
            foreach ($reversed_raws as $rawly) {
                if ($rawly->getLongVal() && $rawly->getLongVal() > 0) {
                    return Carbon::createFromTimestamp($rawly->getLongVal())->setTimezone($tz)->toIso8601String();
                } elseif ($rawly->getTextVal()) {
                    return  Carbon::parse($rawly->getTextVal())->setTimezone($tz)->toIso8601String();
                }
            }
            return null;
        }
        return parent::getFinalOfKey($key);
    }

}