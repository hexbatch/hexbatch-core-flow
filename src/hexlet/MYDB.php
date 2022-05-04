<?php
/** @noinspection PhpUnused */
namespace app\hexlet;
use Exception;
use app\hexlet\hexlet_exceptions\SQLDataException;
use app\hexlet\hexlet_exceptions\SQLException;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;


/**
 * @author Will Woodlief
 * @license MIT Licence
 * @link https://gist.github.com/willwoodlief/caeae241fb36bffe470ed77a18958097 for updates and code fixes outside this project
 *
 * This is a general database class I have been using though the years, and has its origin from all over the internet
 *   as well as my improvements.
 *
 * I often use it as a drop in class
 *
 */

require_once realpath(dirname(__FILE__)) . "/JsonHelper.php";





/**
 * Class MYDB
 *   Database wrapper class which makes it easier to do prepared statements and commits and rollbacks
 *    throws exceptions if there are any sql errors
 */
class MYDB
{


    /**
     * @const integer MYDB::LAST_ID is used in @see MYDB::execSQL() for insert statements only
     */
    const LAST_ID = 1;


    /**
     * @const integer MYDB::ROWS_AFFECTED is used in @see MYDB::execSQL() for update statements only
     */
    const ROWS_AFFECTED = 2;

    /**
     * @const integer MYDB::RESULT_SET is used in @see MYDB::execSQL() for select statements only
     */
    const RESULT_SET = 3;


    /**
     * @var null|mysqli the mysqli object, the database connection
     */
    public ?mysqli $mysqli = null;

    /**
     * @var bool $destroyMe is a flag which tells the destructor if this connection needs to be taken down
     */
    private bool $destroyMe = false;

    /**
     * @var mysqli_result|null is used in the static connection helper methods
     */
    protected null|mysqli_result $result = null;

    //  $transactionCount is  in $mysqli;
    //for storing and reusing prepared statements as requested, they are put into
    //statementCache in the $mysqli

    /**
     * @var int $aardvark is used in the constructor to help make sure there are not connection leaks
     */
    private static int $aardvark = 0;//to debug to see if more than one connection

    /**
     * @var int $AnExistCount is a remembering of how many open connections there are when the object is created
     */
    private int $AnExistCount = -1010;


    protected array $statement_cache = [];
    
    protected int $transactionCount = 0;

    /**
     *  Tests if a value is part of an enum set of the column
     * @param $test
     * @param $table
     * @param $column
     * @return bool
     * @throws
     */
    public function checkEnumLegal($test, $table, $column): bool
    {
        $ee = new MYDB_EnumValues($table, $column, $this->mysqli);
        return $ee->belongs($test);

    }

    /**
     * Gets the hash of cached statements
     * @see MYDB::execSQL()
     * @return array
     *    key value pair of "name to remember prepared statement": mysqli_stmt
     *
     */
    public function getCachedStatements(): array
    {
        return $this->statement_cache;
    }

    /**
     * The object can either open a new connection or be a wrapper for an existing connection
     *
     * MYDB constructor.
     * @param $mysqli object|null <p>
     *   pass in null to make a new database connection
     *   pass in an existing mysqli object to wrap it around this class
     * </p>
     * @param array|null $db_setup <p>
     *  if creating new connection ($mysqli is null above) then this must be filled out
     *   @param bool $bIgnoreAardvark <p>
     *    if set to false, then an exception will be thrown if more than one database connection is kept open at one time
     *    if set to true then this behavior is turned off
     *    default is false, which means the default will be throwing exceptions if more than one db connection open
     * </p>
     * @throws SQLException  if connection cannot be made, or $bIgnoreAardvark is false and a second connection made
 * </p>
     *@see MYDB::getMySqliDatabase() for details
     *  but if passing in mysqli object above, then this will be ignored
     *  default is null
     *
     * </p>
     */
    public function __construct(?object $mysqli, array $db_setup = null, bool $bIgnoreAardvark = false)
    {
        $this->transactionCount = 0;
        $this->statement_cache = [];
        if (is_null($mysqli)) {

            $this->destroyMe = true;
            $this->mysqli = self::getMySqliDatabase($db_setup);
            MYDB::$aardvark++;
            $this->AnExistCount = MYDB::$aardvark;

            if (!$bIgnoreAardvark) { //only if deliberate should the program not exit if more than one connection open
                if ($this->AnExistCount > 1) {
                    try {

                        $what = $this->AnExistCount;
                        throw new SQLException("++>exists greater than one [$what]", $this->AnExistCount);

                    } catch (SQLException $e) {
                        //getEID
                        if (method_exists($e, 'getEID')) {
                            $eid = $e->getEID();
                        } else {
                            $eid = '';
                        }
                        print "<hr>too many database connectoins open eid[$eid]<hr>";
                        exit;
                    }
                }
            }
        } else {
            if (!($mysqli instanceof mysqli)) {
                $out = print_r($mysqli, true);
                throw new SQLException("the mysqli passed in is not who you think it is", $out);
            }
            $this->AnExistCount = MYDB::$aardvark;
            $this->destroyMe = false;
            $this->mysqli = $mysqli;
        }


        if (!isset($this->transactionCount)) {
            $this->transactionCount = 0;
        }


    }

