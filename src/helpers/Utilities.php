<?php

namespace app\helpers;

use app\hexlet\JsonHelper;
use Carbon\Carbon;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use LogicException;

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

    public static function generate_iso_time_stamp() : string {
        $me = static::get_utilities();
        $tz = $me->get_program_timezone();
        $now = Carbon::now($tz);
        return $now->toIso8601String();
    }

    public static function convert_to_object($what) : ?object {
        if (is_null($what)) { return null;}
        if (is_array($what) || is_object($what)) {
            $json = JsonHelper::toString($what);
        } elseif (JsonHelper::isJson($what)) {
            $json = $what;
        } else {
            throw new InvalidArgumentException("This cannot be converted to an object: ".print_r($what,true));
        }
        return JsonHelper::fromString($json,true,false);
    }

    public static function deep_copy($what) {
        if (!(is_array($what) || is_object($what))) { return $what; } //will copy if primitive
        $json = JsonHelper::toString($what);
        $b_to_array = false;
        if (is_array($what)) { $b_to_array = true;}
        return JsonHelper::fromString($json,true,$b_to_array);
    }
}