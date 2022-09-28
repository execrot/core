<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class RouterVarMustBeProvided extends Exception
{
  /**
   * @param string $var
   */
  public function __construct(string $var)
  {
    parent::__construct($var, 500);
  }
}