<?php
namespace app\hexlet;

use ParagonIE\AntiCSRF\AntiCSRF;

class FlowAntiCSRF extends AntiCSRF {
    //todo try this out on ajax
    const SET_LOCK_TO_ANY_PAGE = -1;

    const UNUSED_TOKEN_LIFE = 60*60; //1 hour
    const RECYCLE_AFTER = 300; //only allow the most recent 300 tokens

    static array $fake_server = [
        'REQUEST_URI' => '/',
    ];


    public function __construct( &$post = null,
                                 &$session = null,
                                 &$server = null) {


        parent::__construct($post,$session,$server);
        $this->reconfigure([
            "recycle_after" => static::RECYCLE_AFTER,
            "expire_old" => true,
        ]);
    }

    /**
     * Allow derived classes to inject arguments for newly formed tokens
     * here, we are adding in a unix timestamp to purge older ones easier
     * @param array $args
     * @return array
     */
    protected function buildBasicToken(array $args = []): array
    {
        $args['created_at_ts'] = time();
        return $args;
    }


    //adds a time filter too
    protected function recycleTokens() : AntiCSRF{
        parent::recycleTokens();
        if (!$this->expire_old) {
            // This is turned off.
            return $this;
        }
        // the parent already knocked off any extra quantity, now lets knock off by time
        if ($this->useNativeSession) {
            /** @var array<string, array<string, string|int>> $sess */
            $sess =& $_SESSION[$this->sessionIndex];
        } else {
            /** @var array<string, array<string, string|int>> $sess */
            $sess =& $this->session[$this->sessionIndex];
        }
        // Sort by creation time
        uasort(
            $sess,
            function (array $a, array $b): int {
                return  $a['created'] <=> $b['created'];
            }
        );

        $now = time();

        while (
            count($sess) &&
            array_key_exists('created_at_ts',$sess[array_key_first($sess)]) &&
            ($now - $sess[array_key_first($sess)]['created_at_ts'] > static::UNUSED_TOKEN_LIFE)
        ) {
            // Let's knock off the oldest one that is expired
            array_shift($sess);
        }
        return $this;
    }
}

