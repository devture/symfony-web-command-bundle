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

	private $authToken;

	public function __construct(string $authToken) {
		$this->authToken = $authToken;
	}

	/**
	 * @Route("/execute/{name}", name="devture_web_command.command.execute", methods={"POST"})
	 */
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
		$input = new ArrayInput(array_merge($commandInput, [
			'command' => $name,
		]));

		$outputVerbosity = (array_key_exists('outputVerbosity', $payload) ? (int) $payload['outputVerbosity']: OutputInterface::VERBOSITY_NORMAL);

		$application = new Application($kernel);
		$application->setAutoExit(false);

		$output = new BufferedOutput($outputVerbosity);
		$application->run($input, $output);

		$content = $output->fetch();

		return new Response($content);
	}

}
