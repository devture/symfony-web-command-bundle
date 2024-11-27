<?php
namespace Devture\Bundle\WebCommandBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class DevtureWebCommandExtension extends Extension {

	public function load(array $configs, ContainerBuilder $container): void {
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		foreach ($config as $key => $value) {
			$container->setParameter(sprintf('devture_web_command_config.%s', $key), $value);
		}

		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.yaml');
	}

}
