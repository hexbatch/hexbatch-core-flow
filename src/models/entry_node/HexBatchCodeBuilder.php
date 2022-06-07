<?php

namespace app\models\entry_node;

use JBBCode\CodeDefinition;
use JBBCode\CodeDefinitionBuilder;

class HexBatchCodeBuilder extends CodeDefinitionBuilder {

    public function build(): HexBatchCodeDefinition|CodeDefinition
    {

        $definition = HexBatchCodeDefinition::construct($this->tagName,
            $this->replacementText,
            $this->useOption,
            $this->parseContent,
            $this->nestLimit,
            $this->optionValidator,
            $this->bodyValidator);
        return $definition;
    }
}