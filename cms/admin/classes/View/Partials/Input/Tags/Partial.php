<?php 
/**
 * 
 */

namespace Frootbox\Admin\View\Partials\Input\Tags;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Tags $tagsRepository
    ): void
    {
        $tags = [];

        if ($this->hasData('tags')) {
            array_push($tags, ...$this->getData('tags'));
        }
        elseif ($this->hasData('object')) {

            $object = $this->getData('object');

            if (!method_exists($object, 'getTags')) {
                throw new \Exception('Das Objekt unterstüzt das Speichern von Tags nicht.');
            }

            foreach ($object->getTags() as $tagRow) {
                $tags[] = $tagRow->getTag();
            }
        }

        $view->set('tags', $tags);

        // Fetch tags
        $availableTags = [];

        if (!empty($config->get('tags.preset'))) {
            foreach ($config->get('tags.preset') as $tag) {
                $availableTags[$tag] = $tag;
            }
        }

        $result = $tagsRepository->fetch();

        foreach ($result as $tag) {
            $availableTags[$tag->getTag()] = $tag->getTag();
        }

        $view->set('availableTags', $availableTags);
    }
}
