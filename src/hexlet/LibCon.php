<?php
namespace app\hexlet;

use app\hexlet\lib_con\LibConException;
use app\hexlet\lib_con\LibConTestStringAndEmpty;
use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use JsonException;
use LogicException;

/** @noinspection PhpUnused */

/**
 * Class LibCon (LIBrary for CONversion)
 * converts data from one format to another
 *
 *
 * The easiest way to use this is by the @see LibCon::parseFormatString(format).convert(var) where var is a variable to convert
 *  There is another argument it can take to double convert the var
 * @see {WP install location}/wp-admin/admin.php?page=symphony-actions-chart for the full comparision of the types
 * @example the different formats:
 *
//basic types
'bool' alias 'boolean','boolean_yes_no','boolean_true_false','boolean_on_off' (having a bool will cast certain words and numbers to true and false)
'tri' (null,false,true - empties are make null and not false)
'int'  alias 'integer','date_time'  (because casting a date string to an int will give a unix timestamp, like in older)
'float'
'text' alias 'string'
'binary'
'json'
'object'
'array'

//boolean strings
'text.y_or_n'
'text.t_or_f'
'text.yes_or_no'
'text.true_or_false'
'text.on_or_off'

//date time (defaults to utc, but see below for timezone)
'text.iso8601'
'text.rfc822'
'text.rfc850'
'text.rfc1036'
'text.rfc1123'
'text.rfc2822'
'text.rfc3339'
'text.rss'
'text.w3c'
'text.atom'
'text.cookie'
'text.rfc7231'
'text.iso8601zulu'

//date time with timezone, any php timezone is allowed @see http://php.net/manual/en/timezones.php
 * and I added in eastern,central,mountain,pacific to make things easier
text.iso8601.eastern
text.cookie.europe/kiev
text.rfc1036.central

examples of conversion

var    to type    result
-------------------------
2019-02-28T17:39:46+00:00       | int                   | 1551375586  (unix timestamp)
2008-09-15T15:53:00+05:00       | text.iso8601.eastern  | '2008-09-15T5:53:00-05:00'
array                           | json                  | json string of array
array                           | object                | standard object with the properties being array keys
json string                     | object                | converted to standard object
json string                     | array                 | converted to array with keys
'yes'|true|1|1234|'ok'|'on'     | text.yes_or_no        | 'yes'
'off'|false|0|'no'|'false'      | text.on_or_off        | 'off'
boolean true                    | float                 |   1.0


Rules and Restrictions for conversions. Generally, any format can be converted to any other format, except for the rules listed below

 *  null is always cast to null
 *  floats are rounded down to integers if this is an int type
 *  text is run though unicode cleaning (binary is not)
 *  an array cannot be converted to bool,int,float
 *  an object cannot be converted to int,float,text or binary, or datetime if it does not override the __toString method (for example StdObj)
 *  a string can only be converted to json if its already in json form
 *  if converting a numeric value to a datetime format, its assumed to be a timestamp and uses the default or give timezone
 *  if converting a float to a datetime format, its like an int above but the float will be rounded down to the previous second
 *  if converting a string to an array or object, if the string is not valid json, then will throw exception
 *      (but will be okay with any of the other types as long as they are not string)
 *  if converting an array to an object, the object will be StdObj
 *  if converting an object to an array, the private and protected members will be listed, but binary data is okay
 * integers and floats of any value are allowed to be timestamps
 * if converting an integer, float, boolean, or string to an array, it will be returned as a single element in a new array

 * Anything else goes. Check the chart page in the plugin in case of questions:
 *
 * @since 0.2.2
 * @package gokabam_lib
 */
class LibCon {

    //formats
    const BOOL_FORMAT               = 'bool';
    const TRI_FORMAT                = 'tri';
    const INT_FORMAT                = 'int';
    const FLOAT_FORMAT              = 'float';
    const TEXT_FORMAT               = 'text';
    const BINARY_FORMAT             = 'binary';
    const JSON_FORMAT               = 'json';
    const OBJECT_FORMAT             = 'object';
    const ARRAY_FORMAT              = 'array';

    //subtypes
    const BOOL_YES_NO               = 'bool-yes/no'; //if not y or yes, or yea, then false, else true
    const BOOL_TRUE_FALSE           = 'bool-true/false'; //if not t or true, then false, else true
    const BOOL_ON_OFF               = 'bool-on/off';//if not on, then false, else true
    const BOOL_Y_N                  = 'bool-y/n';// same as yes/no, but out put is single letter
    const BOOL_T_F                  = 'bool-t/f'; // same as true/false, but output is single letter

    const ISO8601                   = 'time/iso8601';
    const RFC850                    = 'time/rfc850';
    const RFC822                    = 'time/rfc822';
    const RFC1036                   = 'time/rfc1036';
    const RFC1123                   = 'time/rfc1123';
    const RFC2822                   = 'time/rfc2822';
    const RFC3339                   = 'time/rfc3339';
    const RSS                       = 'time/rss';
    const W3C                       = 'time/w3c';
    const ATOM                      = 'time/atom';
    const COOKIE                    = 'time/cookie';
    const MYSQL                     = 'time/mysql';
    const RFC7231                   = 'time/rfc7231';//GMT
    const ISO8601ZULU               = 'time/iso8601zulu'; //GMT

    const ELEMENT_INTEGER           = 'element/integer';
    const ELEMENT_INT               = 'element/int';
    const ELEMENT_STRING            = 'element/string';
    const ELEMENT_TEXT              = 'element/text';
    const ELEMENT_JSON              = 'element/json';
    const ELEMENT_FLOAT              = 'element/float';
    const ELEMENT_OBJECT            = 'element/object';



