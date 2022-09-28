<?php

declare(strict_types=1);

namespace Light\Core;

use Light\Core\Exception\RequestMethodIsNotSupported;
use Light\Core\File;

class Request
{
  /**
   * Supported request method
   */
  const METHOD_GET = 'get';
  const METHOD_POST = 'post';
  const METHOD_PUT = 'put';
  const METHOD_DELETE = 'delete';

  /**
   * @var array
   */
  private array $headers = [];

  /**
   * @var string
   */
  private string $method;

  /**
   * @var string|null
   */
  private ?string $hierPart;

  /**
   * @var string|null
   */
  private ?string $query;

  /**
   * @var File[]
   */
  private array $files = [];

  /**
   * @var array
   */
  private array $body = [];

  /**
   * @var array
   */
  private array $getParams = [];

  /**
   * @var array
   */
  private array $uriParams = [];

  /**
   * @var string
   */
  private $domain = null;

  /**
   * @var string
   */
  private $scheme = null;

  /**
   * @var int
   */
  private $port = null;

  /**
   * @var string
   */
  private $ip = null;

  /**
   * @var bool
   */
  private $isAjax = false;

  /**
   * @return bool
   */
  public function isAjax(): bool
  {
    return $this->isAjax;
  }

  /**
   * @param bool $isAjax
   */
  public function setIsAjax(bool $isAjax): void
  {
    $this->isAjax = $isAjax;
  }

  /**
   * @return void
   */
  public function fillRequestFromServer()
  {
    foreach (getallheaders() as $key => $value) {
      $this->headers[strtolower($key)] = $value;
    }

    $this->method = strtolower($_SERVER['REQUEST_METHOD']);
    $this->uri = urldecode($_SERVER['REQUEST_URI']);
    $this->domain = $_SERVER['SERVER_NAME'];
    $this->scheme = isset($_SERVER['HTTP_X_SCHEME']) ? $_SERVER['HTTP_X_SCHEME'] : ($_SERVER['REQUEST_SCHEME'] ?? null);
    $this->port = (int)$_SERVER['SERVER_PORT'];
    $this->ip = $_SERVER['REMOTE_ADDR'];

    $this->hierPart = explode('?', $_SERVER['REQUEST_URI'])[0];
    $this->query = explode('?', $_SERVER['REQUEST_URI'])[1] ?? '';

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $this->isAjax = true;
    }

    $this->getParams = array_filter($_GET);

    if ($this->isPost()) {
      $this->body = array_filter($_POST);

    } else if ($this->isPut()) {
      $this->body = $this->raw2array();
    }

