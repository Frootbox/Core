<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Forms\Captchas\SimpleMathChallenge;

class Captcha implements \Frootbox\Ext\Core\Forms\Captchas\CaptchaInterface
{
    protected $view;

    /**
     *
     */
    public function __construct(
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        $this->view = $view;
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function challenge(\Frootbox\Http\Post $post): void
    {
        $data = $post->get('age');
        $key = key($data);
        $value = current($data);

        $challenge = $_SESSION['forms']['captcha']['challenges'][$key];

        if ($challenge['operator'] == 'Minus') {
            $checkValue = $challenge['a'] - $challenge['b'];
        }
        else {
            $checkValue = $challenge['a'] + $challenge['b'];
        }

        if ($value != $checkValue) {
            throw new \Exception('Bitte beachten Sie die Rechenaufgabe.');
        }
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function render(
        \Frootbox\TranslatorFactory $factory,
        \Frootbox\Persistence\Page $page
    ): string
    {
        $translator = $factory->get($page->getLanguage());

        $translator->addResource($this->getPath() . 'resources/private/language/' . $page->getLanguage() . '.php', null, false);
        $translator->setScope(str_replace('\\', '.', substr(substr(get_class($this), 0, -7), 13)));

        $challenge = [
            'a' => rand(6, 10),
            'b' => rand(1, 3),
            'operator' => (rand(1, 2) == 1 ? 'Minus' : 'Plus'),
            'key' => md5(microtime(true))
        ];

        $_SESSION['forms']['captcha']['challenges'][$challenge['key']] = $challenge;

        $this->view->set('a', $challenge['a']);
        $this->view->set('b', $challenge['b']);
        $this->view->set('operator', $challenge['operator']);
        $this->view->set('key', $challenge['key']);

        $this->view->set('t', $translator);

        $html = $this->view->render($this->getPath() . 'resources/private/views/View.html.twig');

        // Inject css
        $cssFile = $this->getPath() . 'resources/public/css/standards.less';

        $html .= PHP_EOL . PHP_EOL . '<link rel="stylesheet/less" href="FILE:' . $cssFile . '" />';


        return $html;
    }
}
