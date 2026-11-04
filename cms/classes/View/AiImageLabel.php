<?php

namespace Frootbox\View;

/** Shared, server-rendered disclosure for editable and template-owned images. */
class AiImageLabel
{
    public const TEXT = 'KI-generiert';

    public static function attributes(?\Frootbox\Persistence\File $file): string
    {
        return 'data-frootbox-ai="' . ($file ? $file->getAiLabelType() : '0') . '"';
    }

    private static function normalize(?string $type): string
    {
        return in_array($type, ['1', 'edited'], true) ? $type : '';
    }

    private static function label(string $type, string $class = ''): string
    {
        if ($type === '') {
            return '';
        }
        $text = $type === '1' ? self::TEXT : 'Mit KI bearbeitet';
        return '<span class="frootbox-ai-label' . ($class ? ' ' . $class : '') . '" data-frootbox-ai-label>' . $text . '</span>';
    }

    public static function render(string $html): string
    {
        if (!str_contains($html, 'data-frootbox-ai')) {
            return $html;
        }

        // A fragment may consist solely of an img/picture. Give it a stable
        // parent so wrapping that root is included in crawler serialization.
        $fragment = !preg_match('/<(?:html|head|body)(?:\s|>)/i', $html);
        $crawler = HtmlPageCrawler::create($fragment ? '<html><head></head><body>' . $html . '</body></html>' : $html);
        $crawler->filter('[data-frootbox-ai]')->each(function ($element) {
            $node = $element->getNode(0);
            // Lightbox triggers carry metadata, but must not get a second label.
            if ($node->tagName === 'a' && !$node->hasAttribute('data-frootbox-ai-container')) {
                return;
            }

            $mobile = self::normalize($element->getAttribute('data-frootbox-ai'));
            $desktop = $element->getAttribute('data-frootbox-ai-desktop');
            $desktop = $desktop === null ? $mobile : self::normalize($desktop);

            if ($node->tagName === 'img') {
                $target = $node->parentNode instanceof \DOMElement && $node->parentNode->tagName === 'picture' ? $node->parentNode : $node;
                $host = $target->parentNode;
                if ($host instanceof \DOMElement && $host->hasAttribute('data-frootbox-ai-host')) {
                    HtmlPageCrawler::create($host)->filter('[data-frootbox-ai-label]')->remove();
                    if (!$mobile && !$desktop) {
                        $host->parentNode->insertBefore($target, $host);
                        $host->parentNode->removeChild($host);
                    }
                }
                for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
                    if ($parent->tagName === 'a' && $parent->hasAttribute('data-fancybox')) {
                        $parent->setAttribute('data-frootbox-ai', $mobile ?: ($desktop ?: '0'));
                        break;
                    }
                }
            }

            // Refreshes can re-use an existing background element.
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMElement && $child->hasAttribute('data-frootbox-ai-label')) {
                    $node->removeChild($child);
                }
            }
            if (!$mobile && !$desktop) {
                $element->removeClass('frootbox-ai-background');
                return;
            }

            $label = $mobile === $desktop
                ? self::label($mobile)
                : self::label($mobile, 'frootbox-ai-mobile-only') . self::label($desktop, 'frootbox-ai-desktop-only');

            if ($node->tagName === 'img') {
                // Keep picture/source semantics and existing gallery containers intact.
                $host = $node->parentNode;
                if ($host instanceof \DOMElement && $host->tagName === 'picture') {
                    $host = $host->parentNode;
                    $target = $node->parentNode;
                }
                else {
                    $target = $node;
                }

                if ($host instanceof \DOMElement && $host->hasAttribute('data-frootbox-ai-host')) {
                    HtmlPageCrawler::create($host)->filter('[data-frootbox-ai-label]')->remove();
                }
                else {
                    $wrapper = $node->ownerDocument->createElement('span');
                    $wrapper->setAttribute('class', 'frootbox-ai-image');
                    $wrapper->setAttribute('data-frootbox-ai-host', '');
                    $target->parentNode->insertBefore($wrapper, $target);
                    $wrapper->appendChild($target);
                    $host = $wrapper;
                }
                HtmlPageCrawler::create($host)->append($label);

                for ($parent = $host->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
                    if ($parent->tagName === 'a' && $parent->hasAttribute('data-fancybox')) {
                        $parent->setAttribute('data-frootbox-ai', $mobile ?: $desktop);
                        break;
                    }
                }
            }
            else {
                $element->addClass('frootbox-ai-background');
                // role=img hides descendants; group keeps the existing accessible
                // name and exposes the disclosure as real text.
                if ($element->getAttribute('role') === 'img') {
                    $element->setAttribute('role', 'group');
                }
                $element->append($label);
            }
        });

        $prefix = '';
        $suffix = '';
        if ($crawler->filter('[data-frootbox-ai-label]')->count() && !$crawler->filter('style[data-frootbox-ai-styles]')->count()) {
            $assets = '<style data-frootbox-ai-styles>' . file_get_contents(__DIR__ . '/resources/ai-image-label.css') . '</style>';
            $head = $crawler->filter('head');
            if ($head->count()) {
                $head->append($assets);
            }
            else {
                $prefix = $assets;
            }
        }

        if ($crawler->filter('[data-frootbox-ai-label]')->count() && !$crawler->filter('script[data-frootbox-ai-script]')->count()) {
            $script = '<script data-frootbox-ai-script src="FILE:' . __DIR__ . '/resources/ai-image-label.js"></script>';
            $body = $crawler->filter('body');
            if ($body->count()) {
                $body->append($script);
            }
            else {
                $suffix = $script;
            }
        }

        if ($fragment) {
            return $crawler->filter('head')->getInnerHtml() . $crawler->filter('body')->getInnerHtml();
        }

        return $prefix . $crawler->saveHTML() . $suffix;
    }
}