    foreach ($_FILES as $key => $file) {
      if (is_array($file['tmp_name'])) {
        $this->files[$key] = array_map(function ($name, $type, $tmpName, $error, $size) {
          return new File([
            'name' => $name,
            'type' => $type,
            'tmpName' => $tmpName,
            'error' => $error,
            'size' => $size
          ]);
        }, $_FILES[$key]['name'],
          $_FILES[$key]['type'],
          $_FILES[$key]['tmp_name'],
          $_FILES[$key]['error'],
          $_FILES[$key]['size']
        );
      } else {
        $this->files[$key] = new File([
          'name' => $file['name'],
          'type' => $file['type'],
          'tmpName' => $file['tmp_name'],
          'error' => $file['error'],
          'size' => $file['size']
        ]);
      }
    }
  }

  /**
   * @return void
   */
  public function fillRequestFromCli()
  {
    global $argv;

    $route = null;

    foreach ($argv as $arg) {

      $e = explode('=', $arg);

      if ($e[0] == 'route') {
        $route = $e[1];
        continue;
      }

      if (count($e) == 2) {
        $this->getParams[$e[0]] = $e[1];
      }
    }

    $this->uri = $route;
    $this->domain = 'cli';
  }

  /**
   * @param string $key
   * @param mixed|null $default
   * @return mixed
   */
  public function getGet(string $key, mixed $default = null): mixed
  {
    return $this->getParams[$key] ?? $default;
  }

  /**
   * @return array
   */
  public function getGetAll(): array
  {
    return $this->getParams;
  }

  /**
   * @param string $key
   * @param mixed|null $default
   * @return mixed
   */
  public function getBodyVar(string $key, mixed $default = null): mixed
  {
    return $this->body[$key] ?? $default;
  }

  /**
   * @return array
   */
  public function getBody(): array
  {
    return $this->body;
  }

  /**
   * @return array
   */
  public function getHeaders(): array
  {
    return $this->headers;
  }

  /**
   * @param string $key
   * @return string
   */
  public function getHeader(string $key)
  {
    return $this->headers[$key] ?? null;
  }

  /**
   * @param string $key
   * @param string $value
   */
  public function setHeader(string $key, string $value)
  {
    $this->headers[$key] = $value;
  }

  /**
   * @return bool
   */
  public function isGet(): bool
  {
    return $this->method == self::METHOD_GET;
  }

  /**
   * @return bool
   */
  public function isPost(): bool
  {
    return $this->method == self::METHOD_POST;
  }

  /**
   * @return bool
   */
  public function isPut(): bool
  {
    return $this->method == self::METHOD_PUT;
  }

  /**
   * @return bool
   */
  public function isDelete(): bool
  {
    return $this->method == self::METHOD_DELETE;
  }

  /**
   * @return string
   */
  public function getMethod()
  {
    return $this->method;
  }

  /**
   * @param string $method
   * @throws Exception\RequestMethodIsNotSupported
   */
  public function setMethod(string $method)
  {
    if (!in_array($method, [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE])) {
      throw new RequestMethodIsNotSupported($method);
    }

    $this->method = $method;
  }

  /**
   * @return string
   */
  public function getUri(): string
  {
    return $this->uri;
  }

  /**
   * @param string $uri
   */
  public function setUri(string $uri)
  {
    $this->uri = $uri;
  }

  /**
   * @return string
   */
  public function getDomain(): string
  {
    return $this->domain;
  }

  /**
   * @param string $domain
   */
  public function setDomain(string $domain)
  {
    $this->domain = $domain;
  }

  /**
   * @return string
   */
  public function getScheme(): string
  {
    return $this->scheme;
  }

  /**
   * @param string $scheme
   */
  public function setScheme(string $scheme)
  {
    $this->scheme = $scheme;
  }

  /**
   * @return int
   */
  public function getPort(): int
  {
    return $this->port;
  }

  /**
   * @param int $port
   */
  public function setPort(int $port)
  {
    $this->port = $port;
  }

  /**
   * @return string
   */
  public function getIp(): string
  {
    return $this->ip;
  }

  /**
   * @param string $ip
   */
  public function setIp(string $ip)
  {
    $this->ip = $ip;
  }

  /**
   * @param string $key
   * @param $value
   * @param bool $replace
   */
  public function setGetParam(string $key, $value, bool $replace = false)
  {
    if ($replace || !isset($this->getParams[$key])) {
      $this->getParams[$key] = $value;
      return;
    }
  }

  /**
   * @return array
   */
  public function getUriParams(): array
  {
    return $this->uriParams;
  }

  /**
   * @param string $key
   * @param mixed|null $default
   * @return mixed
   */
  public function getUriParam(string $key, mixed $default = null): mixed
  {
    return $this->uriParams[$key] ?? $default;
  }

  /**
   * @param string $uriParams
   */
  public function setUriParams(array $uriParams): void
  {
    $this->uriParams = $uriParams;
  }

  /**
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public function setUriParam(string $key, mixed $value): void
  {
    $this->uriParams[$key] = $value;
  }

  /**
   * @return null
   */
  public function getUriRequest()
  {
    return $this->uriRequest;
  }

  /**
   * @param null $uriRequest
   */
  public function setUriRequest($uriRequest): void
  {
    $this->uriRequest = $uriRequest;
  }

  /**
   * @param string $fileKey
   * @return File|null
   */
  public function getFile(string $fileKey)
  {
    return $this->files[$fileKey] ?? null;
  }

  /**
   * @param string $fileKey
   * @return File[]|array
   */
  public function getMultipleFile(string $fileKey): array
  {
    return $this->files[$fileKey] ?? [];
  }

  /**
   * @return File[]
   */
  public function getFiles(): array
  {
    return $this->files ?? [];
  }

  /**
   * @return array
   */
  private function raw2array(): array
  {
    $aData = [];
    $input = file_get_contents('php://input');
    preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
    $boundary = $matches[1];
    $aBlocks = preg_split("/-+$boundary/", $input);
    array_pop($aBlocks);

    foreach ($aBlocks as $id => $block) {
      if (empty($block)) {
        continue;
      }
      if (strpos($block, 'application/octet-stream') !== FALSE) {
        // match "name", then everything after "stream" (optional) except for prepending newlines
        preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);

      } // parse all other fields
      else {
        // match "name" and optional value in between newline sequences
        preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
      }
      $aData[$matches[1]] = $matches[2];
    }

    $aData = http_build_query($aData);
    $aData = urldecode($aData);
    parse_str($aData, $aData);

    return $aData;
  }
}
