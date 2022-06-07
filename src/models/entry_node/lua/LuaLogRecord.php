<?php

namespace app\models\entry_node\lua;

use app\helpers\Utilities;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use JsonSerializable;

class LuaLogRecord implements JsonSerializable {

    /**
     * @param string $level
     * @param string $pre
     * @param mixed $data
     */
    public function __construct(readonly string $level,readonly string $pre, readonly mixed $data)
    {

    }

    /**
     * @return mixed
     * @throws JsonException
     */
    #[ArrayShape(['level' => "string", 'pre' => "string", 'data' => "mixed"])]
    public function jsonSerialize(): array
    {

        if (is_object($this->data) || is_array($this->data)) {
            $data = Utilities::convert_to_object($this->data);
        } else {
            $data = $this->data;
        }
       return [
         'level' => $this->level,
         'pre' => $this->pre,
         'data' => $data
       ];
    }
}