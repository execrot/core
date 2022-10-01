<?php

namespace Light\Core;

class Plugin
{
  /**
   * @param Request $request
   * @param Response $response
   * @param Router $router
   */
  public function preRun(Request $request, Response $response, Router $router): void
  {
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param Router $router
   */
  public function postRun(Request $request, Response $response, Router $router): void
  {
  }
}