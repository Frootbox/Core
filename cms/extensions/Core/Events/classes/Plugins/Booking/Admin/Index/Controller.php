<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): \Frootbox\Admin\Controller\Response
    {
        // Clear old "sources" settings because plugin->addConfig works additive
        $this->plugin->unsetConfig('source');

        // Update new config
        $this->plugin->unsetConfig('paymentmethods');
        $this->plugin->addConfig([
            'source' => $post->get('source'),
            'recipient' => $post->get('recipient'),
            'mailTemplate' => $post->get('mailTemplate'),
            'minPersons' => $post->get('minPersons'),
            'closeEventAfterFirstBooking' => $post->get('closeEventAfterFirstBooking'),
            'paymentmethods' => $post->get('paymentmethods'),
        ]);

        $this->plugin->save();

        return self::response('json');
    }

    /**
     *
     */
    public function indexAction (
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\ContentElements $plugins,
        \Frootbox\Builder $builder,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch news plugins
        $result = $plugins->fetch([
            'where' => [ 'className' => \Frootbox\Ext\Core\Events\Plugins\Schedule\Plugin::class ]
        ]);
        $view->set('plugins', $result);

        // Gather mail templates
        $templates = $builder->setPlugin($this->plugin)->getTemplates('Mail');
        $view->set('mailTemplates', $templates);

        // Gather payment methods
        $paymentMethods = [];
        $path = realpath($this->getPath() . '../../') . '/PaymentMethods/';

        $directory = new \Frootbox\FileSystem\Directory($path);

        foreach ($directory as $file) {
            $paymentMethods[] = $file;
        }

        foreach ($extensionsRepository->fetch() as $extension) {

            $path = $extension->getExtensionController()->getPath() . 'classes/Events/Booking/PaymentMethods/';

            if (!file_exists($path)) {
                continue;
            }

            $directory = new \Frootbox\FileSystem\Directory($path);

            foreach ($directory as $file) {
                $paymentMethods[] = $file;
            }
        }

        return self::getResponse('html', 200, [
            'paymentMethods'=> $paymentMethods,
        ]);
    }
}
