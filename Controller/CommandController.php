<?php
namespace Devture\Bundle\WebCommandBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class CommandController extends AbstractController {

	public function __construct(private string $authToken) {
	}

	#[Route('/execute/{name}', name: 'devture_web_command.command.execute', methods: ['POST'])]
	public function execute(
		Request $request,
		string $name,
		KernelInterface $kernel
	): Response {
		$authorization = (string) $request->headers->get('Authorization', '');
		if (strpos($authorization, 'Bearer ') !== 0) {
			return new Response('Bad Authorization token', 401);
		}

		$credential = substr($authorization, 7);
		if ($credential === '') {
			return new Response('Missing credential', 401);
		}
		if (!hash_equals($this->authToken, $credential)) {
			return new Response('Invalid credential', 403);
		}

		$postBody = file_get_contents('php://input');

		$payload = [];
		if ($postBody !== '') {
			$payload = @json_decode($postBody, true);

			if ($payload === null) {
				return new Response('Bad payload. Non-decodable JSON.', 400);
			}
		}

		$commandInput = (array_key_exists('input', $payload) ? (array) $payload['input']: []);

		// Ordering of the array elements is important.
		// Whichever element ends up being the first in the list is the one that is the command name.
		$input = new ArrayInput(array_merge([
			'command' => $name,
		], $commandInput));

		$outputVerbosity = (array_key_exists('outputVerbosity', $payload) ? (int) $payload['outputVerbosity']: OutputInterface::VERBOSITY_NORMAL);

		$application = new Application($kernel);
		$application->setAutoExit(false);
		// We don't wish for exceptions/errors to be handled there and print a stacktrace without erroring out.
		// See: https://github.com/devture/symfony-web-command-bundle/issues/2
		// We'd like these to propagate here, so Symfony can handle them like any other exception/error.
		$application->setCatchExceptions(false);
		$application->setCatchErrors(false);

		$output = new BufferedOutput($outputVerbosity);
		$application->run($input, $output);

		$content = $output->fetch();

		return new Response($content);
	}

}
