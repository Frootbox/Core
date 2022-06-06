<?php
/**
 *
 */

namespace Frootbox\Persistence\Content;

class Text extends \Frootbox\Persistence\AbstractConfigurableRow
{
    use \Frootbox\Persistence\Traits\Uid;

    protected $table = 'content_texts';
    protected $model = Repositories\Texts::class;

    /**
     * Delete text
     */
    public function delete()
    {
        // Clear widgets from text
        if (preg_match_all('#<figure data-id="([0-9]{1,})"></figure>#', $this->getText(), $matches)) {
            
            $widgets = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Widgets::class);
            
            foreach ($matches[0] as $index => $tagline) {

                try {
                    $widget = $widgets->fetchById($matches[1][$index]);
                    $widget->delete();
                }
                catch ( \Frootbox\Exceptions\NotFound $e ) {
                    // Ignore if widget is not found
                    // A prior cleanup problem is assumed
                }
            }
        }
        
        return parent::delete();
    }
}
