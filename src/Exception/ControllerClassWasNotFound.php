<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class ControllerClassWasNotFound extends Exception
{
  /**
   * @param string $controllerClassName
   */
  public function __construct(string $controllerClassName)
  {
    parent::__construct($controllerClassName, 404);
  }
}