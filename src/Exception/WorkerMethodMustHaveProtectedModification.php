<?php

declare(strict_types=1);

namespace Light\Core\Exception;

class WorkerMethodMustHaveProtectedModification extends \Exception
{
  /**
   * @param string $methodName
   */
  public function __construct(string $methodName)
  {
    parent::__construct($methodName, 404);
  }
}