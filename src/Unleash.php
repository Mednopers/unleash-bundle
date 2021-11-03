<?php

namespace Stogon\UnleashBundle;

use Stogon\UnleashBundle\Event\UnleashContextEvent;
use Stogon\UnleashBundle\Repository\FeatureRepository;
use Stogon\UnleashBundle\Strategy\DefaultStrategy;
use Stogon\UnleashBundle\Strategy\FlexibleRolloutStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutRandomStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutSessionIdStrategy;
use Stogon\UnleashBundle\Strategy\GradualRolloutUserIdStrategy;
use Stogon\UnleashBundle\Strategy\StrategyInterface;
use Stogon\UnleashBundle\Strategy\UserWithIdStrategy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Unleash implements UnleashInterface
{
	protected $requestStack;
	protected $tokenStorage;
	protected $eventDispatcher;
	protected $featureRepository;
	/** @var iterable<StrategyInterface> */
	protected $strategiesMapping;

	public function __construct(
		RequestStack $requestStack,
		TokenStorageInterface $tokenStorage,
		EventDispatcherInterface $eventDispatcher,
		FeatureRepository $featureRepository,
		iterable $strategiesMapping
	) {
		$this->requestStack = $requestStack;
		$this->tokenStorage = $tokenStorage;
		$this->eventDispatcher = $eventDispatcher;
		$this->featureRepository = $featureRepository;
		$this->strategiesMapping = $strategiesMapping;
	}

	public function getFeatures(): array
	{
		return $this->featureRepository->getFeatures();
	}

	public function getFeature(string $name): ?FeatureInterface
	{
		return $this->featureRepository->getFeature($name);
	}

	public function isFeatureEnabled(string $name, bool $defaultValue = false): bool
	{
		$feature = $this->featureRepository->getFeature($name);

		if ($feature === null || $feature->isDisabled()) {
			return false;
		}

		$strategies = $this->convertStrategiesMappingToArray($this->strategiesMapping);
		$token = $this->tokenStorage->getToken();
		$user = null;

		if ($token !== null && $token->isAuthenticated()) {
			$user = $token->getUser();
		}

		$event = new UnleashContextEvent([
			'request' => $this->requestStack->getMasterRequest(),
			'user' => $user,
		]);

		$this->eventDispatcher->dispatch(UnleashContextEvent::class, $event);

		$context = $event->getPayload();

		foreach ($feature->getStrategies() as $strategyData) {
			$strategyName = $strategyData['name'];

			if (!array_key_exists($strategyName, $strategies)) {
				return false;
			}

			$strategy = $strategies[$strategyName];

			if (!$strategy instanceof StrategyInterface) {
				throw new \Exception(sprintf('%s does not implement %s interface.', $strategyName, StrategyInterface::class));
			}

			if ($strategy->isEnabled($strategyData['parameters'] ?? [], $context)) {
				return true;
			}
		}

		return $defaultValue;
	}

	public function isFeatureDisabled(string $name, bool $defaultValue = true): bool
	{
		return !$this->isFeatureEnabled($name, $defaultValue);
	}

	private function convertStrategiesMappingToArray(iterable $strategiesMapping): array
	{
		$strategies = [];

		foreach (iterator_to_array($strategiesMapping) as $strategy) {
			if ($strategy instanceof DefaultStrategy) {
				$strategies['default'] = $strategy;
				$strategies[DefaultStrategy::class] = $strategy;
			} elseif ($strategy instanceof UserWithIdStrategy) {
				$strategies['userWithId'] = $strategy;
				$strategies[UserWithIdStrategy::class] = $strategy;
			} elseif ($strategy instanceof FlexibleRolloutStrategy) {
				$strategies['flexibleRollout'] = $strategy;
				$strategies[FlexibleRolloutStrategy::class] = $strategy;
			} elseif ($strategy instanceof GradualRolloutUserIdStrategy) {
				$strategies['gradualRolloutUserId'] = $strategy;
				$strategies[GradualRolloutUserIdStrategy::class] = $strategy;
			} elseif ($strategy instanceof GradualRolloutSessionIdStrategy) {
				$strategies['gradualRolloutSessionId'] = $strategy;
				$strategies[GradualRolloutSessionIdStrategy::class] = $strategy;
			} elseif ($strategy instanceof GradualRolloutRandomStrategy) {
				$strategies['gradualRolloutSessionId'] = $strategy;
				$strategies[GradualRolloutRandomStrategy::class] = $strategy;
			}
		}

		return $strategies;
	}
}
