<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class Trim
 * @package Light\Filter
 */
class Trim extends FilterAbstract
{
  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    return trim($value ?? '');
  }
}
