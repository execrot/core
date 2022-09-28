<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class RequestMethodIsNotSupported extends Exception
{
  /**
   * @param string $method
   */
  public function __construct(string $method)
  {
    parent::__construct($method, 500);
  }
}