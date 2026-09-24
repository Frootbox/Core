<?php

namespace Frootbox\Ext\Core\System\Apps\GlobalPassword;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function indexAction(\Frootbox\Config\Config $config): Response
    {
        return self::getResponse('html', 200, [
            'enabled' => (bool) $config->get('globalPassword.enabled'),
            'hasPassword' => !empty($config->get('globalPassword.hash')),
        ]);
    }

    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\ConfigStatics $statics
    ): Response
    {
        $enabled = (bool) $post->get('enabled');
        $password = trim((string) ($post->get('password') ?? ''));
        $hash = (string) ($config->get('globalPassword.hash') ?? '');

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($enabled && $hash === '') {
            throw new \Frootbox\Exceptions\RuntimeError('Bitte zuerst ein Passwort festlegen.');
        }

        $statics->addConfig([
            'globalPassword' => [
                'enabled' => $enabled,
                'hash' => $hash,
            ],
        ])->write();

        return self::getResponse('json', 200, [ 'success' => 'Einstellungen gespeichert.' ]);
    }
}
