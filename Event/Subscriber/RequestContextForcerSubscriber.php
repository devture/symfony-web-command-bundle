<?php
namespace Devture\Bundle\WebCommandBundle\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

/**
 * Force-sets the request context Host parameter for web-command requests.
 *
 * Usually, `router.request_context.host` is obeyed automatically for console commands,
 * while for web requests, the context parameters are detected from the request headers (Host, X-Forwarded-Host, etc.).
 *
 * In certain environments (containers), the console command caller may be a local container
 * invoking us through a different hostname (e.g. `http://nginx`).
 * We'd like to do the right thing and override the detected host with the one provided.
 */
class RequestContextForcerSubscriber implements EventSubscriberInterface {

	/**
	 * @var string
	 */
	private $forcedUri;

	/**
	 * @var RequestContext
	 */
	private $requestContext;

	public function __construct(string $forcedUri, RequestContext $requestContext) {
		$this->forcedUri = $forcedUri;
		$this->requestContext = $requestContext;
	}

	public function onKernelRequest(GetResponseEvent $event) {
		if ($this->forcedUri === '') {
			return;
		}

		// We only wish to force-change the request context for requests leading
		// to our own routes.

		$requestAttributes = $event->getRequest()->attributes->all();

		if (!array_key_exists('_route', $requestAttributes)) {
			return;
		}

		if (strpos($requestAttributes['_route'], 'devture_web_command.') !== 0) {
			return;
		}

		$parts = parse_url($this->forcedUri);

		if (!array_key_exists('scheme', $parts) || !array_key_exists('host', $parts)) {
			throw new \RuntimeException(sprintf(
				'Cannot parse URI: %s',
				$this->forcedUri
			));
		}

		$this->requestContext->setScheme($parts['scheme']);
		$this->requestContext->setHost($parts['host']);

		if (array_key_exists('port', $parts)) {
			// Port is explicitly specified.
			// Let's force that port for the given scheme.
			if ($parts['scheme'] === 'http') {
				$this->requestContext->setHttpPort($parts['port']);
			} else if ($parts['scheme'] === 'https') {
				$this->requestContext->setHttpsPort($parts['port']);
			} else {
				throw new \RuntimeException(sprintf(
					'Unknown scheme %s in URI: %s',
					$parts['scheme'],
					$this->forcedUri
				));
			}
		}
	}

	static public function getSubscribedEvents() {
		return [
			// `Symfony\Component\HttpKernel\EventListener\RouterListener`,
			// automatically populates the request context from the request (`$this->context->fromRequest($request)`).
			//
			// We want to override this behavior, so we want to run after that listener.
			// That listener runs at priority 32, so to run after, we need to use a smaller number.
			KernelEvents::REQUEST => [['onKernelRequest', 31]],
		];
	}

}
