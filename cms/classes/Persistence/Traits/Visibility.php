<?php
/**
 *
 */

namespace Frootbox\Persistence\Traits;

trait Visibility
{
    public function getVisibilityIcon(): string
    {
        $icons = [
            'fa-traffic-light-stop',
            'fa-traffic-light-slow',
            'fa-traffic-light-go',
        ];

        $icon = $icons[$this->getVisibility()];

        return '<i class="fal ' . $icon . ' xicon visibility visibility-' . $this->getVisibility() . '"></i>';
    }

    /**
     *
     */
    public function getVisibilityString(): string
    {
        return 'visibility-' . $this->getVisibility();
    }

    /**
     * @return void
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function validateVisibility(): void
    {
        $visibility = $this->getVisibility();

        if ($visibility == 0) {
            throw new \Frootbox\Exceptions\NotFound();
        }

        if ($visibility == 1 and !IS_EDITOR) {
            throw new \Frootbox\Exceptions\NotFound();
        }
    }

    /**
     *
     */
    public function visibilityPush(): int
    {
        $visibility = ($this->getVisibility() + 1) % 3;

        $this->setVisibility($visibility);
        $this->save();

        return $visibility;
    }
}
