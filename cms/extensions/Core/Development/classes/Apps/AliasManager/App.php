<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\AliasManager;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /** 
     * 
     */
    public function indexAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\Db\Db $db
    )
    {
        // Clear dead aliases
        $result = $aliasesRepository->fetch();

        foreach ($result as $alias) {

            try {

                if (!class_exists($alias->getItemModel())) {
                    d($alias);
                }

                $model = $db->getRepository($alias->getItemModel());
                $row = $model->fetchById($alias->getItemId());

                $row->save();
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {
                $alias->delete();
            }
            catch ( \Exception $e ) {

                p($alias);
                d($e);
            }
        }

        d("OKKK");
        d($result);
        $result = $aliasesRepository->fetchByQuery('SELECT SQL_CALC_FOUND_ROWS itemModel, itemId FROM aliases GROUP BY itemModel, itemId');

        // Refresh aliases
        $aliasesRepository->truncate();

        $loop = 0;

        foreach ($result as $alias) {

            try {

                $model = $db->getmodel($alias->getItemModel());
                $row = $model->fetchById($alias->getItemId());

                $row->setAlias(null);
                $row->save();

                ++$loop;
            }
            catch ( \Exception $e ) {

                d($e);
                $alias->delete();
            }
        }


        die("Fixed " . $loop . " Aliases.");

        return self::getResponse();
    }    
}