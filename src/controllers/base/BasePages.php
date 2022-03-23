<?php
namespace app\controllers\base;


use app\common\BaseConnection;
use Psr\Http\Message\ServerRequestInterface;


class BasePages extends BaseConnection
{


    protected function is_ajax_call(ServerRequestInterface $request) : bool {
        $x_header = $request->getHeader('X-Requested-With') ?? [];
        if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
            return  false;
        } else {
            return true;
        }
    }



}