<?php

namespace app\hexlet;

use app\hexlet\hexlet_exceptions\JsonHelperException;
use ForceUTF8\Encoding;
use JetBrains\PhpStorm\NoReturn;
use ReflectionClass;
use ReflectionException;

//http://jbbcode.com



class JsonHelper {


    public static bool $b_output_headers = true;

    /**
     * Helper function to output json format and exit
     * @param array $phpArray
     * @param int $http_code the code to return in the output as well as to die with
     * @return never
     * @noinspection PhpUnused
     *
     */
    #[NoReturn] public static function endJSONAndDie(array $phpArray=[], int $http_code=200) :never {
        if ( ! headers_sent() ) {
            http_response_code($http_code);
            header('Content-Type: application/json');
        }

        print self::toString($phpArray);//,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        die(0); //does not print if integer
    }

    /**
     * sends $phpArray as json out with a json header and the $http_code as the server code
     * @param mixed|array $message the info to send, if not array will be cast as string and put into a message field
     * @param int $http_code
     * @param bool $b_valid
     * @param int|null $status_code - - if null then is same status as http code
     * @return never , script will exit here
     */
    #[NoReturn]
    public static function printStatusJSONAndDie(mixed $message=[], int $http_code=200, bool $b_valid = true,
                                                             int   $status_code = null) :never{

        if (is_null($status_code)) {
            $status_code = $http_code;
        }

        $phpArray=[];
        if (!is_array($message)) {
            $phpArray['message'] = strval($message);
        } else {
            $phpArray['message'] = $message;
        }

        if (self::$b_output_headers) {
            if ( ! headers_sent() ) {
                http_response_code($http_code);
                header('Content-Type: application/json');
            }

        }


        $phpArray['code'] = $status_code;
        $phpArray['valid'] = $b_valid;
        $out = static::toString($phpArray);
        print $out;
        die($b_valid ? 0 : 1);
    }

    /**
     * @param $message mixed
     * @param int $http_code
     * @param int|null $status_in_json - if null then is same status as http code
     * @uses JsonHelper::printStatusJSONAndDie()
     *
     *
     * @noinspection PhpUnused
     */
    public static function printErrorJSONAndDie(mixed $message, int $http_code=500, int $status_in_json=null) : never  {

        self::printStatusJSONAndDie($message,$http_code, false,$status_in_json);
    }

    /**
     * @param mixed $string
     * @return bool
     */
    public static function isJson(mixed $string): bool
    {
        if (!is_string($string)) {return false;}
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    //throws error if cannot convert

    /**
     * Converts a php array to a json string
     * @param $phpArray mixed
     * @param $options integer - any options to pass to json_encode
     * @return string
     * @throws JsonHelperException if json error
     */
    public static function toString(mixed $phpArray, int $options=JSON_UNESCAPED_UNICODE): string
    {
        $out = json_encode($phpArray, $options );
        if ($out) {
            return $out;
        } else {
            $oops =  json_last_error_msg();
            throw  new JsonHelperException($oops);
        }
    }

    /**
     * Returns the decoded json from the input
     * if input is null then returns null
     * if input is empty returns []
     * else converts the string value of the input from a json string to a php structure
     * @param mixed $what
     * @param boolean $b_exception default true <p>
     *  will throw an exception if is not proper json if true
     *  will return the string if not proper json
     * </p>
     * @param bool $b_association - default true (which is the reverse of normal)
     * @return array|mixed|null
     * @throws JsonHelperException if json error
     */
    public static function fromString(mixed $what, bool $b_exception=true, bool $b_association=true): mixed {
        if (is_null($what) ) { return null;}
        if (empty($what) && !is_numeric($what)) {
            if ($b_association) {
                return [];
            } else {
                return (object)[];
            }

        }
        $original_what = $what;
        $what = strval($what);
        if ( strcasecmp($what,'null') == 0) {return null;}
        $out = json_decode(strval($what), $b_association);
        if (is_null($out)) {
            if ($b_exception) {
                $oops =  json_last_error_msg();
                $oops .= "\n Data: \n". $what;
                throw  new JsonHelperException($oops);
            }else {
                return $original_what;
            }

        } else {
            if (empty($out) ) {
                if (is_array($out) || is_object($out)) {
                    if ($b_association) {
                        return [];
                    } else {
                        return (object)[];
                    }
                }
            }
            return $out;
        }


    }

    /**
     * if the param is an array then processed by toString,
     * but if not an array then the string casting of mixed is returned
     *  if boolean returns a '1' or '0'
     *  if its a string, then its forced to uft8 and trimmed
     * finally, if null then null is returned
     * @param $what mixed any value
     * @param boolean $b_empty_is_null <p>
     *      if value is empty its returned as null, default true
     * </p>
     * @param boolean $b_cast_bools_as_int <p>
     *
     * </p>
     * @return string|null
     * @since 0.1
     * @version 0.4.0 empty arrays do not cast as null any more
     */
    public static function toStringAgnostic(mixed $what, bool $b_empty_is_null=true, bool $b_cast_bools_as_int = true): ?string
    {
        if (is_null($what)) {return null;}
        if (is_string($what)) {
            $what = self::to_utf8($what);
            $what = trim($what);
        }
        if ($b_empty_is_null && empty($what) &&
            !is_numeric($what) && !is_bool($what) && !is_array($what) && !is_object($what)
        )
        {return null;}

        if (is_bool($what)) {
            if ($b_cast_bools_as_int) {
                if ($what) {return '1';}
                else {return '0';}
            } else {
                if ($what) {
                    return 'true';
                } else {
                    return 'false';
                }
            }

        }

        if (is_array($what)) {
            return self::toString($what);
        }
        if (is_object($what)) {
            return self::toString($what);
        }
        else {
            return strval($what);
        }

    }


    public static function var_to_binary_values($var,$true_value,$false_value){
        $what = self::var_to_boolean($var);
        if ($what) {
            return $true_value;
        } else {
            return $false_value;
        }
    }

    /**
     * Will Convert any scalar to boolean
     * @param mixed $var
     * @param bool $b_null_is_good , default false <p>
     *   added
     * @version 0.4.0
     *   sometimes a tri-state is needed, if $b_null_is_good is true, then null is not cast to boolean
     * </p>
     * @since 0.1.0
     * @return bool|null
     */
    public static function var_to_boolean(mixed $var, bool $b_null_is_good = false): ?bool
    {

        if ($b_null_is_good) {
            if (is_null($var)) {return null;} //0.4.0 tri-state
        }

        if (is_object($var)) {
            if (method_exists($var , '__toString')) {
                $string = (string)$var;
            } else {
                if (empty($var)) {  //see if __isset() is implemented, and returns true
                    return false;
                }
                return true; //if it is not empty, or __isset not set, then return true
            }

        } elseif (is_array($var)) {
            if (empty($var)) {
                return false;
            } return true;
        }
        else {
            $string = trim(strval($var));
        }

        //see if an number not = to 0
        if (is_numeric($var) && (intval($var) == floatval($var))) {
            $test = intval($var);
            if ($test) {
                return true;
            } else {
                return false;
            }
        }

        if (empty($string)) {
            return false;
        }

        if (strcasecmp($string, 'on') == 0 ) {
            return true;
        }

        if (strcasecmp($string, 'yes') == 0 ) {
            return true;
        }

        if (strcasecmp($string, 'ok') == 0 ) {
            return true;
        }

        if (strcasecmp($string, 'true') == 0 ) {
            return true;
        }

        if (strcasecmp($string, '1') == 0 ) {
            return true;
        }

        if (strcasecmp($string, 't') == 0 ) {
            return true;
        }

        if (strcasecmp($string, 'y') == 0 ) {
            return true;
        }

        if ( intval($string) > 1) {
            return true;
        }

        return false;
    }


    /**
     * Casts string to data, its the opposite of @dataTo and is designed to convert data from a text field
     * to different data types , based on what the category is
     * called in the constructor
     * @param ?string $data_type  a valid category name, will throw exception if not valid
     * @param ?string $what  the thing to cast as a string. If this is null the return will be null regardless of the category
     * @return mixed <p> returns a string whose value depends on
     *  what the meta type is
     *      'boolean' must be integer or string representation. 0 is false, anything else is true
     *       boolean_yes_no, boolean_on_off,boolean_true_false are aliases of boolean to be compatible with dataToString
     *
     *      'integer' this needs to be either an integer or a string representation of an integer and returns as an integer
     *      'float'  needs to be either a float or integer or string representation  of it, returns as a float
     *      'text',   cast to string and returned
     *      'json', expects a json string and returns an associative array, throws exception if cannot covert
     *      'date_time' <p> if integer or string representation then assumed to be time stamp and returned as number,
     *                       else needs to be a string and is assumed to be in the UTC timezone,returned as integer
     *                  </p>
     *
     *@throws JsonHelperException if not a recognized name, if json what is not an array
     * @noinspection PhpUnused
     */
    public static function dataFromString(?string $data_type, ?string $what): mixed
    {
        if (is_null($what)) {return null;}
        if (is_null($data_type)) {return null;}

        switch ($data_type) {
            case 'boolean':
            case 'boolean_yes_no':
            case 'boolean_on_off':
            case 'boolean_true_false':
                return self::var_to_boolean($what);

            case 'integer':
                if (is_numeric($what)) {
                    return intval($what);
                } else {
                    throw  new JsonHelperException("[$what] is not numeric so cannot convert to integer");
                }

            case 'float':
                if (is_numeric($what)) {
                    return floatval($what);
                } else {
                    throw  new JsonHelperException("[$what] is not numeric so cannot convert to float");
                }

            case 'text':
                return $what;
            case 'json':
                return JsonHelper::fromString($what);
            case 'date_time' :
                if (empty($what)) {return null;}

                if (is_numeric($what) && (intval($what) == floatval($what))) {
                    return intval($what);
                }
                //assume its a string and can be converted from UTC time to a timestamp

                $test = strtotime($what." UTC");
                if ($test) {
                    return $test;
                } else {
                    throw  new JsonHelperException("Cannot convert [$what] to UTC time");
                }
            default:
                throw new JsonHelperException("[$data_type] not recognized as a data type")   ;

        }
    }

    /**
     * Casts data to a string, based on what the category is
     * called in the constructor
     * @param string $data_type  <p>
     *  a valid category name, will throw exception if not valid
     * *  what the meta type is
     *      'boolean' is converted to '1' or '0', anything that evaluates to true will be 1 else it will be 0
     *      'boolean_yes_no' is converted to yes or no from its boolean value
     *      'boolean_on_off' is converted to on or off based on its boolean value
     *      'boolean_true_false' is converted to the words true or false based on its boolean value
     *      'integer' is the base ten string, this needs to be either an integer,float or a string representation of an integer
     *                 if a float or string representation of a float, the decimals will be chopped off
     *      'float'  is the base ten string, this needs to be either a float or integer or string representation of it
     *      'text',   cast to string and  is  trimmed
     *      'json', expects an array and converts to json string, throws exception if cannot covert
     *      'date_time' <p> if integer or string representation then assumed to be time stamp,
     *                        if null or 0 or evaluates to false then  current time
     *                          converted to YYYY-MM-DD HH:MM:SS in UTC time string
     *
     *                       if its a string will convert to utc format above, but its important that the timezone is not on the string
     *                        in other words, this cannot convert between timezones and assumes everything is utc
     *                       if $what is false, null or empty then the current time will be used
     *                  </p>
     *
     * @param mixed $what  the thing to cast as a string
     * @return string <p>
     *  returns a string whose value depends on the data_type given above
     * </p>
     * @throws JsonHelperException if not a recognized $category, and if the data is not following the rules described in the param section
     * @noinspection PhpUnused
     */
    public static function dataToString(string $data_type, mixed $what): string
    {
        //var_dump($data_type);
        // var_dump($what);
        switch ($data_type) {
            case 'boolean':
                return  self::var_to_binary_values($what,'1','0');
            case 'boolean_yes_no':
                return  self::var_to_binary_values($what,'yes','no');
            case 'boolean_on_off':
                return  self::var_to_binary_values($what,'on','off');
            case 'boolean_true_false':
                return  self::var_to_binary_values($what,'true','false');
            case 'integer':
                if (is_string($what)) {
                    $what = trim($what);
                    $what = intval($what);
                }
                if (is_float($what) || is_integer($what)) {
                    return strval($what);
                } else {
                    throw  new JsonHelperException("[$what] is not a float or an int or a string so cannot convert to integer");
                }
            case 'float':
                if (is_string($what)) {
                    $what = trim($what);
                    $what = floatval($what);
                }
                if (is_float($what) || is_integer($what)) {
                    return strval($what);
                } else {
                    throw  new JsonHelperException("[$what] is not a float or an int or a string so cannot convert to float");
                }
            case 'text':
                if (is_array($what)) {
                    throw  new JsonHelperException("Cannot convert array to $data_type");
                } else {
                    return trim(strval($what));
                }
            case 'json':
                if (is_object($what) || is_array($what) || is_null($what) || is_numeric($what) || is_bool($what)) {
                    return JsonHelper::toString($what);
                } else {
                    //check if already a json string
                    if (is_string($what)) {
                        if (JsonHelper::isJson($what)) {
                            return $what;
                        }
                    }

                    throw  new JsonHelperException("$data_type must use array, null,number,boolean or convertable string");
                }
            case 'date_time' :
                if (!$what) {
                    $what = time();
                }

                //if its  a string, then assume utc and cast to timestamp
                // if its not a string, cast to int and return utc String
                if (is_string($what)) {
                    //assume its a string and can be converted from UTC time to a timestamp

                    $what = strtotime($what." UTC");
                    if (!$what) {
                        throw  new JsonHelperException("Cannot convert [$what] to UTC time");
                    }
                } else {
                    $what = intval($what);
                }

                $haha =  gmdate('Y-m-d G:i:s',$what);
                return $haha;
            default:
                throw new JsonHelperException("[$data_type] not recognized as a data type")   ;

        }
    }

    public static function print_nice(mixed $what) :string {
        ob_start();
        static::print_nice_internal($what);
        return ob_get_clean();
    }

    /**
     * Use this to inspect json returns
     * Debug, prints array information to the screen in an easy to read html table
     * I have been using this for years, and its not mine, forget where I found it
     * @param mixed $elem, the only thing to use when calling it, the rest of the method params is for the recursion
     * @param int $max_level
     * @param array $print_nice_stack
     * @return void  it prints to the screen
     * @example print_nice($array)
     *
     *
     * @noinspection PhpUnused
     */
    public static function print_nice_internal(mixed $elem, int $max_level=15, array $print_nice_stack=array()): void
    {
        //if (is_object($elem)) {$elem = object_to_array($elem);}
        if(is_array($elem) || is_object($elem)){
            if(in_array($elem,$print_nice_stack,true)){
                echo "<span style='color:red'>RECURSION</span>";
                return;
            }
            $print_nice_stack[]=&$elem;
            if($max_level<1){
                echo "<span style='color:red'>reached maximum level</span>";
                return;
            }
            $max_level--;
            /** @noinspection HtmlDeprecatedAttribute */
            echo "<table border=1 cellspacing=0 cellpadding=3 width=100%>";
            if(is_array($elem)){
                echo '<tr><td colspan=2 style="background-color:#333333;"><strong><span style="color:white">ARRAY</span></strong></td></tr>';
            }else{
                echo '<tr><td colspan=2 style="background-color:#333333;"><strong>';
                echo '<span style="color:white">OBJECT Type: '.get_class($elem).'</span></strong></td></tr>';
            }
            $color=0;
            foreach($elem as $k => $v){
                if($max_level%2){
                    $rgb=($color++%2)?"#888888":"#44BBBB";
                }else{
                    $rgb=($color++%2)?"#777777":"#22BBFF";
                }
                /** @noinspection HtmlDeprecatedAttribute */
                echo '<tr><td valign="top" style="width:40px;background-color:' . $rgb . ';">';
                echo '<strong style="color:black">'.$k."</strong></td><td style='background-color:white;color:black'>";
                self::print_nice_internal($v,$max_level,$print_nice_stack);
                echo "</td></tr>";
            }
            echo "</table><br><br>";
            return;
        }
        if($elem === null){
            echo "<span style='color:green'>NULL</span>";
        }elseif($elem === 0){
            echo "0";
        }elseif($elem === true){
            echo "<span style='color:green'>TRUE</span>";
        }elseif($elem === false){
            echo "<span style='color:green'>FALSE</span>";
        }elseif($elem === ""){
            echo "<span style='color:green'>EMPTY STRING</span>";
        }else{
            echo str_replace("\n","<strong><span style='color:green'>*</span></strong><br>\n",$elem);
        }
    }

    /**
     * Outputs information about a class. Sometimes it helps to see data objects this way
     * Not really json like, but it goes with print nice as a set of debug functions
     * @param $object
     * @throws JsonHelperException
     * @noinspection PhpUnused
     */
    public static function TO($object): void
    { //Test Object
        try {
            if (!is_object($object)) {
                throw new JsonHelperException("This is not a Object");
            }
            if (class_exists(get_class($object), true)) echo "<pre>CLASS NAME = " . get_class($object);
            $reflection = new ReflectionClass(get_class($object));
            echo "<br />";
            echo $reflection->getDocComment();

            echo "<br />";

            $metody = $reflection->getMethods();
            foreach ($metody as  $value) {
                echo "<br />" . $value;
            }

            echo "<br />";

            $vars = $reflection->getProperties();
            foreach ($vars as $value) {
                echo "<br />" . $value;
            }
            echo "</pre>";
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch ( ReflectionException $r) {
            throw new JsonHelperException($r->getMessage(),0,$r);
        }
    }

    /**
     * Converts all keys recursively to lower case
     * @param array $arr
     * @return array
     * @noinspection PhpUnused
     */

    public static function array_change_key_case_recursive(array $arr): array
    {
        return array_map(function($item){
            if(is_array($item))
                $item = self::array_change_key_case_recursive($item);
            return $item;
        },array_change_key_case($arr));
    }

    /**
     * Converts a string to proper unicode, for when utf8 is really needed
     * @param string $what
     * @return string
     */
    public static function to_utf8(string $what): string
    {
        if (empty($what) && !is_numeric($what)) {return '';}
        return Encoding::toUTF8($what);
    }




}
