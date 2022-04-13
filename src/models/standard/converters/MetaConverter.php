<?php

namespace app\models\standard\converters;

use app\helpers\ProjectHelper;
use app\helpers\Utilities;
use app\hexlet\JsonHelper;
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
                $outbound =  ProjectHelper::get_project_helper()->stub_from_file_paths($files,$target->getTextVal());
                if ($outbound) {
                    $outbound = strip_tags(JsonHelper::to_utf8($outbound));
                }
                return $outbound;
            }
            case IFlowTagStandardAttribute::META_KEY_PUBLIC_EMAIL:
            case IFlowTagStandardAttribute::META_KEY_WEBSITE: {
                $outbound = parent::getFinalOfKey($key);
                if ($outbound) {
                    $outbound = strip_tags(JsonHelper::to_utf8($outbound));
                }
                return $outbound;
            }
            case IFlowTagStandardAttribute::META_KEY_FIRST_NAME:
            case IFlowTagStandardAttribute::META_KEY_LAST_NAME:
            case IFlowTagStandardAttribute::META_KEY_AUTHOR:
            case IFlowTagStandardAttribute::META_KEY_VERSION:

             {
                $outbound = parent::getFinalOfKey($key);
                 if ($outbound) {
                     $outbound = htmlspecialchars(JsonHelper::to_utf8($outbound),
                         ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,'UTF-8',false);
                 }
                 return $outbound;
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
        if (empty($original)) {
            return $original;
        }

        if (property_exists($original,$image_url_property_name)) {
            $files = new FlowProjectFiles($a->getProjectGuid(),$a->getOwnerUserGuid());
            $original->$image_url_property_name =
                ProjectHelper::get_project_helper()->stub_to_file_paths($files,$original->$image_url_property_name);
        }


        $keys_to_encode = [
            IFlowTagStandardAttribute::META_KEY_FIRST_NAME,
            IFlowTagStandardAttribute::META_KEY_LAST_NAME,
            IFlowTagStandardAttribute::META_KEY_AUTHOR,
            IFlowTagStandardAttribute::META_KEY_VERSION

        ];

        foreach ($keys_to_encode as $a_key) {
            if (property_exists($original,$a_key) && $original->$a_key) {
                $original->$a_key = htmlspecialchars(JsonHelper::to_utf8($original->$a_key),
                    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,'UTF-8',false);
            }
        }

        $keys_to_strip = [
            IFlowTagStandardAttribute::META_KEY_PICTURE_URL,
            IFlowTagStandardAttribute::META_KEY_WEBSITE,
            IFlowTagStandardAttribute::META_KEY_PUBLIC_EMAIL
        ];

        foreach ($keys_to_strip as $a_key) {
            if (property_exists($original,$a_key) && $original->$a_key) {
                $original->$a_key = strip_tags(JsonHelper::to_utf8($original->$a_key));
            }
        }

        return $original;
    }

}