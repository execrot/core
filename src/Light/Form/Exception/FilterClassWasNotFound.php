<?php

declare(strict_types=1);

namespace Light\Form\Exception;

use Exception;

/**
 * Class FilterClassWasNotFound
 * @package Light\Form\Exception
 */
class FilterClassWasNotFound extends Exception
{
  /**
   * FilterClassWasNotFound constructor.
   * @param string $filterClassName
   */
  public function __construct(string $filterClassName)
  {
    parent::__construct('FilterClassWasNotFound: ' . $filterClassName);
  }
}
