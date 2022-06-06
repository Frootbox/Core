<?php
/**
 *
 */

namespace Frootbox\Persistence\Content\Elements;

class Text extends \Frootbox\Persistence\AbstractConfigurableRow implements \Frootbox\Persistence\Interfaces\ContentElement
{
    use \Frootbox\Persistence\Traits\Uid;

    protected $table = 'content_elements';
    protected $model = Repositories\Texts::class;

    protected $isFirst = false;

    /**
     *
     */
    public function getPath ( )
    {
        return CORE_DIR . 'resources/private/text/';
    }


    /**
     *
     */
    public function renderHtml (
        $order,
        \Frootbox\Config\Config $config
    ):string
    {
        $source = '<div class="row">
            <div class="col plugin Text">
                <div class="inner-pillow">
                
                    <h' . ($this->isFirst ? '1' : '2') . ' data-editable data-uid="' . $this->getUid('title') . '">' . ($this->getTitle() ?? 'Überschrift') . '</h' . ($this->isFirst ? '1' : '2') . '>
                    
                    <div data-editable data-uid="' . $this->getUid('text') . '"></div>
                    
                </div>                
            </div>
        </div>';

        return $source;
    }


    /**
     *
     */
    public function getPublicActions ( ) {
        return [ 'index' ];
    }


    /**
     *
     */
    public function isFirst ( ): bool
    {
        return $this->isFirst;
    }


    /**
     * Mark element as first
     */
    public function setFirst ( ): Text
    {
        // Mark as first
        $this->isFirst = true;

        return $this;
    }
}