<?php
/** @noinspection PhpUnused */
namespace src\hexlet;

use src\hexlet\hexlet_exceptions\SQLException;

require_once realpath(dirname(__FILE__)) . '/MYDB.php';
require_once realpath(dirname(__FILE__)) . '/JsonHelper.php';


class DBSelector {


    /**
     * @var array of string , these are the allowed values to pass to
     * @see DBSelector::getConnection()
     */
    protected static array $db_names = [
    	'flow',
        'ignore'
    ];
    protected static array $cache = [];

    //returns stored connection, may created it first

    /**
     * Gets the database connection for the connection
     * @param string $what <p>
     *   @see MYDB::getMySqliDatabase() for details of keys in the array used in the code
     * @uses DBSelector::$db_names
     *
     * @return object|MYDB
     * @throws SQLException
     *
     */
    public static function getConnection($what = 'hexlet') {
        if (isset(self::$cache[$what])) {
            $mysqli =  self::$cache[$what]->getDBHandle();
            return new MYDB($mysqli); //smart pointer, db will only go out of scope when the static class def does
        }

        if (in_array($what, self::$db_names)) {
            switch ($what) {
                case 'ignore': {
                    $mydb = null;
                    break;
                }
                case 'flow':
	                $db_stuff = [
                        'host' => DB_FLOW_HOST,
                        'port' => DB_FLOW_HOSTPORT,
                        'username' => DB_FLOW_USER,
                        'password' => DB_FLOW_PASSWORD,
                        'database_name' => DB_FLOW_DATABASE,
                        'character_set' => DB_FLOW_CHARSET,
                        'collation' => DB_FLOW_COLLATION,

	                ];
                    $mydb = new MYDB(null,$db_stuff,true);
                    break;

                default:
                    throw new SQLException("Cannot create new db connection from name of [$what]");
            }
        } else {
            throw new SQLException("Error creating new db connection from name of [$what]: ");
        }

        self::$cache[$what] = $mydb;
        return $mydb;
    }




}