    const WORDS = [
        self::BOOL_FORMAT               => self::BOOL_FORMAT	,
        self::TRI_FORMAT                => self::TRI_FORMAT	,
        self::INT_FORMAT                => self::INT_FORMAT	,
        self::FLOAT_FORMAT              => self::FLOAT_FORMAT	,
        self::TEXT_FORMAT               => self::TEXT_FORMAT	,
        self::BINARY_FORMAT             => self::BINARY_FORMAT,
        self::JSON_FORMAT               => self::JSON_FORMAT	,
        self::ARRAY_FORMAT              => self::ARRAY_FORMAT	,
        self::OBJECT_FORMAT             => self::OBJECT_FORMAT	,
        self::BOOL_T_F                  => self::BOOL_T_F	,
        self::BOOL_Y_N                  => self::BOOL_Y_N	,
        self::BOOL_YES_NO               => self::BOOL_YES_NO,
        self::BOOL_TRUE_FALSE           => self::BOOL_TRUE_FALSE	,
        self::BOOL_ON_OFF               => self::BOOL_ON_OFF	,
        self::ISO8601                   => self::ISO8601,
        self::RFC822                    => self::RFC822,
        self::RFC850                    => self::RFC850,
        self::RFC1036                   => self::RFC1036,
        self::RFC1123                   => self::RFC1123,
        self::RFC2822                   => self::RFC2822,
        self::RFC3339                   => self::RFC3339,
        self::RSS                       => self::RSS,
        self::W3C                       => self::W3C,
        self::ATOM                      => self::ATOM,
        self::COOKIE                    => self::COOKIE,
        self::MYSQL                     => self::MYSQL,
        self::RFC7231                   => self::RFC7231,//GMT
        self::ISO8601ZULU               => self::ISO8601ZULU, //GMT
        self::ELEMENT_INT               => self::ELEMENT_INT,
        self::ELEMENT_INTEGER               => self::ELEMENT_INTEGER,
        self::ELEMENT_STRING               => self::ELEMENT_STRING,
        self::ELEMENT_TEXT               => self::ELEMENT_TEXT,
        self::ELEMENT_JSON               => self::ELEMENT_JSON,
        self::ELEMENT_FLOAT               => self::ELEMENT_FLOAT,
        self::ELEMENT_OBJECT               => self::ELEMENT_OBJECT

    ];

    const FORMATS   = [self::BOOL_FORMAT,self::TRI_FORMAT,self::INT_FORMAT,self::FLOAT_FORMAT,self::TEXT_FORMAT,self::BINARY_FORMAT,
        self::JSON_FORMAT, self::ARRAY_FORMAT, self::OBJECT_FORMAT];

    const SUBTYPES   = [

        self::TEXT_FORMAT => [self::BOOL_YES_NO,self::BOOL_TRUE_FALSE,self::BOOL_ON_OFF,
            self::BOOL_Y_N,self::BOOL_T_F,
            self::ISO8601,self::RFC822,self::RFC850,self::RFC1036,self::RFC1123,
            self::RFC2822,self::RFC3339,
            self::RFC7231,self::ISO8601ZULU, //timezone is always GMT
            self::RSS,self::W3C,self::ATOM,self::COOKIE,self::MYSQL],

        self::ARRAY_FORMAT => [self::ELEMENT_INT, self::ELEMENT_INTEGER, self::ELEMENT_STRING,
            self::ELEMENT_TEXT, self::ELEMENT_JSON, self::ELEMENT_FLOAT, self::ELEMENT_OBJECT]
    ];

    const SUBTYPES_SINGLE_ARRAY   = [

        self::BOOL_YES_NO,self::BOOL_TRUE_FALSE,self::BOOL_ON_OFF,
        self::BOOL_Y_N,self::BOOL_T_F,
        self::ISO8601,self::RFC822,self::RFC850,self::RFC1036,self::RFC1123,
        self::RFC2822,self::RFC3339,
        self::RFC7231,self::ISO8601ZULU, //timezone is always GMT
        self::RSS,self::W3C,self::ATOM,self::COOKIE,self::MYSQL,
        self::ELEMENT_INT, self::ELEMENT_INTEGER, self::ELEMENT_STRING, self::ELEMENT_TEXT,
        self::ELEMENT_JSON, self::ELEMENT_FLOAT,self::ELEMENT_OBJECT
    ];

    const DATE_TIME_SUBTYPES = [
        self::ISO8601,self::RFC822,self::RFC850,self::RFC1036,self::RFC1123,
        self::RFC2822,self::RFC3339,
        self::RFC7231,self::ISO8601ZULU, //timezone is always GMT
        self::RSS,self::W3C,self::ATOM,self::COOKIE,self::MYSQL
    ];

    const BOOL_TEXT_SUBTYPES = [
        self::BOOL_YES_NO,self::BOOL_TRUE_FALSE,self::BOOL_ON_OFF,
        self::BOOL_Y_N,self::BOOL_T_F
    ];

    const ARRAY_SUBTYPES = [
        self::ELEMENT_INT, self::ELEMENT_INTEGER, self::ELEMENT_STRING, self::ELEMENT_TEXT,self::ELEMENT_JSON, self::ELEMENT_FLOAT,self::ELEMENT_OBJECT
    ];

    const ONLY_UTC_TIMEZONE = [self::RFC7231,self::ISO8601ZULU];

    const TZ_ALIAS = ['Eastern' => "America/New_York",'Central'=>"America/Chicago",
        'Mountain'=>"America/Denver",'Pacific'=>"America/Los_Angeles"];


    /**
     * @since 0.5.1
     * not connected to any code in this plugin, allows constructing full lists of options in other plugins
     */
    const TYPE_ALIAS = [
        'integer' => ['type'=>self::INT_FORMAT, 'subtype'=>null],
        'boolean' => ['type'=>self::BOOL_FORMAT, 'subtype'=>null],
        'date_time' => ['type'=>self::INT_FORMAT, 'subtype'=>null],
        'string' => ['type'=>self::TEXT_FORMAT, 'subtype'=>null],
        'boolean_yes_no' => ['type'=>self::TEXT_FORMAT, 'subtype'=>self::BOOL_YES_NO],
        'boolean_true_false' => ['type'=>self::TEXT_FORMAT, 'subtype'=>self::BOOL_YES_NO],
        'boolean_on_off' => ['type'=>self::TEXT_FORMAT, 'subtype'=>self::BOOL_YES_NO]
    ];

