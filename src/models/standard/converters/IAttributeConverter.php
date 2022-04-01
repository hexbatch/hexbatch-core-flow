<?php

namespace app\models\standard\converters;


Interface IAttributeConverter {

    public function convert() : ?object;
}