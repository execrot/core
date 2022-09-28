<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class RouterWasNotFound extends Exception
{
  /**
   * @param string $uri
   * @param string|null $method
   */
  public function __construct(string $uri, string $method = null)
  {
    parent::__construct(($method ? strtoupper($method) . ': ' : '') . $uri, 404);
  }
}