    /**
     * This will be set to one of the values in the @see LibCon::FORMATS
     * @since 0.2.2
     * @var string|null $format
     */
    protected ?string $format = null;


    /**
     * @since 0.2.2
     * @var string|null $subtype, this will be set to NULL OR one of the values in the @see LibCon::SUBTYPES
     */
    protected ?string $subtype = null;


    /**
     * @since 0.2.2
     * @var string|null , this will be set to NULL or one of the values returned by @see LibCon::get_details_for_sub format
     */
    protected ?string $detail = null;

    /**
     * LibCon constructor.
     *
     * args are case insensitive
     *
     * @param string $format <p>
     *  one of the values in the @param string|null $subtype <p>
     *  NULL OR one of the values in the @param string|null $detail <p>
     *   NULL or one of the values returned by @throws LibConException if one of the conditions is not met
     * @see LibCon::FORMATS
     * </p>
     *
     * @see LibCon::get_details_for_subformat
     *   Right now, only the datetime subtypes have details, and they use this as a timezone, if no timezone given (if no detail given)
     *   the timezone is utc. Some datetime formats (like RFC7231,ISO8601ZULU) are always in utc, so there timezone is ignored
     * </p>
     *
     * @see LibCon::SUBTYPES_SINGLE_ARRAY
     * </p>
     * @since 0.2.2
     */
    public function __construct(string $format, string $subtype = null, string $detail = null) {
        WillFunctions::will_do_nothing(static::TYPE_ALIAS);
        if (empty($format)) {
            throw new LibConException("Format missing in");
        }
        $format = strtolower($format);
        if (!in_array($format, self::FORMATS)) {
            throw new LibConException("Format given was [$format], but not a format ");
        }

        if (empty($subtype)) {
            $subtype = null;
        } else {
            $subtype = strtolower($subtype);
        }


        //only subtypes are in text and array right now
        if ($subtype &&
            !(
                in_array($subtype, self::SUBTYPES[self::TEXT_FORMAT])  ||
                in_array($subtype, self::SUBTYPES[self::ARRAY_FORMAT])
            ) ) {
            throw new LibConException("SubType given was [$subtype], but not a subtypes ");
        }


        $b_found = false;
        if ( $subtype && in_array( $subtype, self::BOOL_TEXT_SUBTYPES ) ) {
            $detail = null;
            $b_found = true;
        } elseif ($subtype && in_array( $subtype, self::ARRAY_SUBTYPES )) {
            $detail = null;
            $b_found = true;
        }
        else if ($subtype &&  in_array( $subtype, self::DATE_TIME_SUBTYPES ) ) {
            $b_found = true;
            if ( empty( $detail ) ) {
                $detail = 'utc';
            } else {
                $tz_array     = static::get_timezones();
                $detail_lower = strtolower( $detail );
                $b_found      = false;
                foreach ( $tz_array as $tz ) {
                    if ( strtolower( $tz ) === $detail_lower ) {
                        $b_found = true;
                        break;
                    }
                }
                if ( ! $b_found ) {
                    throw new LibConException( "Detail given was [$detail],  not found by case insensitive search in the php timezone list. see https://php.net/manual/en/timezones.php" );
                }
            }
            $detail = strtolower($detail);
            if (in_array($subtype,self::ONLY_UTC_TIMEZONE)) {
                if (($detail !== 'utc')  ) {
                    throw new LibConException( "Date Time type $subtype is always utc");
                }
            }

        }

        if ($subtype && (!$b_found)) {
            throw new LibConException("Could not find [$subtype] in the accepted subtype list of " . implode('|',self::SUBTYPES_SINGLE_ARRAY));
        }



        $this->detail = $detail;
        $this->subtype = $subtype;
        $this->format = $format;

        //below is weird stuff for editor which checks code, the below methods are not used very much, and I do not want them showing up in error reports
        $b_never = static::usually_unused_goes_here(static::get_formats(),static::get_details_for_subformat('none'),static::get_subtype_for_format('none'));
        if ($b_never) {
            static::test();
        }
    }

    //only exists to give a clean bill of health for unused methods in editor error checker

    /** @noinspection PhpUnusedParameterInspection
     * @noinspection PhpBooleanCanBeSimplifiedInspection
     */
    protected static function usually_unused_goes_here(...$params): bool
    {
        return false && count($params);
    }


