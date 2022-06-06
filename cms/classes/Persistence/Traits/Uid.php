<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait Uid
{
    /**
     *
     */
    public function getFileByUid(string $uidSegment, array $parameters = null): ?\Frootbox\Persistence\File
    {
        $model = $this->db->getModel(\Frootbox\Persistence\Repositories\Files::class);

        $parameters['order'] = 'orderId DESC';
        if (!$row = $model->fetchByUid($this->getUid($uidSegment), $parameters)) {
            return null;
        }

        return $row;
    }

    /**
     *
     */
    public function getEntityByUid(): ?\Frootbox\Db\Row
    {
        $uid = $this->extractUid($this->getUidRaw());

        $class = '\\' . str_replace('-', '\\', $uid['base']);

        if (!class_exists($class)) {
            return null;
        }

        $repository = $this->getDb()->getRepository($class);

        return $repository->fetchById($uid['id']);
    }

    /**
     *
     */
    public function getUidModelClass(): string
    {
        $uid = $this->extractUid($this->getUidRaw());
        $class = '\\' . str_replace('-', '\\', $uid['base']);

        return $class;
    }

    /**
     *
     */
    public function getTextByUid(string $uidSegment, array $options = null): ?string
    {
        $model = $this->db->getModel(\Frootbox\Persistence\Content\Repositories\Texts::class);

        if (!$row = $model->fetchByUid($this->getUid($uidSegment), $options)) {
            return null;
        }

        return $row->getText();
    }

    /**
     *
     */
    public function getUid(string $uidSegment): string
    {
        // Generate uid
        $uid = $this->getUidBase() . $uidSegment;

        return $uid;
    }
    
    /**
     * 
     */
    public function getUidBase(): string
    {
        return str_replace('\\', '-', $this->getModelClass()) . ':' . $this->getId() . ':';        
    }

    /**
     *
     */
    public function getUidRaw(): ?string
    {
        return $this->data['uid'] ?? null;
    }

    /**
     *
     */
    static public function extractUid(string $uid): array
    {
        if (preg_match('#^(.*?):(\d+):(.*?)$#', $uid, $match)) {
            return [
                'base' => $match[1],
                'id' => $match[2],
                'segment' => $match[3]
            ];
        }
        else {
            return [
                'base' => $uid,
                'id' => null,
                'segment' => null
            ];
        }
    }
}