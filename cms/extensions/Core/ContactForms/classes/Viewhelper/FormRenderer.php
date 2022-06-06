<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Viewhelper;

use Frootbox\Config\Config;

class FormRenderer extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $container;
    protected $view;
    public $options = [];

    protected $arguments = [
        'render' => [
            [ 'name' => 'parameters', 'default' => [ ] ],
        ]
    ];

    /**
     * 
     */
    public function renderAction(
        array $parameters,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config,
        \DI\Container $container
    ): string
    {
        $this->container = $container;
        $this->view = $view;

        if (empty($parameters['form']) and !empty($parameters['formId'])) {

            // Fetch form
            $formsRepository = $container->get(\Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms::class);
            $form = $formsRepository->fetchById($parameters['formId']);
        }

        if (empty($form)) {
            return '';
        }

        // Parse added payload
        if (!empty($parameters['payload'])) {
            $parameters['payload']['token'] = md5(json_encode($parameters['payload']));
        }

        // Render source
        $view->set('renderer', $this);
        $view->set('form', $form);
        $view->set('addedPayload', $parameters['payload'] ?? []);
        $view->set('config', $config);
        $view->set('plugin', $parameters['plugin'] ?? null);

        if (!empty($parameters['options'])) {
            $this->options = $parameters['options'];
        }

        $extController = new \Frootbox\Ext\Core\ContactForms\ExtensionController;
        $viewFile = $extController->getPath() . 'resources/private/views/Form.html.twig';
        $source = $view->render($viewFile);

        return $source;
    }

    /**
     *
     */
    public function renderField(\Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField $field): string
    {
        // Render source
        $this->view->set('renderer', $this);
        $this->view->set('field', $field);

        return $this->view->render($field->getPath() . 'resources/private/views/Field.html.twig');
    }
}
