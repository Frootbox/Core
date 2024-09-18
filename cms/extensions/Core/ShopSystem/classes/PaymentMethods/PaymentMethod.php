<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods;

abstract class PaymentMethod
{
    protected $title;
    protected $isActive = false;
    protected bool $forcesPaymentExtraStep = false;
    protected bool $isForcingNewPaymentFlow = false;
    protected bool $hasCheckoutControl = false;
    protected array $paymentData = [];

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
     * @return string
     */
    public function getClass(): string
    {
        return get_class($this);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        preg_match('#\\\\([a-z0-9]+)\\\\Method$#i', get_class($this), $match);

        return $match[1];
    }

    /**
     * @return string
     */
    public function getImageDataUri(): string
    {
        $source = file_get_contents($this->getPath() . 'resources/public/logo.svg');

        preg_match('#\<svg(.*?)\<\/svg\>#s',$source, $match);


        return 'data:image/svg+xml;base64,' . base64_encode($match[0]);
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
            $titleIntern = null;

            if (file_exists($path)) {

                $data = require $path;

                if (!empty($data['Method.Title'])) {
                    $title = $data['Method.Title'];
                }

                if (!empty($data['Method.TitleIntern'])) {
                    $titleIntern = $data['Method.TitleIntern'];
                }
            }

            $list[] = [
                'id' => $entry,
                'title' => $title,
                'titleIntern' => $titleIntern ?? $title,
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
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking
     * @return string|null
     */
    public function getTitleFinalForBooking(\Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking): ?string
    {
        return $this->getTitle();
    }


    public function getViewExtendPath(string $path): string
    {
        return realpath($this->getPath() . $path);
    }


    /**
     * @return bool
     */
    public function hasCheckoutControl(): bool
    {
        return $this->hasCheckoutControl;
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool
    {
        return file_exists($this->getPath() . 'resources/public/logo.svg');
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isForcingNewPaymentFlow(): bool
    {
        return $this->isForcingNewPaymentFlow;
    }

    /**
     * @return bool
     */
    public function forcesPaymentExtraStep(): bool
    {
        return $this->forcesPaymentExtraStep;
    }

    /**
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @return string
     */
    public function renderInputHtml(
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view,
    ): ?string
    {
        // Obtain view-file
        $files = [];
        $files[] = $this->getPath() . 'resources/private/views/Input.html.twig';
        $files[] = $this->getPath() . 'resources/private/views/Input.html';

        $viewFile = null;

        foreach ($files as $file) {

            if (file_exists($file)) {
                $viewFile = $file;
                break;
            }
        }

        if (empty($viewFile)) {
            return null;
        }

        if (method_exists($this, 'onBeforeRenderInput')) {
            $payload = $container->call([ $this, 'onBeforeRenderInput' ]);
        }
        else {
            $payload = [];
        }

        // Render input html
        $html = $view->render($viewFile, $payload);

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
     * @param array $paymentData
     * @return void
     */
    public function setPaymentData(array $paymentData): void
    {
        $this->paymentData = $paymentData;
    }

    /**
     *
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}