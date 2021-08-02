<?php
namespace app\hexlet;

use ParagonIE\AntiCSRF\AntiCSRF;

class FlowAntiCSRF extends AntiCSRF {


    public function __construct( &$post = null,
                                 &$session = null,
                                 &$server = null) {
        parent::__construct($post,$session,$server);
        $this->reconfigure([
            "recycle_after" => 60*60*6, //6 hours
            "expire_old" => true,
        ]);
    }
}

