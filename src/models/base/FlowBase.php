<?php

namespace app\models\base;

use app\hexlet\WillFunctions;
use DI\Container;
use Exception;
use ParagonIE\EasyDB\EasyDB;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;

class FlowBase  {

    const MAX_SIZE_TITLE = 40;
    const MAX_SIZE_BLURB = 120;

    private ?RouteParserInterface $routeParser;

    public function __construct()
    {
        $this->routeParser = null;
    }


    /**
     * @return RouteParserInterface|null
     */
    protected function getRouteParser(): ?RouteParserInterface
    {
        return $this->routeParser;
    }

    /**
     * @param RouteParserInterface $routeParser
     */
    public function setRouteParser(RouteParserInterface $routeParser): void
    {
        $this->routeParser = $routeParser;
    }




    /**
     * @var Container $container
     */
    protected static Container $container;

    public static function set_container($c): void
    {
        static::$container = $c;
    }

    /**
     * @return EasyDB
     */
    protected static function get_connection() : EasyDB {
        try {
            return  static::$container->get('connection');
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot connect to the database",['exception'=>$e]);
            die( static::class . " Cannot get connetion");
        }
    }

    /**
     * @return LoggerInterface
     */
    protected static function get_logger() : LoggerInterface {
        try {
            return  static::$container->get(LoggerInterface::class);
        } catch (Exception $e) {
            die( static::class . " Cannot get logger ". $e->getMessage());
        }
    }


    protected static function minimum_check_valid_name(?string $words,int $max_length) : bool  {

        if (empty($words)) {return false;}

        if (is_numeric(substr($words, 0, 1)) ) {
            return false;
        }

        if (ctype_digit($words) ) {
            return false;
        }

        if (ctype_xdigit($words) && (mb_strlen($words) > 25) ) {
            return false;
        }

        if ((mb_strlen($words) < 1) || (mb_strlen($words) > $max_length) ) {
            return false;
        }
        return true;
    }

    protected bool $b_brief_json_flag = false;
    public function set_brief_json_flag(bool $what): void{
        $this->b_brief_json_flag = $what;
    }
    public function get_brief_json_flag() : bool { return $this->b_brief_json_flag ; }


    public static function check_valid_title($words) : bool  {

        if (empty($words)) {return false;}
        if (!static::minimum_check_valid_name($words,static::MAX_SIZE_TITLE)) {return false;}

        $b_match = preg_match('/^[[:alnum:]\-]+$/u',$words,$matches);
        WillFunctions::will_do_nothing($matches);
        if ($b_match === false) {
            $error_preg = array_flip(array_filter(get_defined_constants(true)['pcre'], function ($value) {
                return str_ends_with($value, '_ERROR');
            }, ARRAY_FILTER_USE_KEY))[preg_last_error()];
            throw new RuntimeException($error_preg);
        }
        return (bool)$b_match;

    }

}