<?php

namespace Frootbox\View;

/**
 * CMS crawler that preserves script bodies across HTML parsing and serialization.
 */
class HtmlPageCrawler extends \Wa72\HtmlPageDom\HtmlPageCrawler
{
    public static function create($content)
    {
        if ($content instanceof self) {
            return $content;
        }

        $crawler = new static();
        $crawler->add($content);
        return $crawler;
    }

    public function addContent($content, $type = null): void
    {
        if ($type !== null && !str_starts_with($type, 'text/html')) {
            parent::addContent($content, $type);
            return;
        }

        $this->parseScripts($content, fn ($html) => parent::addContent($html, $type));
    }

    public function addHtmlContent(string $content, string $charset = 'UTF-8'): void
    {
        $this->parseScripts($content, fn ($html) => parent::addHtmlContent($html, $charset));
    }

    public function addHtmlFragment($content, $charset = 'UTF-8')
    {
        $this->parseScripts($content, fn ($html) => parent::addHtmlFragment($html, $charset));
    }

    private function parseScripts(string $html, callable $parse): void
    {
        $scripts = new ScriptContents();
        $parse($scripts->protect($html));

        foreach ($this->scriptElements() as $element) {
            $content = $scripts->restore($element->textContent);

            if ($content !== $element->textContent) {
                $element->textContent = $content;
            }
        }
    }

    public function saveHTML()
    {
        return $this->serializeScripts(fn () => parent::saveHTML());
    }

    public function html(?string $default = null): string
    {
        return $this->serializeScripts(fn () => parent::html($default));
    }

    public function getInnerHtml()
    {
        return $this->html();
    }

    public function outerHtml(): string
    {
        return $this->serializeScripts(fn () => parent::outerHtml());
    }

    private function serializeScripts(callable $serialize): string
    {
        $scripts = new ScriptContents();
        $originals = [];

        try {
            foreach ($this->scriptElements() as $element) {
                $children = iterator_to_array($element->childNodes);
                $originals[] = [$element, $children];
                $token = $scripts->placeholder($element->textContent);
                $element->textContent = $token;
            }

            return $scripts->restore($serialize());
        }
        finally {
            // Restore the actual nodes so existing selections and subsequent edits work.
            foreach ($originals as [$element, $children]) {
                while ($element->firstChild !== null) {
                    $element->removeChild($element->firstChild);
                }
                foreach ($children as $child) {
                    $element->appendChild($child);
                }
            }
        }
    }

    /** @return \DOMElement[] */
    private function scriptElements(): array
    {
        $scripts = [];

        foreach ($this as $node) {
            if ($node instanceof \DOMElement && strtolower($node->tagName) === 'script') {
                $scripts[spl_object_id($node)] = $node;
            }
            if ($node instanceof \DOMElement || $node instanceof \DOMDocument) {
                foreach ($node->getElementsByTagName('script') as $script) {
                    $scripts[spl_object_id($script)] = $script;
                }
            }
        }

        return array_values($scripts);
    }

    // The library's insertion methods use self::create(), so pass an already
    // protected crawler to keep inserted fragments on the same parsing path.
    public function setInnerHtml($content)
    {
        return parent::setInnerHtml(self::create($content));
    }

    public function replaceWith($content)
    {
        return parent::replaceWith(self::create($content));
    }

    public function append($content)
    {
        return parent::append(self::create($content));
    }

    public function prepend($content)
    {
        return parent::prepend(self::create($content));
    }

    public function before($content)
    {
        return parent::before(self::create($content));
    }

    public function after($content)
    {
        return parent::after(self::create($content));
    }

    public function wrap($content)
    {
        return parent::wrap(self::create($content));
    }

    public function wrapAll($content)
    {
        return parent::wrapAll(self::create($content));
    }

    public function wrapInner($content)
    {
        return parent::wrapInner(self::create($content));
    }

    // These library methods return new base crawlers; keep their returned
    // selections protected as well as the insertion targets.
    public function appendTo($element)
    {
        return self::create(parent::appendTo(self::create($element)));
    }

    public function prependTo($element)
    {
        return self::create(parent::prependTo(self::create($element)));
    }

    public function insertBefore($element)
    {
        return self::create(parent::insertBefore(self::create($element)));
    }

    public function insertAfter($element)
    {
        return self::create(parent::insertAfter(self::create($element)));
    }

    public function replaceAll($element)
    {
        return self::create(parent::replaceAll(self::create($element)));
    }
}