    /**
     *  notice that it will not close out the mysqli if this object is being used as a smart pointer
     */
    public function __destruct()
    {
        if ($this->destroyMe) {
            //close out the cached statements

            foreach ($this->statement_cache as $s) {
                /** @var $s mysqli_stmt */
                $s->close();
                unset($s);
            }
            mysqli_close($this->mysqli);
            unset($this->mysqli);
            $this->mysqli = null;
        }

    }

    /**
     * Gets the underlying mysqli object
     * @return mysqli|null
     */
    public function getDBHandle() : ?mysqli
    {
        return $this->mysqli;
    }

    /**
     * begins an sql transaction for the mysqli object
     * once this is called @see MYDB::commit() needs to be called to apply anything between this and that to the database
     * This is applied to the mysqli object in this class and not the class itself
     * Can be called multiple times, but the @see MYDB::commit() must be called same amount of times before the data is commited
     * @return void
     */
    public function beginTransaction(): void
    {


        $this->transactionCount++;

        //jad( "doing begin ".$this->transactionCount);
        if ($this->transactionCount > 1) {
            return;
        }
        $this->mysqli->autocommit(FALSE);

    }

    /**
     * Finishes an sql transaction for the mysqli object
     *   But will only do the work when this is called same number of times as @see MYDB::beginTransaction() was before
     * @throws SQLException if beginTransaction was not called ahead of time
     * @return void
     */
    public function commit(): void
    {

        $this->transactionCount--;
        //jad( "doing commit ".$this->transactionCount);
        if ($this->transactionCount < 0) {
            throw new SQLException('Commit in mydb is misaligned', $this->transactionCount);
        }
        if ($this->transactionCount > 0) {
            return;
        }
        $this->mysqli->commit();
        $this->mysqli->autocommit(TRUE);


    }

    /**
     * Rolls back a transaction, will set the transaction count, relied on by @see MYDB::commit() to 0
     * @return void
     */
    public function rollback(): void
    {
        //jad( "doing rollback");
        $this->mysqli->rollback();
        $this->mysqli->autocommit(TRUE);
        $this->transactionCount = 0;
    }

    /**
     * Object wrapper for doing a mysqli_query. If one needs to not use @param $sql
     * @return mysqli_result|null
     * @throws SQLException
     *@see MYDB::staticExecute()
     * @see MYDB::execSQL()
     * Can be used with @see MYDB::fetch()
     * and @see MYDB::fetchThrowIfNull()
     */
    public function execute($sql): ?mysqli_result
    {
        $this->result = self::staticExecute($sql, $this->mysqli);
        return $this->result;
    }

    /**
     * Can get the data after using @see MYDB::execute()
     * use in a loop, it will return boolean false (null) if end of data rows
     * @return array|null  the array in key:value pairs for column_name:data
     */
    public function fetch(): ?array
    {
        return mysqli_fetch_array($this->result);
    }

