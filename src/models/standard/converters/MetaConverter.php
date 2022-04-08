<?php

namespace app\models\standard\converters;

use app\helpers\ProjectHelper;
use app\helpers\Utilities;
use app\models\project\FlowProjectFiles;
use app\models\standard\IFlowTagStandardAttribute;
use Carbon\Carbon;
use Exception;

class MetaConverter extends BaseConverter {

    const STANDARD_NAME = IFlowTagStandardAttribute::STD_ATTR_NAME_META;

    /**
     * @param string $key
     * @return object
     * @throws Exception
     */
    protected function getFinalOfKey(string $key): ?string
    {
        switch ($key) {
            case IFlowTagStandardAttribute::META_KEY_DATETIME: {
                $me = Utilities::get_utilities();
                $tz = $me->get_program_timezone();
                $raws = $this->getRawOfKey(IFlowTagStandardAttribute::META_KEY_DATETIME);
                $reversed_raws =array_reverse($raws);
                foreach ($reversed_raws as $rawly) {
                    if ($rawly->getTextVal()) {
                        return  Carbon::parse($rawly->getTextVal())->setTimezone($tz)->toIso8601String();
                    } elseif($rawly->getLongVal() && $rawly->getLongVal() > 0) {
                        return Carbon::createFromTimestamp($rawly->getLongVal())->setTimezone($tz)->toIso8601String();
                    }
                }
                return null;
            }


            case IFlowTagStandardAttribute::META_KEY_PICTURE_URL: {
                $raws = $this->getRawOfKey(IFlowTagStandardAttribute::META_KEY_PICTURE_URL);
                if (empty($raws)) {return null;}
                $reversed_raws =array_reverse($raws);
                $target = $reversed_raws[0];
                $files = new FlowProjectFiles($target->getProjectGuid(),$target->getOwnerUserGuid());
                return ProjectHelper::get_project_helper()->stub_from_file_paths($files,$target->getTextVal());
            }
        }

        return parent::getFinalOfKey($key);
    }

    /**
     * @param IFlowTagStandardAttribute $a
     * @return object|null
     * @throws Exception
     */
    public static function pre_process_outbound(IFlowTagStandardAttribute $a ): ?object {
        $original = $a->getStandardValue();
        $image_url_property_name = IFlowTagStandardAttribute::META_KEY_PICTURE_URL;
        if (empty($original) ||
            !property_exists($original,$image_url_property_name) ||
            empty($original->$image_url_property_name)
        ) {
            return $original;
        }
        $files = new FlowProjectFiles($a->getProjectGuid(),$a->getOwnerUserGuid());
        $original->$image_url_property_name =
            ProjectHelper::get_project_helper()->stub_to_file_paths($files,$original->$image_url_property_name);
        return $original;
    }

}