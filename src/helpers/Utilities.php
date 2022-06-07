<?php

namespace app\helpers;

use app\hexlet\hexlet_exceptions\GuidException;
use app\hexlet\hexlet_exceptions\JsonHelperException;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use Carbon\Carbon;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use JsonException;
use LogicException;
use RuntimeException;

class Utilities extends BaseHelper {

    public static function get_utilities() : Utilities {
        try {
            return static::get_container()->get('utilities');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }

    /**
     * To compare a version string as a number
     * @author https://stackoverflow.com/questions/1142496/regex-replace-for-decimals (the heart of it,The regex and the if statement)
     * @since 0.5.0
     * @example converts something like '0.1.2' to 0.12
     * @param string|null $version_string
     *
     * @return float|null
     */
    function convert_version_to_float(?string $version_string) : ?float {
        if (empty($version_string)) {return null;}
        $version_string = strval($version_string);
        $dashes_and_underscores_to_points = str_replace('-','.',$version_string);
        $dashes_and_underscores_to_points = str_replace('_','.',$dashes_and_underscores_to_points);
        $only_numbers_and_points = preg_replace('/[^\d.]+/', '', $dashes_and_underscores_to_points);
        if (($pos = strpos($only_numbers_and_points, '.')) !== false) {
            $no_extra_points = substr($only_numbers_and_points, 0, $pos+1).str_replace('.', '', substr($only_numbers_and_points, $pos+1));
        } else {
            $no_extra_points = $only_numbers_and_points;
        }
        $val = floatval($no_extra_points);
        return $val;
    }

    function get_version_string() : ?string {
        $program = $this->get_settings()->program ?? (object)[];
        return $program->version ?? null;
    }

    /**
     * @since 0.5.3
     * @return string|null
     */
    function get_version_description() : ?string {
        $program = $this->get_settings()->program ?? (object)[];
        return $program->version_description_short ?? null;
    }

    /**
     * @since 0.5.3
     * @return string|null
     */
    function get_version_link() : ?string {
        $program = $this->get_settings()->program ?? (object)[];
        return $program->version_link ?? null;
    }


    /** @noinspection PhpUnused */
    public static function get_version_float() : float {
        $me = static::get_utilities();
        $string_version = $me->get_version_string();
        return $me->convert_version_to_float($string_version);
    }

    function get_program_timezone() : ?string {
        $program = $this->get_settings()->program ?? (object)[];
        return $program->timezone ?? null;
    }

    /**
     * @return string[]
     */
    function get_fonts() : array {
        $fonts = $this->get_settings()->fonts ?? [];
        return $fonts;
    }

    public static function generate_iso_time_stamp() : string {
        $me = static::get_utilities();
        $tz = $me->get_program_timezone();
        $now = Carbon::now($tz);
        return $now->toIso8601String();
    }


    /**
     * @throws JsonException
     */
     protected static function convert_to_type($what,$b_to_array = false ) : null|array|object {
        if (is_null($what)) { return null;}
        if (is_array($what) || is_object($what)) {
            $json = JsonHelper::toString($what);
        } elseif (JsonHelper::isJson($what)) {
            $json = $what;
        } else {
            throw new InvalidArgumentException(
                "[convert_to_object] This cannot be converted to an object: ".print_r($what,true));
        }
        $converted =  JsonHelper::fromString($json,true,$b_to_array);
        if (! (is_object($converted) || is_array($converted) || is_null($converted))) {
            throw new JsonHelperException("[convert_to_object] not an array, object or null ! ". $json);
        }
        return $converted;
    }

    /**
     * @throws JsonException
     */
    public static function convert_to_object($what) : null|array|object {
       return static::convert_to_type($what);
    }

    /**
     * @throws JsonException
     */
    public static function convert_to_array($what) : array {
        if ($what === null) {return [];}
        if (is_array($what) || is_object($what)) {
            $what =  static::convert_to_type($what,true);
        }

        if (is_array($what)) {return $what;}
        return [$what];
    }

    /**
     * @throws JsonException
     */
    public static function deep_copy($what, $b_to_array = false) {
        if (!(is_array($what) || is_object($what))) { return $what; } //will copy if primitive
        $json = JsonHelper::toString($what);
        $to_array_flag = false;
        if (is_array($what) || $b_to_array) { $to_array_flag = true;}
        return JsonHelper::fromString($json,true,$to_array_flag);
    }

    /**
     * @throws JsonException
     */
    public static function print_nice($what): string
    {
        if (is_object($what)) {
            return JsonHelper::print_nice(static::convert_to_object($what));
        }
        return JsonHelper::print_nice($what);
    }

    /**
     * Converts a string to proper unicode, for when utf8 is really needed
     * nulls are returned as null
     * @param string|null $what
     * @return string|null
     */
    public static function to_utf8(?string $what): ?string
    {
        if (is_null($what)) {return null;}
        return JsonHelper::to_utf8($what);
    }

    public static function valid_guid_format_or_null_or_throw(?string $guid): ?string
    {
        if (is_null($guid)) return null ;
        $b_what =  WillFunctions::is_valid_guid_format($guid);
        if (!$b_what) {
            throw new GuidException(sprintf("Invalid guid[%s]",$guid));
        }
        return $guid;
    }

    public static function if_empty_null(mixed $what) : mixed {
        if (empty($what)) {return null;}
        return $what;
    }

    /**
     *
     * if values and keys is empty throw
     * if only one $values_and_keys returns the key to that
     * then looks for direct match and returns first key of that found
     * if master is one unicode letter and the smallest of the values is also one unicode letter return key one of the 1 size values
     *
     * Then loops through and finds the closest size to the master and returns that key
     *
     * It was not a trivial task to find a good way to find closest text match based on content if the characters were not 2 byte unicode or less
     *  (so would have not worked for emoticons or many asian characters)
     *  Thus, temporarily using the above approach
     *
     * @param string $master
     * @param array<string,string> $keys_and_values
     * @return string
     */
    public  function return_most_matching_key(string $master,array $keys_and_values): string {
        if (empty($keys_and_values)) {
            throw new LogicException("[return_most_matching_key] Empty array ");}

        if (count($keys_and_values) === 1) { return array_key_first($keys_and_values);}

        foreach ($keys_and_values as $return_key => $value) {
            if ($value === $master) {return $return_key;}
        }
        unset($return_key); unset($value);
        $values_and_keys = array_flip($keys_and_values);
        $values = array_values($keys_and_values);
        array_multisort(array_map('mb_strlen', $values), $values);
        if (mb_strlen($master) === 1 && mb_strlen($values[0]) === 1) { return $values_and_keys[$values[0]];}

        //return the closest size
        $target_size = mb_strlen($master);

        $smallest_delta_i = 0;
        $smallest_delta = PHP_INT_MAX;
        for ($i=0; $i < count($values); $i++) {
            $me = $values[$i];
            $my_size = mb_strlen($me);
            if (abs($my_size-$target_size) < $smallest_delta) {$smallest_delta_i = $i;}
        }
        return $values_and_keys[$values[$smallest_delta_i]];


    }

    public static function throw_if_preg_error($preg_result) {
        if ($preg_result === false) {
            $error_preg = array_flip(array_filter(get_defined_constants(true)['pcre'], function ($value) {
                return str_ends_with($value, '_ERROR');
            }, ARRAY_FILTER_USE_KEY))[preg_last_error()];
            throw new RuntimeException($error_preg);
        }
    }

}