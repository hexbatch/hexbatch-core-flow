<?php
namespace app\handlers;

use app\hexlet\JsonHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler;

use Slim\Interfaces\CallableResolverInterface;
use Throwable;

class HttpErrorHandler extends ErrorHandler
{
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    public const NOT_ALLOWED = 'NOT_ALLOWED';
    public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    /**
     * @var ContainerInterface $container
     */
    protected ContainerInterface $container;

    public function __construct(CallableResolverInterface $callableResolver,
                                ResponseFactoryInterface $responseFactory,
                                ?LoggerInterface $logger = null,
                                ?ContainerInterface $container = null

    )
    {
        $this->container = $container;
        parent::__construct($callableResolver, $responseFactory, $logger);

    }

    protected function respond(): ResponseInterface
    {

        $exception = $this->exception;
        $statusCode = 500;
        $type = self::SERVER_ERROR;
        $description = 'An internal error has occurred while processing your request.';
        $error_title = 'Ouch!';
        $detailed_error_title = '';
        $error_page = 'ye_old_error_page';

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $description = $exception->getMessage();

            if ($exception instanceof HttpNotFoundException) {
                $type = self::RESOURCE_NOT_FOUND;
                $detailed_error_title = 'Not Found';
            }if ($exception instanceof HttpInternalServerErrorException) {
                $type = self::INTERNAL_SERVER_ERROR;
                $detailed_error_title = 'Scary Internal Error';
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $type = self::NOT_ALLOWED;
                $detailed_error_title = 'You are not allowed at all to do this!';
            } elseif ($exception instanceof HttpUnauthorizedException) {
                $type = self::UNAUTHENTICATED;
                $detailed_error_title = 'Not Authorized Hahahahaha';
            } elseif ($exception instanceof HttpForbiddenException) {
                $type = self::UNAUTHENTICATED;
                $detailed_error_title = 'The Permissions are not strong with this one';
            } elseif ($exception instanceof HttpBadRequestException) {
                $detailed_error_title = 'Bad Request; it was not meant to be';
                $type = self::BAD_REQUEST;
            } elseif ($exception instanceof HttpNotImplementedException) {
                $detailed_error_title = 'Not Yet Created. Please make a pull request to add it';
                $type = self::NOT_IMPLEMENTED;
            }
        }

        if (
            !($exception instanceof HttpException)
            && ($exception instanceof Throwable)
            && $this->displayErrorDetails
        ) {
            $detailed_error_title = get_class($exception);
            $description = $exception->getMessage();
        }

        if ($this->displayErrorDetails) {
            $error_title = $detailed_error_title;
        }

        // if json request, return json error, else do twig error page, if exception for twig error page, do json


        $response = $this->responseFactory->createResponse($statusCode);


        $x_header = $this->request->getHeader('X-Requested-With') ?? [];
        if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
            try {
                $view = $this->container->get('view');
                return $view->render($response, 'main.twig', [
                    'page_template_path' => "errors/$error_page.twig",
                    'page_title' => $error_title,
                    'page_description' => 'Error',
                    'error_code' => $statusCode,
                    'error_message' => $description
                ]);
            } catch (Throwable $e) {
                //fall through
                $this->logger->error("Could not render error page",['exception'=>$e]);
            }
        }

        $error = [
            'statusCode' => $statusCode,
            'error' => [
                'type' => $type,
                'title' => $error_title,
                'message' => $description,
                'success'=>false,
            ],
        ];

        $payload = JsonHelper::toString($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response->getBody()->write($payload);
        return $response;
    }
}