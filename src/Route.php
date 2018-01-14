<?php declare (strict_types = 1);

namespace OdbavTo\PresenterRoute;

use Nette;
use Nette\Application\IRouter;
use Nette\Application\Request;

class Route implements IRouter
{
	/**
	 * @var string
	 */
	private $route;

	/**
	 * @var string
	 */
	private $presenterClassName;

	/**
	 * @var array|null
	 */
	private $supportedHttpMethods;
	
	/**
	 * @var PathMatcherInterface
	 */
	private $pathMatcher;

	/** @var  bool */
	private $allowOnceAllHttpMethods = FALSE;


	public function __construct(string $route, string $presenterClassName ,
			array $supportedHttpMethods = null, PathMatcherInterface $pathMatcher = null)
	{
		$this->route = $route;
		$this->presenterClassName = $presenterClassName;
		$this->supportedHttpMethods = $supportedHttpMethods;
		$this->pathMatcher = $pathMatcher ?: PathMatcher::getInstance();
	}


	public function supportedHttpMethods(): ?array
	{
		return $this->supportedHttpMethods;
	}


	public function allowOnceAllHttpMethods()
	{
		$this->allowOnceAllHttpMethods = TRUE;
	}


	/**
	 * Maps HTTP request to a Request object.
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?Request
	{
		if (!$this->isHttpMethodSupported($httpRequest->getMethod())) {
			return NULL;
		}
		
		$path = $httpRequest->getUrl()->getPath();
		$matches = $this->pathMatcher->match($this->route, $path);
		
		if ($matches === null) {
			return null;
		}
		
		$params = array_merge($httpRequest->getQuery(), $matches);
		
		return new Request(
			$this->presenterClassName,
			$httpRequest->getMethod(),
			$params,
			$httpRequest->getPost(),
			$httpRequest->getFiles(),
			[Request::SECURED => $httpRequest->isSecured()]
		);
	}


	/**
	 * Constructs absolute URL from Request object.
	 */
	public function constructUrl(Request $appRequest, Nette\Http\Url $refUrl): ?string
	{
		$baseUrl = $refUrl->getHostUrl();
		
		$path = $this->pathMatcher->createUrl($this->route, $appRequest->getParameters());
		
		if ($path === null) {
			throw new RouteException('There was an error on constructing url with: ' . $this->route);
		}
		
		return $baseUrl . '/' . $path;
		
	}


	private function isHttpMethodSupported(string $httpMethod): bool
	{
		if ($this->areAllMethodsAllowed()) {
			return TRUE;
		}

		if (is_array($this->supportedHttpMethods)) {
			return in_array($httpMethod, $this->supportedHttpMethods, TRUE);
		}

		return TRUE;
	}


	private function areAllMethodsAllowed(): bool
	{
		if ($this->allowOnceAllHttpMethods) {
			// this is just one time permission, so disallow it again
			$this->allowOnceAllHttpMethods = FALSE;
			return TRUE;
		}

		return FALSE;
	}
}
