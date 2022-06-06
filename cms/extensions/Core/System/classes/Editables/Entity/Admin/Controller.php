<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Entity\Admin;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
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
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    )
    {
        list($uid, $property) = explode('----', $get->get('uid'));

        // Parse uid
        preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:(.*?)$#i', $uid, $match);
        $className = str_replace('-', '\\', $match[1]);

        // Fetch target object
        $model = new $className($db);
        $row = $model->fetchById($match[2]);

        // Extract property
        if (substr($property, 0, 7) == 'config.') {

            $configPath = substr($property, 7);

            $row->addConfig([
                $configPath => $post->get('value')
            ]);
        }
        else {
            $setter = 'set' . ucfirst($property);
            $propertyValue = $row->$setter($post->get('value'));
        }

        $row->save();

        return self::getResponse('json', 200, [
            'value' => $post->get('value'),
            'uid' => $uid,
            'property' => $property
        ]);
    }


    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Admin\View $view
    )
    {
        list($uid, $property) = explode('----', $get->get('uid'));

        // Parse uid
        preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:(.*?)$#i', $uid, $match);
        $className = str_replace('-', '\\', $match[1]);

        // Fetch target object
        $model = new $className($db);
        $row = $model->fetchById($match[2]);

        // Extract property
        if (substr($property, 0, 7) == 'config.') {
            $propertyValue = $row->getConfig(substr($property, 7));
        }
        else {
            $getter = 'get' . ucfirst($property);
            $propertyValue = $row->$getter();
        }

        $view->set('value', $propertyValue);
        $view->set('row', $row);

        return self::getResponse();
    }
}