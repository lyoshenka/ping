<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

initMainRoutes($app);
function initMainRoutes($app) {
  $routes = $app['controllers_factory'];

  $routes->match('/', function(Request $request) use($app) {
    return '<h1>PING</h1>';
  })
  ->method('GET|POST|HEAD')
  ->bind('home');

  $routes->match('/mailgun_webhook', function(Request $request) use($app) {
    if ($request->getMethod() == 'HEAD')
    {
      return new Response('ok');
    }

    $app->log('Got an email from mailgun');
    $app->log('Subject is ' . $request->get('subject'));

    $validRequest = hash_hmac('sha256', $request->get('timestamp') . $request->get('token'), $app['mailgun_api_key']) === $request->get('signature');
    if (!$validRequest)
    {
      return $app->abort(403, "Invalid signature");
    }

    $app->log('Signature is valid');

    $recipient = $request->get('recipient');
    $when = substr($recipient, 0, strpos($recipient, '@'));

    $app['parser']->process($when, $app, trim($request->get('subject')), trim($request->get('body-plain')), $request->get('from'));

    return new Response('ok');
  })
  ->method('POST|HEAD');



  $app->mount('/', $routes);
}
