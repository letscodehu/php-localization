<?php

use Middleware\AuthorizationMiddleware;
use Services\ForgotPasswordService;
use Request\RequestFactory;
use Exception\SqlException;
use Laminas\I18n\Translator\Loader\Gettext;
use Laminas\I18n\Translator\Loader\PhpArray;
use Laminas\I18n\Translator\Translator;
use Middleware\CsrfMiddleware;
use Middleware\FlashMessageCleanupMiddleware;
use Middleware\LocalizationMiddleware;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Validation\Validator;

return [
    "translator" => function(ServiceContainer $container) {
        $translator = new Translator();
        $baseDir = $container->get("basePath")."/i18n/";
        $pattern = "%s/messages.php";
        $translator->addTranslationFilePattern(PhpArray::class, $baseDir, $pattern, "messages");
        return $translator;
    }, 
    "responseFactory" => function (ServiceContainer $container) {
        return new Response\ResponseFactory($container->get("viewRenderer"));
    },
    "viewRenderer" => function (ServiceContainer $container) {
        return new ViewRenderer($container->get("basePath"), $container->get('csrf'), $container->get("translator"));
    },
    'responseEmitter' => function () {
        return new Response\ResponseEmitter();
    },
    "config" => function (ServiceContainer $container) {
        $base = $container->get("basePath");
        return include_once $base . "/config.php";
    },
    "connection" => function (ServiceContainer $container) {
        $config = $container->get("config");
        $connection = mysqli_connect($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        if (!$connection) {
            throw new SqlException('Connection error: ' . mysqli_error($connection));
        }
        return $connection;
    },
    "validator" => function() {
        return new Validator();
    },
    "baseUrl" => function() {
        $protocol = strpos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
        return $protocol.$_SERVER['HTTP_HOST'];
    },
    "photoService" => function (ServiceContainer $container) {
        return new Services\PhotoService($container->get("connection"));
    },
    'homeController' => function (ServiceContainer $container) {
        return new Controllers\Image\HomeController($container->get("photoService"));
    },
    'singleImageController' => function (ServiceContainer $container) {
        return new Controllers\Image\SingleImageController($container->get("photoService"));
    },
    'imageServeController' => function (ServiceContainer $container) {
        return new Controllers\Image\ImageServeController($container->get("basePath"));
    },
    'singleImageEditController' => function (ServiceContainer $container) {
        return new Controllers\Image\SingleImageEditController($container->get("photoService"));
    },
    'singleImageDeleteController' => function (ServiceContainer $container) {
        return new Controllers\Image\SingleImageDeleteController($container->get("photoService"));
    },
    'imageCreateFormController' => function (ServiceContainer $container) {
        return new Controllers\Image\ImageCreateFormController($container->get("session"));
    },
    'imageCreateSubmitController' => function (ServiceContainer $container) {
        return new Controllers\Image\ImageCreateSubmitController($container->get("basePath"), 
        $container->get("request"), $container->get("photoService"), $container->get("validator"));
    },
    'loginFormController' => function (ServiceContainer $container) {
        return new Controllers\Auth\LoginFormController($container->get("session"));
    },
    'loginSubmitController' => function (ServiceContainer $container) {
        return new Controllers\Auth\LoginSubmitController($container->get("authService"), $container->get("session"));
    },
    'logoutSubmitController' => function (ServiceContainer $container) {
        return new Controllers\Auth\LogoutSubmitController($container->get("authService"));
    },
    'forgotPasswordController' => function (ServiceContainer $container) {
        return new Controllers\ForgotPassword\ForgotPasswordController($container->get("session"));
    },
    'passwordResetController' => function (ServiceContainer $container) {
        return new Controllers\ForgotPassword\PasswordResetController($container->get("request"));
    },
    'passwordResetSubmitController' => function (ServiceContainer $container) {
        return new Controllers\ForgotPassword\PasswordResetSubmitController($container->get("request"), $container->get("forgotPasswordService"));
    },
    'forgotPasswordSubmitController' => function (ServiceContainer $container) {
        return new Controllers\ForgotPassword\ForgotPasswordSubmitController($container->get("request"), $container->get("forgotPasswordService"));
    },
    "authService" => function (ServiceContainer $container) {
        return new Services\AuthService($container->get("connection"), $container->get("session"));
    },
    'notFoundController' => function () {
        return new Controllers\NotFoundController();
    },
    'forgotPasswordService' => function (ServiceContainer $container) {
        return new ForgotPasswordService($container->get("connection"), $container->get("mailer"), $container->get("baseUrl"));
    },
    "mailer" => function (ServiceContainer $container) {
        $mailerConfig = $container->get("config")["mail"];
        $transport = (new Swift_SmtpTransport($mailerConfig["host"], $mailerConfig["port"]))
            ->setUsername($mailerConfig["username"])
            ->setPassword($mailerConfig["password"]);
        return new Swift_Mailer($transport);
    },
    'session' => function (ServiceContainer $container) {
        $sessionConfig = $container->get("config")["session"];
        return \Session\SessionFactory::build($sessionConfig["driver"], $sessionConfig["config"]);
    },
    'request' => function (ServiceContainer $container) {
        return RequestFactory::createFromGlobals($container);
    },
    'csrf' => function(ServiceContainer $serviceContainer) {
        return new CsrfTokenManager(new UriSafeTokenGenerator(), $serviceContainer->get('session'));
    },
    'pipeline' => function (ServiceContainer $container) {
        $config = $container->get("config");
        $pipeline = new Middleware\MiddlewareStack();
        $authMiddleware = new AuthorizationMiddleware(["^/$", "^/image/[0-9]+$", "^/private/[a-z\.0-9]+"], $container->get("authService"), "/login");
        $dispatcherMiddleware = new Middleware\DispatchingMiddleware($container->get("dispatcher"), $container->get("responseFactory"));
        $pipeline->addMiddleware(new CsrfMiddleware($container->get("csrf")));
        $pipeline->addMiddleware($authMiddleware);
        $pipeline->addMiddleware(new FlashMessageCleanupMiddleware);
        $pipeline->addMiddleware(new LocalizationMiddleware($config["default_locale"], $config["available_locales"]));
        $pipeline->addMiddleware($dispatcherMiddleware);
        return $pipeline;
    },
    'dispatcher' => function (ServiceContainer $container) {
        $dispatcher = new Dispatcher($container, 'notFoundController@handle');
        $dispatcher->addRoute('/', 'homeController@handle');
        $dispatcher->addRoute('/image/(?<id>[\d]+)', 'singleImageController@display');
        $dispatcher->addRoute('/image/(?<id>[\d]+)/edit', 'singleImageEditController@edit', "POST");
        $dispatcher->addRoute('/image/(?<id>[\d]+)/delete', 'singleImageDeleteController@delete', "POST");

        $dispatcher->addRoute('/private/(?<id>[a-z\.0-9]+)', 'imageServeController@show');

        $dispatcher->addRoute('/login', 'loginFormController@show');
        $dispatcher->addRoute('/logout', 'logoutSubmitController@submit');
        $dispatcher->addRoute('/login', 'loginSubmitController@submit', "POST");

        $dispatcher->addRoute('/forgotpass', 'forgotPasswordController@show');
        $dispatcher->addRoute('/forgotpass', 'forgotPasswordSubmitController@submit', "POST");

        $dispatcher->addRoute('/reset', 'passwordResetController@show');
        $dispatcher->addRoute('/reset', 'passwordResetSubmitController@submit', "POST");

        $dispatcher->addRoute('/image/add', 'imageCreateFormController@show');
        $dispatcher->addRoute('/image/add', 'imageCreateSubmitController@submit', "POST");
        return $dispatcher;
    }
];