    /**
     * Can get the data after using @see MYDB::execute()
     * call @see MYDB::getRowCount() to see how many rows are called
     *   use in a for(;;) statement
     *   its designed to throw an exception is called too many times
     * @return array|null
     * @throws SQLDataException if called after data is finished
     */
    public function fetchThrowIfNull(): ?array
    {
        $res = mysqli_fetch_array($this->result);
        if (!$res) {
            throw new SQLDataException("no_data");
        }
        return $res;
    }

    /**
     * Gets the created row Primary key right after an insert, can be used after both
     * @see MYDB::execute() and
     * @see MYDB::execSQL()
     * @return integer <p>
     *   will be 0 if no last insert value, or if the table does not have AUTO_INCREMENT on a PK
     */
    public function getLastIndex(): int
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Gets the number of rows in a results set
     * Can only be used after @return int|string
     *@see MYDB::execute()
     */
    public function getRowCount(): int|string
    {
        return mysqli_num_rows($this->result);
    }

    /**
     * Prepares a statement without binding variables
     * Helper wrapper for mysqli_prepare
     *
     * @param $sql
     *
     * @return bool|mysqli_stmt
     * @throws SQLException  if there is any sql error
     */
    public function prepare($sql): bool|mysqli_stmt
    {
        $st = mysqli_prepare($this->mysqli, $sql);
        if (!$st) {
            self::throwSQLErrorWithHtml("Could not prepare statement", $this->mysqli);
        }
        return $st;
    }

    /**
     * strips tags and escapes string for insertion into database
     * @see MYDB::ICleanString()
     * @param $string
     * @return string
     */
    public function cleanString($string): string
    {
        try {
            $string = self::sanitizeString($string, false, $this->mysqli);
        } catch (SQLException $e) {
            print $e; //will never get here, but needed so don't have to declare exception in phpdoc
        }

        return $string;
    }




