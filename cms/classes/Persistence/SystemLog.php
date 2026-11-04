<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2022-04-21
 */

namespace Frootbox\Persistence;

class SystemLog extends \Frootbox\Persistence\AbstractRow
{
    protected $table = 'system_log';
    protected $model = Repositories\SystemLogs::class;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->data['log_code'];
    }

    public function getEntity(): ?\Frootbox\Db\Row
    {
        $data = $this->getLogData();

        $className = $data[2] ?? null;

        if (!isset($data[0]) || !is_string($className) || !is_subclass_of($className, \Frootbox\Db\Row::class)) {
            return null;
        }

        try {
            $entity = new $className();

            $repository = $this->getDb()->getRepository($entity->getModelClass());
            $entity = $repository->fetchById($data[0]);

            return $entity;
        }
        catch (\Throwable $e) {
            return null;
        }
    }

    /**
     *
     */
    public function getLogData(): array
    {
        return $this->config ?? [];
    }

    /**
     * @return User
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getUser(): \Frootbox\Persistence\User
    {
        // Fetch user
        $userRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Users::class);

        return $userRepository->fetchById($this->getUserId());
    }
}
