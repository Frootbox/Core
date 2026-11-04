<?php
// Run: php tests/alias_reservation_test.php
// Exercise the real alias trait with an in-memory repository; no customer DB is used.
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('MULTI_LANGUAGE', true);
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');

function checkAlias(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

class AliasTestDb extends \Frootbox\Db\Db {
    public AliasTestRepository $aliases;
    public function __construct() { $this->aliases = new AliasTestRepository($this); }
    public function getRepository($model): \Frootbox\Db\Model { return $this->aliases; }
    public function transactionStart(): \Frootbox\Db\Db { return $this; }
    public function transactionCommit(): \Frootbox\Db\Db { return $this; }
}
class AliasTestRow extends \Frootbox\Persistence\Alias {
    public function save(): \Frootbox\Db\Row {
        $this->getDb()->aliases->rows[$this->getId()] = $this;
        return $this;
    }
    public function delete() { unset($this->getDb()->aliases->rows[$this->getId()]); }
}
class AliasTestRepository extends \Frootbox\Persistence\Repositories\Aliases {
    public array $rows = [];
    private int $nextId = 1;
    public function __construct(private AliasTestDb $memoryDb) {}
    public function fetch(array $params = null): \Frootbox\Db\Result {
        $matches = array_filter($this->rows, function ($row) use ($params) {
            foreach ($params['where'] ?? [] as $key => $value) {
                if ($value instanceof \Frootbox\Db\Conditions\NotEqual) {
                    $parameter = $value->getParameters()[0];
                    if ($row->{'get' . ucfirst($parameter->getColumn())}() == $parameter->getValue()) { return false; }
                } elseif ($row->{'get' . ucfirst($key)}() != $value) { return false; }
            }
            return true;
        });
        return new \Frootbox\Db\Result(array_values($matches), $this->memoryDb);
    }
    public function fetchOne(array $params = null, array $options = null): ?\Frootbox\Db\Row {
        return $this->fetch($params)->current();
    }
    public function persist(\Frootbox\Db\Row $row): \Frootbox\Db\Row {
        foreach ($this->rows as $existing) {
            checkAlias($existing->getAlias() !== $row->getAlias(), 'Duplicate path: ' . $row->getAlias());
        }
        $data = $row->getData();
        $data['id'] = $this->nextId++;
        $data['status'] ??= 200;
        if (is_array($data['payload'] ?? null)) { $data['payload'] = json_encode($data['payload']); }
        $saved = new AliasTestRow($data, $this->memoryDb);
        $this->rows[$saved->getId()] = $saved;
        return $saved;
    }
    public function at(string $path): ?AliasTestRow { return $this->fetchOne(['where' => ['alias' => $path]]); }
}
class AliasTestParent extends \Frootbox\Db\Row {
    public function save(): \Frootbox\Db\Row { return $this; }
    public function hasColumn($column): bool { return false; }
}
class AliasTestProduct extends AliasTestParent implements \Frootbox\Persistence\Interfaces\MultipleAliases {
    use \Frootbox\Persistence\Traits\Alias;
    protected $model = 'TestProducts';
    public array $paths = ['index' => ['de' => 'alpha', 'en' => 'en/alpha']];
    public function getLanguageAliases(): array { return json_decode($this->data['aliases'] ?? '{}', true); }
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias { return null; }
    protected function getUriFromAlias(\Frootbox\Persistence\Alias $alias): string { return $alias->getVirtualDirectory()[0]; }
    public function getNewAliases(): array {
        $sections = [];
        foreach ($this->paths as $section => $languages) {
            foreach ($languages as $language => $path) {
                $sections[$section][] = new \Frootbox\Persistence\Alias([
                    'uid' => 'product:' . $this->getId(), 'language' => $language,
                    'pageId' => 10, 'virtualDirectory' => [$path],
                    'payload' => ['productId' => $this->getId(), 'revision' => $this->getRevision()],
                ]);
            }
        }
        return $sections;
    }
}
function fixture(): array {
    $db = new AliasTestDb();
    return [$db->aliases, new AliasTestProduct(['id' => 1], $db)];
}
function seed(AliasTestRepository $repo, string $path, array $data = []): AliasTestRow {
    return $repo->persist(new \Frootbox\Persistence\Alias($data + [
        'alias' => $path, 'status' => 200, 'language' => 'de', 'uid' => 'other',
        'itemId' => 2, 'itemModel' => 'TestProducts', 'pageId' => 10, 'payload' => [],
    ]));
}

[$repo, $product] = fixture();
$product->save();
$repo->at('alpha')->addConfig(['seo' => ['title' => 'Keep SEO']]);
$product->paths['index']['de'] = 'beta';
$product->save();
checkAlias($repo->at('alpha')->getStatus() == 301 && $repo->at('alpha')->getConfig('target') === 'beta', 'Rename redirects to new path');
checkAlias($repo->at('en/alpha')->getStatus() == 200, 'Other language stays active');
$repo->at('alpha')->unsetConfig('target'); // Simulate a legacy broken redirect.
$product->paths['index']['de'] = 'alpha';
$product->save();
checkAlias($repo->at('alpha')->getStatus() == 200 && $repo->at('alpha')->getConfig('target') === null, 'Rename back reactivates historical path');
checkAlias($repo->at('alpha')->getConfig('seo.title') === 'Keep SEO', 'Own historical SEO metadata survives reactivation');
checkAlias($repo->at('beta')->getConfig('target') === 'alpha', 'History points directly to current path');
$product->save();
checkAlias(count($repo->rows) === 3, 'Repeated save does not duplicate paths');

foreach ([['uid' => null], ['language' => 'en'], ['uid' => 'product:1', 'itemId' => 1, 'language' => 'en']] as $owner) {
    [$repo, $product] = fixture();
    $product->paths = ['index' => ['de' => 'alpha']];
    $reserved = seed($repo, 'alpha', $owner);
    $before = $reserved->getData();
    $product->save();
    checkAlias($reserved->getData() === $before, 'Foreign 200 remains unchanged, including UID-less and other-language records');
    checkAlias($repo->at('alpha-1')->getStatus() == 200, 'Collision gets suffix');
    $product->setRevision(2);
    $product->save();
    checkAlias(json_decode($repo->at('alpha-1')->getPayload(), true)['revision'] === 2, 'Suffixed alias payload gets refreshed');
    checkAlias(count($repo->rows) === 2, 'Suffixed alias reused without duplicates');
}

foreach (['alpha', 'alpha-1'] as $recycledPath) {
    [$repo, $product] = fixture();
    if ($recycledPath !== 'alpha') { seed($repo, 'alpha'); }
    seed($repo, $recycledPath, ['status' => 301, 'language' => 'en', 'config' => '{"target":"old-owner"}']);
    $product->save();
    $current = $repo->at($recycledPath);
    checkAlias($current->getStatus() == 200 && $current->getItemId() == 1 && $current->getConfig('target') === null, 'Foreign 301 recycled with fresh ownership and no redirect target');
}

[$repo, $product] = fixture();
$product->paths = ['index' => ['de' => 'alpha'], 'details' => ['de' => 'details']];
$product->save();
checkAlias($repo->at('alpha')->getStatus() == 200 && $repo->at('details')->getStatus() == 200, 'All sections generated without redirecting each other');

// Preserve a suffix even if a smaller number becomes available later.
[$repo, $product] = fixture();
seed($repo, 'alpha');
$temporary = seed($repo, 'alpha-1');
$product->save();
$temporary->delete();
$product->save();
checkAlias($repo->at('alpha-2')->getStatus() == 200 && $repo->at('alpha-1') === null, 'Existing suffix stays stable');

// A matching historical suffix must be updated in place, not inserted twice.
[$repo, $product] = fixture();
seed($repo, 'alpha');
seed($repo, 'alpha-1', ['uid' => 'product:1', 'itemId' => 1, 'status' => 301, 'config' => '{"target":"beta"}']);
$product->save();
checkAlias($repo->at('alpha-1')->getStatus() == 200 && $repo->at('alpha-1')->getConfig('target') === null, 'Historical suffix reactivated');

foreach ([29, 30] as $lastOccupied) {
    [$repo, $product] = fixture();
    for ($i = 0; $i <= $lastOccupied; ++$i) { seed($repo, $i ? 'alpha-' . $i : 'alpha'); }
    try {
        $product->save();
        checkAlias($lastOccupied === 29 && $repo->at('alpha-30')->getItemId() == 1, 'Last available suffix is usable');
    } catch (\Frootbox\Exceptions\RuntimeError $exception) {
        checkAlias($lastOccupied === 30, 'Only exhausted suffix space fails');
        checkAlias(count($repo->rows) === 31 && $repo->at('alpha-30')->getItemId() == 2, 'Exhaustion does not overwrite a reserved URL');
    }
}
echo "Alias reservation regression tests passed.\n";
