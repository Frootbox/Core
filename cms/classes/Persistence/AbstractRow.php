<?php 
/**
 * 
 */

namespace Frootbox\Persistence;

abstract class AbstractRow extends \Frootbox\Db\Row implements \JsonSerializable
{
    /**
     * @var array|mixed
     */
    protected $config = [ ];

    /**
     *
     */
    public function __construct(array $record = null, \Frootbox\Db\Db $db = null)
    {
        parent::__construct($record, $db);
        
        if (!empty($this->data['config'])) {
            $this->config = !is_array($this->data['config']) ? json_decode($this->data['config'], true) : $this->data['config'];
        }
    }
    
    /**
     * 
     */
    public function delete()
    {
        // Cleanup alias
        if (!empty($this->data['alias'])) {

            $aliases = $this->db->getRepository(\Frootbox\Persistence\Repositories\Aliases::class);
            $result = $aliases->fetch([
                'where' => [
                    'alias' => $this->getAlias(),
                ],
            ]);

            $result->map('delete');
        }

        if (!empty($this->data['aliases'])) {

            $aliasRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Aliases::class);

            $aliasData = json_decode($this->data['aliases'], true);

            foreach ($aliasData as $cat => $aliases) {
                foreach ($aliases as $alias) {
                    $result = $aliasRepository->fetch([
                        'where' => [
                            'alias' => $alias,
                        ],
                    ]);

                    $result->map('delete');
                }
            }
        }

        // Clean tags
        $traits = class_uses($this);

        if (in_array(\Frootbox\Persistence\Traits\Tags::class, $traits)) {
            $this->getTags()->map('delete');
        }

        // Clean category connections
        $connectionsRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\CategoriesConnections::class);

        $result = $connectionsRepository->fetch([
            'where' => [
                'itemClass' => get_class($this),
                'itemId' => $this->getId(),
            ],
        ]);
        $result->map('delete');

        // Clean direct item connections
        $itemConnectionsRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\ItemConnections::class);
        $result = $itemConnectionsRepository->fetch([
            'where' => [
                'itemClass' => get_class($this),
                'itemId' => $this->getId(),
            ]
        ]);

        $result->map('delete');

        // Clean edit mode contents
        if (method_exists($this, 'getUid')) {

            // Cleanup blocks
            $blocks = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Blocks::class);
            $result = $blocks->fetchByQuery('SELECT * FROM blocks WHERE uid LIKE "' . $this->getUidBase() . '%"');

            $result->map('delete');

            // Cleanup files from edit mode
            $files = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);
            $result = $files->fetchByQuery('SELECT * FROM files WHERE uid LIKE "' . $this->getUidBase() . '%"');

            $result->map('delete');

            // Cleanup texts from edit mode
            $texts = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Texts::class);
            $result = $texts->fetchByQuery('SELECT * FROM content_texts WHERE uid LIKE "' . $this->getUidBase() . '%"');

            $result->map('delete');
        }

        return parent::delete();
    }

    /**
     *
     */
    public function duplicate(): \Frootbox\Db\Row
    {
        $row = parent::duplicate();


        // Get traits
        $traits = $this->getTraits();

        if (!empty($traits['Frootbox\\Persistence\\Traits\\Tags'])) {

            $tags = [];

            foreach ($this->getTags() as $xtag) {
                $tags[] = $xtag->getTag();
            }

            $row->setTags($tags);
        }

        return $row;
    }

    /**
     *
     */
    public function getTraits(): array
    {
        $traits = [];

        $class = $this;

        do {
            $traits = array_merge(class_uses($class, true), $traits);
        } while($class = get_parent_class($class));

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, true), $traits);
        }

        $traits = array_unique($traits);

        return $traits;
    }

    /**
     *
     */
    public function jsonSerialize(): mixed
    {
        $cacheFile = FILES_DIR . 'cache/system/classes/' . md5(get_class($this)) . '.php';

        if (!file_exists($cacheFile)) {

            $data = [
                'class' => get_class($this),
                'parameters' => [

                ],
                'exposedParameters' => [

                ],
            ];

            $reflector = new \ReflectionClass(get_class($this));

            foreach($reflector->getProperties() as $property) {

                $exposed = (strpos($property->getDocComment(), '@expose') !== false);

                $data['parameters'][$property->getName()] = [
                    'exposed' => $exposed,
                ];

                if ($exposed) {
                    $data['exposedParameters'][] = $property->getName();
                }
            }

            $source = "<?php\n/**\n * autogenerated " . date('Y-m-d H:i:s') . "\n */\nreturn " . var_export($data, true) . ";\n";

            $file = new \Frootbox\Filesystem\File($cacheFile);
            $file->setSource($source);
            $file->write();
        }

        $data = require $cacheFile;

        $list = [];

        foreach ($data['exposedParameters'] as $parameter) {
            $getter = 'get' . ucfirst($parameter);
            $list[$parameter] = $this->$getter();
        }

        return $list;
    }

    /**
     *
     */
    public function persist ( ) {
    
        $path = FILES_DIR . 'persistency/' . $this->data['id'] . '.php';
        $source = '<?php return unserialize("' . addslashes(serialize($this)) . '");';
    
        $file = new \Frootbox\Filesystem\File($path);
        $file->setSource($source);
        $file->write();
    
        return $this;
    }
}