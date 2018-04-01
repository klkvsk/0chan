<?php

class App extends Singleton {

	/** @var HttpRequest */
    protected $request;

	/** @var BaseController */
	protected $controller;

	/** @var string */
	protected $controllerPath;

	/** @var UserSession */
	protected $session;

    /**
     * @return App
     */
    public static function me() {
        return Singleton::getInstance(__CLASS__);
    }

    /**
     * @param HttpRequest $request
     * @throws UnexpectedValueException
     * @return BaseHttpResponse
     */
    public function run(HttpRequest $request) {
        $this->request = $request;


		if ($request->getMethod()->getId() == HttpMethod::POST) {
			if (!$request->hasServerVar('CONTENT_TYPE')) {
				throw new BadRequestException;
			}
			if (strpos($request->getServerVar('CONTENT_TYPE'), 'application/json') !== false) {
				$jsonPost = file_get_contents("php://input");
				if ($jsonPost) {
					$jsonPostData = json_decode($jsonPost, true);
					if (is_array($jsonPostData)) {
						foreach ($jsonPostData as $paramName => $paramValue) {
							$request->setPostVar($paramName, $paramValue);
						}
					}
				}
			}
		} else if ($request->getMethod()->getId() == HttpMethod::OPTIONS) {
            return OkHttpResponse::create('');
        }

        $this->controllerPath = $this->route();
		$controllerClassName = ucfirst($this->controllerPath) . 'Controller';
        $controllerClassName = preg_replace_callback('/\/([a-z])/', function ($m) {
			return strtoupper($m[1]);
		}, $controllerClassName);

        /** @var BaseController $controller */
        $this->controller = new $controllerClassName;
        Assert::isInstance($this->controller, 'BaseController');

		$this->session = UserSession::detect($request);
        if (!$this->session) {
            $this->session = UserSession::start($request, null);
        }
		$request->setAttachedVar('session', $this->session);

        $mav = $this->controller->handleRequest($request);

        if ($mav instanceof ModelAndView) {
            $view = $mav->getView();
			$model = $mav->getModel();
            if ($view instanceof CleanRedirectView) {
                $response = RedirectHttpResponse::create($view->getUrl());
            } else if (is_string($view)) {
				// extend model
				$model->set('controller', $this->controller);
				$model->set('controllerPath', $this->controllerPath);

				$view = $this->getViewResolver()->resolveViewName($view);
                $body = $view->toString($model);
				if ($this->controller->getLayoutPath()) {
					$header = $this->getViewResolver()->resolveViewName($this->controller->getLayoutPath() . '/header')->toString($model);
					$footer = $this->getViewResolver()->resolveViewName($this->controller->getLayoutPath() . '/footer')->toString($model);
				} else {
					$header = $footer = '';
				}
				$response = OkHttpResponse::create($header . $body . $footer);
            } else if ($view instanceof ApiView) {
				$response = ApiHttpResponse::create($view->toString($model), $view->getHeaders(), $view->getHttpCode());

            } else if ($view instanceof Stringable) {
				$response = OkHttpResponse::create($view->toString());

			} else {
                throw new UnexpectedValueException('your view is shit');
            }
        } else if ($mav instanceof BaseHttpResponse) {
            $response = $mav;
        } else {
            $response = OkHttpResponse::create((string)$mav);
        }

        return $response;
    }

    protected function route() {
        RouterRewrite::me()
            ->addRoutes(
				array(
					'default' =>
						RouterTransparentRule::create('api/:controller/:action/*')
							->setDefaults(array(
								'controllerPath' => 'api',
								'controller' => 'board',
								'action' => 'default'
							)),
				)
            )
            ->route($this->request);

        if (!$this->request->hasAttachedVar('controller')) {
            throw new RouterException('cannot find controller');
        }

		$controllerName = $this->request->getAttachedVar('controller');
		if ($this->request->hasAttachedVar('controllerPath')) {
			$controllerName = $this->request->getAttachedVar('controllerPath') . '/' . $controllerName;
		}

        return $controllerName;
    }

    protected function getViewResolver() {
        return MultiPrefixPhpViewResolver::create()
            ->setViewClassName('SimplePhpView')
            ->addPrefix(PATH_TEMPLATES)
            ->setPostfix('.php');
    }

    public function getRequest() {
        return $this->request;
    }

	public function getController() {
		return $this->controller;
	}

	public function getControllerPath() {
		return $this->controllerPath;
	}

	public function getSession() {
		return $this->session;
	}

	public function getUser() {
		return $this->getSession() ? $this->getSession()->getUser() : null;
	}

    public function getRequestHost()
    {
        return $this->getRequest()->getServerVar('HTTP_HOST');
	}
} 