<?php

declare(strict_types=1);

namespace Light\Core;

use Exception;
use Error;

class ErrorController extends Controller
{
  /**
   * @var Exception|Error
   */
  private $exception = null;

  /**
   * @var bool
   */
  private $exceptionEnabled = true;

  /**
   * @return bool
   */
  public function isExceptionEnabled(): bool
  {
    return $this->exceptionEnabled;
  }

  /**
   * @param bool $exceptionEnabled
   */
  public function setExceptionEnabled(bool $exceptionEnabled): void
  {
    $this->exceptionEnabled = $exceptionEnabled;
  }

  /**
   * Setup status code and message
   */
  public function init()
  {
    parent::init();

    $this->getResponse()->setStatusCode(
      $this->getException()->getCode()
    );

    $this->getResponse()->setStatusMessage(
      $this->getException()->getMessage()
    );
  }

  /**
   * @return \Exception|\Error
   */
  public function getException()
  {
    return $this->exception;
  }

  /**
   * @param \Exception|\Error $exception
   */
  public function setException($exception)
  {
    $this->exception = $exception;
  }
}
