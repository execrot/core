<?php

namespace Light\ViewHelper;

use Light\Front;
use Light\ViewHelper;

/**
 * Class Asset
 * @package Light\ViewHelper
 */
class Asset extends ViewHelper
{
  /**
   * @var array
   */
  public $config = [
    'underscore' => false,
    'prefix' => '',
    'defer' => false,
    'async' => false
  ];

  /**
   * Asset constructor.
   * Config initialization
   */
  public function __construct()
  {
    $this->config = Front::getInstance()->getConfig()['light']['asset'] ?? $this->config;
  }

  /**
   * @param $assets
   * @return string
   */
  public function call($assets)
  {
    $assetsHtml = [];

    foreach ((array)$assets as $asset) {

      $assetParts = explode('.', explode('?', $asset)[0]);

      $assetsHtml[] = end($assetParts) == 'js' ? $this->js($asset) : $this->css($asset);
    }

    return implode("\n", $assetsHtml);
  }

  /**
   * @param string $uri
   * @return string
   */
  public function js(string $uri): string
  {
    $defer = $this->config['defer'] ?? false ? 'defer' : '';
    $async = $this->config['async'] ?? false ? 'async' : '';

    $settings = implode(' ', [$defer, $async]);

    return '<script src="' . $this->prepareUnderscore($uri) . '" ' . $settings . '></script>';
  }

  /**
   * @param string $uri
   * @return string
   */
  public function css(string $uri): string
  {
    $defer = $this->config['defer'] ?? false ? 'defer' : '';
    $async = $this->config['async'] ?? false ? 'async' : '';

    $settings = implode(' ', [$defer, $async]);

    return '<link href="' . $this->prepareUnderscore($uri) . '" ' . $settings . ' rel="stylesheet" />';
  }

  /**
   * @param string $uri
   * @return string
   */
  public function prepareUnderscore(string $uri): string
  {
    if ($this->config['underscore']) {

      if (str_contains($uri, '?')) {
        $uri = $uri . '&_=' . microtime();
      } else {
        $uri = $uri . '?_=' . microtime();
      }
    }

    if (!empty($this->config['prefix'])) {

      if (!str_starts_with($uri, '//') && !str_starts_with($uri, 'http://') && !str_starts_with($uri, 'https://')) {

        if (!str_starts_with($uri, '/')) {
          $uri = '/' . $uri;
        }

        $uri = $this->config['prefix'] . $uri;
      }
    }

    return $uri;
  }
}