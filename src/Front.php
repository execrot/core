<?php

declare(strict_types=1);

namespace Light\Core;

use Exception;
use Error;
use Throwable;
use Closure;

use Light\Core\Exception\ActionMethodWasNotFound;
use Light\Core\Exception\InjectorParamRequired;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use Light\Core\Exception\ControllerClassWasNotFound;
use Light\Core\Exception\ActionMethodIsReserved;
use Light\Core\Exception\Stop;

/**
 * Class Front
 * @package Light
 */
final class Front
{
  /**
   * @var Front
   */
  private static $instance = null;

  /**
   * @var array
   */
  private $config = [];

  /**
   * @var Loader
   */
  private $loader = null;

  /**
   * @var BootstrapAbstract
   */
  private $bootstrap = null;

  /**
   * @var Request
   */
  private $request = null;

  /**
   * @var Response
   */
  private $response = null;

  /**
   * @var Router
   */
  private $router = null;

  /**
   * @var Closure[]
   */
  private $injectors = [];

  /**
   * Front constructor.
   * @param array $config
   */
  private function __construct(array $config = [])
  {
    $this->config = $config;
    $this->loader = new Loader($this->config);

    $bootstrapClassName = '\\' . $this->config['light']['loader']['namespace'] . '\\Bootstrap';

    if (class_exists($bootstrapClassName)) {
      $this->bootstrap = new $bootstrapClassName();
      $this->bootstrap->setConfig($this->config);
    }
  }

  /**
   * @param array $config
   * @return Front
   */
  public static function getInstance(array $config = [])
  {
    if (!self::$instance) {
      self::$instance = new self($config);
    }

    return self::$instance;
  }

  /**
   * @return Router
   */
  public function getRouter(): Router
  {
    return $this->router;
  }

  /**
   * @param Router $router
   */
  public function setRouter(Router $router)
  {
    $this->router = $router;
  }

  /**
   * @return Response
   */
  public function getResponse(): Response
  {
    return $this->response;
  }