    /**
     * @param string $format_string <p>
     * Expects FORMAT.SUB FORMAT.DETAIL
     * always must have a format, the sub formats and details are optional, but have defaults sometimes
     * @return LibCon
     * @throws LibConException if the format is not usable
     * @example  'int'
     * @example  'text.yes_or_no'
     * @example  'text.iso8601'   note: assumes utc timezone there
     * @example  'text.cookie.pacific'
     * @example  'array'
     *
     *
     * @see LibCon::__construct for the defaults
     * @since 0.2.2
     * note: supports some older aliases integer, boolean, boolean_yes_no, boolean_on_off,
     *                                      boolean_true_false,date_time,string
     */
    public static function parseFormatString(string $format_string): LibCon
    {
        $subformat_word = $detail = null;
        $stuff =  @explode('.',$format_string);
        $format_word = $stuff[0];
        if (count($stuff) >= 3) {
            $subformat_word = $stuff[1];
            $detail = $stuff[2];
        } elseif (count($stuff) === 2) {
            $subformat_word = $stuff[1];
        }

        //do aliases here
        //integer, boolean , boolean_yes_no, boolean_on_off, boolean_true_false
        //date_time is casting to an int, to make it be int
        //go from the words to the internal coding
        if (strcasecmp ($format_word, 'integer') === 0 ) {
            $format_word = self::INT_FORMAT;
        }
        if (strcasecmp ($format_word, 'boolean') === 0 ) {
            $format_word = self::BOOL_FORMAT;
        }
        if (strcasecmp ($format_word, 'date_time') === 0 ) {
            $format_word = self::INT_FORMAT;
        }

        if (strcasecmp ($format_word, 'string') === 0 ) {
            $format_word = self::TEXT_FORMAT;
        }
        if (strcasecmp ($format_word, 'boolean_yes_no') === 0 ) {
            $format_word = self::TEXT_FORMAT;
            $subformat_word = self::BOOL_YES_NO;
        }

        if (strcasecmp ($format_word, 'boolean_true_false') === 0 ) {
            $format_word = self::TEXT_FORMAT;
            $subformat_word = self::BOOL_TRUE_FALSE;
        }

        if (strcasecmp ($format_word, 'boolean_on_off') === 0 ) {
            $format_word = self::TEXT_FORMAT;
            $subformat_word = self::BOOL_ON_OFF;
        }

        $vlookup = array_flip(self::WORDS);
        $format = null;
        $subformat = null;

        if ($format_word && array_key_exists($format_word,$vlookup)) {
            $format = $vlookup[$format_word];
        }

        if ($subformat_word && array_key_exists($subformat_word,self::WORDS)) {
            $subformat = $subformat_word;
        }

        if(empty($format)) {
            throw new LibConException("Format string malformed, does not have a recognizable format. Got [$format_word] from $format_string");
        }

        if ($subformat_word && !$subformat) {
            throw new LibConException("Subformat [$subformat_word] could not be found for [$format_word] ");
        }

        return new LibCon($format,$subformat,$detail);
    }

    /**
     * converts to dot string notation
     * @return string
     * @since 0.2.2
     */
    public function __toString() {
        $what = [$this->format];
        if ($this->subtype) {$what[] = $this->subtype;}
        if ($this->detail) {$what[] = $this->detail;}
        $ret = implode('.',$what);
        return $ret;
    }

    /**
     * List of timezones, with the US and utc on top
     * @return string[]
     * @since 0.2.2
     */
    public static function get_timezones(): array
    {

        //put some easy short ones up front, the ones that are used the most
        $show_first = ['utc'];
        foreach (self::TZ_ALIAS as $alias=>$zone) {
            $show_first[] = $alias;
        }
        $ret = $show_first;
        $t = timezone_identifiers_list();
        for($i = 0; $i < sizeof($t); $i++) {
            if (in_array($t[$i] ,$show_first)) {continue;}
            $ret[] = $t[$i];
        }
        return $ret;
    }

    /**
     * returns the formats,
     * the keys are the internal text code, and the values are the display description
     * @since 0.2.2
     * @return string[]
     */
    public static function get_formats(): array
    {
        $ret = [];
        foreach (self::FORMATS as $f) {
            $word = self::WORDS[$f];
            $ret[$word] = $f;
        }
        return $ret ;
    }

    /**
     * returns all subtypes for a given format,
     * the keys are the internal text code, and the values are the display description
     * @param string $format
     *
     * @return string[]
     * @since 0.2.2
     */
    public static function get_subtype_for_format(string $format): array
    {
        $ret = [];
        if (array_key_exists($format,self::SUBTYPES)) {

            foreach (self::SUBTYPES[$format] as $f) {
                $word = self::WORDS[$f];
                $ret[$word] = $f;
            }
        }
        return $ret;
    }

    /**
     * If there are details for a sub format available, it will list it here
     * the return is a one dimensional array of the values
     * @param string $subformat
     *
     * @return string[]
     * @since 0.2.2
     */
    public static function get_details_for_subformat(string $subformat): array
    {
        if ( in_array(strtolower($subformat) , self::DATE_TIME_SUBTYPES) ) {
            return static::get_timezones();
        }  else {
            return [];
        }
    }

    /**
     * if $to is empty, then $what is cast to this type and subtype
     * if $to is another LibCon ,  the casted $what is then cast again to $to
     *
     *
     * an exception will be thrown if cannot cast
     *
     * see rules in the top comments for what cannot be converted
     *
     * @param float|object|integer|bool|array|string|null $what
     * @param LibCon|null $to , default null
     *
     * @return mixed , the $what, converted
     * @throws JsonException
     * @since 0.2.2
     */
    public function convert(float|object|int|bool|array|string|null $what, LibCon $to = null): mixed
    {
        /*
         * determine which format to format, and go from there
         */

        if (is_string($what) && ($this->format !== self::BINARY_FORMAT)) {
            $what = JsonHelper::to_utf8($what); //unicode filter
        }

        if ($what === null && ($this->format !== self::BOOL_FORMAT)) {
            $cast = null;
        }
        else if ($this->subtype) {
            $cast = self::convert_var_to_subtype($what,$this->subtype,$this->detail);
        } else {
            $cast = self::convert_var_to_type($what,$this->format);
        }

        if (empty($to)) {
            return $cast;
        }

        return $to->convert($cast);

    }

