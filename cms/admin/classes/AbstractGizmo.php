<?php
/**
 *
 */

namespace Frootbox\Admin;

abstract class AbstractGizmo
{
    private $hasError = false;

    /**
     *
     */
    abstract public function getPath(): string;

    /**
     *
     */
    protected function getResponse(array $payload = null): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response('Html', 200, $payload);
    }

    /**
     *
     */
    public function hasError(): bool
    {
        return $this->hasError;
    }

    /**
     *
     */
    public function renderHtml(
        \DI\Container $container,
        \Frootbox\Admin\View $view,
        \Frootbox\TranslatorFactory $translatorFactory
    ): ?string
    {
        try {

            preg_match('#^Frootbox\\\\Ext\\\\(?P<vendor>[a-z]+)\\\\(?P<extension>[a-z]+)\\\\Admin\\\\Gizmos\\\\(?P<gizmo>[a-z]+)\\\\Gizmo$#i', get_class($this), $classData);

            $translator = $translatorFactory->get('de-DE');
            $scope = $classData['vendor'] . '.' . $classData['extension'] . '.Gizmos.' . $classData['gizmo'];

            $translator->addResource($this->getPath() . 'resources/private/language/de-DE.php', $scope);
            $translator->setScope($scope);

            // Check rendering method
            if (!method_exists($this, 'onBeforeRendering')) {
                throw new \Exception('Method onBeforeRendering does not exist.');
            }

            // Call before renderer
            $response = $container->call([ $this, 'onBeforeRendering' ]);

            if ($response === null) {
                return null;
            }

            $viewFile = $this->getPath() . 'resources/private/views/View.html.twig';

            if (!file_exists($viewFile)) {

                $html = 'Missing Template file.';
            }
            else {
                if (empty($data = $response->getBodyData())) {
                    $data = [];
                }

                $html = $view->render($viewFile, null, $data);
            }

        }
        catch(\Frootbox\Exceptions\Interfaces\Exception $exception) {

            $html = '<h4>' . $translator->translate('Gizmo.Title') . '</h4><p>' . $translator->translate($exception->toString(), $exception->getProperties()) . '</p>';

            $this->setError();
        }

        $classes = [
            $classData['vendor'],
            $classData['extension'],
            $classData['gizmo']
        ];

        if ($this->hasError()) {
            $classes[] = 'hasError';
        }

        $gizmoHtml = '<div class="Gizmo ' . implode(' ', $classes) . '">';

        $gizmoHtml .= $html;

        $gizmoHtml .= '</div>';

        return $gizmoHtml;
    }

    /**
     *
     */
    public function setError(): void
    {
        $this->hasError = true;
    }
}