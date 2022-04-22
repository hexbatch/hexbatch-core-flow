<?php
namespace app\models\base;

use app\helpers\Utilities;

abstract class FlowSimpleModel {

    /**
     * @param array|object|null $object
     */
    public function __construct($object=null)
    {

        if (empty($object)) {
            return;
        }

        if (is_array($object)) {
            $object = Utilities::convert_to_object($object);
        }

        foreach ($object as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
}