    /**
     * @throws JsonException
     */
    protected static function convert_var_to_string_array($what): array
    {
        //convert to array, then each element to text
        $array = self::convert_to_array($what);
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[$key] = self::convert_to_text($value);
        }
        return $ret;
    }

    /**
     * @throws JsonException
     */
    protected static function convert_var_to_int_array($what): array
    {
        //convert to array, then each element to int
        $array = self::convert_to_array($what);
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[$key] = self::convert_to_int($value);
        }
        return $ret;
    }

    /**
     * @throws JsonException
     */
    protected static function convert_var_to_json_array($what): array
    {
        //convert to array, then each element to json
        $array = self::convert_to_array($what);
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[$key] = self::convert_to_json($value);
        }
        return $ret;
    }

    /**
     * @throws JsonException
     */
    protected static function convert_var_to_float_array($what): array
    {
        //convert to array, then each element to float
        $array = self::convert_to_array($what);
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[$key] = self::convert_to_float($value);
        }
        return $ret;
    }

    /**
     * @throws JsonException
     */
    protected static function convert_var_to_object_array($what): array
    {
        //convert to array, then each element to object
        $array = self::convert_to_array($what);
        $ret = [];
        foreach ($array as $key => $value) {
            $ret[$key] = self::convert_to_object($value);
        }
        return $ret;
    }

    /**
     * null stays null
     * @param mixed $what
     *
     * @return object|null
     * @throws JsonException
     */
    protected static function convert_to_object(mixed $what): ?object
    {
        if (is_null($what)) {return null;} //nulls stay null
        if (empty($what) && (!(is_numeric($what) || is_bool($what)))) {
            $cast = (object)[];  //null already processed, if its empty other than null, a number or boolean
        } elseif (is_string($what)) {
            if (JsonHelper::isJson($what)) {
                try {
                    $cast = JsonHelper::fromString( $what, true, false );
                } catch (JsonException $bad_json) {
                    //should not get here if passed is_json
                    throw new LibConException("Cannot convert json string [$what] to object ". $bad_json->getMessage());
                }
            } else {
                throw new LibConException("Canot use string as json. Its not json\n [$what]: ");
            }
        } elseif (is_array($what)) {
            //convert from array to json, then from json to object
            $temp_json = static::convert_var_to_type($what,'json');
            $cast = static::convert_var_to_type($temp_json,'object');
        } elseif (is_object($what)) {
            $cast = $what;
        }
        else {
            $bad_type = gettype($what);
            throw new LibConException("Cannot convert [$bad_type] to object. Its not a json string, object, empty, or array " );
        }
        return $cast;
    }

    /**
     * empty strings are converted to null json
     * @param mixed $what
     *
     * @return string|null
     */
    protected static function convert_to_json(mixed $what): ?string
    {
        if (is_string($what)) {
            if (empty($what)) {$what = null;}
            if (JsonHelper::isJson($what)) {
                $cast = $what;
            } else {
                throw new LibConException("Canot use string as json. Its not json\n [$what]: ");
            }
        } elseif (is_object($what) || is_array($what) || is_null($what) || is_numeric($what) || is_bool($what)) {
            $cast = JsonHelper::toString( $what,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
        } else {
            throw new LibConException("Cannot covert [$what] to json. The data type is ". gettype($what));
        }
        return $cast;
    }

    /**
     * converts to float, nulls stay null
     * @param mixed $what
     *
     * @return float|null
     */
    protected static function convert_to_float(mixed $what): ?float
    {
        if (is_null($what)) {return null;} //nulls stay null
        if (is_numeric($what) || is_bool($what)) {
            $cast =  floatval($what);
        } elseif (is_object($what)) {
            if (method_exists($what , '__toString')) {
                $test = (string)$what;
            } else {
                if (empty($var)) {  //see if empty or __isset() is implemented and returns empty, then will be zero
                    $test = 0;
                } else {
                    $test = 1; //if it is not empty, or __isset not set, then will be one
                }
            }
            if (is_numeric($test)) {
                $cast =  floatval($test);
            } else {
                $class_name = get_class($what);
                throw  new LibConException("$class_name is an object whose string value [$test] is not numeric so cannot convert to unix timestamp float");
            }
        } elseif (is_string($what)) {
            //maybe a date time string, try to parse it
            try {
                $test = Carbon::parse($what);
                $cast = floatval($test->timestamp);
            } catch ( Exception ) {
                throw  new LibConException("[$what] is not a known date time string so cannot convert to float ");
            }

        }else {
            if (is_array($what)) {
                $val = print_r($what,true);
            } else {
                $val = (string)$what;
            }
            throw  new LibConException("[$val] is not numeric so cannot convert to float");
        }
        return $cast;
    }

    /**
     * converts to text, nulls stay null
     * objects have their string method called, if that does not exist, then cast to json
     * arrays are cast to json
     * @param mixed $what
     *
     * @return string|null
     * @throws JsonException
     */
    protected static function convert_to_text(mixed $what): ?string
    {
        if (is_null($what)) {return null;} //nulls stay null
        if (is_object($what)) {
            if ( method_exists( $what, '__toString' ) ) {
                $cast_before = (string) $what;
            } else {
                $cast_before = static::convert_var_to_type($what,'json');
            }
        } else if (is_array($what)) {
            $cast_before = static::convert_var_to_type($what,'json');
        } else if (is_bool($what)) {
            $cast_before = JsonHelper::toStringAgnostic($what,false,false);
        }else {
            $cast_before =  strval($what);
        }

        $cast = JsonHelper::to_utf8($cast_before); //unicode filter
        return $cast;
    }

    /**
     * converts to int or throws exception
     * empty things, not bool or numeric, are converted to null
     * @param mixed $what
     *
     * @return int|null
     *
     */
    protected static function convert_to_int(mixed $what): ?int
    {
        if (is_numeric($what) || is_bool($what)) {
            $cast =  intval($what);
        }
        elseif (empty($what)) {
            return null;
        }
        elseif (is_object($what)) {
            if (method_exists($what , '__toString')) {
                $test = (string)$what;
            } else {
                if (empty($var)) {  //see if empty or __isset() is implemented and returns empty, then will be zero
                    $test = 0;
                } else {
                    $test = 1; //if it is not empty, or __isset not set, then will be one
                }
            }
            if (is_numeric($test)) {
                $cast =  intval($test);
            } else {
                $class_name = get_class($what);
                throw  new LibConException("[$class_name] is an object whose string value [$test] is not numeric so cannot convert to integer");
            }
        } elseif (is_string($what)) { //see if non numeric string is a date time
            if (empty($what)) {
                $cast = null; //empty strings converted to integers will be null
            } else {
                //maybe a date time string, try to parse it
                try {
                    $test = Carbon::parse($what); //will return current time/date if what is empty
                    $cast = $test->timestamp;
                } catch ( Exception ) {
                    //cast it as a bool string
                    $cast = intval(JsonHelper::var_to_boolean($what));
                }
            }


        }else {
            if (is_array($what)) {
                $val = print_r($what,true);
            } else {
                $val = (string)$what;
            }
            throw  new LibConException("[$val] is not numeric so cannot convert to integer");
        }
        return $cast;
    }
    /**
     * converts to array or throws exception
     * single bool, int, float, non json string values are converted to arrays of one element
     * @param mixed $what
     *
     * @return array
     * @throws LibConException
     * @throws JsonException
     */
    protected static function convert_to_array(mixed $what): array
    {
        if (empty($what) && (!(is_numeric($what) || is_bool($what)))) {
            $cast = [];  //null already processed, if its empty other than null, a number or boolean
        } elseif (is_string($what) && (!(is_numeric($what) || is_bool($what)))  ) {
            if (JsonHelper::isJson($what)) {
                try {
                    $cast = JsonHelper::fromString( $what );
                } catch (JsonException $bad_json) {
                    //should not get here, ever, since passed is_json
                    throw new LibConException("Cannot convert json string [$what] to array ". $bad_json->getMessage());
                }
            } else {
                $cast = [$what];
            }
        } elseif (is_object($what)) {
            $temp_json = static::convert_var_to_type($what,'json');
            $cast = static::convert_to_array($temp_json);
        } elseif (is_array($what)) {
            $cast = $what;
        }
        elseif (is_numeric($what) || is_bool($what)) {
            $cast = [$what];
        }
        else {
            throw new LibConException("Cannot convert  to array. Its not empty, or a string, object, array,numeric,boolean or empty ". gettype($what));
        }
        return $cast;
    }
    /**
     * Will try to convert anything to a Carbon Object with the timezone given
     * Carbon may throw an exception if not valid
     * @param mixed $what
     * @param $timezone
     *
     * @return Carbon
     * @since 0.2.2
     *
     * @throws LibConException , if cannot cast $what to a string
     */
    protected static function convert_var_to_carbon(mixed $what, $timezone): Carbon
    {

        try {
            if ($what === false || $what === true) {
                throw new LibConException("Cannot process boolean to DateTime");
            }
            //first, some house keeping with the timezone
            if ( empty( $timezone ) ) {
                $timezone = 'utc';
            }

            if ( array_key_exists( $timezone, self::TZ_ALIAS ) ) {
                $timezone = self::TZ_ALIAS[ $timezone ]; //for example 'central';
            }

            if ( is_numeric( $what ) ) {
                $timestamp = intval( $what );
                $start     = Carbon::createFromTimestamp( $timestamp, $timezone );
            } else if ( is_string( $what ) ) {
                $start = Carbon::parse( $what); //do not cast timezone here, we want whatever is the original in the string,
                // and some strings like mysql do not have the tz info, will default to utc
            } else {

                if ($what instanceof DateTimeInterface ) {
                    $start = Carbon::instance($what);
                }
                else {
                    if ( is_object( $what ) ) {
                        if ( method_exists( $what, '__toString' ) ) {
                            $test = (string) $what;
                        } else {
                            $class_name = get_class( $what );
                            throw  new LibConException( "[$class_name] is an object which does not override __toString, so cannot convert to string to decide a date" );
                        }

                    } else if ( is_array( $what ) ) {
                        $huh = print_r( $what, true );
                        throw  new LibConException( "$huh is an array , so cannot convert to string to decide a date" );
                    } else {
                        $test = (string) $what;
                    }
                    if (empty($test)) {
                        throw new LibConException("Cannot create a date time from an empty string");
                    }
                    //try to cast it as a string, may be an exception
                    $start = Carbon::parse( $test, $timezone );
                }
            }
            $start->setTimezone( $timezone );
            return $start;
        } catch (LibConException $lib) {
            throw $lib;
        } catch ( Exception $e) {
            throw new LibConException("Could not parse datetime string",0,$e);
        }
    }

    /**
     * Assumes that the $what is already cast to the type the subtype belongs to
     * if $what is being converted to a boolean subtype, it cast as a bool, and then to the string
     *
     * if $what is being converted to a datetime subtype, its cast as a string, or integer, and then to the formatted text
     * @param mixed $what
     * @param string $subtype
     * @param string|null $detail
     *
     * @return mixed
     * @throws JsonException
     * @since 0.2.2
     */

    protected static function convert_var_to_subtype(mixed $what, string $subtype, ?string $detail): mixed
    {

        if (empty($subtype)) {
            throw new LogicException("Subtype param is empty");
        }

        if ($detail) {
            //Capitalize the first letter
            $old_detail = ucfirst($detail);
            if (array_key_exists($old_detail,self::TZ_ALIAS)) {
                $detail = self::TZ_ALIAS[$old_detail];
            }

        }
        //rules
        //if this is a date string subtype, we need to convert it to a Carbon first

        //if this is a boolean string subtype, we need to cast this to a boolean first

        switch ($subtype) {
            case self::BOOL_YES_NO : {
                return JsonHelper::var_to_binary_values($what,'yes','no');
            }
            case self::BOOL_TRUE_FALSE:{
                return JsonHelper::var_to_binary_values($what,'true','false');
            }
            case self::BOOL_ON_OFF:{
                return JsonHelper::var_to_binary_values($what,'on','off');
            }
            case self::BOOL_Y_N:{
                return JsonHelper::var_to_binary_values($what,'y','n');
            }
            case self::BOOL_T_F:{
                return JsonHelper::var_to_binary_values($what,'t','f');
            }
            case self::ISO8601:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toIso8601String();
            }
            case self::RFC822:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc822String();
            }
            case self::RFC850:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc850String();
            }
            case self::RFC1036:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc1036String();
            }
            case self::RFC1123:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc1123String();
            }
            case self::RFC2822:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc2822String();
            }
            case self::RFC3339:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc3339String();
            }
            case self::RFC7231:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRfc7231String();
            }
            case self::ISO8601ZULU:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toIso8601ZuluString();
            }
            case self::RSS:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toRssString();
            }
            case self::W3C:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toW3cString();
            }
            case self::ATOM:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toAtomString();
            }
            case self::COOKIE:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toCookieString();
            }
            case self::MYSQL:{
                $carbon = self::convert_var_to_carbon($what,$detail);
                return $carbon->toDateTimeString() ;
            }
            case self::ELEMENT_TEXT:
            case self::ELEMENT_STRING:	{
                return self::convert_var_to_string_array($what);
            }
            case self::ELEMENT_INTEGER:
            case self::ELEMENT_INT:	{
                return self::convert_var_to_int_array($what);
            }
            case self::ELEMENT_JSON:	{
                return self::convert_var_to_json_array($what);
            }
            case self::ELEMENT_FLOAT:	{
                return self::convert_var_to_float_array($what);
            }
            case self::ELEMENT_OBJECT:	{
                return self::convert_var_to_object_array($what);
            }
            default: {
                throw new LibConException("Cannot cast subtype, do not recognize [$subtype], it needs to be one of ". implode('|',self::SUBTYPES_SINGLE_ARRAY));
            }
        }
    }

    /**
     * null is always cast to null
     *
     * @param mixed $what
     * @param string $type
     *
     * @return string|array|bool|int|object|float|null
     * @throws JsonException
     * @since 0.2.2
     */
    protected static function convert_var_to_type(mixed $what, string $type): string|array|bool|int|null|object|float
    {

        if (is_null($what) && ($type !== self::BOOL_FORMAT)) {return null;}
        switch ($type) {
            case self::BOOL_FORMAT : {
                $cast = JsonHelper::var_to_boolean($what);
                break;
            }
            case self::TRI_FORMAT : {
                //if string, then spaces are removed
                $processed_what = $what;
                if (is_string($processed_what)) {
                    $processed_what = trim($what);
                }
                //if empty, then make it null
                //else its the same as a boolean
                if (empty($processed_what) && (!is_numeric($processed_what)) && (!is_bool($processed_what))) {
                    $cast = null;
                } else {
                    $cast = JsonHelper::var_to_boolean($processed_what);
                }
                break;
            }
            case self::INT_FORMAT :{
                $cast = self::convert_to_int($what);
                break;
            }

            case self::FLOAT_FORMAT :{
                $cast = self::convert_to_float($what);
                break;
            }

            case self::TEXT_FORMAT : {
                $cast = self::convert_to_text($what);
                break;
            }

            case self::BINARY_FORMAT : {
                if (is_object($what)) {
                    if ( method_exists( $what, '__toString' ) ) {
                        $cast = (string) $what;
                    } else {
                        $class_name = get_class($what);
                        throw  new LibConException("[$class_name] is an object which does not implement the __toString override function. It has no text conversion. Use the Json Case instead");
                    }
                } else if (is_array($what)) {
                    $cast = print_r($what,true);
                }else {
                    $cast =  strval($what);
                }
                break;
            }

            case self::JSON_FORMAT : {
                $cast = self::convert_to_json($what);
                break;
            }

            case self::OBJECT_FORMAT : {
                $cast = self::convert_to_object($what);
                break;
            }
            case self::ARRAY_FORMAT : {
                $cast = self::convert_to_array($what);
                break;
            }

            default: {
                throw new LogicException("Did not match a format [$type] in the switch statement.");
            }
        }
        return $cast;
    }


    /**
     *
     * prints a table to the screen of each results and its type
     * its low tech, but I needed to test all combinations and its easier than making complicated test cases
     *
     * Make a table with columns of the types to be tested
     * and each row tests a different data type
     * if cannot cast, then the error notice in the cell is determined error_level
     *
     * @param integer $error_level <p>
     *   0 : there is an x when a conversion does not work
     *   1 : an error description is put in every box when something does not work
     *   2 : an error description and the exception class, the line and file it appeared in
     *
     * </p>
     * @return string
     * @since 0.2.2
     *
     * @see LibConTestStringAndFull for an unused testing stub
     */
    public static function test(int $error_level = 0): string
    {
        $types_to_test = [
            'bool',
            'tri',
            'int',
            'float',
            'text',
            'binary',
            'json',
            'object',
            'array',
            //boolean strings
            'text.bool-y/n',
            'text.bool-t/f',
            'text.bool-yes/no',
            'text.bool-true/false',
            'text.bool-on/off',
            //date time (defaults to utc, but see below for timezone)
            'text.time/iso8601',
            'text.time/rfc822.central',
            'text.time/rfc850.utc',
            'text.time/rfc1036',
            'text.time/rfc1123',
            'text.time/rfc2822.mountain',
            'text.time/rfc3339.pacific',
            'text.time/rss',
            'text.time/w3c',
            'text.time/atom',
            'text.time/cookie',
            'text.time/rfc7231',
            'text.time/iso8601zulu',
            //date time with timezone, any php timezone is allowed @see http://php.net/manual/en/timezones.php
            'text.time/iso8601.eastern',
            'text.time/cookie.europe/kiev',
            'text.time/rfc1036.central'
        ];

        $thing_to_test_with= [
            ['value'=>true,'type'=>'boolean true' ],
            ['value'=>false,'type'=>'boolean false' ],
            ['value'=>'yes','type'=>'string true' ],
            ['value'=>'no','type'=>'string false' ],
            ['value'=>-40,'type'=>'integer (negative)' ],
            ['value'=>0,'type'=>'integer (zero)' ],
            ['value'=>10,'type'=>'integer' ],
            ['value'=>1551375586,'type'=>'integer (a unix time)' ],
            ['value'=>3.1415,'type'=>'float' ],
            ['value'=>0.0,'type'=>'float (zero)' ],
            ['value'=>-101.25647,'type'=>'float (negative)' ],
            ['value'=>'hello there','type'=>'text' ],
            ['value'=> pack("nvc*", 0x1234, 0x5678, 65, 66),'type'=>'binary' ],
            ['value'=>'{"widget": {"debug": "on" } }','type'=>'json' ],
            ['value'=>(object)['apple'=>'worm'],'type'=>'standard object' ],
            ['value'=>new LibConTestStringAndEmpty(''),'type'=>'class empty string and is not set' ],
            ['value'=>new LibConTestStringAndEmpty('33'),'type'=>'class numeric string and is not set' ],
            ['value'=>new LibConTestStringAndEmpty('Barney'),'type'=>'class non numeric string and is not set' ],
            ['value'=>new LibConTestStringAndEmpty('yes'),'type'=>'class bool positive string and is set' ],
            ['value'=>new LibConTestStringAndEmpty('50'),'type'=>'class numeric string and is set' ],
            ['value'=>new LibConTestStringAndEmpty('Harvey'),'type'=>'class non numeric string and is set' ],
            ['value'=>[1,2,3,4],'type'=>'array' ],
            ['value'=>[],'type'=>'empty array' ],
            ['value'=>'2019-02-28T17:39:46+00:00','type'=>'iso1806 Date String' ],
            ['value'=>'Thu, 28 Feb 19 11:39:46 -0600','type'=>'rfc822 Date String' ],
            ['value'=>'Thursday, 28-Feb-19 17:39:46 utc','type'=>'rfc850 Date String' ],
            ['value'=>'Thu, 28 Feb 19 17:39:46 +0000','type'=>'rfc1036 Date String' ],
            ['value'=>'Thu, 28 Feb 2019 17:39:46 +0000','type'=>'rfc1123 Date String' ],
            ['value'=>'Thu, 28 Feb 2019 10:39:46 -0700','type'=>'rfc2822 Date String' ],
            ['value'=>'2019-02-28T09:39:46-08:00','type'=>'rfc3339 Date String' ],
            ['value'=>'Thu, 28 Feb 2019 17:39:46 +0000','type'=>'rss Date String' ],
            ['value'=>'2019-02-28T17:39:46+00:00','type'=>'w3c Date String' ],
            ['value'=>'2019-02-28T17:39:46+00:00','type'=>'atom Date String' ],
            ['value'=>'Thursday, 28-Feb-2019 17:39:46 utc','type'=>'cookie Date String' ],
            ['value'=>'Thu, 28 Feb 2019 17:39:46 GMT','type'=>'rfc7231 Date String (always utc)' ],
            ['value'=>'2019-02-28T17:39:46Z','type'=>'iso8601zulu Date String (always utc)' ]


        ];
        $s = "<table  class='table table-striped conversions'>" .
            '<caption>Conversion Chart (if a datetime type does not have a timezone , the default timezone is utc (+0). Also any php timezone can be used)</caption>' .
            '<thead>' .
            '<tr>
		<th style="font-weight: bold;color: black; background-color: darkgray; min-width: 200px">Test</th>
		<th style="font-weight: bold;color: black; background-color: darkgray">Original</th>' ;
        for($i = 0; $i< sizeof($types_to_test) ; $i++ ) {
            $s .= "<th style='font-weight: bold;color: black; background-color: darkgray'>$types_to_test[$i]</th>";
        }

        $get_var_info = function($var) {
            ob_start();
            var_dump($var);
            $result = ob_get_clean();
            $output = str_replace(__FILE__,'',$result);
            preg_match('/:\d+:/', $output, $output_array);
            if ($output_array && is_array($output_array) && sizeof($output_array) > 0) {
                $output = str_replace($output_array[0],'',$output);
            }


            return $output;
        };

        $s .= '</tr>' .
            '</thead>' .
            '<tbody>';
        for($j = 0; $j< sizeof($thing_to_test_with) ; $j++ ) {
            $s .= "<tr>";
            $s .= "<td style='font-weight: bold;background-color: lightgreen'>{$thing_to_test_with[$j]['type']}</td>";
            $original_info = $get_var_info($thing_to_test_with[$j]['value']);
            $s .= "<td style='font-weight: bold;background-color: #77c877'>$original_info</td>";
            $value = $thing_to_test_with[$j]['value'];
            for($i = 0; $i< sizeof($types_to_test) ; $i++ ) {
                try {
                    $format_string = $types_to_test[ $i ];
                    $c             = LibCon::parseFormatString( $format_string );
                    $casted        = $c->convert( $value );
                    $data          = $get_var_info( $casted );
                    $s             .= "<td style='background-color: lightgrey'>$data</td>";
                } catch ( Exception $e) {
                    switch ($error_level) {
                        case 0: {
                            $err = 'x';
                            break;
                        }
                        case 1: {
                            $err = $e->getMessage();
                            break;
                        }
                        case 2: {
                            $ex_class = get_class($e);
                            $err = $e->getMessage() . ' [' .$ex_class .'] '. $e->getFile() . ' ' . $e->getLine();
                            break;
                        }
                        default: {
                            $err = 'x';
                        }
                    }

                    $s             .= "<td class='con-error' style='background-color: darkred; color: white; text-align: center'>$err</td>";
                }
            }
            $s .= "</tr>";
        }


        $s .= "</tbody></table>";

        return $s;
    }


}

