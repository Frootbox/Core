<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Viewhelper;

class Form extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getGroup' => [
            'groupId',
        ],
        'guessFieldValue' => [
            'field',
            'plugin',
        ],
        'render' => [
            'object',
            [ 'name' => 'parameters', 'default' => [ ] ],
        ]
    ];

    protected $customFieldViewsFolder = null;
    
    /**
     * 
     */
    public function getFormAction ( ) {
        
        return new \Frootbox\Ext\Core\Forms\Persistence\Form;
    }

    /**
     *
     */
    public function getGroupAction(
        $groupId,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository
    ): ?\Frootbox\Ext\Core\Forms\Persistence\Group
    {
        return $groupsRepository->fetchById($groupId);
    }
    
    /**
     *
     */
    public function guessFieldValueAction(
        \Frootbox\Ext\Core\Forms\Persistence\Field $field,
        \Frootbox\Persistence\AbstractPlugin $plugin,
        \Frootbox\Http\Get $get
    )
    {
        $fields = $plugin->getAttribute('fields', [ ]);

        return $fields[$field->getId()] ?? $get->get('fields')[$field->getId()] ?? (string) null;
    }

    /**
     * 
     */
    public function renderAction ( $object, $parameters, \Frootbox\View\Engines\Interfaces\Engine $view ): string {
        
        switch (get_class($object)) {
            
            case 'Frootbox\\Ext\\Core\\Forms\\Persistence\\Field':

                if (!empty($this->customFieldViewsFolder)) {
                    $viewFile = $this->customFieldViewsFolder . '/' . $object->getType() . '.html.twig';
                }

                if (!isset($viewFile) or !file_exists($viewFile)) {
                    $basePath = explode(DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR, __FILE__)[0] . '/';
                    $viewFile = $basePath . 'resources/private/views/fields/' . $object->getType() . '.html.twig';
                }

                $view->set('parameters', $parameters);
                $view->set('field', $object);

                $source = $view->render($viewFile);
            break;
        }
        
        return $source;
    }

    /**
     *
     */
    public function setCustomFieldViewsFolder(string $path): void
    {
        $this->customFieldViewsFolder = $path;
    }
}
