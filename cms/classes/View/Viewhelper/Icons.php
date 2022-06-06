<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class Icons extends AbstractViewhelper
{
    protected $arguments = [
        'add' => [
            'icon',
            [ 'name' => 'addedClasses', 'default' => [ ] ],
        ],
    ];

    /**
     *
     */
    public function addAction(
        $icon,
        $addedClasses,
        \Frootbox\Config\Config $configuration,
    ): ?string
    {
        if (empty($configuration->get('icons'))) {
            return null;
        }

        foreach ($configuration->get('icons') as $iconData) {

            if (file_exists($iconFile = $iconData['path'] . $icon. '.svg')) {

                $classes = 'icon ' . $addedClasses;

                $source = file_get_contents($iconFile);
                $source = str_replace('<svg', '<svg fill="currentColor" class="' . $classes . '" ', $source);

                return $source;
            }
        }

        return null;
    }

}
