<?php

abstract class ApiBaseController extends BaseController {

    protected $captchaAsserted = false;

    public function __construct()
    {
        parent::__construct();
        $this->registerDecorator('@Auth', function ($next) {
            if (!$this->getUser()) {
                throw new ApiForbiddenException();
            }
            return $next();
        });
        $this->registerDecorator('@Post', function ($next) {
            if (!$this->isPostRequest()) {
                throw new ApiBadRequestException();
            }
            return $next();
        });
    }

    public function assertCaptcha() {
        // already checked in this request
        if ($this->captchaAsserted) {
            return;
        }

		$captchaId = null;
		if ($this->getRequest()->hasGetVar('captcha')) {
			$captchaId = $this->getRequest()->getGetVar('captcha');
		} else if ($this->getRequest()->hasPostVar('captcha')) {
			$captchaId = $this->getRequest()->getPostVar('captcha');
		}

		if (!$captchaId || !CaptchaStorage::me()->useCaptcha($captchaId)) {
			throw new ApiCaptchaRequiredException;
		}

		$this->captchaAsserted = true;
	}

    public function limitWithCaptcha($event, $timespan, $max = 1)
    {
        $this->limit($event, $timespan, $max, function () {
            $this->assertCaptcha();
        });
        return $this;
    }

    public function limit($event, $timespan, $max = 1, callable $callbackOnReached)
    {
        $ipHash = RequestUtils::getRealIpHash($this->getRequest());
        if (empty($ipHash) && PRODUCTION) {
            // tor
            $callbackOnReached();
        } else {
            $limiter = new Limiter($ipHash, $event, $timespan);
            try {
                if ($limiter->isReached($max)) {
                    $callbackOnReached();
                }
            } finally {
                $limiter->increment();
            }
        }
        return $this;
    }

    public function makeCallable(HttpRequest $request)
    {
        try {
            return parent::makeCallable($request);
        } catch (ObjectNotFoundException $e) {
            throw new ApiNotFoundException();
        } catch (BadRequestException $e) {
            throw new ApiBadRequestException();
        }
	}

	public function handleRequest(HttpRequest $request) {
	    $apiView = ApiView::create();
		$this->mav->setView($apiView);
		try {

		    parent::handleRequest($request);

		} catch (ApiException $e) {
		    $apiView->setHttpCode($e->getCode());
			$this->mav->setModel(
				Model::create()
					->set('error', $e->getCode())
					->set('message', $e->getMessage())
					->set('details', $e->getDetails())
			);
		}

		return $this->mav;
	}

    /**
     * @param $value
     * @return bool
     */
    public function getBooleanParam($value) {
        if (is_bool($value)) {
            return $value;
        } else if (is_string($value)) {
            return ($value === '1' || $value === 'true' || $value === 'on');
        } else {
            return !empty($value);
        }
    }

    public function applyPagination(Criteria $criteria, $page = 1, $itemsPerPage = 30)
    {
        return $criteria
            ->setLimit($itemsPerPage)
            ->setOffset(($page - 1) * $itemsPerPage);
    }

} 