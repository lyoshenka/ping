<?php

class MyApp extends Silex\Application {
  use Silex\Application\UrlGeneratorTrait;
  use Silex\Application\MonologTrait;

  function forward($path, $requestType = 'GET')
  {
    $subReq = Symfony\Component\HttpFoundation\Request::create($path, $requestType);
    return $this->handle($subReq, Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
  }
}