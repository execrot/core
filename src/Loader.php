<?php

declare(strict_types=1);

namespace Light\Core;

use Light\Core\Exception\ClassWasNotFound;

class Loader
{
  /**
   * @var array
   */
  private $config = [];

  /**
   * @param array $config
   * @throws ClassWasNotFound
   */
  public function __construct(array $config = [])
  {
    $this->config = $config;
    $self = $this;
    spl_autoload_register(function ($className) use ($config, $self) {
      $classFilePath = $self->getClassFilePath($className);
      if (!$classFilePath) {
        throw new ClassWasNotFound($classFilePath);
      }
      if (file_exists($classFilePath)) {
        require_once $classFilePath;
      }
    });
  }

  /**
   * @param string $namespace
   * @return string|null
   */
  public function getClassFilePath(string $namespace)
  {
    $namespace = explode('\\', $namespace);

    if ($namespace[0] == 'Light') {
      unset($namespace[0]);
      return implode('/', array_merge([realpath(__DIR__)], $namespace)) . '.php';

    } else if ($namespace[0] == $this->config['light']['loader']['namespace']) {
      unset($namespace[0]);
      return implode('/', array_merge(
          [$this->config['light']['loader']['path']],
          $namespace
        )) . '.php';
    }
  }
}