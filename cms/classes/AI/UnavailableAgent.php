<?php

namespace Frootbox\AI;

/** Keeps admin apps accessible before a provider extension is configured. */
final class UnavailableAgent implements AgentInterface
{
    public function getName(): string
    {
        return 'Kein KI-Anbieter eingerichtet';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function supportsImages(): bool
    {
        return false;
    }

    public function generate(string $prompt, array $images = []): string
    {
        throw new \RuntimeException('Es ist kein KI-Anbieter eingerichtet.');
    }
}
