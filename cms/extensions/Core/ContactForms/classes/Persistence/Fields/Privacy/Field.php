<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Persistence\Fields\Privacy;

class Field extends \Frootbox\Ext\Core\ContactForms\Persistence\Fields\Checkbox\Field
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    /**
     * @return \Frootbox\Persistence\File|null
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function getFile(): ?\Frootbox\Persistence\File
    {
        if (empty($this->getConfig('linkedFile'))) {
            return null;
        }

        // Fetch file
        $fileRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);
        $file = $fileRepository->fetchByUid($this->getUid('file'));

        return $file;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $url
     * @return string|null
     */
    public function getPrivacyText(string $url): ?string
    {
        if (empty($this->getConfig('privacyText'))) {
            return null;
        }

        // Replace link text
        $text = preg_replace('#\[(.*?)\]#i', '<a href="' . $url . '">\\1</a>', $this->getConfig('privacyText'));

        return $text;
    }

    /**
     * @param string $text
     * @param string $url
     * @return string
     */
    public function injectPrivacyLink(string $text, string $url): string
    {
        return preg_replace('#\[(.*?)\]#i', '<a href="' . $url . '">\\1</a>', $text);
    }

    /**
     * Update field form post data
     *
     * @param \Frootbox\Http\Post $post
     * @return void
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'linkedFile' => !empty($post->get('linkedFile')),
            'privacyText' => $post->get('privacyText'),
        ]);
    }
}
