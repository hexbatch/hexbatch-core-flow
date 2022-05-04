<?php

namespace app\models\standard\converters;

use app\helpers\Utilities;
use app\models\standard\FlowTagStandardAttribute;
use app\models\standard\RawAttributeData;
use app\models\tag\FlowTag;
use BlueM\Tree;
use LogicException;

abstract class BaseConverter implements IAttributeConverter {

    const STANDARD_NAME = 'DEFINE IN CHILD CLASS';

    private array $ordered_data;
    /**
     * @param RawAttributeData[] $raw_data

     */
    public function __construct(array $raw_data)
    {
        $map = [];
        foreach ($raw_data as $raw) {
            if (!isset($map[$raw->getAttributeName()])) { $map[$raw->getAttributeName()] = [];}
            $map[$raw->getAttributeName()][] = $raw;
        }
        $this->ordered_data = [];
        foreach ($map as $attribute_name => $row_array) {
            $this->ordered_data[$attribute_name] = $this->sort_raw_array_by_parent($row_array);
        }

    }

    protected  function getKeysOfStandard() : array {
        return FlowTagStandardAttribute::getStandardAttributeKeys(static::STANDARD_NAME,false);
    }

    /**
     * @param string $key
     * @return RawAttributeData[]
     */
    protected function getRawOfKey(string $key): array  {
        if (!in_array($key,$this->getKeysOfStandard())) {
            throw new LogicException("Key '$key' not in this standard: ". static::STANDARD_NAME);
        }
        return $this->ordered_data[$key]?? [];
    }

    /**
     * Default version just gets the last child of the key or null
     * @param string $key
     * @return string|null
     */
    protected  function getFinalOfKey(string $key) : ?string  {
        $raws = $this->getRawOfKey($key);
        if (count($raws)) {
            $ret = $raws[count($raws)-1];
            return $ret->getTextVal();
        }
        return null;

    }

    public function convert(): ?object
    {
        $ret_array = [];
        foreach ($this->getKeysOfStandard() as $key) {
            $ret_array[$key] = $this->getFinalOfKey($key);
        }

        return Utilities::convert_to_object($ret_array);

    }

    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param RawAttributeData[] $raw_array_to_sort
     * @return FlowTag[]
     */
    protected  function sort_raw_array_by_parent(array $raw_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','raw'=>null];
        foreach ($raw_array_to_sort as $raw) {
            $data[] = ['id' => $raw->getAttributeID(), 'parent' => $raw->getParentAttributeID()??0,
                        'title' => $raw->getAttributeGuid(),'raw'=>$raw];
        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->raw??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }



}