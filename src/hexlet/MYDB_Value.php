<?php
namespace src\hexlet;




class MYDB_Value {
    /**
     * @var string|null $expression
     */
    public ?string $expression = null;

    /**
     * @var mixed $value
     */
    public $value = null;

    /**
     * @var string|null $flag
     */
    public ?string $flag = null;

    public function __construct() {
        $this->flag = 's';
    }
}

