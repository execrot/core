<?php

declare(strict_types=1);

namespace Light\Core\Exception;

class ActionMethodWasNotFound extends \Exception
{
  /**
   * @param string $actionClassName
   */
  public function __construct(string $actionClassName)
  {
    parent::__construct($actionClassName, 404);
  }
}