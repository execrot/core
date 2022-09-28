<?php

declare(strict_types=1);

namespace Light\Core;

class Response
{
  /**
   * @var int
   */
  private $statusCode = 200;

  /**
   * @var string
   */
  private $statusMessage = '';

  /**
   * @var mixed
   */
  private $body = null;

  /**
   * @var array
   */
  private $headers = [];

  /**
   * @return int
   */
  public function getStatusCode(): int
  {
    return $this->statusCode;
  }

  /**
   * @param int $statusCode
   */
  public function setStatusCode(int $statusCode)
  {
    $this->statusCode = $statusCode;
  }

  /**
   * @return string
   */
  public function getStatusMessage(): string
  {
    return $this->statusMessage;
  }

  /**
   * @param string $statusMessage
   */
  public function setStatusMessage(string $statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }

  /**
   * @return mixed
   */
  public function getBody()
  {
    return $this->body;
  }

  /**
   * @param mixed $body
   */
  public function setBody($body)
  {
    $this->body = $body;
  }

  /**
   * @return array
   */
  public function getHeaders(): array
  {
    return $this->headers;
  }

  /**
   * @param array $headers
   */
  public function setHeaders(array $headers)
  {
    $this->headers = $headers;
  }

  /**
   * @param array $headers
   */
  public function addHeaders(array $headers)
  {
    foreach ($headers as $key => $val) {
      $this->headers[$key] = $val;
    }
  }

  /**
   * @param string $key
   * @param string $value
   */
  public function setHeader(string $key, string $value)
  {
    $this->headers[$key] = $value;
  }
}