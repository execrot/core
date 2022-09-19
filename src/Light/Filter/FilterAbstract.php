<?php

declare(strict_types=1);

namespace Light\Filter;

/**
 * Class FilterAbstract
 */
abstract class FilterAbstract
{
  /**
   * FilterAbstract constructor.
   * @param array $options
   */
  public function __construct(array $options = [])
  {
    foreach ($options as $name => $value) {

      if (is_callable([$this, 'set' . ucfirst($name)])) {
        call_user_func_array([$this, 'set' . ucfirst($name)], [$value]);
      }
    }
  }

  /**
   * @param string $value
   * @return string
   */
  public abstract function filter(string $value): string;
}