    /**
     * Multipurpose statement to write prepared statements to the database
     *
     * @param $sql object|string <p>
     *   if string, then the sql statement must have at least one ? in it. if a statement does not need a ?
     *   then add a "AND ?" to the where, for example, and then place a variable with 1 in the params
     *
     *   Can be object of type mysqli_stmt, if need to pass in an already compiled statement
     * </p>
     * @param $params array|null <p>
     *
     *   The rest of the array depends on if using ? notation or named  notation in the sql string and if $sql is an object or a string
     *
     * DANGER: Named params do not work for all use cases, would not use
     *
     * @param $close integer <p>
     *   these are the following named constants
     *   MYDB::LAST_ID        returns the primary key of an insert operation
     *   MYDB::ROWS_AFFECTED  returns the number of rows affected during an update or delete
     *   MYDB::RESULT_SET     used to get the results back from a select
     *
     *   Please note that the proper close value must be put with the type of sql statement
     *    it will not be an error if the wrong close type is put in, but the return results may be unexpected
     *
     *   anything not MYDB::LAST_ID or MYDB::ROWS_AFFECTED will be MYDB::RESULT_SET
     *   default is  MYDB::RESULT_SET
     * </p>
     * @param string|null $lookupKey <p>
     *  a string to remember the compiled sql statement,
     *  will speed up things a lot if called with same statement multiple times
     *  it can save the step of compiling the statement for later identical calls if the the same sql is used again
     *  when lookup key is set, it will ignore the $sql string if there is already a prepared statement under that key
     *  but when $lookupKey is set to null, will not save the statement for later
     *    the $lookupKey is set to the mysqli object, and not to the MYDB object. Which is important to remember if
     *     creating this class from an existing mysqli object
     *
     *   If $lookupKey is null this function will close the statement if no key if sql is a string
     *   lookup keys must have the phrase @sey@ in them or else an exception is thrown
     *   WARNING: USE AT OWN RISK, THIS IS NOT USED IN OTHER LIBRARIES FOR A VERY GOOD REASON.
     *      Forgetting to change the key for different statements will lead to very hard to track down bugs and will shorten your lifespan
     *   But, since I am the only one using this, I like it, because it increases speed a lot in some situations,
     *    like when ten thousand things need to be updated
     * </p>
     * @return mixed. <p>
     *   The return is based on what the close param is
     *     MYDB::LAST_ID        returns the primary key of an insert operation
     *     MYDB::ROWS_AFFECTED  returns the number of rows affected during an update or delete
     *     MYDB::RESULT_SET     an array of standard objects where each property is a key value pair of column_name:value
     * </p>
     * @throws SQLException if anything goes wrong, including sql errors, bad params, etc
     *@example
     *  execSQL("SELECT * FROM table WHERE id = ?", array('i', $id), MYDB::ROWS_AFFECTED);
     *  execSQL("SELECT * FROM table");
     *  execSQL("INSERT INTO table(id, name) VALUES (?,?)", array('ss', $id, $name), MYDB::LAST_ID);
     *  execSQL("UPDATE table(id, name) SET A = ? , B = ? ", array('is', $id, $name), MYDB::ROWS_AFFECTED, 'Remember this statement for cool method');
     *  execSQL("SELECT * FROM table where groups = :groups AND dogs = :dogs", ['groups'=>'brown','dogs'=>['value'=>4.5, 'flag'=>'d']], MYDB::ROWS_AFFECTED);
     *  execSQL("SELECT * FROM table where groups = :groups AND dogs = :dogs NOT IN (:mud)", [
     *                                                                      'dogs'=>['value'=>4.5, 'flag'=>'d'],
     *                                                                      'groups'=> $brown,
     *                                                                      'mud'=> get_mud_ratio()/2 * $f]);
     *
     * @example for ? notation  "SELECT apple from tree_table where color = ?"
     *          each param is ?
     *
     * @example for named notation  "SELECT apple from tree_table where color = :color_name"
     *      see the : before the param name, all param names need to begin with :
     *
     *  If $sql is an object must use ? notation
     *
     *   for ? notation
     *   a single dimension array needs to be passed
     *    the first element of the array will be a string, these will be the letters discussed in
     *   @link http://php.net/manual/en/mysqli-stmt.bind-param.php
     *    i	    corresponding variable has type integer
     *    d	    corresponding variable has type double
     *    s	    corresponding variable has type string
     *    b	    corresponding variable is a blob and will be sent in packets
     *
     *   these letters need to in order of the params in the sql statement
     *     the rest of the elements of the array are the values matching the order and number of ? and the letter flags
     *     It is an exception to have a mismatch in the count of ?, letter, and value
     *     The values can be literals, variables, or expressions
     *     @example  ['iid',4,$b,time()]
     *
     *
     *   for named param notation (like :puppies )
     *     the array is a hash of param information, and the param values can be either a single value or an array
     *     @example   ['what'=>3, 'is'=> ['value'=>'nothing','flag=>'s']]
     *     if the value is not an array, then the flag is assumed to be an s
     *       otherwise if the value is an array , then the keys of value and flag must be there, else an exception will rise
     *
     *     The order of the params do not matter
     *      Each value can be passed as a literal or a variable or expression
     *     @example [:first=>1,'groups'=>$b,'betty_right'=>['flag'=>'d','value'='on tv'] 'another'=>3.1415]
     *
     *   Can pass in null if don't need to bind params
     *   When passing in values, booleans will be converted to 0 or 1, and arrays will be converted to json, and objects will be cast to string value
     *
     * Default is null
     *
     * </p>
     */
    public function execSQL(object|string $sql, ?array $params=null, int $close=MYDB::RESULT_SET, string $lookupKey = null): mixed
    {


        $parameters = [];
        $row = [];
        $mysqli = $this->mysqli;
        $bIsStatement = false;
        //pass in prepared statement or sql
        $bStatementClose = true;
        if (is_object($sql) && get_class($sql) == 'mysqli_stmt') {
            $bIsStatement = true;
            $bStatementClose = false;
        } elseif ($lookupKey) {
            //check to make sure it has @sey@
	        if (!str_contains($lookupKey, '@sey@')) {
	        	throw new SQLException("Lookup key does not have @sey@ in it somewhere");
	        }

        	//see if we previously put a statement in this key

            if (isset($this->statementCache[$lookupKey])) {
                $previousStatement = $this->statementCache[$lookupKey];
                $sql = $previousStatement;
                $bIsStatement = true;
                $bStatementClose = false;
            }


        }

        //removed named params block

        $stmt = $bIsStatement ? $sql :

            $mysqli->prepare($sql);
        if (!$stmt) {
            self::throwSQLErrorWithHtml("Could not prepare statement", $this->mysqli,$sql);
        }

        //if lookup key, then put this statement in the key
        if ($lookupKey) {
            $this->statement_cache[$lookupKey] = $stmt;
            $bStatementClose = false;
        }


        try {

            //make sure all the params are converted from php data types: arrays and booleans
	        if ($params ) {
		        for($i = 1; $i < count($params); $i++) {
			        $params[$i] = JsonHelper::toStringAgnostic($params[$i]);
		        }
	        }
        } catch ( Exception $e) {
            throw new SQLException($e->getMessage() . " : Index was $i");
        }



        //only call bind if $params is not empty
        if (!empty($params)) {
            if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params))) {

                self::throwSQLStatement("Could not bind param ", $stmt,$sql . " " . JsonHelper::toString($params));
            }
        }


        $res = $stmt->execute();

        if (!$res) {
            self::throwSQLStatement("could not execute statement ", $stmt,$sql);
        }

        if ($close === MYDB::ROWS_AFFECTED) {
            $retVal = $stmt->affected_rows;
            //$retVal = $stmt->num_rows();
            if ($bStatementClose) {
                $stmt->close();
            }

            return $retVal;

        } elseif ($close === MYDB::LAST_ID) {
            $retVal = $mysqli->insert_id;
            if ($bStatementClose) $stmt->close();
            return $retVal;
        } else {
            $results = [];
            $meta = $stmt->result_metadata();

            if (!$meta) {
                return $results;
            }

            while ($field = $meta->fetch_field()) {
                $parameters[] = &$row[$field->name];
            }


            if (!call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters))) {
                self::throwSQLStatement("Could not bind result  ", $stmt);
            }
            while ($stmt->fetch()) {
                $x = array();
                foreach ($row as $key => $val) {
                    $x[$key] = $val;
                }
                $results[] = (object)$x;
            }

            $result = $results;
        }

        if ($bStatementClose) $stmt->close();


        return $result;
    }

    private function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * Ease of use function to update a single row in a table
     * @param string $table  name of the table
     * @param mixed $id   primary key value of the table
     * @param array $fields  column_name: value hash
     * @param string $pk_name, if the primary key is not called id, then put the name of the primary key here
     * @return integer the number of rows updated, will be 0 or 1
     * @throws SQLException when the underlying table or field does not exist
     */
    public function update(string $table, mixed $id, array $fields, string $pk_name = 'id'): int
    {


        $flags = '';
	    $values_data = [];
	    $update_parts = [];

	    /**
	     * @var MYDB_Value|string $value
	     */
        foreach ($fields as $name => $value) {

	        if (is_object($value) && is_a($value,'hexlet\MYDB_Value')) {
		        $update_parts[] = "$name = $value->expression";
		        $values_data[] = $value->value;
		        $flags .= $value->flag;
	        } else {
		        $update_parts[] = "$name = ?";
		        $values_data[] = $value;
		        $flags .= 's';
	        }
        }

	    $set =  implode(',',$update_parts);

        $flags .= 's';
        $values_data[] = $id;

        array_unshift($values_data, $flags);
        $sql = "UPDATE $table SET $set WHERE $pk_name = ?";

        return $this->execSQL($sql, $values_data, MYDB::ROWS_AFFECTED);
    }

    /**
     * ease of use function to insert array
     * @param string $table  the name of the table inserting into
     * @param array $fields , or array of arrays, <p>
     *      if single array, then its an array of  key value pairs of each column to use in the insert, column_name=>value
     *      if array of arrays, then its an array whose values are each an array described above
     * @return array|integer <p>
     *    if the fields are
     * @throws SQLException
     */
    public function insert(string $table, array $fields): int|array
    {
        if ( empty($fields)) { throw new SQLException("Insert Function in database class needs fields to be a populated array");}

        if (isset($fields[0]) && is_array($fields[0])) {
            $ret = [];
            foreach ($fields as $f) {
                $ret[]= $this->insert($table,$f);
            }
            return $ret;
        }
        $sql_columns = [];
        $sql_values = [];
	    $values_data = [];

        $flags = '';


	    /**
	     * @var MYDB_Value|string $value
	     */
        foreach ($fields as $name => $value) {

	        $sql_columns[] = $name;


        	if (is_object($value) && is_a($value,'hexlet\MYDB_Value')) {
		        $sql_values[] = $value->expression;
		        $values_data[] = $value->value;
		        $flags .= $value->flag;
	        } else {
		        $sql_values[] = '?';
		        $values_data[] = $value;
		        $flags .= 's';
	        }
        }
        $columns_string = implode(',',$sql_columns);
        $values_string = implode(',',$sql_values);

        array_unshift($values_data, $flags);
        $sql = "INSERT INTO $table($columns_string) VALUES ($values_string) ;";

        return $this->execSQL($sql, $values_data, MYDB::LAST_ID);
    }

    /**
     * Internal function to connect to database
     * @param $configs array <p>
     *   has the following values which must be supplied
     *     username : database username
     *     password : for the username
     *     host:      the url, which can be localhost or an ip, or url
     *     database_name: name of the database
     *  optional values:
     *    character_set: if not supplied the character set will be utf8
     * </p>
     * @return mysqli
     * @throws SQLException if connection fails
     */
    protected static function getMySqliDatabase(array $configs): mysqli
    {

        $MySQLUsername = $configs['username'];
        $MySQLPassword = $configs['password'];
        $sql_address = $configs['host'];
        $dbname = $configs['database_name'];
        $charset = 'utf8';
        if (isset($configs['character_set']) && $configs['character_set']) {
            $charset = $configs['character_set'];
        }
        $port = '';
        if (isset($configs['port'])) {
	        $port = $configs['port'];
        }

        $mysqli = mysqli_connect($sql_address, $MySQLUsername, $MySQLPassword, $dbname,$port);
        if (!$mysqli) {
            throw new SQLException(sprintf("Connect failed:<br> %s\n", mysqli_connect_error()));
        }

        //set for unicode

        $mysqli->query("SET CHARACTER SET $charset");
        $mysqli->set_charset($charset);
        return $mysqli;

    }


    /**
     * Helper function to throw errors with messages from the mysqli object
     *
     * @param mysqli $mysqli  cannot be null
     * @param $sql string additional information, usually here the sql string that caused the issue
     *
     * @throws SQLException every time
     */
    public static function throwSQLError(mysqli $mysqli, string $sql)
    {
        $help = sprintf("SQL Error\n %s\nSQL is:\n %s", mysqli_error($mysqli), $sql);
        throw new SQLException($help);
    }

    /**
     * helper method to throw errors with messages from the mysqli object, has html line breaks
     *
     * @param $msg1 string additional information, usually here the sql string that caused the issue
     * @param mysqli $mysqli  object cannot be null
     * @param string|null default null information to put on next line
     *
     * @throws SQLException every time
     */
    public static function throwSQLErrorWithHtml(string $msg1, mysqli $mysqli, $msg2 = null)
    {
        if ($msg2) {
            throw new SQLException(sprintf("%s:<br> %s<br>    error:<br>%s<br>", $msg1, $msg2, mysqli_error($mysqli)));
        } else {
            throw new SQLException(sprintf("%s:<br>error:<br>%s<br>", $msg1, mysqli_error($mysqli)));
        }
    }

