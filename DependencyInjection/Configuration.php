<?php
namespace Devture\Bundle\WebCommandBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder('devture_web_command');

		$rootNode = $treeBuilder->getRootNode();

		$rootNode
			->children()
				->scalarNode('auth_token')->end()
			->end()
		;

		return $treeBuilder;
	}

}
