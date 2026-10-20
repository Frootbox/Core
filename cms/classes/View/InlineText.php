<?php

namespace Frootbox\View;

/** Inline title markup: text, emphasis and plain-text line endings. */
class InlineText
{
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NONET);
        }
        finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $body = $document->getElementsByTagName('body')->item(0);
        return $body === null ? '' : trim(self::children($body));
    }

    public static function plainText(?string $html): string
    {
        return html_entity_decode(strip_tags(self::sanitize($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function children(\DOMNode $parent): string
    {
        $html = '';

        foreach ($parent->childNodes as $node) {
            if ($node instanceof \DOMText) {
                $content = str_replace(["\r\n", "\r"], "\n", $node->textContent);
                if ($node->previousSibling instanceof \DOMElement && strtolower($node->previousSibling->tagName) === 'br') {
                    $content = preg_replace('/^\n/', '', $content);
                }
                $html .= htmlspecialchars($content, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
                continue;
            }

            if (!$node instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            // Discard embedded code/media entirely, including their text contents.
            if (in_array($tag, ['script', 'style', 'head', 'title', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'img', 'video', 'audio'], true)) {
                continue;
            }

            if ($tag === 'br') {
                $html .= "\n";
                continue;
            }

            $content = self::children($node);
            $tag = ['b' => 'strong', 'i' => 'em'][$tag] ?? $tag;

            if ($tag === 'strong' || $tag === 'em') {
                if ($content !== '') {
                    $html .= '<' . $tag . '>' . $content . '</' . $tag . '>';
                }
            }
            elseif (in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'ul', 'ol', 'blockquote', 'pre', 'section', 'article', 'header', 'footer', 'table', 'tr', 'td', 'th'], true)) {
                // Keep paragraph boundaries without storing block elements.
                $html = rtrim($html) . ($html !== '' ? "\n" : '') . trim($content) . "\n";
            }
            else {
                $html .= $content;
            }
        }

        return $html;
    }
}