  /**
   * @param Response $response
   */
  public function setResponse(Response $response)
  {
    $this->response = $response;
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
   * @return BootstrapAbstract
   */
  public function getBootstrap(): BootstrapAbstract
  {
    return $this->bootstrap;
  }

  /**
   * @param BootstrapAbstract $bootstrap
   */
  public function setBootstrap(BootstrapAbstract $bootstrap)
  {
    $this->bootstrap = $bootstrap;
  }

  /**
   * @return Loader
   */
  public function getLoader(): Loader
  {
    return $this->loader;
  }

  /**
   * @param Loader $loader
   */
  public function setLoader(Loader $loader)
  {
    $this->loader = $loader;
  }

  /**
   * @param string $type
   * @param Closure $closure
   * @return void
   */
  public function registerInjector(string $type, Closure $closure)
  {
    $this->injectors[$type] = $closure;
  }

  /**
   * @return $this
   * @throws ReflectionException
   */
  public function bootstrap()
  {
    set_error_handler(function ($number, $message, $file, $line) {
      throw new Exception(implode(':', [$message, $file, $line]), $number);
    });

    if ($this->bootstrap) {

      $bootReflection = new ReflectionClass(
        $this->bootstrap
      );

      foreach ($bootReflection->getMethods() as $method) {

        if ($method->class != BootstrapAbstract::class) {
          $this->bootstrap->{$method->name}();
        }
      }
    }

    return $this;
  }

  /**
   * @param Exception|Error $exception
   * @return string
   *
   * @throws Exception
   */
  public function run($exception = null)
  {
    if (!$this->request) {

      $this->request = new Request();

      if (php_sapi_name() !== 'cli') {
        $this->request->fillRequestFromServer();
      } else {
        $this->request->fillRequestFromCli();
      }
    }

    if (!$this->response) {
      $this->response = new Response();
    }

    try {

      if (!$this->router) {
        $this->router = new Router();
        $this->router->setRoutes($this->config['router'] ?? []);
        $this->router->setRequest($this->request);
        $this->router->parse();
      }

      $this->config['light'] = array_replace_recursive(
        $this->config['light'],
        $this->router->getConfig()
      );

      foreach ($this->config['light']['phpIni'] ?? [] as $key => $val) {
        ini_set($key, $val);
      }

      foreach ($this->config['light']['startup'] ?? [] as $key => $val) {
        if (function_exists($key)) {
          if (!is_array($val)) {
            $val = [$val];
          }
          call_user_func_array($key, $val);
        }
      }

      foreach ($this->config['light']['headers'] ?? [] as $key => $val) {
        $this->response->setHeader($key, $val);
      }

      $modules = null;

      if ($this->config['light']['modules'] ?? false) {
        $modules = implode('/', array_slice(explode('\\', $this->config['light']['modules']), 2));
      }

      $controllerClassName = $this->getControllerClassName($this->router);

      if (!class_exists($controllerClassName, true) || !is_subclass_of($controllerClassName, Controller::class)) {

        if ($exception) {
          throw $exception;
        }

        throw new ControllerClassWasNotFound($controllerClassName);
      }

      $this->runModuleBootstrap($this->router);

      /** @var Controller|ErrorController $controller */
      $controller = new $controllerClassName();

      $controller->setRequest($this->request);
      $controller->setResponse($this->response);
      $controller->setRouter($this->router);

      if ($exception && is_subclass_of($controller, ErrorController::class)) {
        $controller->setException($exception);
        $controller->setExceptionEnabled($this->config['light']['exception'] ?? false);

      } else if ($exception) {
        throw $exception;
      }

      /** @var Plugin[] $plugins */
      $plugins = [];

      $pluginsPaths = [
        '\\' . $this->config['light']['loader']['namespace'] . '\\Plugin\\' =>
          realpath($this->config['light']['loader']['path'] . '/Plugin')
      ];

      if ($modules) {
        $pluginsPaths['\\' . $this->config['light']['loader']['namespace'] . '\\' . $modules . '\\' .
        ucfirst($this->router->getModule()) . '\\Plugin\\'] =
          realpath(implode('/', [
            $this->config['light']['loader']['path'],
            $modules,
            ucfirst($this->router->getModule()),
            'Plugin'
          ]));
      }

      foreach (array_filter($pluginsPaths) as $pluginNamespace => $pluginsPath) {
        foreach (glob($pluginsPath . '/*.php') as $pluginClass) {
          $pluginClassName = $pluginNamespace . str_replace('.php', '', basename($pluginClass));
          if (is_subclass_of($pluginClassName, Plugin::class)) {
            $plugins[] = new $pluginClassName();
          }
        }
      }

      foreach ($plugins as $plugin) {
        $plugin->preRun($this->request, $this->response, $this->router);
      }

      $controller->init();

      if (is_callable([$controller, $this->router->getAction()])) {

        if (in_array($this->router->getAction(), get_class_methods(Controller::class))) {
          throw new ActionMethodIsReserved($this->router->getAction());
        }

        $content = call_user_func_array(
          [$controller, $this->router->getAction()],
          $this->inject($controller, $this->router, $this->request)
        );

        $controller->postRun();

        $this->response->setBody($content);

        foreach ($plugins as $plugin) {
          $plugin->postRun($this->request, $this->response, $this->router);
        }

      } else {
        throw new ActionMethodWasNotFound($this->router->getAction());
      }

      return $this->render($this->response);
    } catch (\Throwable $localException) {

      if ($localException instanceof Stop) {
        return $this->render($this->response);
      }

      if (!$exception) {

        $errorRouter = new Router();

        $errorRouter->setRequest($this->request);
        $errorRouter->setModule($this->router->getModule());
        $errorRouter->setController('error');
        $errorRouter->setAction('index');
        $errorRouter->setRoutes($this->config['router'] ?? []);
        $errorRouter->setConfig($this->router->getConfig());

        $this->setRouter($errorRouter);
        return $this->run($localException);
      }

      if ($this->config['light']['exception'] ?? false) {
        throw $localException;
      }
    }
  }

  /**
   * @param Router $router
   * @return string
   */
  public function runModuleBootstrap(Router $router): void
  {
    if ($this->config['light']['modules'] ?? false) {
      $moduleBootstrap = implode('\\', [
        $this->config['light']['modules'],
        ucfirst($router->getModule()),
        'Bootstrap'
      ]);

      try {
        if (class_exists($moduleBootstrap, true)) {

          /** @var BootstrapAbstract $bootstrap */
          $bootstrap = new $moduleBootstrap();

          if ($bootstrap instanceof BootstrapAbstract) {
            $bootstrap->setConfig($this->config);

            $bootReflection = new ReflectionClass($bootstrap);
            foreach ($bootReflection->getMethods() as $method) {
              if ($method->class != BootstrapAbstract::class) {
                $bootstrap->{$method->name}();
              }
            }
          }
        }
      } catch (Throwable $e) {
      }
    }
  }

  /**
   * @param Router $router
   * @return string
   */
  public function getControllerClassName(Router $router): string
  {
    $module = $router->getModule();
    $controller = $router->getController();

    if ($this->config['light']['modules'] ?? false) {
      return implode('\\', [
        $this->config['light']['modules'],
        ucfirst($module),
        'Controller',
        ucfirst($controller),
      ]);
    }

    return implode('\\', [
      $this->config['light']['loader']['namespace'],
      'Controller',
      ucfirst($controller),
    ]);
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

    if ($this->bootstrap) {
      $this->bootstrap->setConfig($config);
    }
  }

  /**
   * @param Controller $controller
   * @param Router $router
   * @param Request $request
   *
   * @return array
   * @throws ReflectionException
   */
  public function inject(Controller $controller, Router $router, Request $request)
  {
    $reflection = new ReflectionMethod($controller, $router->getAction());
    $routerInjector = $router->getInjector();

    $params = [];

    if ($docComment = $reflection->getDocComment()) {
      $docComment = str_replace('*', '', $reflection->getDocComment());

      $docComment = array_filter(array_map(function ($line) {

        $line = trim($line);

        if (strlen($line) > 0) {
          return $line;
        }
      }, explode("\n", $docComment)));

      foreach ($docComment as $line) {
        if (substr($line, 0, strlen('@param')) == '@param') {
          try {
            $param = explode('|', explode('$', $line)[1]);
          } catch (Exception $e) {
          }

          $params[trim($param[0])] = trim($param[1] ?? 'id');
        }
      }
    }

    $args = [];

    foreach ($reflection->getParameters() as $parameter) {

      $var = $parameter->getName();

      if (isset($routerInjector[$var])) {
        $args[$var] = $injector[$var]($router->getUrlParams()[$var] ?? null);

      } else {
        $value = $request->getUriParam($var)
          ?? $request->getGet($var)
          ?? $request->getBodyVar($var);

        if (!$parameter->getType()) {
          $args[$var] = $value;
          continue;
        }

        switch ($parameter->getType()->getName()) {

          case 'int':
            $args[$var] = $value ? intval($value) : null;
            break;

          case 'string':
            $args[$var] = $value ? strval($value) : null;
            break;

          case 'bool':
            $args[$var] = boolval(intval($value));
            break;

          default:
            try {
              if ($injector = ($this->injectors[$parameter->getType()->getName()] ?? false)) {
                $args[$var] = $injector($parameter, $value);
              }

            } catch (InjectorParamRequired $injectorParamRequired) {
              throw $injectorParamRequired;

            } catch (Throwable $e) {
              $args[$var] = $value;
            }
        }
      }
      if (is_null(($args[$var] ?? null)) && !$parameter->allowsNull()) {
        if ($parameter->isDefaultValueAvailable()) {
          $args[$var] = $parameter->getDefaultValue();
          
        } else {
          throw new InjectorParamRequired($var);
        }
      }
    }

    return $args;
  }

  /**
   * @param Response $response
   * @return mixed
   */
  public function render(Response $response)
  {
    $content = $response->getBody();

    if (is_callable([$content, 'toArray'])) {
      $content = $content->toArray();
    }

    if (is_array($content)) {
      $content = json_encode($content);
      $this->response->setHeader('Content-type', 'application/json');
    }

    $response->setBody($content);

    /** Setup Status **/
    $statusCode = $response->getStatusCode();

    if (php_sapi_name() == 'cli') {
      return null;
    }

    $phpSapiName = substr(php_sapi_name(), 0, 3);

    if ($phpSapiName == 'cgi' || $phpSapiName == 'fpm') {
      header('Status: ' . $statusCode);
    } else {
      $protocol = isset($SERVER['SERVER_PROTOCOL']) ? $SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
      header($protocol . ' ' . $statusCode);
    }

    /** Setup Headers **/
    foreach ($response->getHeaders() as $name => $value) {
      header($name . ': ' . $value);
    }

    return $response->getBody();
  }

  /**
   * @throws Stop
   */
  public function stop()
  {
    throw new Stop();
  }
}
