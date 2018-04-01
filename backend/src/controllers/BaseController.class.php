<?php

abstract class BaseController implements Controller {

    protected $actionName = 'default';
    /** @var HttpRequest */
    protected $request;
	/** @var ModelAndView */
	protected $mav;
	/** @var User */
	protected $user;

	private $decorators = [];

    protected function getRequest() {
        return $this->request;
    }

	public  function __construct() {
		$this->mav = ModelAndView::create();
	}

	/**
	 * @return UserSession|null
	 */
	protected function getSession() {
		if ($this->getRequest()->hasAttachedVar('session')) {
			return $this->getRequest()->getAttachedVar('session');
		}
		return null;
	}

	/**
     * @return User|null
     */
    protected function getUser() {
		if (!$this->user) {
			$session = $this->getSession();
			if ($session instanceof UserSession && $session->getUserId()) {
				$this->user = User::dao()->getById($session->getUserId());
			}
		}
		return $this->user;
    }

    /**
     * MyBitchesController -> myBitches
     * @return string
     */
	public function getTemplatePath() {
        return str_replace('Controller', '', lcfirst(get_class($this)));
    }

	public function getLayoutPath() {
		return 'layouts/main';
	}

	public function registerDecorator($name, callable $hook) {
	    $this->decorators[$name] = $hook;
    }

    // removed for arguments independency
    //abstract function defaultAction();

    /**
     * @param HttpRequest $request
     * @return Closure
     * @throws BadRequestException
     * @throws ObjectNotFoundException
     */
    public function makeCallable(HttpRequest $request)
    {
        if ($request->hasAttachedVar('action')) {
            $this->actionName = $request->getAttachedVar('action');
        }

        $actionMethodName = $this->actionName . 'Action';
        if (!method_exists($this, $actionMethodName)) {
            throw new BadRequestException('no action: ' . $this->actionName);
        }

        $reflection = new ReflectionMethod($this, $actionMethodName);

        $actionMethodArgs = array();
        foreach ($reflection->getParameters() as $parameter) {
            $value = $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : null;

            if ($this->request->hasGetVar($parameter->getName())) {
                $value = $this->request->getGetVar($parameter->getName());
            }
            $className = $parameter->getClass() ? $parameter->getClass()->getName() : null;
            if ($className !== null && $value !== null) {
                if (is_subclass_of($className, DAOConnected::class)) {
                    $dao = call_user_func(array($className, 'dao'));
                    try {
                        if ($dao instanceof IdentifiableByRequestDAO) {
                            $value = $dao->getByRequestedValue($value);
                        } elseif ($dao instanceof GenericDAO) {
                            $value = $dao->getById(intval($value));
                        }
                    } catch (ObjectNotFoundException $e) {
                        if ($parameter->isOptional()) {
                            $value = null;
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    throw new BadRequestException('cant import: ' . $className);
                }
            }
            $actionMethodArgs[$parameter->getName()] = $value;
        }

        $closure = function () use ($actionMethodName, $actionMethodArgs) {
            return call_user_func_array(array($this, $actionMethodName), $actionMethodArgs);
        };

        $docAttributesCount = preg_match_all('/@\w+/', $reflection->getDocComment(), $docAttributes);
        for ($i = 0; $i < $docAttributesCount; $i++) {
            if (isset($this->decorators[$docAttributes[0][$i]])) {
                $hook = $this->decorators[$docAttributes[0][$i]];
                $closure = function () use ($closure, $hook) {
                      return $hook($closure);
                };
            }
        }

        $this->assertRights();

        return $closure;
    }

    public function handleRequest(HttpRequest $request) {
        $this->request = $request;

        $requestCall = $this->makeCallable($request);

        $result = $requestCall();

        if (is_array($result)) {
            foreach ($result as $key => $data) {
                $this->mav->getModel()->set($key, $data);
            }
			if (!$this->mav->getView()) {
				$this->mav->setView($this->getTemplatePath() . '/' . $this->actionName);
			}
        } else if ($result instanceof GenericUri || $result instanceof a) {
            return $this->mav->setView(RedirectView::create($result->toString()));

        } else {
            $this->mav = $result;
        }

        return $this->mav;
    }

	public function isPostRequest() {
		return $this->request->getMethod()->getId() == HttpMethod::POST;
	}

	public function isDeleteRequest() {
		return $this->request->getMethod()->getId() == HttpMethod::DELETE;
	}

    public function assertRights() {}
} 