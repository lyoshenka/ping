<?php

ini_set('error_reporting', E_ALL|E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);

require_once ROOT_DIR.'/vendor/autoload.php';

require_once APP_DIR.'/config.php';
require_once APP_DIR.'/app.php';

$app = new MyApp();
$app['debug'] = true;

// Initialize Services
require_once APP_DIR.'/services.php';


// Mount Routes
require_once APP_DIR.'/routes/main.php';

// If no route matches, 404
$app->error(function(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $code) use ($app) {
  return new Response('<h1>404</h1>', Response::HTTP_NOT_FOUND);
});

$app->error(function (\Exception $e, $code) use ($app) {
  if ($app['debug'])
  {
    return;
  }
  //$app['mailer']->sendErrorEmail($e);
  return new Response('<h1>500</h1><pre>'.$e.'</pre>', Response::HTTP_INTERNAL_SERVER_ERROR);
});