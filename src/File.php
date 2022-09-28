<?php

declare(strict_types=1);

namespace Light\Core;

class File
{
  /**
   * @var string
   */
  public $name = null;

  /**
   * @var string
   */
  public $type = null;

  /**
   * @var string
   */
  public $tmpName = null;

  /**
   * @var int
   */
  public $error = 0;

  /**
   * @var int
   */
  public $size = 0;

  /**
   * File constructor.
   * @param array $options
   */
  public function __construct(array $options = [])
  {
    foreach ($options as $key => $value) {
      if (is_callable([$this, 'set' . ucfirst($key)])) {
        $this->{'set' . ucfirst($key)}($value);
      }
    }
  }

  /**
   * @return string
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(?string $name): void
  {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getType(): ?string
  {
    return $this->type;
  }

  /**
   * @param string $type
   */
  public function setType(?string $type): void
  {
    $this->type = $type;
  }

  /**
   * @return string
   */
  public function getTmpName(): ?string
  {
    return $this->tmpName;
  }

  /**
   * @param string $tmpName
   */
  public function setTmpName(?string $tmpName): void
  {
    $this->tmpName = $tmpName;
  }

  /**
   * @return int
   */
  public function getError(): int
  {
    return $this->error;
  }

  /**
   * @param int $error
   */
  public function setError(int $error): void
  {
    $this->error = $error;
  }

  /**
   * @return int
   */
  public function getSize(): int
  {
    return $this->size;
  }

  /**
   * @param int $size
   */
  public function setSize(int $size): void
  {
    $this->size = $size;
  }

  /**
   * @return array
   */
  public function toArray(): array
  {
    return [
      'name' => $this->getName(),
      'type' => $this->getType(),
      'tmpName' => $this->getTmpName(),
      'error' => $this->getError(),
      'size' => $this->getSize()
    ];
  }
}