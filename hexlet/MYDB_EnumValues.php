<?php /** @noinspection ALL */

namespace hexlet;

use function mysqli_fetch_object;

/**
 * Class EnumValues
 *  The is a helper class which is makes it easier to use column type enumeration in the database
 *  gets values of a column if its an enum
 *    great if you are using enums in a database
 */
class MYDB_EnumValues
{


    /**
     * @var array of strings , each a name of valid enum
     */
    protected $values;

    /**
     * @return array of valid enums for this column
     */
    function getValues(): array
    {
        return $this->values;
    }

    /**
     * tests if something is a valid enum for the column
     * @param $type
     * @return bool
     */
    function belongs($type): bool
    {
        $ok = false;
        foreach ($this->values as $e) {
            if ($type == $e) {
                $ok = true;
                break;
            }
        }
        return $ok;
    }

    /**
     * EnumValues constructor.
     * @param string $table
     * @param string $column
     * @param object $mysqli
     * @throws SQLException if the column does not exist
     */
    public function __construct(string $table, string $column, object $mysqli)
    {

        $mydb = new MYDB($mysqli);
        $sql = "SHOW COLUMNS FROM $table LIKE '$column'";

        $result = $mydb->execute($sql);
        if ($result) { // If the query's successful

            $enum = mysqli_fetch_object($result);
            preg_match_all("/'([\w ]*)'/", $enum->Type, $values);
            $this->values = $values[1];


        } else {

            MYDB::throwSQLErrorWithHtml("unable to get enum from column", $mydb->mysqli);

        }

    }
}