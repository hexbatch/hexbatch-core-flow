<?php
namespace app\exceptions;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Throwable;

class HexletErrorToUser extends HttpInternalServerErrorException{
    public function __construct(ServerRequestInterface $request, ?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($request, $message, $previous);
        $this->message = $message;
    }
}

