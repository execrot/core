<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class Stop extends Exception
{
  public function __construct()
  {
    parent::__construct();
  }
}