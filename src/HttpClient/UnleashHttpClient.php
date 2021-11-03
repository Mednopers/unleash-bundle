<?php

namespace Stogon\UnleashBundle\HttpClient;

use GuzzleHttp\ClientInterface;

class UnleashHttpClient
{
	private $httpClient;
	protected $apiUrl;
	protected $instanceId;
	protected $environment;

	public function __construct(ClientInterface $unleashClient, string $apiUrl, string $instanceId, string $environment)
	{
		$this->httpClient = $unleashClient;
		$this->apiUrl = $apiUrl;
		$this->instanceId = $instanceId;
		$this->environment = $environment;
	}

	public function fetchFeatures(): array
	{
		$response = $this->httpClient->request('GET', 'client/features');

		$jsonContents = $response->getBody()->getContents();
		$features = json_decode($jsonContents, true);

		if (array_key_exists('features', $features)) {
			return $features['features'];
		}

		return [];
	}
}
