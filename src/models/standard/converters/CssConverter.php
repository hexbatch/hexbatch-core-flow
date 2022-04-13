<?php

namespace app\models\standard\converters;

use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\models\standard\FlowTagStandardAttribute;
use app\models\standard\IFlowTagStandardAttribute;
use LogicException;

class CssConverter extends BaseConverter {

    const STANDARD_NAME = IFlowTagStandardAttribute::STD_ATTR_NAME_CSS;
    /**
     * @param array $raw_data
     */
    public function __construct(array $raw_data) {
        parent::__construct($raw_data);
    }


    /**
     * @param string $key
     * @return object
     */
    protected function getFinalOfKey(string $key): ?string
    {
        $raws = $this->getRawOfKey($key);

        switch ($key) {

           case IFlowTagStandardAttribute::CSS_KEY_BACKGROUND_COLOR:
           case IFlowTagStandardAttribute::CSS_KEY_FONT_FAMILY:
           case IFlowTagStandardAttribute::CSS_KEY_COLOR: {
               $only_css_key = $key;
               $clean_ret = [];
               $allowed_public_keys = FlowTagStandardAttribute::getStandardAttributeKeys(static::STANDARD_NAME);
               foreach ($raws as $raw) {

                   $maybe_css_rules = array_map(function($x) {return trim($x);},explode(';',$raw->getTextVal()));
                   $maybe_css_object = JsonHelper::fromString($raw->getTextVal(),false,false);
                   if (is_object($maybe_css_object)) {
                       foreach ($maybe_css_object as $css_name => $css_value) {
                           if ($only_css_key) {
                               if ($css_name !== $only_css_key) {continue;}
                           }
                           if (in_array($css_name,$allowed_public_keys)) {
                               $clean_ret[$css_name] = $css_value;
                           }
                       }
                   } else
                   {
                       if ($only_css_key && count($maybe_css_rules) === 1) {
                           if (count(explode(':',$maybe_css_rules[0])) === 1) {
                               $clean_ret[$only_css_key] = $maybe_css_rules[0];
                               continue;
                           }

                       }

                       foreach ($maybe_css_rules as $css_line) {
                           $maybe_css_parts = array_map(function($x) {return trim($x);},explode(':',$css_line));

                           $css_rule = $maybe_css_parts[0];
                           if ($only_css_key) {
                               if ($css_rule !== $only_css_key) {continue;}
                           }
                           if (in_array($css_rule,$allowed_public_keys) && !empty($maybe_css_parts[1])) {
                               $clean_ret[$maybe_css_parts[0]] = $maybe_css_parts[1];
                           }
                       }


                   }

               }
               return JsonHelper::toString($clean_ret);
           }


           default: {
               throw new LogicException("Did not recognize css key of ". $key );
           }
       }



    }

    public function convert(): ?object
    {
        $ret_array = [];
        foreach ($this->getKeysOfStandard() as $key) {
            $css_json = $this->getFinalOfKey($key);
            $css_array = JsonHelper::fromString($css_json);
            $ret_array = array_merge($ret_array,$css_array);
        }

        return Utilities::convert_to_object($ret_array);

    }
}