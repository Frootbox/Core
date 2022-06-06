<?php
/**
 *
 */

namespace Frootbox\Admin\Viewhelper;


class Translator extends AbstractViewhelper
{
    protected $translator = null;

    /**
     *
     */
    public function setScope($object): void
    {
        if ($this->translator === null) {
            $factory = $this->container->get(\Frootbox\TranslatorFactory::class);
            $this->translator = $factory->get(GLOBAL_LANGUAGE);
        }

        $this->translator->setScope(str_replace('\\', '.', substr(substr(get_class($object), 0, -7), 13)));
    }

    /**
     *
     */
    public function translate($key, $insets = null): string
    {
        if ($this->translator === null) {
            $factory = $this->container->get(\Frootbox\TranslatorFactory::class);
            $this->translator = $factory->get(GLOBAL_LANGUAGE);
        }

        return $this->translator->translate($key, $insets);


        echo '">';

        d($translator);
        $object = $this->parameters['object'];

        if (!isset($arguments[0])) {
            $arguments[0] = [ ];
        }

        // Perform interface action
        $result = $this->container->call([ $object, $method ], $arguments[0]);

        return $result;
    }
}
