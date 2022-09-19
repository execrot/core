<?php

declare(strict_types=1);

namespace Light\Exception;

use Exception;

/**
 * Class ValidatorClassWasNotFound
 * @package Light\Exception
 */
class ValidatorClassWasNotFound extends Exception
{
  /**
   * ValidatorClassWasNotFound constructor.
   * @param ?string $validatorClassName
   */
  public function __construct(?string $validatorClassName)
  {
    parent::__construct('ValidatorClassWasNotFound: ' . $validatorClassName);
  }
}