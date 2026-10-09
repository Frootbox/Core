<?php
// Run: php tests/stripe_order_reference_test.php [path/to/frootbox-customers]
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
spl_autoload_register(function ($class) {
    $prefix = 'Frootbox\\Ext\\Core\\ShopSystem\\';
    if (str_starts_with($class, $prefix)) {
        require CORE_DIR . 'cms/extensions/Core/ShopSystem/classes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

function checkReference(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class ReferenceCart extends \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart
{
    public float $total = 12.50;
    public function __construct() {}
    public function getTotal(): float { return $this->total; }
    public function getPersonal(string $attribute): ?string { return 'test'; }
}

class ReferenceCheckout extends \Frootbox\Persistence\AbstractPlugin
{
    public function getPath(): string { return __DIR__ . "/"; }
    public function getAjaxUri($action, array $payload = null, array $options = null): string
    {
        return 'https://example.test/checkout';
    }
}

class ReferenceContents extends \Frootbox\Persistence\Content\Repositories\ContentElements
{
    public function __construct() {}
    public function fetchOne(array $params = null, array $options = null): ?\Frootbox\Db\Row
    {
        return new ReferenceCheckout();
    }
}

// Use the actual Stripe SDK with a transport that cannot make network requests.
class ReferenceStripeTransport implements \Stripe\HttpClient\ClientInterface
{
    public array $intent = [];
    public array $requests = [];
    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        $path = parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = [$method, $path, $params];
        if ($path === '/v1/customers/search') {
            $result = ['object' => 'search_result', 'data' => [['object' => 'customer', 'id' => 'cus_test']], 'has_more' => false];
        } elseif ($path === '/v1/payment_methods/pm_test') {
            $result = ['object' => 'payment_method', 'id' => 'pm_test', 'type' => 'card'];
        } elseif ($path === '/v1/payment_intents' && $method === 'post') {
            $this->intent = array_merge([
                'object' => 'payment_intent', 'id' => 'pi_test', 'status' => 'requires_payment_method',
                'amount_received' => 0, 'client_secret' => 'test_secret', 'payment_method' => 'pm_test',
            ], $params);
            $result = $this->intent;
        } elseif ($path === '/v1/payment_intents/pi_test') {
            if ($method === 'post') {
                $this->intent = array_replace_recursive($this->intent, $params);
            }
            $result = $this->intent;
        } else {
            throw new RuntimeException('Unexpected Stripe request: ' . $path);
        }
        return [json_encode($result), 200, []];
    }
}

$_SESSION = [];
$cart = new ReferenceCart();
$reference = $cart->getUniqueId();
checkReference((bool) preg_match('/^[a-f0-9]{32}$/', $reference), 'Reference format');
checkReference($reference === $cart->getUniqueId(), 'Stable within request');
checkReference($reference === (new ReferenceCart())->getUniqueId(), 'Stable across requests');
$_SESSION['cart']['uniqueId'] = 'existing-reference';
checkReference((new ReferenceCart())->getUniqueId() === 'existing-reference', 'Preserve in-flight references');
$_SESSION = [];
checkReference((new ReferenceCart())->getUniqueId() !== $reference, 'New cart gets new reference');

$config = new \Frootbox\Config\Config();
$config->append(['Stripe' => ['Api' => ['Secret' => 'sk_test_fake', 'Key' => 'pk_test_fake']]]);
foreach (['Stripe', 'StripeCard', 'StripeApplepay', 'StripeKlarna', 'StripePaypal', 'StripeIdeal', 'StripeGiropay'] as $name) {
    $_SESSION = [];
    $cart = new ReferenceCart();
    $transport = new ReferenceStripeTransport();
    \Stripe\ApiRequestor::setHttpClient($transport);
    $class = 'Frootbox\\Ext\\Core\\ShopSystem\\PaymentMethods\\' . $name . '\\Method';
    $method = new $class();
    $method->onBeforeRenderInput($config, $cart, new ReferenceContents());
    $reference = $cart->getUniqueId();
    checkReference($transport->intent['metadata']['orderReference'] === $reference, "$name: metadata at creation");
    checkReference($_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'] === 'pi_test', "$name: intent persisted");

    // Simulate an intent from before deployment; repair metadata and preserve other keys.
    $transport->intent['metadata'] = ['unrelated' => 'keep'];
    $cart->total = 20;
    $method->onBeforeRenderInput($config, new ReferenceCart(), new ReferenceContents());
    checkReference($transport->intent['metadata']['orderReference'] === $reference, "$name: reused intent reference");
    checkReference($transport->intent['metadata']['unrelated'] === 'keep', "$name: unrelated metadata preserved");
    $method->onBeforeRenderInput($config, $cart, new ReferenceContents());
    checkReference((int) $transport->intent['amount'] === 2000, "$name: amount update");
    checkReference($transport->intent['metadata']['orderReference'] === $reference, "$name: stable after amount update");
    $transport->intent['amount_received'] = 2000;
    $cart->setOrderNumber('ORDER-123');
    $method->preCheckoutAction($config, $cart);
    checkReference($transport->intent['metadata']['orderReference'] === $reference, "$name: reference after checkout");
    checkReference($transport->intent['metadata']['orderNumber'] === 'ORDER-123', "$name: order number retained");
    echo "$name: OK\n";
}

// Optional cross-repository test for the actual Avaro transfer adapter.
if (isset($argv[1])) {
    require $argv[1] . '/Avaro2/Api/classes/Client.php';
    require $argv[1] . '/Avaro2/ShopIntegration/classes/Integrations/Booking.php';

    class ReferenceBooking extends \Frootbox\Ext\Core\ShopSystem\Persistence\Booking
    {
        public array $savedReferences = [];
        public function save(?array $options = null): \Frootbox\Db\Row
        {
            $this->savedReferences[] = $this->getUidRaw();
            return $this;
        }
        public function getItems(): array { return []; }
    }
    class ReferenceAvaroClient extends \Frootbox\Ext\Avaro2\Api\Client
    {
        public array $payloads = [];
        public bool $fail = false;
        public function __construct(public ReferenceBooking $booking) {}
        public function post(string $uri, array $payload = null): array
        {
            checkReference($uri === 'shop/order/create', 'Avaro endpoint');
            checkReference(in_array($payload['order']['id'], $this->booking->savedReferences, true), 'Persist before transfer');
            $this->payloads[] = $payload;
            if ($this->fail) {
                throw new RuntimeException('Simulated transfer failure');
            }
            return ['order' => ['id' => 'avaro-test', 'orderNumber' => 'AV-1']];
        }
    }
    foreach ([$reference, null] as $uid) {
        $booking = new ReferenceBooking(['id' => 42, 'date' => '2026-10-07', 'uid' => $uid, 'config' => [
            'orderNumber' => 'ORDER-123', 'payment' => ['methodClass' => $class],
            'shipping' => ['type' => 'pickup'],
        ]]);
        if ($uid !== null) {
            $booking->save();
        }
        $client = new ReferenceAvaroClient($booking);
        $integration = new \Frootbox\Ext\Avaro2\ShopIntegration\Integrations\Booking($client);
        $client->fail = true;
        try {
            $integration->transferBooking($booking);
            throw new LogicException('Expected transfer failure');
        } catch (RuntimeException $e) {
            checkReference($e->getMessage() === 'Simulated transfer failure', $e->getMessage());
        }
        $client->fail = false;
        $integration->transferBooking($booking);
        checkReference($client->payloads[0]['order']['id'] === $client->payloads[1]['order']['id'], 'Stable retry reference');
        checkReference($client->payloads[1]['order']['id'] === $booking->getUidRaw(), 'Avaro matches booking');
        checkReference((bool) preg_match('/^[a-f0-9]{32}$/', $booking->getUidRaw()), 'Valid transfer reference');
        if ($uid !== null) {
            checkReference($client->payloads[1]['order']['id'] === $reference, 'Stripe and Avaro match');
        }
    }
    echo "Avaro transfer and retries: OK\n";
}
echo "Order reference checks passed.\n";
