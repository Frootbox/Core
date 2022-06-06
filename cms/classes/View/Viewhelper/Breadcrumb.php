<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Breadcrumb extends AbstractViewhelper
{
    protected $arguments = [
        'getFolder' => [
            'folderId'
        ],
        'getThumbnail' => [
            'file'
        ]
    ];

    protected $sections = [];

    /**
     *
     */
    public function addSection($title, $href = null): void
    {
        $this->sections[] = [
            'title' => $title,
            'href' => $href,
        ];
    }

    /**
     *
     */
    public function addSections($input, array $options = null): void
    {
        $trace = $input->getTrace();

        if (!empty($options['skipfirst'])) {
            $trace->shift();
        }

        foreach ($trace as $item) {
            $this->sections[] = [
                'title' => $item->getTitle(GLOBAL_LANGUAGE),
                'href' => $item->getUri(),
            ];
        }
    }

    /**
     *
     */
    public function getSections(\Frootbox\Persistence\Page $page): array
    {
        $sections = [];

        foreach ($page->getTrace() as $npage) {

            if (!empty($npage->getConfig('variables.hideInBreadcrumb'))) {
                continue;
            }

            $sections[] = [
                'title' => $npage->getTitle(GLOBAL_LANGUAGE),
                'href' => $npage->getUri(),
            ];
        }

        foreach ($this->sections as $section) {

            $sections[] = $section;
        }

        return $sections;
    }
}
