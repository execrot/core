<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class Phone
 * @package Light\Filter
 */
class Phone extends FilterAbstract
{
  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    return '+' . trim(str_replace(['+', '-', ' ', '-', '(', ')', '.', ','], '', $value));
  }
}
