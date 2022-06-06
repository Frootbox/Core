<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class GeneralPurpose extends AbstractViewhelper
{
    /**
     *
     */
    public function redirect($url, $status = 301): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     *
     */
    public function renderPartial ( string $partialClass ): string {

        $partial = $this->container->get($partialClass);

        if (method_exists($partial, 'onBeforeRendering')) {
            $this->container->call([ $partial, 'onBeforeRendering']);
        }

        $html = $this->container->call([ $partial, 'render']);

        return $html;
    }
}
