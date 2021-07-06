<?php /** @noinspection PhpUnhandledExceptionInspection */

use Delight\Auth\Auth;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Cookie\Session;
use ParagonIE\EasyDB\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Factory\AppFactory;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require_once 'flow-config.php';
require HEXLET_BASE_PATH . '/vendor/autoload.php';

global $hexlet_db,$auth,$user_info;

$db_host = DB_FLOW_HOST;
$db_name = DB_FLOW_DATABASE;
$charset = DB_FLOW_CHARSET;
$collation = DB_FLOW_COLLATION;
$hexlet_db = Factory::fromArray([
    "mysql:host=$db_host;dbname=$db_name;charset=$charset",
    DB_FLOW_USER,
    DB_FLOW_PASSWORD
]);

$hexlet_db->safeQuery("set names $charset collate $collation");

$db =$hexlet_db->getPdo();

Session::start('Strict');

$auth = new Auth($db);

$user_info = [
    'id' => $auth->getUserId(),
    'username' =>$auth->getUsername(),
    'email' => $auth->getEmail()
];

$app = AppFactory::create();

// Create Twig
$twig = Twig::create(
    [HEXLET_TWIG_TEMPLATE_PATH, HEXLET_TWIG_PAGES_PATH, HEXLET_TWIG_PARTIALS_PATH],
    ['cache' => HEXLET_TWIG_CATCHE_PATH,'strict_variables'=>true]
);



// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

$app->get('/', function (Request $request, Response $response /*, $args*/) {
    global $user_info;
    $view = Twig::fromRequest($request);
    return $view->render($response, 'main.twig', [
        'page_template_name' => 'home.twig',
        'page_title' => 'Home',
        'page_description' => 'No Place Like Home',
        'user' => $user_info,
    ]);
})->setName('home');


$app->get('/logout', function (Request $request, Response $response /*, $args*/) {
    global $auth;

    $auth->logOut();
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $url = $routeParser->urlFor('home');
    $response = $response->withStatus(302);

    return $response->withHeader('Location', $url);

})->setName('logout');


$app->get('/register', function (Request $request, Response $response /*, $args*/) {
    global $user_info;
    $view = Twig::fromRequest($request);
    return $view->render($response, 'main.twig', [
        'page_template_name' => 'register.twig',
        'page_title' => 'Register',
        'page_description' => 'Please Sign UP',
        'user' => $user_info
    ]);
})->setName('register');

$app->post('/submit_registration', function (Request $request, Response $response ) {
    global $auth;
    global $user_info;
    $view = Twig::fromRequest($request);
    $args = $request->getParsedBody();
    $user_name = $args['hexbatch_username'];
    $email = $args['hexbatch_email'];
    $password = $args['hexbatch_password'];
    $confirm_password = $args['hexbatch_confirm_password'];

    $b_name_ok = false;
    if (preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $user_name) === 0) {
        if (mb_strlen($user_name) > 3 && mb_strlen($user_name) < 30) {
            $b_name_ok = true;
        }
    }



    $b_password_ok = false;
    if (preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $password) === 0) {
        if ($confirm_password === $password) {
            $b_password_ok = true;
        }
    }



    try {

        if (!$b_name_ok) {
            throw new InvalidArgumentException('User name cannot have invisible chars and be between 4 and 29 characters');
        }

        if (!$b_password_ok) {
            throw new InvalidArgumentException('Password cannot have invisible chars and be between 4 and 29 characters and match the confirmation password');
        }


        $userId = $auth->register($email, $password, $user_name, function ($selector, $token)  {
            global $auth;
            $error_message = '';
            try {
                $auth->confirmEmail($selector, $token);

            }
            catch (InvalidSelectorTokenPairException $e) {
                $error_message =  ('Invalid token');
            }
            catch (TokenExpiredException $e) {
                $error_message =  ('Token expired');
            }
            catch (UserAlreadyExistsException $e) {
                $error_message =  ('Email address already exists');
            }
            catch (TooManyRequestsException $e) {
                $error_message =  ('Too many requests');
            }
            if ($error_message) {
                throw new InvalidArgumentException($error_message);
            }

        });

        $message =  'We have signed up a new user with the ID ' . $userId;
    }
    catch (InvalidEmailException $e) {
        $message = 'Invalid email address';
    }
    catch (InvalidPasswordException $e) {
        $message = 'Invalid password';
    }
    catch (UserAlreadyExistsException $e) {
        $message = 'User already exists';
    }
    catch (TooManyRequestsException $e) {
        $message = 'Too many requests';
    }
    catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
    }
    return $view->render($response, 'main.twig', [
        'page_template_name' => 'submit_registration.twig',
        'page_title' => 'Thank You',
        'page_description' => 'The check is in the mail',
        'user' => $user_info,
        'user_name' => $user_name,
        'email' => $email,
        'password' => $password,
        'confirm_password' => $confirm_password,
        'message' => $message

    ]);
})->setName('submit_registration');


$app->get('/login', function (Request $request, Response $response /*, $args*/) {
    global $user_info;
    $view = Twig::fromRequest($request);
    return $view->render($response, 'main.twig', [
        'page_template_name' => 'login.twig',
        'page_title' => 'Login',
        'page_description' => 'Login Here',
        'user' => $user_info
    ]);
})->setName('login');



$app->post('/submit_login', function (Request $request, Response $response ) {
    global $auth;
    global $user_info;
    $view = Twig::fromRequest($request);
    $args = $request->getParsedBody();
    $email = $args['hexbatch_email'];
    $password = $args['hexbatch_password'];
    $remember_me = 60 * 60;
    if (isset($args['hexbatch_remember_me'])) {
        $remember_me  = $remember_me * 24 * 7;
    }

    try {
        $auth->login($email, $password , $remember_me);

        $message =  'User is logged in';
    }
    catch (InvalidEmailException $e) {
        $message = ('Wrong email address');
    }
    catch (InvalidPasswordException $e) {
        $message =('Wrong password');
    }
    catch (EmailNotVerifiedException $e) {
        $message =('Email not verified');
    }
    catch (TooManyRequestsException $e) {
        $message = ('Too many requests');
    }
    return $view->render($response, 'main.twig', [
        'page_template_name' => 'submit_login.twig',
        'page_title' => 'You are Logged IN',
        'page_description' => 'The mouse dropped the cheese',
        'user' => $user_info,
        'email' => $email,
        'password' => $password,
        'remember_me' => $remember_me,
        'message' => $message

    ]);
})->setName('submit_login');



$app->get('/phpinfo', function (Request $request, Response $response /*, $args*/) {

    ob_start();
    phpinfo();
    $info = ob_get_clean();
    $response->getBody()->write($info);
    return $response;
});


$check_logged_in_middleware = function (Request $request, RequestHandler $handler) {
    global $auth;
    if (!$auth->isLoggedIn()) {
        throw new HttpForbiddenException($request, "Need to be logged in first");
    }
    $response = $handler->handle($request);
    $new_response = $response->withAddedHeader('checks-out', time());

    return $new_response;
};

$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/foo', function ($request, $response /*, array $args*/) {
        // Route for /billing
        $response->getBody()->write("protected shit");
        return $response;
    });

    $group->get('/bar/{id:[0-9]+}', function ($request, $response, array $args) {
        // Route for /invoice/{id:[0-9]+}
        $response->getBody()->write("protected poop: ". $args['id']);
        return $response;
    });
})->add( $check_logged_in_middleware);

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();
