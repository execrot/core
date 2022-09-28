<?php

declare(strict_types=1);

namespace Light\Core;

class Controller
{
  /**
   * @var Request
   */
  private $request = null;

  /**
   * @var Response
   */
  private $response = null;

  /**
   * @var Router
   */
  private $router = null;

  /**
   * @var View
   */
  private $view = null;

  /**
   * @return Response
   */
  public function getResponse()
  {
    return $this->response;
  }

  /**
   * @param Response $response
   */
  public function setResponse(Response $response)
  {
    $this->response = $response;
  }

  /**
   * @return Router
   */
  public function getRouter()
  {
    return $this->router;
  }

  /**
   * @param Router $router
   */
  public function setRouter(Router $router)
  {
    $this->router = $router;
  }

  /**
   * @return View
   */
  public function getView(): View
  {
    return $this->view;
  }

  /**
   * @param View $view
   */
  public function setView(View $view): void
  {
    $this->view = $view;
  }

  /**
   * @param string $uri
   */
  public function redirect(string $uri)
  {
    header('Location: ' . $uri, true);
    die();
  }

  /**
   * @param string $name
   * @param null $default
   *
   * @return array|int|mixed|string|null
   */
  public function getParam(string $name, $default = null)
  {
    return $this->getRequest()->getBodyVar($name, $default)
      ?? $this->getRequest()->getGet($name, $default)
      ?? $this->getRequest()->getUriParam($name, $default);
  }

  /**
   * @return Request
   */
  public function getRequest(): Request
  {
    return $this->request;
  }

  /**
   * @param Request $request
   */
  public function setRequest(Request $request)
  {
    $this->request = $request;
  }

  /**
   * @return array
   */
  public function getParams()
  {
    return array_merge(
      $this->getRequest()->getBody(),
      $this->getRequest()->getGetAll(),
      $this->getRequest()->getUriParams()
    );
  }

  public function init()
  {
  }

  public function postRun()
  {
  }
}
