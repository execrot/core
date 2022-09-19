<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class HtmlSpecialChars
 * @package Light\Filter
 */
class HtmlSpecialChars extends FilterAbstract
{
  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    return htmlspecialchars($value);
  }
}