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
