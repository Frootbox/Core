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
     * @param array $parameters
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Config\Config $config
     * @param \DI\Container $container
     * @return string
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
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
        $view->set('options', $parameters['options'] ?? []);
        $view->set('config', $config);
        $view->set('plugin', $parameters['plugin'] ?? null);
        $view->set('onSuccess', $parameters['onSuccess'] ?? null);
        $view->set('addedClassName', $parameters['class'] ?? null);
        $view->set('redirect', $parameters['redirect'] ?? null);

        if (!empty($parameters['options'])) {
            $this->options = $parameters['options'];
        }

        // Obtain extension controller
        if (!empty($parameters['viewFile'])) {

            $viewFile = $parameters['viewFile'];
        }
        else {

            $extController = new \Frootbox\Ext\Core\ContactForms\ExtensionController;
            $viewFile = $extController->getPath() . 'resources/private/views/Form.html.twig';
        }

        // Render html source
        $source = $view->render($viewFile);

        return $source;
    }

    /**
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField $field
     * @return string
     */
    public function renderField(\Frootbox\Ext\Core\ContactForms\Persistence\Fields\AbstractField $field): string
    {
        // Render source
        $html = $this->view->render($field->getPath() . 'resources/private/views/Field.html.twig', [
            'renderer' => $this,
            'field' => $field,
        ]);

        return $html;
    }
}
