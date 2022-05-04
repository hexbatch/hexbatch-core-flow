<?php

namespace app\models\standard\converters;

use app\hexlet\JsonHelper;
use app\models\standard\IFlowTagStandardAttribute;
use Exception;

class GitConverter extends BaseConverter  {

    const STANDARD_NAME = IFlowTagStandardAttribute::STD_ATTR_NAME_GIT;

    /**
     * @param string $key
     * @return string|null
     */
    protected function getFinalOfKey(string $key): ?string
    {
        switch ($key) {
            case IFlowTagStandardAttribute::GIT_KEY_SSH_KEY:
            case IFlowTagStandardAttribute::GIT_KEY_REPO_URL:
            case IFlowTagStandardAttribute::GIT_KEY_NOTES:
            case IFlowTagStandardAttribute::GIT_KEY_WEB_PAGE:
            case IFlowTagStandardAttribute::GIT_KEY_BRANCH: {
                $outbound = parent::getFinalOfKey($key);
                if ($outbound) {
                    $outbound = strip_tags(JsonHelper::to_utf8($outbound));
                }
                return $outbound;
            }
            case IFlowTagStandardAttribute::GIT_KEY_AUTOMATE: {
                $outbound = parent::getFinalOfKey($key);
                return (bool)$outbound;
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
        if (empty($original)) {
            return $original;
        }

        $keys_to_strip = [
            IFlowTagStandardAttribute::GIT_KEY_BRANCH,
            IFlowTagStandardAttribute::GIT_KEY_REPO_URL,
            IFlowTagStandardAttribute::GIT_KEY_SSH_KEY,
            IFlowTagStandardAttribute::GIT_KEY_WEB_PAGE,
            IFlowTagStandardAttribute::GIT_KEY_NOTES,
        ];

        foreach ($keys_to_strip as $a_key) {
            if (property_exists($original,$a_key) && $original->$a_key) {
                $original->$a_key = strip_tags(JsonHelper::to_utf8($original->$a_key));
            }
        }

        return $original;
    }

}