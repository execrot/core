<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class Lowercase
 * @package Light\Filter
 */
class Lowercase extends FilterAbstract
{
  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    return strtolower($value);
  }
}