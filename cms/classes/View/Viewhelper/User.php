<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\View\Viewhelper;

class User extends AbstractViewhelper
{
    protected ?\Frootbox\Persistence\Repositories\Users $userRepository = null;

    /**
     * @param \Frootbox\Persistence\Repositories\Users $userRepository
     * @return void
     */
    public function onInit(
        \Frootbox\Persistence\Repositories\Users $userRepository,
    ): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param string|null $email
     * @return \Frootbox\Persistence\User|null
     */
    public function getByEmail(?string $email): ?\Frootbox\Persistence\User
    {
        if (empty($email)) {
            return null;
        }

        /**
         * Fetch user
         * @var ?\Frootbox\Persistence\User $user
         */
        $user = $this->userRepository->fetchOne([
            'where' => [
                'email' => $email,
            ]
        ]);

        return $user;
    }
}
