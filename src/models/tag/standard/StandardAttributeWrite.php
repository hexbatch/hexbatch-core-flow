<?php

namespace app\models\tag\standard;

use app\models\base\SearchParamBase;
use InvalidArgumentException;

class StandardAttributeWrite  {

    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];


    public function __construct(array $tag_guids)
    {
        foreach ($tag_guids as $guid) {
            $type = SearchParamBase::find_type_of_arg($guid);
            if ($type === SearchParamBase::ARG_IS_HEX ) {
                $this->tag_guids[] = $guid;
            } else {
                throw new InvalidArgumentException("Tag must be added by guid: ". $type);
            }
        }
    }

    /**
     * Reads the attributes that matter for the tag guids from sql
     *      Some standards have modifiers for their attribute data, to massage them into an object
     * combines them into the aggregated standards
     * updates the table
     * does any post actions like writing to another column
     * @return int
     */
    public function write() : int  {
        $ret = 0;

        return $ret;
    }
}