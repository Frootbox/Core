<?php

namespace Frootbox\Ext\Core\McpServer\Apps\McpServer;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return $this->getResponse();
    }
}
