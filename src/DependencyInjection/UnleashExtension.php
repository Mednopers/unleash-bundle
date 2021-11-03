<?php

namespace Stogon\UnleashBundle\DependencyInjection;

use GuzzleHttp\ClientInterface;
use Stogon\UnleashBundle\Repository\FeatureRepository;
use Stogon\UnleashBundle\Strategy\StrategyInterface;
use Symfony\Component\Cache\Simple\Psr6Cache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class UnleashExtension extends Extension implements PrependExtensionInterface
{
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new PhpFileLoader(
			$container,
			new FileLocator(__DIR__.'/../../config/')
		);

		$loader->load('services.php');

		$container->registerForAutoconfiguration(StrategyInterface::class)->addTag('unleash.strategy');

		$definition = $container->getDefinition(FeatureRepository::class);
		$definition->replaceArgument('$cache', new Reference($container->getParameter('unleash.cache.service')));
	}

	public function prepend(ContainerBuilder $container)
	{
		$configuration = new Configuration();

		$config = $this->processConfiguration($configuration, $container->getExtensionConfig($this->getAlias()));

		$container->setParameter('unleash.api_url', $config['api_url']);
		$container->setParameter('unleash.auth_token', $config['auth_token']);
		$container->setParameter('unleash.instance_id', $config['instance_id']);
		$container->setParameter('unleash.environment', $config['environment']);
		$container->setParameter('unleash.cache.service', $config['cache']['service']);
		$container->setParameter('unleash.cache.ttl', $config['cache']['enabled'] ? $config['cache']['ttl'] : 0);

		$container->prependExtensionConfig('eight_points_guzzle', [
			'clients' => [
				'unleash_client' => [
					'base_url' => '%unleash.api_url%',
					'options' => [
						'headers' => [
							'Accept' => 'application/json',
							'Authorization' => '%unleash.auth_token%',
							'UNLEASH-APPNAME' => '%kernel.environment%',
							'UNLEASH-INSTANCEID' => '%unleash.instance_id%',
						],
					],
				],
			],
		]);

		$container->setAlias(ClientInterface::class, 'eight_points_guzzle.client.unleash_client');

		$cacheServiceId = 'cache.app';
		if ($config['cache']['enabled'] && $config['cache']['service'] === null) {
			$container->prependExtensionConfig('framework', [
				'cache' => [
					'pools' => [
						'cache.unleash.strategies' => null,
					],
				],
			]);

			$cacheServiceId = $config['cache']['service'] = 'cache.unleash.strategies';
		}

		$definition = new Definition(Psr6Cache::class, [
			new Reference($cacheServiceId),
		]);

		$container->setDefinition($cacheServiceId.'.simple', $definition);

		$container->setParameter('unleash.cache.service', $cacheServiceId.'.simple');
	}

	public function getAlias()
	{
		return 'unleash';
	}
}
