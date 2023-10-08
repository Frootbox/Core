<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListForms;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforerendering(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Categories $categoryRepository,
    ): Response
    {
        // Obtain app
        $app = $this->getData('app');

        if (empty($get->get('categoryId'))) {

            $category = $categoryRepository->fetchOne([
                'where' => [
                    'uid' => $app->getUid('categories'),
                    'parentId' => 0,
                ],
            ]);
        }
        else {

            $category = $categoryRepository->fetchById($get->get('categoryId'));
        }

        // Fetch forms
        $sql = 'SELECT
            *
        FROM
            assets
        WHERE
            className = :className AND ';

        if ($category->getParentId() == 0) {
            $sql .= ' (
                parentId = ' . $category->getId() . ' OR
                parentId = 0 OR
                parentId IS NULL
            ) ';
        }
        else {
            $sql .= ' parentId = ' . $category->getId();
        }

        $result = $formsRepository->fetchByQuery($sql, [
            'className' => \Frootbox\Ext\Core\ContactForms\Persistence\Form::class,
        ]);

        return new Response('html', 200, [
            'category' => $category,
            'forms' => $result
        ]);
    }
}