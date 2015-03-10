<?php

initServices($app);
function initServices($app) {

  /*------------------------------*\
              CONFIG
  \*------------------------------*/
  $app['config.support_email'] = 'alex@grin.io';
  $app['mailgun_api_key'] = MAILGUN_API_KEY;


  /*------------------------------*\
              LOGGING
  \*------------------------------*/
  $app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => ROOT_DIR.'/dev.log',
  ]);


  /*------------------------------*\
              ROUTING
  \*------------------------------*/
  $app->register(new Silex\Provider\UrlGeneratorServiceProvider());


  /*------------------------------*\
              DATABASE
  \*------------------------------*/

  require_once APP_DIR.'/db.php';

  $app['pdo.db'] = SQLITE_DB;

  $app['pdo'] = $app->share(function ($app) {
    $app->log('Opening db at ' . $app['pdo.db']);
    return new myPDO($app, 'sqlite:'.$app['pdo.db'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
  });
  $app['pdo']->execute('PRAGMA encoding = "UTF-8"');

  /*------------------------------*\
              MAILER
  \*------------------------------*/

  // require_once __DIR__.'/mailer.php';

  // $app['mandrill.token'] = MANDRILL_API_KEY;
  // $app['mailer'] = $app->share(function($app) {
  //   return new BgMailer($app, $app['mandrill.token']);
  // });

}
