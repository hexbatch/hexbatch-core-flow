<?php
namespace app\hexlet;

class WillFunctions {
    public static function will_do_nothing(...$what): void
    {

    }

    public static function will_do_action_later(...$what): void
    {

    }

    /**
     * @param object $obj
     * @param string[]  $names_to_try
     * @param mixed|null $default
     * @return mixed|null
     */
    public static function value_from_property_names_or_default(object $obj, array $names_to_try, mixed $default=null): mixed
    {
        foreach ($names_to_try as $name) {
            if (property_exists($obj,$name)) {
                return $obj->$name;
            }
        }
        return $default;
    }

    public static function is_valid_guid_format(?string $guid) : bool{
        if (empty($guid)) {return false;}
        if (!ctype_xdigit($guid)) {return false;}
        if (strlen($guid) !== 32) {return false;}
        return true;
    }
}