<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Stogon\UnleashBundle\HttpClient\UnleashHttpClient;
use Stogon\UnleashBundle\Repository\FeatureRepository;
use Stogon\UnleashBundle\Strategy\DefaultStrategy;
use Stogon\UnleashBundle\Strategy\FlexibleRolloutStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutRandomStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutSessionIdStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutUserIdStrategy;
use Stogon\UnleashBundle\Strategy\StrategyInterface;
use Stogon\UnleashBundle\Strategy\UserWithIdStrategy;
use Stogon\UnleashBundle\Twig\UnleashExtension;
use Stogon\UnleashBundle\Unleash;
use Stogon\UnleashBundle\UnleashInterface;

return function (ContainerConfigurator $configurator) {
	$services = $configurator->services();

	$services->instanceof(StrategyInterface::class)->tag('unleash.strategy');

	$services->set(UnleashHttpClient::class)
		->arg('$apiUrl', '%unleash.api_url%')
		->arg('$instanceId', '%unleash.instance_id%')
		->arg('$environment', '%unleash.environment%')
		->autowire(true)
	;

	// Strategies definitions
	$services->set(DefaultStrategy::class)->tag('unleash.strategy');
	$services->set(UserWithIdStrategy::class)->tag('unleash.strategy');
	$services->set(FlexibleRolloutStrategy::class)->tag('unleash.strategy');
	$services->set(GradualRolloutUserIdStrategy::class)->tag('unleash.strategy');
	$services->set(GradualRolloutSessionIdStrategy::class)->tag('unleash.strategy');
	$services->set(GradualRolloutRandomStrategy::class)->tag('unleash.strategy');

	$services->set(FeatureRepository::class)
		->arg('$httpClient', ref(UnleashHttpClient::class))
		->arg('$cache', ref('%unleash.cache.service%'))
		->arg('$ttl', '%unleash.cache.ttl%')
		->autowire(true)
		->autoconfigure(true)
	;

	$services->set(Unleash::class)
		->arg('$strategiesMapping', tagged('unleash.strategy'))
		->autowire(true)
	;

	$services->alias(UnleashInterface::class, Unleash::class);

	$services->set(UnleashExtension::class)
		->arg('$unleash', ref(UnleashInterface::class))
		->tag('twig.extension')
	;
};
