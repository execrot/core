<?php

declare(strict_types=1);

namespace Light\Core;

abstract class BootstrapAbstract
{
  /**
   * @var array
   */
  private $config = [];

  /**
   * @return array
   */
  final public function getConfig(): array
  {
    return $this->config;
  }

  /**
   * @param array $config
   */
  final public function setConfig(array $config)
  {
    $this->config = $config;
  }
}