<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\View\Viewhelper;

class Translator extends AbstractViewhelper
{
    protected $arguments = [
        'translate' => [
            'key',
            'insets'
        ]
    ];

    protected $translator = null;

    /**
     *
     */
    public function onInit(
        \Frootbox\TranslatorFactory $translatorFactory
    ): void
    {
        $this->translator = $translatorFactory->get(GLOBAL_LANGUAGE);
    }

    /**
     *
     */
    public function translateAction($key, $insets = null): string
    {
        return $this->translator->translate($key, $insets);
    }
}