//

    /**
     * Helper function to throw errors with messages from the mysqli_stmt object , has html line breaks
     *
     * @param string $msg1  additional information, usually here the sql string that caused the issue
     * @param mysqli_stmt $stmt  mysqli_stmt
     * @param string|null $msg2 additional information on the next line (optional)
     *
     * @throws SQLException every time
     */
    public static function throwSQLStatement(string $msg1, mysqli_stmt $stmt, string $msg2 = null)
    {
        if ($msg2) {
            throw new SQLException(sprintf("error:<br>\n%s<br>\n%s<br>\n%s<br>", $msg1,  mysqli_stmt_error($stmt),$msg2));
        } else {
            throw new SQLException(sprintf("%s:<br>error:<br>%s<br>", $msg1, mysqli_stmt_error($stmt)));
        }

    }

    /**
     * helper function to execute a mysqli_stmt
     * @link http://php.net/manual/en/mysqli-stmt.execute.php
     *
     * @param null|mysqli_stmt $state statement object
     *
     * @return void
     * @throws SQLException if something goes wrong
     */
    public static function executeStatement(?mysqli_stmt $state) : void
    {
        if ( empty($state)) {
            throw new SQLException("statement was null or empty");
        }
        $res = mysqli_stmt_execute($state);
        if (!$res) {
            self::throwSQLStatement("could not execute statement", $state);
        }
    }

    /**
     * Debugging function to print out the character set information of a connection with html line breaks
     * this method will print things directly out
     * @param $mysqli
     * @throws SQLException
     */
    public static function printDatabaseLanguageVariables($mysqli): void
    {
        $sql = "show variables like 'character_set%'";

        $res = self::staticExecute($sql, $mysqli);


        // GOING THROUGH THE DATA
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $name = $row['Variable_name'];
                $value = $row['Value'];
                print "[$name]=>[$value]<br>";
            }
        } else {
            echo 'NO RESULTS';
        }
    }


    /**
     * Helper method to run mysqli_query and throw exception if wrong
     *
     * @param string $query the sql string to execute
     * @param object|mysqli database object
     *
     * @return mysqli_result the result of the query
     * @return bool|mysqli_result
     * @throws SQLException if anything goes wrong
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function staticExecute(string $query, $mysqli)
    {
        if ( empty($query)) {
            throw new SQLException("sql was null or empty");
        }

        $res = mysqli_query($mysqli, $query);
        if (!$res) {
            throw new SQLException(sprintf("Could not run execute:<br> %s<br>    sql:<br>%s<br>", mysqli_error($mysqli), $query));
        }
        return $res;
    }


    /**
     * returns true if the object can be interpreted as a whole number
     * will return false if can be interpreted as a fraction or only a non number
     * @param mixed $var
     * @return bool
     */
    public static function is_whole_number(mixed $var): bool
    {
        return (is_numeric($var) && (intval($var) == floatval($var)));
    }

    /**
     * Helper function that makes sure that the input can only be a whole number and nothing else
     * @param mixed $number
     * @return bool true if only a whole number, false if anything else
     */
    public static function isNumberClean(mixed $number): bool
    {
        if (!self::is_whole_number($number)) {
            return false;
        }
        $oldNumber = $number;
        $number = preg_replace('/[^\d-]/', '', $number);
        if ($oldNumber != $number) {
            return false;
        }
        return true;
    }


    /**
     * Helper function that will throw an exception if the number is not indisputably a whole integer
     * @param mixed $number
     * @return string value of the input
     * @throws SQLDataException if not a whole integer only
     */
    public static function cleanNumber(mixed $number): string
    {

        $what = self::isNumberClean($number);
        if (!$what) {
            throw new SQLDataException("not a whole number: $number" );
        }

        return strval($number);
    }



    /**
     * Helper function, if the input evaluates to an empty string after being trimmed,
     * then returns null else returns the stringified input
     * @param mixed $s
     * @return null|string
     */
    public static function stringOrNull(mixed $s): ?string
    {
        $s = trim((string)$s);
        if (empty($s)) {
            return null;
        } else {
            return strval($s);
        }
    }

    /**
     * Helper method to sanitize sql string
     * will make sure that magic quotes is not an issue
     * can escape a string for mysql input
     * and will strip out html
     * @param string $s
     * @param boolean $b_strip_tags default false, if true then tags will be stripped out
     * @param object|null optional mysqli object
     * @return string the modified input
     * @throws SQLException if invalid mysqli param and its not null
     */
    //for prepared statements
    public static function sanitizeString(string $s, bool $b_strip_tags, $mysqli = null): string
    {
        if (empty($s)) {
            $s = '';
        }

        if ($b_strip_tags) {
            $s = strip_tags($s);
        }


        if ($mysqli) {
            $s = mysqli_real_escape_string($mysqli, $s);
        }
        return $s;

    }



    /**
     * Converts a timestamp to a string that has the UTC time
     * @param $ts
     * @throws SQLDataException
     * @return string
     */
    public static function timestampToUTCString($ts): string
    {
       // 'YYYY-MM-DD HH:MM:SS'
        $tsc = self::cleanNumber($ts);
        $what =  gmdate('Y-m-d G:i:s',$tsc);
        if (!$what) {
            throw  new SQLDataException("$ts cannot be converted to a date time");
        }
        return $what;
    }

    /**
     * Convenience function to convert array of strings to a single comma delimited string with each thing escaped and quoted
     * @param $arr array
     * @return string comma delimited and escaped string
     */
    public  function arrayToQuotedString(array $arr): string
    {
        $cloned = [];
        foreach ($arr as $a) {
            $cloned[] = "'".$this->cleanString($a)."'";
        }
        return implode(',',$cloned);
    }

	/**
	 * @param $key_like_this
	 *
	 * @return bool
	 * @throws SQLException
	 */
    public function foreignKeyExists($key_like_this): bool
    {
    	$res = $this->execSQL('SELECT database() AS the_db;');
	    $schema = $res[0]->the_db;
    	$param = "%$key_like_this%";
    	$res = $this->execSQL( /** @lang text */
		    'SELECT *
							 FROM information_schema.REFERENTIAL_CONSTRAINTS
						     WHERE CONSTRAINT_SCHEMA = ?
						     AND CONSTRAINT_NAME like ?;',['ss',$schema,$param]);
    	if (empty($res)) {
    		return false;
	    }
	    return true;
    }


	/**
	 * Will drop all triggers that have the param as part or all the name
	 * @param string $trigger_like_this - part or whole of the trigger name
	 * @param bool $b_dry_run
	 * @return array list of triggers dropped
	 * @throws SQLException
	 */
	public function dropTriggersLike(string $trigger_like_this, bool $b_dry_run = false): array
    {
		$res = $this->execSQL('SELECT database() AS the_db;');
		$schema = $res[0]->the_db;
		$param = "%$trigger_like_this%";
		$ret = [];
		$res = $this->execSQL( /** @lang text */
			'SELECT TRIGGER_NAME
					FROM `information_schema`.`TRIGGERS`
					WHERE TRIGGER_SCHEMA = ? and Trigger_Name like ?;',
				['ss',$schema,$param]);
		if ($res) {
			foreach ($res as $row) {
				$trigger_name = $row->TRIGGER_NAME;
				if (empty($trigger_name)) {continue;}
				$ret[] = $trigger_name;
                if ($b_dry_run) {continue;}
				$this->execute("DROP TRIGGER IF EXISTS $trigger_name;");
			}
		}
		return $ret;
	}

	/**
	 * reads all files in the included folder path
	 * @param string $folder_path
	 *
	 * @throws SQLException
	 */
	public function execute_nested_sql_files(string $folder_path): void
    {

		try {
			$this->beginTransaction();

			$dir = realpath($folder_path) ;
			if (!$dir) {
				throw new SQLException("Cannot Find the folder path $folder_path");
			}

			$files = self::recursive_search_sql_files($dir);

			foreach ($files as $file) {
				$sql = trim(file_get_contents($file));
				if (empty($sql)) {continue;}
				$this->execute($sql);
			}

			$this->commit();
		} catch (SQLException $e) {
			$this->rollback();
			throw $e;
		}
	}

	public static function recursive_search_sql_files($folder): array
    {
        $dir = new RecursiveDirectoryIterator($folder);
		$ite = new RecursiveIteratorIterator($dir);
		$files = new RegexIterator($ite, '/.*\.sql$/', RegexIterator::GET_MATCH);
		$fileList = array();
		foreach($files as $file) {
			$fileList = array_merge($fileList, $file);
		}
		return $fileList;
	}


}

