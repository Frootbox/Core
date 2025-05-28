<?php
/**
 *
 */

namespace Frootbox\Routing;

abstract class AbstractRoute
{
    /**
     * @param \Frootbox\Http\ClientRequest $request
     * @param \Frootbox\Config\Config $configuration
     */
    public function __construct(
        protected \Frootbox\Http\ClientRequest $request,
        protected \Frootbox\Config\Config $configuration,
    )
    { }

    /**
     * Get extensions path
     *
     * @return string
     */
    public function getExtensionPath(): string
    {
        // Extract vendor and extension
        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\#', get_class($this), $match);

        // Build extension controller class
        $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

        // Instantiate extension controller
        $extensionController = new $class;

        // Return path
        return $extensionController->getPath();
    }

    /**
     * @return string
     */
    abstract protected function getMatchingRegex(): string;

    /**
     *
     */
    public function getRequest(): \Frootbox\Http\ClientRequest
    {
        return $this->request;
    }

    /**
     * Match request against routing
     */
    public function match(): bool
    {
        return preg_match($this->getMatchingRegex(), $this->getRequest()->getRequestTarget());
    }
}
