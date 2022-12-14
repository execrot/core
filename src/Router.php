<?php

declare(strict_types=1);

namespace Light\Core;

use Light\Core\Exception\RouterDomainWasNotFound;
use Light\Core\Exception\RouterVarMustBeProvided;
use Light\Core\Exception\RouterWasNotFound;
use Light\Core\Exception\DomainMustBeProvided;

class Router
{
  /**
   * @var Request
   */
  private $request = null;

  /**
   * @var string
   */
  private $module = '';

  /**
   * @var string
   */
  private $controller = '';

  /**
   * @var string
   */
  private $action = '';

  /**
   * @var array
   */
  private $routes = [];

  /**
   * @var array
   */
  private $urlParams = [];

  /**
   * @var array
   */
  private $injector = [];

  /**
   * @var array
   */
  private $config = [];

  /**
   * @return string
   */
  public function getModule(): string
  {
    return $this->module;
  }

  /**
   * @param string $module
   */
  public function setModule(string $module)
  {
    $this->module = $module;
  }

  /**
   * @return string
   */
  public function getController(): string
  {
    return $this->controller;
  }

  /**
   * @param string $controller
   */
  public function setController(string $controller)
  {
    $this->controller = $controller;
  }

  /**
   * @return string
   */
  public function getAction(): string
  {
    return $this->action;
  }

  /**
   * @param string $action
   */
  public function setAction(string $action)
  {
    $this->action = $action;
  }

  /**
   * @return array
   */
  public function getRoutes()
  {
    return $this->routes;
  }

  /**
   * @param array $routes
   */
  public function setRoutes(array $routes)
  {
    $this->routes = $routes;
  }

  /**
   * @return array
   */
  public function getUrlParams(): array
  {
    return $this->urlParams;
  }

  /**
   * @param array $urlParams
   */
  public function setUrlParams(array $urlParams): void
  {
    $this->urlParams = $urlParams;
  }

  /**
   * @return array
   */
  public function getInjector(): array
  {
    return $this->injector;
  }

  /**
   * @param array $injector
   */
  public function setInjector(array $injector): void
  {
    $this->injector = $injector;
  }

  /**
   * @return array
   */
  public function getConfig(): array
  {
    return $this->config;
  }

  /**
   * @param array $config
   */
  public function setConfig(array $config): void
  {
    $this->config = $config;
  }

  /**
   * @param array $requestedRoute
   * @param array $params
   * @param bool $reset
   *
   * @return string
   *
   * @throws DomainMustBeProvided
   * @throws RouterVarMustBeProvided
   */
  public function assemble(array $requestedRoute = [], array $params = [], bool $reset = false)
  {
    $module = $requestedRoute['module'] ?? $this->module;
    $controller = $requestedRoute['controller'] ?? ($reset ? '' : $this->controller);
    $action = $requestedRoute['action'] ?? ($reset ? '' : $this->action);

    if (!$reset) {
      $params = array_merge($this->urlParams, $params);
    }

    $uri = null;
    $selectedDomain = null;

    foreach ($this->routes as $domain => $router) {

      if (($router['module'] ?? null) == $module) {

        $selectedDomain = $domain;

        foreach ($router['routes'] ?? [] as $routeUri => $route) {

          if ($routeUri == '*' && is_callable($route)) {
            continue;
          }

          $routeUri = ($router['prefix'] ?? '') . $routeUri;

          $currentRouteSettings = [
            'controller' => $route['controller'] ?? 'index',
            'action' => $route['action'] ?? 'index'
          ];

          $chController = $controller == '' ? 'index' : $controller;
          $chAction = $action == '' ? 'index' : $action;

          if ($chController == $currentRouteSettings['controller']
            && $chAction == $currentRouteSettings['action']) {

            $releaseParts = [];

            foreach (explode('/', $routeUri) as $part) {

              if (substr($part, 0, 1) == ':') {

                $var = substr($part, 1);

                $val = $params[$var] ?? null;

                if ($params[$var] ?? null) {
                  $releaseParts[] = $params[$var];
                  unset($params[$var]);
                }
              } else {
                $releaseParts[] = $part;
              }
            }

            $uri = implode('/', $releaseParts);
          }
        }
      }
    }

    if (!$selectedDomain) {
      throw new DomainMustBeProvided();
    }

    if (!$uri) {
      $uri = '/' . implode('/', array_filter([$controller, $action]));
    }

    if (count($params)) {
      $uri = $uri . '?' . http_build_query($params);
    }

    if ($selectedDomain == '*') {
      $selectedDomain = $this->getRequest()->getDomain();
    }

    $port = '';

    if ($this->getRequest()->getPort() != 80 && $this->getRequest()->getPort() != 443) {
      $port = ':' . $this->getRequest()->getPort();
    }

    return $this->getRequest()->getScheme() . '://' . $selectedDomain . $port . $uri;
  }

