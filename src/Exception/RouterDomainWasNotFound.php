<?php

declare(strict_types=1);

namespace Light\Core\Exception;

use Exception;

class RouterDomainWasNotFound extends Exception
{
  /**
   * @param string $domain
   */
  public function __construct(string $domain)
  {
    parent::__construct($domain, 404);
  }
}