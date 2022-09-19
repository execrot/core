<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class Email
 * @package Light\Filter
 */
class Email extends FilterAbstract
{
  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    return trim(strtolower($value));
  }
}
