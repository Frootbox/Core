<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Forms\Captchas;

interface CaptchaInterface
{
    /**
     * @param \Frootbox\Http\Post $post
     */
    public function challenge(\Frootbox\Http\Post $post): void;

    /**
     * @return string
     */
    public function getPath(): string;
}