<?php

namespace Frootbox\AI;

/**
 * Provider-neutral text generation, optionally with image input.
 *
 * Provider extensions implement this contract and keep credentials, model names,
 * HTTP transport and response parsing inside their implementation. Select the
 * implementation's fully qualified class name through the config key ai.agent.
 * Consumers can then inject this interface through PHP-DI.
 */
interface AgentInterface
{
    public function getName(): string;

    /** Local configuration check only; must not make a remote API request. */
    public function isConfigured(): bool;

    /** Whether the selected model accepts images. */
    public function supportsImages(): bool;

    /**
     * Perform a single, stateless request and return plain generated text.
     *
     * @param Image[] $images Image bytes, never publicly accessible URLs.
     * @throws \RuntimeException On missing configuration, unsupported image input,
     *                           API failure or an empty/unusable response.
     *                           Error messages must not expose credentials.
     */
    public function generate(string $prompt, array $images = []): string;
}
