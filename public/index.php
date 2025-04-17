<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use DI\Container;

// Requerimos el archivo autoload.php de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Cargar configuración de la aplicación
require_once __DIR__ . '/../src/config/config.php';
$appConfig = loadConfig('app');

// Configurar zona horaria
date_default_timezone_set($appConfig['timezone']);

/**
 * Inicializa y configura la aplicación
 *
 * @return \Slim\App
 */
function initializeApp(): \Slim\App
{
    // Creamos una instancia del contenedor de dependencias
    $container = new Container();

    // Configuramos el contenedor antes de crear la app
    configureContainer($container);

    // Creamos una instancia de Slim con el contenedor
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Registramos la aplicación en el contenedor
    $container->set('app', $app);

    // Configurar middleware
    configureMiddleware($app, $container);

    // Configurar rutas base
    configureBaseRoutes($app);

    // Cargar rutas de la aplicación
    loadApplicationRoutes(__DIR__ . '/../src/routes', $container);

    return $app;
}

/**
 * Configura el contenedor de dependencias
 *
 * @param Container $container
 * @return void
 */
function configureContainer(Container $container): void
{
    // Incluimos el cargador de configuraciones
    require_once __DIR__ . '/../src/config/config.php';

    // Registramos el servicio de configuración
    $container->set('config', function () {
        return function (string $configName) {
            return loadConfig($configName);
        };
    });

    // Configuramos Medoo como servicio en el contenedor usando la configuración
    $container->set('db', function ($c) {
        $config = $c->get('config');
        return new \Medoo\Medoo($config('database'));
    });

    // Registramos el helper de base de datos
    $container->set('dbHelper', function ($c) {
        return new \App\Helpers\DbHelper($c->get('db'));
    });

    // Registramos el helper de JWT
    $container->set('jwtHelper', function ($c) {
        $config = $c->get('config');
        return new \App\Helpers\JwtHelper($config('jwt'));
    });

    // Registramos el factory de validator
    $container->set('validator', function ($c) {
        return function ($data = null, $dbHelper = null) use ($c) {
            // Si $data es null, usamos un array vacío
            if ($data === null) {
                $data = [];
            }
            
            // Si no se proporciona un DbHelper, usamos el del contenedor
            if ($dbHelper === null) {
                $dbHelper = $c->get('dbHelper');
            }
            
            return new \App\Helpers\Validator($data, $dbHelper);
        };
    });

    // Puedes añadir más servicios aquí según sea necesario
}

/**
 * Configura los middleware necesarios para la aplicación
 *
 * @param \Slim\App $app
 * @param \DI\Container $container
 * @return void
 */
function configureMiddleware(\Slim\App $app, \DI\Container $container): void
{
    // Obtenemos la configuración
    $config = $container->get('config');
    $appConfig = $config('app');
    $corsConfig = $config('cors');

    // Agregamos middleware para parsear el cuerpo de la solicitud en formato JSON
    $app->addBodyParsingMiddleware();

    // Agregamos middleware para manejar las rutas
    $app->addRoutingMiddleware();

    // Agregamos middleware para manejar CORS
    $app->add(new \App\Middleware\CorsMiddleware($corsConfig));

    // Determinar si estamos en producción o desarrollo
    $isProduction = $appConfig['env'] === 'production';

    // Configuración de los detalles de errores según el entorno
    $displayErrorDetails = !$isProduction; // false en producción, true en desarrollo
    $logErrors = true; // Siempre registra errores
    $logErrorDetails = true; // Siempre registra detalles completos en los logs

    // Agregamos middleware para manejar errores
    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

    // Solo sobrescribe el manejador predeterminado si estamos en producción
    if ($isProduction) {
        // Manejador personalizado para errores generales en producción
        $errorMiddleware->setDefaultErrorHandler(
            function (Request $request, Throwable $exception, bool $displayErrorDetails) use ($app) {
                $response = $app->getResponseFactory()->createResponse(500);

                // Detectamos si es una solicitud de API o no
                if (isApiRequest($request)) {
                    return \App\Helpers\JsonResponse::error(
                        $response,
                        'Se ha producido un error en el servidor',
                        ['error' => $exception->getMessage()],
                        500
                    );
                } else {
                    // Vista HTML genérica para usuarios finales
                    $response->getBody()->write(
                        '<html><head><title>Error</title></head>' .
                            '<body><h1>Error</h1><p>Se ha producido un error en el servidor. ' .
                            'Por favor, inténtalo de nuevo más tarde.</p></body></html>'
                    );

                    return $response->withHeader('Content-Type', 'text/html');
                }
            }
        );
    }
}

/**
 * Verifica si la solicitud es una solicitud de API
 *
 * @param Request $request
 * @return bool
 */
function isApiRequest(Request $request): bool
{
    return strpos($request->getUri()->getPath(), '/api') === 0;
}

/**
 * Configura las rutas base de la aplicación
 *
 * @param \Slim\App $app
 * @return void
 */
function configureBaseRoutes(\Slim\App $app): void
{
    // Ruta OPTIONS - Responde a las solicitudes preflight de CORS
    $app->options('/{routes:.+}', function (Request $request, Response $response) {
        return $response;
    });

    // Definimos una ruta para la raíz de la aplicación
    $app->get('/', function (Request $request, Response $response) {
        $data = [
            'status' => 'success',
            'message' => 'Biennevenido a miapí'
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    });
}

/**
 * Carga todos los archivos de rutas de la aplicación
 *
 * @param string $directory Directorio donde están las rutas
 * @param Container $container Contenedor con las dependencias
 * @return void
 */
function loadApplicationRoutes(string $directory, Container $container): void
{
    if (!is_dir($directory)) {
        throw new \RuntimeException("El directorio de rutas no existe: {$directory}");
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            // Hacemos el contenedor disponible en el archivo de rutas
            require_once $file->getRealPath();
        }
    }
}

// Inicializar y ejecutar la aplicación
$app = initializeApp();
$app->run();