<?php
namespace app\user;

use app\exceptions\HexletErrorToUser;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


class UserPages {

    protected  Auth $auth;
    protected  Logger $logger;
    /**
     * @var Container $container
     */
    protected  Container $container;

    protected Twig $view;

    protected object $user;

    protected static string $flash_key_in_session;

    public static function set_flash_key_in_session($key) {
        static::$flash_key_in_session = $key;
    }

    public static function add_flash_message($type,$message) {
        $flash_key = static::$flash_key_in_session ;
        if (!array_key_exists($flash_key,$_SESSION)) {
            $_SESSION[$flash_key] = [];
        }
        $node = (object)[
          'type' => $type,
          'message' => $message,
        ];
        $_SESSION[$flash_key][] = $node;
    }

    /**
     * UserLogInPages constructor.
     * @param Auth $auth
     * @param Logger $logger
     * @param Container $container
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function __construct (Auth $auth,Logger $logger, Container $container) {
        $this->auth = $auth;
        $this->logger = $logger;
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->user = $this->container->get('user');
    }


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function login_form(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/login.twig',
                'page_title' => 'Login',
                'page_description' => 'Login Here',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function do_logout(RequestInterface $request, ResponseInterface $response) :ResponseInterface {

        try {
            $this->auth->logOut();
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('root');
            $response = $response->withStatus(302);

            return $response->withHeader('Location', $url);
        } catch (Exception $e) {
            $this->logger->error("Could not logout",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function register_form(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig',  [
                'page_template_path' => 'user/register.twig',
                'page_title' => 'Register',
                'page_description' => 'Please Sign UP',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function submit_login(RequestInterface $request, ResponseInterface $response) :ResponseInterface  {


        $args = $request->getParsedBody();
        $email_or_username = $args['hexbatch_email_or_username'];
        $password = $args['hexbatch_password'];
        $remember_me = 60 * 60;
        if (isset($args['hexbatch_remember_me'])) {
            $remember_me = $remember_me * 24 * 7;
        }

        try {
            if(filter_var($email_or_username, FILTER_VALIDATE_EMAIL)) {
                // valid address
                $this->auth->login($email_or_username, $password, $remember_me);
            }
            else {
                // invalid address
                $this->auth->loginWithUsername($email_or_username, $password, $remember_me);
            }

            try {
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('user_home');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not render logged in page",['exception'=>$e]);
                throw new HexletErrorToUser($request,$e->getMessage());
            }

        } catch (InvalidEmailException $e) {
            $message = ('Wrong email address');
        } catch (InvalidPasswordException $e) {
            $message = ('Wrong password');
        } catch (EmailNotVerifiedException $e) {
            $message = ('Email not verified');
        } catch (TooManyRequestsException $e) {
            $message = ('Too many requests');
        } catch (Exception $e) {
            $this->logger->error('some sort of error in user login',['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }

        try {
            static::add_flash_message('warning',$message);
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('login');
            $response = $response->withStatus(302);
            return $response->withHeader('Location', $url);
        } catch (Exception $e) {
            $this->logger->error("Could not render logged in page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }


    }


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function submit_registration(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
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


            $auth = $this->auth;
            $userId = $auth->register($email, $password, $user_name, function ($selector, $token)  use($auth)  {

                $error_message = '';
                try {
                    $this->auth->confirmEmail($selector, $token);

                } catch (InvalidSelectorTokenPairException $e) {
                    $error_message = ('Invalid token');
                } catch (TokenExpiredException $e) {
                    $error_message = ('Token expired');
                } catch (UserAlreadyExistsException $e) {
                    $error_message = ('Email address already exists');
                } catch (TooManyRequestsException $e) {
                    $error_message = ('Too many requests');
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                if ($error_message) {
                    throw new InvalidArgumentException($error_message);
                }

            });

            $message = 'We have signed up a new user with the ID ' . $userId;
        } catch (InvalidEmailException $e) {
            $message = 'Invalid email address';
        } catch (InvalidPasswordException $e) {
            $message = 'Invalid password';
        } catch (UserAlreadyExistsException $e) {
            $message = 'User already exists';
        } catch (TooManyRequestsException $e) {
            $message = 'Too many requests';
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
        } catch (AuthError $e) {
            $message = 'Auth:' . $e->getMessage();
        } catch (Exception $e) {
            $message = 'General' . $e->getMessage();
        }

        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/submit_registration.twig',
                'page_title' => 'Thank You',
                'page_description' => 'The check is in the mail',
                'user_name' => $user_name,
                'email' => $email,
                'password' => $password,
                'confirm_password' => $confirm_password,
                'message' => $message

            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render submit registration page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function user_home(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_home.twig',
                'page_title' => 'User Home',
                'page_description' => 'No Place Like Home',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function user_page(RequestInterface $request, ResponseInterface $response, string $user_name) :ResponseInterface {
        if ($this->user->id) {
            return $this->user_page_for_self($request, $response,$user_name);
        } else {
            return $this->user_page_for_others($request, $response,$user_name);
        }
    }



    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function user_page_for_self(RequestInterface $request, ResponseInterface $response, string $user_name) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_page_for_self.twig',
                'page_title' => 'User Page',
                'page_description' => 'No Place Like Home',
                'requested_user' => ['user_name'=>$user_name]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function user_page_for_others(RequestInterface $request, ResponseInterface $response, string $user_name) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_page_for_others.twig',
                'page_title' => 'User Page',
                'page_description' => 'No Place Like Home',
                'user' => $this->user,
                'requested_user' => ['user_name'=>$user_name]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }
}