<?php

namespace app\hexlet;

use Exception;
use Highlight\Highlighter;
use PHPHtmlParser\Dom;
use app\hexlet\hexlet_exceptions\JsonException;
use ForceUTF8\Encoding;
use JBBCode\CodeDefinition;
use JBBCode\CodeDefinitionBuilder;
use JBBCode\DefaultCodeDefinitionSet;
use JBBCode\Parser;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;
use PHPHtmlParser\Exceptions\UnknownChildTypeException;
use PHPHtmlParser\Options;
use ReflectionClass;
use ReflectionException;

//http://jbbcode.com



class JsonHelper {


    public static bool $b_output_headers = true;
    /**
     * Helper function to output json format and exit
     * @param array $phpArray
     * @param int $http_code the code to return in the output as well as to die with
     * @throws JsonException if cannot parse array to json
     * @noinspection PhpUnused
     */
    public static function sendJSONAndDie(array $phpArray=[], int $http_code=200) {
        if ( ! headers_sent() ) {
            http_response_code($http_code);
            header('Content-Type: application/json');
        }

        print self::toString($phpArray);//,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        die(0); //does not print if integer
    }

    /**
     * sends $phpArray as json out with a json header and the $http_code as the server code
     * @param mixed $message the info to send, if not array will be cast as string and put into a message field
     * @param int $http_code
     * @param bool $b_valid
     * @param int|null $status_code - - if null then is same status as http code
     * @return void , script will exit here
     */
    public static function printStatusJSONAndDie($message=[], int $http_code=200, bool $b_valid = true, int $status_code = null) {

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
    public static function printErrorJSONAndDie($message, int $http_code=500, int $status_in_json=null) {

        self::printStatusJSONAndDie($message,$http_code, false,$status_in_json);
    }

    public static function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    //throws error if cannot convert

    /**
     * Converts a php array to a json string
     * @param $phpArray mixed
     * @param $options integer - any options to pass to json_encode
     * @return string
     * @throws JsonException if json error
     */
    public static function toString($phpArray, int $options=JSON_UNESCAPED_UNICODE): string
    {
        $out = json_encode($phpArray, $options );
        if ($out) {
            return $out;
        } else {
            $oops =  json_last_error_msg();
            throw  new JsonException($oops);
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
     * @throws JsonException if json error
     */
    public static function fromString($what, bool $b_exception=true, bool $b_association=true) {
        if (is_null($what) ) { return null;}
        if (empty($what) && !is_numeric($what)) {
            if ($b_association) {
                return [];
            } else {
                return (object)[];
            }

        }
        $what = strval($what);
        if ( strcasecmp($what,'null') == 0) {return null;}
        $out = json_decode(strval($what), $b_association);
        if (is_null($out)) {
            if ($b_exception) {
                $oops =  json_last_error_msg();
                $oops .= "\n Data: \n". $what;
                throw  new JsonException($oops);
            }else {
                return $what;
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
     * @return string
     * @throws JsonException
     * @since 0.1
     * @version 0.4.0 empty arrays do not cast as null any more
     */
    public static function toStringAgnostic($what, bool $b_empty_is_null=true, bool $b_cast_bools_as_int = true): ?string
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
     * @param string|integer|float|null|boolean|object|array $var
     * @param bool $b_null_is_good, default false <p>
     *   added @version 0.4.0
     *   sometimes a tri-state is needed, if $b_null_is_good is true, then null is not cast to boolean
     * </p>
     * @return bool
     * @since 0.1.0
     */
    public static function var_to_boolean($var, bool $b_null_is_good = false): ?bool
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
     *@throws JsonException if not a recognized name, if json what is not an array
     * @noinspection PhpUnused
     */
    public static function dataFromString(?string $data_type, ?string $what) {
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
                    throw  new JsonException("[$what] is not numeric so cannot convert to integer");
                }

            case 'float':
                if (is_numeric($what)) {
                    return floatval($what);
                } else {
                    throw  new JsonException("[$what] is not numeric so cannot convert to float");
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
                    throw  new JsonException("Cannot convert [$what] to UTC time");
                }
            default:
                throw new JsonException("[$data_type] not recognized as a data type")   ;

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
     * @throws JsonException if not a recognized $category, and if the data is not following the rules described in the param section
     * @noinspection PhpUnused
     */
    public static function dataToString(string $data_type, $what): string
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
                    throw  new JsonException("[$what] is not a float or an int or a string so cannot convert to integer");
                }
            case 'float':
                if (is_string($what)) {
                    $what = trim($what);
                    $what = floatval($what);
                }
                if (is_float($what) || is_integer($what)) {
                    return strval($what);
                } else {
                    throw  new JsonException("[$what] is not a float or an int or a string so cannot convert to float");
                }
            case 'text':
                if (is_array($what)) {
                    throw  new JsonException("Cannot convert array to $data_type");
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

                    throw  new JsonException("$data_type must use array, null,number,boolean or convertable string");
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
                        throw  new JsonException("Cannot convert [$what] to UTC time");
                    }
                } else {
                    $what = intval($what);
                }

                $haha =  gmdate('Y-m-d G:i:s',$what);
                return $haha;
            default:
                throw new JsonException("[$data_type] not recognized as a data type")   ;

        }
    }

    /**
     * Use this to inspect json returns
     * Debug, prints array information to the screen in an easy to read html table
     * I have been using this for years, and its not mine, forget where I found it
     * @param $elem, the only thing to use when calling it, the rest of the method params is for the recursion
     * @param int $max_level
     * @param array $print_nice_stack
     * @return void  it prints to the screen
     * @example print_nice($array)
     *
     *
     * @noinspection PhpUnused
     */
    public static function print_nice($elem, int $max_level=15, array $print_nice_stack=array()){
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
                self::print_nice($v,$max_level,$print_nice_stack);
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
     * @throws JsonException
     * @noinspection PhpUnused
     */
    public static function TO($object){ //Test Object
        try {
            if (!is_object($object)) {
                throw new JsonException("This is not a Object");
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
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch ( ReflectionException $r) {
            throw new JsonException($r->getMessage(),0,$r);
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



    public static function html_from_bb_code($original) {

       // will_send_to_error_log('original',$original);
        $safe_encoding = self::to_utf8($original);
       // will_send_to_error_log('$safe_encoding',$safe_encoding);
        $trimmed = trim($safe_encoding);
        if (empty($trimmed)) {return $trimmed;}

        //convert any p , br and non linux line returns to /n
        $lines_standardized = self::tags_to_n($trimmed,false,false);

      //  will_send_to_error_log('$lines_standardized',$lines_standardized);



        //remove any remaining tags
        $body = strip_tags($lines_standardized);

        //$body = str_replace(' ','&nbsp',$body);//unicode space
        //$body = str_replace(' ','&nbsp;',$body);//regular space


        $body = str_replace('] ',']&nbsp',$body);//unicode space
        $body = str_replace('] ',']&nbsp;',$body);//regular space



        //will_send_to_error_log('after preg callback u space',$body);
        $body = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$body);//tab


        //will_send_to_error_log('bb code after whitespace filter(2)',$body);


        //change font size
        /*
         * 1: 10px
            2: 13px
            3: 16px
            4: 18px
            5: 24px
            6: 32px
            7: 48px
         */
        $body = str_replace('[size=1]','[size=10]',$body);
        $body = str_replace('[size=2]','[size=13]',$body);
        $body = str_replace('[size=3]','[size=16]',$body);
        $body = str_replace('[size=4]','[size=18]',$body);
        $body = str_replace('[size=5]','[size=24]',$body);
        $body = str_replace('[size=6]','[size=32]',$body);
        $body = str_replace('[size=7]','[size=48]',$body);


        //the parser will clip whitespace on the option, so temporarily rename the fonts generated by the js client when spaces in name


        $body = str_replace('Arial Black','Arial-Black',$body);
        $body = str_replace('Comic Sans MS','Comic-Sans-MS',$body);
        $body = str_replace('Courier New','Courier-New',$body);
        $body = str_replace('Times New Roman','Times-New-Roman',$body);


        $parser = new Parser();
        $parser->addCodeDefinitionSet(new DefaultCodeDefinitionSet());

        $builder = new CodeDefinitionBuilder('sub', '<sub>{param}</sub>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('sup', '<sup>{param}</sup>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('ul', '<ul>{param}</ul>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('ol', '<ol>{param}</ol>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('li', '<li>{param}</li>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('table', '<table>{param}</table>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('th', '<th>{param}</th>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('tr', '<tr>{param}</tr>');
        $parser->addCodeDefinition($builder->build());

        $builder = new CodeDefinitionBuilder('td', '<td>{param}</td>');
        $parser->addCodeDefinition($builder->build());

        $parser->addCodeDefinition( CodeDefinition::construct("font",
            '<span style="font-family:{option}">{param}</span>',
            true
        ));

        $parser->addCodeDefinition( CodeDefinition::construct("size",
            '<span style="font-size:{option}px">{param}</span>',
            true
        ));

        //strikethrough
        $parser->addCodeDefinition( CodeDefinition::construct("s",
            '<span style="text-decoration: line-through">{param}</span>',
            false
        ));

        // alignment
        $parser->addCodeDefinition( CodeDefinition::construct("center",
            '<div style="text-align: center;display: inline-block">{param}</div>',
            false
        ));

        $parser->addCodeDefinition( CodeDefinition::construct("left",
            '<div style="text-align: left;display: inline-block">{param}</div>',
            false
        ));

        $parser->addCodeDefinition( CodeDefinition::construct("right",
            '<div style="text-align: right;display: inline-block">{param}</div>',
            false
        ));

        $parser->addCodeDefinition( CodeDefinition::construct("justify",
            '<div style="text-align: justify;display: inline-block">{param}</div>',
            false
        ));

        $parser->addCodeDefinition( CodeDefinition::construct("quote",
            '<div style="background-color:#fff7d9; margin: 0.25em;border-left: .3em solid #f4e59f;padding:0.25em">{param}</div>',
            false
        ));


        $parser->addCodeDefinition( CodeDefinition::construct("code",
            '<pre class="flow-code-highlight">{param}</pre>',
            false
        ));


        //strikethrough
        $parser->addCodeDefinition( CodeDefinition::construct("img",
            /** @lang text */ '<img src="{param}" alt="bb image">',
            false
        ));






        $parser->parse($body);
        $post =  $parser->getAsHtml();
        //will_send_to_error_log('after parse ',$post);

        //add in image dimensions, if they exist
        $post = preg_replace('/alt="(\d+)x(\d+)"/', ' width="$1" height="$2" ', $post);

        //remove any trailing commas in font family
        $post = preg_replace( /** @lang text */ '/\\"(font-family:[-_\w]+)(,)/', '"$1', $post);


        //put in br for each /n
       // will_send_to_error_log('before the A',$post);
        $post = str_replace("\n", "<br>", $post);
      //  will_send_to_error_log('after the A',$post);

        //add in hr tag
        $post = str_replace("[hr]", "<hr>", $post);

        //rename the temp named fonts
        $post = str_replace('Arial-Black','Arial Black',$post);
        $post = str_replace('Comic-Sans-MS','Comic Sans MS',$post);
        $post = str_replace('Courier-New','Courier New',$post);
        $post = str_replace('Times-New-Roman','Times New Roman',$post);

        //remove br from ul and ol, and table tr and td and code
        try {
            $dom = new Dom;
            $dom->loadStr( $post );
            $br_in_ul_array = $dom->find( 'ul br' );
            /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            foreach ($br_in_ul_array as &$br ) {
                $br->delete();
                unset( $br );
            }

            $br_in_ol_array = $dom->find( 'ol br' );
            /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            foreach ($br_in_ol_array as &$br ) {
                $br->delete();
                unset( $br );
            }


            $highlight_these = $dom->find( 'pre.flow-code-highlight' );

            foreach ($highlight_these as $highlight_this ) {
                /**
                 * @var HtmlNode $highlight_this
                 */
                $inner_html = $highlight_this->innerHtml();
                $my_text = str_replace('<br />',"\n",$inner_html);

                $hl = new Highlighter();
                $preg_ret = preg_match_all('/#lang#(?P<language>.+)#lang#/', $my_text, $output_array);
                if ($preg_ret === false) {
                    $preg_error = array_flip(get_defined_constants(true)['pcre'])[preg_last_error()];
                    throw new JsonException("[html_from_bb_code] error finding language for code block".$preg_error);
                }
                if (array_key_exists('language',$output_array) && count($output_array['language'])) {
                    $lang = $output_array['language'][0];
                    $my_text = preg_replace('/#lang#(.+)#lang#/', '', $my_text);
                    if ($my_text === null) {
                        $preg_error = array_flip(get_defined_constants(true)['pcre'])[preg_last_error()];
                        throw new JsonException("[html_from_bb_code] error removing language from code block".$preg_error);
                    }
                    $my_text = trim($my_text);
                    try {
                        $highlighted = $hl->highlight($lang, $my_text);
                    } catch (Exception $e) {
                        throw new JsonException("[html_from_bb_code] error highlighting language". $e->getMessage(),$e->getCode(),$e);
                    }

                } else {
                    $hl->setAutodetectLanguages(['php', 'c++', 'html', 'css','python','sh','json','js']);
                    $my_text = trim($my_text);
                    try {
                        $highlighted = $hl->highlightAuto($my_text);
                    } catch (Exception $e) {
                        throw new JsonException("[html_from_bb_code] error auto highlighting language". $e->getMessage(),$e->getCode(),$e);
                    }

                }

                $code_string = "<code class=\"hljs $highlighted->language\">".$highlighted->value."</code>";

                foreach ($highlight_this->getChildren() as $children) {
                    $children->delete();
                }

                $my_dom = new Dom;
                $options = new Options();
                $options->setPreserveLineBreaks(true);
                $my_dom->loadStr($code_string,$options);
                $new_tag = $my_dom->find('code')[0];

                $highlight_this->addChild($new_tag);
            }

            // the only way to preserve whitespace using this algorithm and deal with how the bbcode editor makes tables,
            // is to literally go through and remove the br after I converted them from newlines above
            // otherwise most browsers will dump the br above the table making whitespace layout around tables bad

            $return_before_td_fix = (string) $dom;
            $fixed_up_string = preg_replace('#</td>\s*<br />#','</td>',$return_before_td_fix);
            $fixed_up_string = preg_replace('#</tr>\s*<br />#','</tr>',$fixed_up_string);
            $return = $fixed_up_string;
        } catch (ChildNotFoundException|CircularException|StrictException|NotLoadedException|ContentLengthException|LogicalException|
                    UnknownChildTypeException $e) {
            throw new JsonException($e->getMessage(),$e->getCode(),$e);
        }



        return $return;
    }

    /**
     * Converts <br> and <p> into \n characters while stripping out all previous newline characters
     * Only deals with these two tags so need to remove other tags before or afterwards (preferably before)
     *
     * The algorithm is simple and does not rely on p tags being aligned (so can deal with mismatched and broke html code too)
     *   1) remove optionally the nobreak character
     *   2) remove all existing newlines for all different operating systems
     *   3) for each br tag replace it with \n
     *   4) for each closing p tag replace it with \n
     *   5) remove all opening p tags
     *   6) remove space between newlines
     * @param string $string <p>
     *   the string to be converted, assumes with the unicode replacement that the string is not encoding mangled
     *    so need to make sure that the php and html are talking enough in utf8 ( I did here for when I applied it)
     * <p>
     * @param mixed $replace_nobreak_space <p>
     * the default is false, and means no replacement
     *  otherwise the nobreak unicode character will be replaced by the value of this param
     *  This is here because the ckeditor will put this between p tags and so will add spaces that are not intended
     *   when this function gets done
     *   I added this as an option so this function can be used in different places in the code and sometimes
     *   a no break character is desired
     * </p>
     *
     * @param bool $b_remove_existing_newlines, if true (by default) will remove all existing newlines first
     * @return string
     * @throws JsonException  if one of the operations fail
     */
    public static function tags_to_n(string $string, $replace_nobreak_space= false, bool $b_remove_existing_newlines = true): string
    {

        //note: if having trouble with a mangled utf8 string then use
        //$string = ForceUTF8\Encoding::toUTF8($string);
        // after loading in the vendor file vendor/neitanod/forceutf8/src/ForceUTF8/Encoding.php
        // I did not have to this here, after changing
        // the output headers and charsets in the forms and internal encoding and db connections
        // for all the setting pages

        if (empty($string) && !is_numeric($string)) {return '';}



        //optionally remove the nobreak space, which is not picked up in the regular php replace functions
        if ($replace_nobreak_space !== false) {
            $string = mb_ereg_replace(' ',$replace_nobreak_space,$string); //the empty  is not a regular space but a unicode space!
            //this is a unicode NO-BREAK in the quotes

            if ($string === false) {
                throw new JsonException("Error in mb_ereg_replace in tags_to_n");
            }
        }

        if ($b_remove_existing_newlines) {
            //strip out all newlines (mac, windows, and linux types) \r\n  \n \r
            $string = str_replace("\r\n", '', $string);
            $string = str_replace("\r", '', $string);
            $string = str_replace("\n", '', $string);
        } else {
            $string = str_replace("\r\n", "\n", $string);
            $string = str_replace("\r", "\n", $string);
        }


        // replace <br> <br/> and all whitespace variants  etc with /n
        $string = preg_replace(/** @lang text */
            '#<br */? *>\s*#i', "\n", $string);
        if ($string === null) {
            throw new JsonException("Error in mb_ereg_replace in tags_to_n");
        }

        // replace </p>  and all whitespace variants with /n

        $string = preg_replace('#</p *>#i', "\n", $string);
        if ($string === null) {
            throw new JsonException("Error in mb_ereg_replace in tags_to_n");
        }

        // strip out <p> and all newline variants
        $string = preg_replace(/** @lang text */'#<p *>#i', "", $string);
        if ($string === null) {
            throw new JsonException("Error in mb_ereg_replace in tags_to_n");
        }

        //remove whitespace between two newlines
        $string = preg_replace('#\n +\n#im', "\n\n", $string);
        if ($string === null) {
            throw new JsonException("Error in mb_ereg_replace in tags_to_n");
        }

        return $string;
    }



}
