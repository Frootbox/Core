<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods;

abstract class PaymentMethod
{
    protected $title;
    protected $isActive = false;

    /**
     *
     */
    public function __construct()
    {
        // Load language file
        $path = $this->getPath() . 'resources/private/language/de-DE.php';

        if (file_exists($path)) {
            $data = require $path;

            if (!empty($data['Method.Title'])) {
                $this->title = $data['Method.Title'];
            }
        }
    }

    /**
     *
     */
    public function getClass(): string
    {
        return get_class($this);
    }

    /**
     *
     */
    public static function getMethods(): array
    {
        $dir = dir(__DIR__);

        $list = [];

        while (false !== ($entry = $dir->read())) {

            if ($entry[0] == '.' or $entry == 'PaymentMethod.php' or $entry == 'Unknown') {
                continue;
            }

            // Load language file
            $path = $dir->path . '/' . $entry . '/resources/private/language/de-DE.php';
            $title = $entry;

            if (file_exists($path)) {

                $data = require $path;

                if (!empty($data['Method.Title'])) {
                    $title = $data['Method.Title'];
                }
            }

            $list[] = [
                'id' => $entry,
                'title' => $title,
                'class' => '\Frootbox\Ext\Core\ShopSystem\PaymentMethods\\' . $entry . '\\Method'
            ];
        }

        $dir->close();

        return $list;
    }

    /**
     *
     */
    abstract public function getPath(): string;

    /**
     *
     */
    public function getTitle(): ?string
    {
        return $this->title ?? null;
    }

    /**
     *
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     *
     */
    public function renderInputHtml(
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): string
    {
        // Obtain viewfile
        $viewFile = $this->getPath() . 'resources/private/views/Input.html';

        if (!file_exists($viewFile)) {
            return (string) null;
        }

        $html = $view->render($viewFile);

        return $html;
    }

    /**
     *
     */
    public function renderSummary(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        array $data
    ): ?string
    {
        // Obtain viewfile
        $viewFile = $this->getPath() . 'resources/private/views/Summary.html.twig';

        if (!file_exists($viewFile)) {
            return (string) null;
        }

        $html = $view->render($viewFile, [
            'data' => $data
        ]);

        return $html;
    }

    /**
     *
     */
    public function renderSummarySave(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        array $data
    ): ?string
    {
        // Obtain viewfile
        $viewFile = $this->getPath() . 'resources/private/views/SummarySave.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $this->getPath() . 'resources/private/views/Summary.html.twig';
        }

        if (!file_exists($viewFile)) {
            return (string) null;
        }

        $html = $view->render($viewFile, [
            'data' => $data
        ]);

        return $html;
    }

    /**
     *
     */
    public function setActive(): void
    {
        $this->isActive = true;
    }

    /**
     *
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}