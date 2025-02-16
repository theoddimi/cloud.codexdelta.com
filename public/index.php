<?php

// Register the Composer autoloader...
use Codexdelta\App\App;
use Codexdelta\Libs\Exceptions\Handler;
use Codexdelta\Libs\Http\CdxRequest;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/src/Entities'],
    isDevMode: true,
);

// configuring the database connection
$connection = DriverManager::getConnection([
    'host' => '127.0.0.1',
    'dbname'   => 'cdx_cloud',
    'driver' => 'pdo_mysql',
    'user'     => env('DB_USER'),
    'password' => env('DB_PASSWORD'),
], $config);

$entityManager = new EntityManager($connection, $config);

Handler::setup();
$routesPath = __DIR__ . '/../config/routes.php';

$loader = new FilesystemLoader(__DIR__ . '/../resources/views/');

$twig = new Environment($loader, [
    'cache' => false,// __DIR__ . '/storage/cache/views_cache',
    'debug' => true
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());



$request = CdxRequest::capture();
$response = App::getInstance($routesPath, $twig, $entityManager)->handle($request);

/** @var Response $response */
$response->sendContent();
