<?php
namespace app\hexlet;

class WillFunctions {
    public static function will_do_nothing(...$what) {

    }

    /**
     * @param object $obj
     * @param string[]  $names_to_try
     * @param mixed|null $default
     * @return mixed|null
     */
    public static function value_from_property_names_or_default(object $obj,array $names_to_try,$default=null) {
        foreach ($names_to_try as $name) {
            if (property_exists($obj,$name)) {
                return $obj->$name;
            }
        }
        return $default;
    }
}