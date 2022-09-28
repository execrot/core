<?php

declare(strict_types=1);

namespace Light\Core\Exception;

class ClassWasNotFound extends \Exception
{
  /**
   * @param string|null $className
   */
  public function __construct(string $className = null)
  {
    parent::__construct($className, 404);
  }
}