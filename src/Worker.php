<?php

declare(strict_types=1);

namespace Light\Core;

use ReflectionMethod;

use Light\Core\Exception\WorkerMethodMustHaveProtectedModification;
use Light\Core\Exception\ActionMethodWasNotFound;

abstract class Worker extends Controller
{
  /**
   * @param string $name
   * @param array $arguments
   * @throws Exception
   */
  public function __call($name, $arguments)
  {
    $arguments = $this->getParams();

    if (!is_callable([$this, $name])) {
      throw new ActionMethodWasNotFound($name);
    }

    $reflection = new ReflectionMethod($this, $name);
    if (!$reflection->isProtected()) {
      throw new WorkerMethodMustHaveProtectedModification($name);
    }

    $timeout = Front::getInstance()->getConfig()['light']['worker']['sleep'] ?? 1;

    while (call_user_func([$this, $name], $arguments) !== false) {
      sleep($timeout);
    }
  }
}
