<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Wechselt alle Layouts auf die TWIG Extension.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): void
    {
        $result = $pagesRepository->fetch();

        foreach ($result as $page) {

            if (empty($page->getConfig('view.layout'))) {
                continue;
            }

            $layout = $page->getConfig('view.layout');

            if (substr($layout, -5) == '.twig') {
                continue;
            }

            $layout .= '.twig';

            $page->addConfig([
                'view' => [
                    'layout' => $layout,
                ],
            ]);

            $page->save();
        }
    }
}
