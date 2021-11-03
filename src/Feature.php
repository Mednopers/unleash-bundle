<?php

namespace Stogon\UnleashBundle;

class Feature implements FeatureInterface
{
	private $name;
	private $description;
	private $enabled = false;
	protected $stategies = [];

	public function __construct(string $name, string $description, bool $enabled, array $strategies = [])
	{
		$this->name = $name;
		$this->description = $description;
		$this->enabled = $enabled;
		$this->stategies = $strategies;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function isDisabled(): bool
	{
		return !$this->enabled;
	}

	public function getStrategies(): array
	{
		return $this->stategies;
	}
}
