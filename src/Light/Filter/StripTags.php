<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class StripTags
 * @package Light\Filter
 */
class StripTags extends FilterAbstract
{
  public array $allowableTags = [];

  /**
   * @return array
   */
  public function getAllowableTags(): array
  {
    return $this->allowableTags;
  }

  /**
   * @param array $allowableTags
   * @return void
   */
  public function setAllowableTags(array $allowableTags): void
  {
    $this->allowableTags = $allowableTags;
  }

  /**
   * @param string $value
   * @return string
   */
  public function filter(string $value): string
  {
    $allowableTags = [];

    foreach ($this->allowableTags as $allowableTag) {
      $allowableTags[] = '<' . $allowableTag . '>';
    }

    return strip_tags($value, implode('', $allowableTags));
  }
}