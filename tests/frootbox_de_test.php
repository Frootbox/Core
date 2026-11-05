<?php

// Core/FrootboxDe: signatures, local addresses and the state file of the connection to frootbox.de.

$base = dirname(__DIR__) . '/cms/extensions/Core/FrootboxDe/classes/';
require $base . 'Hub/HubException.php';
require $base . 'Hub/StandardWebhooks.php';
require $base . 'Hub/HubAddress.php';
require $base . 'Hub/State.php';

use Frootbox\Ext\Core\FrootboxDe\Hub\HubAddress;
use Frootbox\Ext\Core\FrootboxDe\Hub\HubException;
use Frootbox\Ext\Core\FrootboxDe\Hub\StandardWebhooks;
use Frootbox\Ext\Core\FrootboxDe\Hub\State;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Signatures after Standard Webhooks "v1a"
$hub = StandardWebhooks::keyPair();
$other = StandardWebhooks::keyPair();
$body = '{"connection":"con_1234567890"}';
$headers = StandardWebhooks::headers($hub['secret'], 'msg_1', $body, 1_800_000_000);

check(preg_match(StandardWebhooks::PUBLIC_KEY_PATTERN, $hub['public']) === 1, 'A public key is whpk_ and 32 bytes.');
check(StandardWebhooks::verify([$other['public'], $hub['public']], $headers, $body, 1_800_000_100), 'Any of the keys of the hub verifies.');
check(!StandardWebhooks::verify([$other['public']], $headers, $body, 1_800_000_100), 'A foreign key does not.');
check(!StandardWebhooks::verify([$hub['public']], $headers, $body . ' ', 1_800_000_100), 'A changed body does not.');
check(!StandardWebhooks::verify([$hub['public']], $headers, $body, 1_800_000_301), 'Older than five minutes does not.');
check(StandardWebhooks::verify([$hub['public']], array_change_key_case(array_combine(array_map('strtoupper', array_keys($headers)), $headers)), $body, 1_800_000_000), 'Headers in any case verify.');

// The answer to verify: the site signs a text without dots, so it never passes as a webhook.
$signature = StandardWebhooks::signContent($hub['secret'], 'frootbox-connect-verify-v1:req_12345678:' . str_repeat('a', 20));
$raw = base64_decode(substr($signature, 4), true);
check(sodium_crypto_sign_verify_detached($raw, 'frootbox-connect-verify-v1:req_12345678:' . str_repeat('a', 20), base64_decode(substr($hub['public'], 5), true)), 'The verify answer is signed with the key of the site.');

// Local addresses
foreach ([
    'http://localhost:8000' => true,
    'http://127.0.0.1:8411' => true,
    'https://192.168.1.20' => true,
    'https://kunde.test' => true,
    'https://intranet' => true,
    'https://www.example.org' => false,
    'https://hub.frootbox.de' => false,
    'https://93.184.216.34' => false,
] as $url => $local) {
    check(HubAddress::isLocal($url) === $local, 'Unexpected for ' . $url);
}

// The state file: not readable in the browser, changed under a lock, forgotten on demand
$file = sys_get_temp_dir() . '/frootbox-de-test-' . bin2hex(random_bytes(4)) . '.php';
$state = new State($file);
check($state->read() === [], 'No file is no connection.');
$state->update(static fn (array $data): array => ['connectionId' => 'con_1', 'secretKey' => $hub['secret']] + $data);
$state->update(static fn (array $data): array => ['status' => true] + $data);
check($state->read() === ['status' => true, 'connectionId' => 'con_1', 'secretKey' => $hub['secret']], 'Changes add up.');
check(str_starts_with((string) file_get_contents($file), "<?php exit; ?>\n"), 'The file shows nothing in the browser.');
check((fileperms($file) & 0777) === 0600, 'Only the owner reads the file.');
$state->forget();
check(!is_file($file), 'Forget deletes the file.');

file_put_contents($file, 'garbage');
try {
    $state->read();
    check(false, 'A broken file is an error.');
}
catch (HubException) {
}
unlink($file);

echo "OK\n";
