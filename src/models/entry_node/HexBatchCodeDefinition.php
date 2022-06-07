<?php

namespace app\models\entry_node;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class HexBatchCodeDefinition extends CodeDefinition {


    /**
     * Constructs a new CodeDefinition.
     */
    public static function construct($tagName, $replacementText, $useOption = false,
                                     $parseContent = true, $nestLimit = -1, $optionValidator = array(),
                                     $bodyValidator = null): HexBatchCodeDefinition
    {
        $def = new HexBatchCodeDefinition();
        $def->elCounter = 0;
        /** @noinspection PhpDeprecationInspection */
        $def->setTagName($tagName);
        /** @noinspection PhpDeprecationInspection */
        $def->setReplacementText($replacementText);
        $def->useOption = $useOption;
        $def->parseContent = $parseContent;
        $def->nestLimit = $nestLimit;
        $def->optionValidator = $optionValidator;
        $def->bodyValidator = $bodyValidator;
        return $def;
    }

    public function asHtml(ElementNode $el) : string
    {
        $prepare = parent::asHtml($el);
        $options = $el->getAttribute();
        if (isset($options['guid']) && !empty($options['guid'])) {
            $html = str_ireplace('{guid}', $options['guid'], $prepare);
        } else {
            $html = str_ireplace('data-guid="{guid}"', '', $prepare);
        }

        if (isset($options['color']) && !empty($options['color'])) {
            $html = str_ireplace('style="color: {option}"', 'style="color: '.$options['color'].'"', $html);
        }

        if (isset($options['size']) && !empty($options['size'])) {
            $html = str_ireplace('style="font-size:{size}px"', 'style="font-size: '.$options['size'].'"', $html);
        }

        if (isset($options['font']) && !empty($options['font'])) {
            $html = str_ireplace('style="font-family:{option}"', 'style="font-family: '.$options['font'].'"', $html);
        }

        if (isset($options['img']) && !empty($options['img'])) {
            $html = str_ireplace('alt="{option}"', 'alt="'.$options['img'].'"', $html);
        }

        if (isset($options['url']) && !empty($options['url'])) {
            $html = str_ireplace('href="{option}"', 'href="'.$options['url'].'"', $html);
        }

        return $html;
    }
}