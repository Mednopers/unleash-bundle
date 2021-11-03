<?php

namespace Stogon\UnleashBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UnleashContextEvent extends Event
{
	private $payload;

	public function __construct(array $payload = [])
	{
		$this->payload = $payload;
	}

	public function getPayload(): array
	{
		return $this->payload;
	}

	public function setPayload(array $payload): self
	{
		$this->payload = $payload;

		return $this;
	}
}
