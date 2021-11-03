<?php

namespace Stogon\UnleashBundle\Repository;

use Psr\SimpleCache\CacheInterface;
use Stogon\UnleashBundle\Feature;
use Stogon\UnleashBundle\HttpClient\UnleashHttpClient;

class FeatureRepository
{
	protected $client;
	protected $cache;
	protected $ttl;

	public function __construct(UnleashHttpClient $httpClient, CacheInterface $cache, int $ttl)
	{
		$this->client = $httpClient;
		$this->cache = $cache;
		$this->ttl = $ttl;
	}

	/**
	 * @return Feature[]
	 */
	public function getFeatures(): array
	{
		$features = [];
		if ($this->cache->has('unleash.strategies')) {
			$features = $this->cache->get('unleash.strategies', []);
		}

		if ([] === $features) {
			$features = $this->client->fetchFeatures();
			$this->cache->set('unleash.strategies', $features, $this->ttl);
		}

		return array_map(static function (array $feature) {
			return new Feature(
				$feature['name'],
				$feature['description'],
				$feature['enabled'],
				$feature['strategies']
			);
		}, $features);
	}

	public function getFeature(string $name): ?Feature
	{
		$features = $this->getFeatures();

		$filtered = array_filter($features, static function (Feature $f) use ($name) {
			return $f->getName() === $name;
		});

		if (!empty($filtered)) {
			return $filtered[$this->getFirstKeyFromArray($filtered)];
		}

		return null;
	}

	private function getFirstKeyFromArray(array $array): ?string
	{
		foreach ($array as $key => $unused) {
			return $key;
		}

		return null;
	}
}
