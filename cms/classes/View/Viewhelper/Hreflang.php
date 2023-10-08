<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Hreflang extends AbstractViewhelper
{
    protected $arguments = [

    ];

    protected $items = [];

    /**
     *
     */
    public function addItem($language, $href): void
    {
        $this->items[] = [
            'language' => $language,
            'href' => $href,
        ];
    }

    /**
     *
     */
    public function getItem($language): ?array
    {
        foreach ($this->items as $item) {
            if ($item['language'] == $language) {

                $item['active'] = ($item['href'] == REQUEST_URI);

                return $item;
            }
        }

        return null;
    }

    /**
     *
     */
    public function getItems(array $parameters = null): array
    {
        if (empty($this->items) and !empty($parameters['fallback'])) {

            $page = $parameters['fallback'];

            foreach ($page->getLanguageAliases() as $language => $href) {
                $this->addItem($language, $href);
            }
        }

        if ($item = $this->getItem('en-GB') or $item = $this->getItem(DEFAULT_LANGUAGE)) {
            $this->addItem('x-default', $item['href']);
        }

        return $this->items;
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