  /**
   * @return Request
   */
  public function getRequest(): Request
  {
    return $this->request;
  }

  /**
   * @param Request $request
   */
  public function setRequest(Request $request)
  {
    $this->request = $request;
  }

  /**
   * @throws RouterDomainWasNotFound
   * @throws RouterWasNotFound
   */
  public function parse()
  {
    $domain = $this->getRequest()->getDomain();

    if (!isset($this->routes[$domain]) && !isset($this->routes['*']) && count($this->routes)) {
      throw new RouterDomainWasNotFound($domain);
    } else if (!isset($this->routes[$domain])) {
      $domain = '*';
    }

    $this->setConfig($this->routes[$domain]['light'] ?? []);

    $routes = $this->routes[$domain] ?? [];
    $this->module = $routes['module'] ?? '';

    $uri = explode('?', $this->getRequest()->getUri())[0];

    $parts = array_values(array_filter(explode('/', $uri)));

    if (!isset($routes['routes'])) {

      if (isset($routes['strict']) && $routes['strict'] === true) {
        throw new RouterWasNotFound($uri);
      }

      $this->controller = $parts[0] ?? 'index';
      $this->action = $parts[1] ?? 'index';

      return;
    }

    $prefix = $routes['prefix'] ?? '';

    if ($uri != '/' && substr($uri, -1) == '/') {
      $uri = $uri . '/';
    }

    $match = false;
    $withPrefix = false;

    foreach ($routes['routes'] as $routerUri => $settings) {

      if (substr($uri, -2) == '//' && $routerUri != '/') {
        $routerUri = $routerUri . '/';
      }

      $pattern = preg_replace('/\\\:[??-????-??????a-zA-Z0-9\_\-]+/', '([??-????-??????a-zA-Z0-9\-\_]+)', preg_quote($routerUri, '@'));
      $pattern = "@^$pattern/?$@uD";

      $matches = [];

      if (preg_match($pattern, $uri, $matches)) {
        $match = true;
        break;
      }
    }

    if (!$match) {

      $withPrefix = true;

      foreach ($routes['routes'] as $routerUri => $settings) {

        if (substr($uri, -2) == '//' && $routerUri != '/') {
          $routerUri = $routerUri . '/';
        }

        $patternPrefix = preg_replace('/\\\:[??-????-??????a-zA-Z0-9\_\-]+/', '([??-????-??????a-zA-Z0-9\-\_]+)', preg_quote($prefix . $routerUri, '@'));
        $patternPrefix = "@^$patternPrefix/?$@uD";

        if (preg_match($patternPrefix, $uri, $matches)) {
          $match = true;
          break;
        }
      }
    }

    if ($match) {

      array_shift($matches);

      $this->controller = $settings['controller'] ?? 'index';
      $this->action = $settings['action'] ?? 'index';
      $this->urlParams = $settings['params'] ?? [];
      $this->module = $settings['module'] ?? $this->module;

      $paramIndex = 0;

      if ($withPrefix) {
        $routerUri = $prefix . $routerUri;
      }

      foreach (explode('/', $routerUri) as $routerUriPart) {

        if (substr($routerUriPart, 0, 1) == ':') {

          $this->getRequest()->setUriParam(
            substr($routerUriPart, 1),
            $matches[$paramIndex] ?? null
          );

          $this->urlParams[substr($routerUriPart, 1)] = $matches[$paramIndex] ?? null;

          $paramIndex++;
        }
      }

      $this->injector = array_merge($settings['injector'] ?? [], $routes['prefixInjector'] ?? []);

      foreach ($this->urlParams as $key => $value) {
        $this->getRequest()->setUriParam($key, $value);
      }

      if (!empty($settings['method']) && $settings['method'] !== $this->getRequest()->getMethod()) {
        throw new RouterWasNotFound($uri, $this->getRequest()->getMethod());
      }

      return;
    }

    if (isset($routes['routes']['*']) && is_callable($routes['routes']['*'])) {

      $route = $routes['routes']['*']($uri);

      if (is_array($route)) {

        $this->controller = $route['controller'] ?? 'index';
        $this->action = $route['action'] ?? 'index';
        $this->urlParams = $route['params'] ?? [];
        $this->injector = $route['injector'] ?? [];

        foreach ($this->urlParams as $key => $value) {
          $this->getRequest()->setGetParam($key, $value);
        }

        return;
      }
    }

    if (isset($routes['strict']) && $routes['strict'] === true) {
      throw new RouterWasNotFound($uri);
    }

    $this->controller = $parts[0] ?? 'index';
    $this->action = $parts[1] ?? 'index';

    $parts = array_slice($parts, 2);

    for ($i = 0; $i < count($parts); $i += 2) {

      if (isset($parts[$i + 1])) {
        $this->getRequest()->setGetParam($parts[$i], $parts[$i + 1]);
        $this->urlParams[$parts[$i]] = $parts[$i + 1];
      }
    }
  }
}
