<?php
// Run with: php tests/ai_agent_test.php

error_reporting(E_ALL & ~E_DEPRECATED);
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'lib/autoload.php';
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register([\Frootbox\Front::class, 'autoload']);

use Frootbox\AI\AgentInterface;
use Frootbox\AI\Image;
use Frootbox\AI\UnavailableAgent;
use Frootbox\Config\Config;

function checkAgent(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class TestImageAgent implements AgentInterface
{
    public function __construct(private Config $config) {}
    public function getName(): string { return $this->config->get('test.name'); }
    public function isConfigured(): bool { return true; }
    public function supportsImages(): bool { return true; }
    public function generate(string $prompt, array $images = []): string
    {
        return $prompt . ':' . count($images);
    }
}

function agentContainer(array $settings): \DI\Container
{
    $config = new Config();
    $config->append($settings);
    $builder = new \DI\ContainerBuilder();
    $builder->addDefinitions(CORE_DIR . 'appconfig.php');
    $builder->addDefinitions([Config::class => $config]);
    return $builder->build();
}

$container = agentContainer([]);
$agent = $container->get(AgentInterface::class);
checkAgent($agent instanceof UnavailableAgent, 'Missing provider must resolve to fallback.');
checkAgent(!$agent->isConfigured() && !$agent->supportsImages(), 'Fallback must not report availability.');
try {
    $agent->generate('Describe image');
    throw new LogicException('Fallback silently accepted generation.');
} catch (RuntimeException $exception) {
    checkAgent(str_contains($exception->getMessage(), 'kein KI-Anbieter'), 'Missing provider error expected.');
}

$container = agentContainer(['ai' => ['agent' => TestImageAgent::class], 'test' => ['name' => 'Test provider']]);
$image = new Image(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='), 'image/png');
$result = $container->call(function (AgentInterface $agent) use ($image) {
    checkAgent($agent->getName() === 'Test provider', 'Provider constructor dependencies were not injected.');
    return $agent->generate('Describe image', [$image]);
});
checkAgent($result === 'Describe image:1', 'Method injection and image input must work.');

foreach ([stdClass::class, 'MissingAgentClass', ['invalid']] as $invalid) {
    try {
        agentContainer(['ai' => ['agent' => $invalid]])->get(AgentInterface::class);
        throw new LogicException('Invalid provider accepted.');
    } catch (RuntimeException $exception) {
        checkAgent(str_contains($exception->getMessage(), 'AgentInterface'), 'Clear interface error expected.');
    }
}

foreach ([['', 'image/png'], ['data', 'text/plain']] as [$data, $mimeType]) {
    try {
        new Image($data, $mimeType);
        throw new LogicException('Invalid image input accepted.');
    } catch (InvalidArgumentException $exception) {
        // Expected.
    }
}

echo "AI agent tests passed.\n";
