<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Admin;

class Front extends \Frootbox\GenericObject
{
    protected $tabs = [];
    protected $flashMessages = [];

    /**
     *
     */
    public function __construct()
    {
        if (!isset($_SESSION['front']['messages'])) {
            $_SESSION['front']['messages'] = [];
        }

        $this->flashMessages =& $_SESSION['front']['messages'];
    }

    /**
     *
     */
    public function addTab(string $title, string $uri = null): void
    {
        $this->tabs[] = [
            'title' => $title,
            'uri' => ($uri ?? '#')
        ];
    }

    /**
     *
     */
    public function flash(
        string $message,
        string $type = 'success'
    ): void
    {
        $this->flashMessages[] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     *
     */
    public function getFlashMessages(): array
    {
        $messages = $this->flashMessages;

        $this->flashMessages = [];

        return $messages;
    }


    /**
     *
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     *
     */
    public static function getUri(
        string $controller,
        string $action = 'index',
        array $payload = null
    ): string
    {
        $url = SERVER_PATH_PROTOCOL . 'cms/admin/' . $controller . '/' . $action;

        if (!empty($payload)) {
            $url .= '?' . http_build_query($payload) . '&' . SID;
        }
        elseif (!empty(SID)) {
            $url .= '?' . SID;
        }

        return $url;
    }

    /**
     *
     */
    public static function getUriFromString(string $string): string
    {
        return $string . '?' . SID;
    }
}
