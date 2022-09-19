<?php

declare(strict_types=1);

namespace Light\Form\Element;

use Exception;
use Light\Filter\FilterAbstract;
use Light\Form\Exception\FilterClassWasNotFound;
use Light\Form\Exception\ValidatorClassWasNotFound;
use Light\Validator\ValidatorAbstract;
use Light\View;

abstract class ElementAbstract
{

  public ?string $name;
  public mixed $value;

  public bool $allowNull = false;

  /**
   * @var ValidatorAbstract[]
   */
  public array $validators = [];

  /**
   * @var FilterAbstract[]
   */
  public array $filters = [];

  /**
   * @var string[]
   */
  public array $errorMessages = [];

  public bool $readOnly = false;


  /**
   * @var string
   */
  public $containerTemplate = 'element/container';

  /**
   * @var string
   */
  public $errorTemplate = 'element/error';

  /**
   * @var string
   */
  public $labelTemplate = 'element/label';

  /**
   * @var string
   */
  public $elementTemplate = null;

  /**
   * @var View
   */
  public $view = null;

  /**
   * @var string
   */
  public $placeholder = null;

  /**
   * ElementAbstract constructor.
   *
   * @param string $name
   * @param array $options
   */
  public function __construct(string $name, array $options = [])
  {
    $this->setName($name);

    foreach ($options as $name => $value) {

      if (is_callable([$this, 'set' . ucfirst($name)])) {
        call_user_func_array([$this, 'set' . ucfirst($name)], [$value]);
      }
    }
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void
  {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getValue(): mixed
  {
    return $this->value;
  }

  /**
   * @param mixed $value
   */
  public function setValue(mixed $value): void
  {
    $this->value = $value;
  }

  /**
   * @param array $validator
   */
  public function addValidator(array $validator): void
  {
    $this->validators[] = $validator;
  }

  /**
   * @return bool
   */
  public function isReadOnly(): bool
  {
    return $this->readOnly;
  }

  /**
   * @param bool $readOnly
   */
  public function setReadOnly(bool $readOnly): void
  {
    $this->readOnly = $readOnly;
  }

  /**
   * @return bool
   */
  public function hasError(): bool
  {
    return (bool)count($this->getErrorMessages());
  }

  /**
   * @return string[]
   */
  public function getErrorMessages(): array
  {
    return $this->errorMessages;
  }

  /**
   * @param string[] $errorMessages
   */
  public function setErrorMessages(array $errorMessages): void
  {
    $this->errorMessages = $errorMessages;
  }

  /**
   * @return string
   */
  public function getElementType(): string
  {
    $template = explode('\\', get_called_class());
    return strtolower($template[count($template) - 1]);
  }

  /**
   * @param mixed $value
   * @return bool
   * @throws FilterClassWasNotFound
   * @throws ValidatorClassWasNotFound
   */
  public function isValid(mixed $value): bool
  {
    $this->errorMessages = [];

    foreach ($this->getValidators() as $validatorClassName => $settings) {

      if (is_int($validatorClassName)) {
        $validatorClassName = $settings;
      }

      try {
        if (!class_exists($validatorClassName)) {
          throw new ValidatorClassWasNotFound($validatorClassName);
        }
      } catch (Exception $exception) {

        if (!($exception instanceof ValidatorClassWasNotFound)) {

          if (isset($settings['isValid'])) {

            if (!$settings['isValid']($value)) {
              $this->errorMessages[] = $settings['message'] ?? '';
            }
            continue;
          }
        }

        throw $exception;
      }

      /** @var ValidatorAbstract $validator */
      $validator = new $validatorClassName($settings['options'] ?? []);

      $validator->setAllowNull($this->isAllowNull());

      if (!$validator->isValid($value)) {
        $this->errorMessages[] = $settings['message'] ?? '';
      }
    }

    $this->value = $value;

    if (empty($value) && !$this->isAllowNull()) {
      $this->errorMessages[] = 'empty-value-is-not-allowed';
    }

    if (!count($this->errorMessages)) {

      foreach ($this->getFilters() as $filterClassName => $settings) {

        if (is_numeric($filterClassName)) {
          $filterClassName = $settings;
        }

        try {
          /** @var FilterAbstract $filter */
          $filter = new $filterClassName($settings['options'] ?? []);
        } catch (Exception $exception) {
          try {
            $filter = $filterClassName($value);
          } catch (Exception $exception) {
            throw new FilterClassWasNotFound($filterClassName);
          }
        }

        $this->value = $filter->filter($this->value);
      }
      return true;
    }
    return false;
  }

  /**
   * @return ValidatorAbstract[]
   */
  public function getValidators(): array
  {
    return $this->validators;
  }

  /**
   * @param ValidatorAbstract[] $validators
   */
  public function setValidators(array $validators): void
  {
    $this->validators = $validators;
  }

  /**
   * @return bool
   */
  public function isAllowNull(): bool
  {
    return $this->allowNull;
  }

  /**
   * @param bool $allowNull
   */
  public function setAllowNull(bool $allowNull): void
  {
    $this->allowNull = $allowNull;
  }

  /**
   * @return FilterAbstract[]
   */
  public function getFilters(): array
  {
    return $this->filters;
  }

  /**
   * @param FilterAbstract[] $filters
   */
  public function setFilters(array $filters): void
  {
    $this->filters = $filters;
  }

  public function init()
  {
  }
}
