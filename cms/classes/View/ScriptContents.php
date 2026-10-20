<?php

namespace Frootbox\View;

/**
 * Temporarily replace script bodies, leaving their tags and attributes accessible.
 */
class ScriptContents
{
    private array $contents = [];
    private string $prefix;

    public function __construct()
    {
        $this->prefix = 'FROOTBOX_SCRIPT_' . bin2hex(random_bytes(16)) . '_';
    }

    public function placeholder(string $content): string
    {
        $token = $this->prefix . count($this->contents);
        $this->contents[$token] = $content;
        return $token;
    }

    public function restore(string $html): string
    {
        return strtr($html, $this->contents);
    }

    public function protect(string $html): string
    {
        $offset = $copied = 0;
        $result = '';
        // Consume whole tags (including quoted attributes), comments and declarations.
        // A script-looking string inside one of these must not become a script.
        $tagPattern = '~<!--.*?(?:-->|$)|<!\[CDATA\[.*?(?:\]\]>|$)|<![^>]*>|<\?.*?(?:\?>|$)|<(/?)([a-z][a-z0-9:-]*)(?=[\x20\t\r\n\f/>])(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*>~is';

        while (preg_match($tagPattern, $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $offset = $match[0][1] + strlen($match[0][0]);
            $tag = strtolower($match[2][0] ?? '');

            if (($match[1][0] ?? '') !== '' || $tag === '') {
                continue;
            }

            if ($tag === 'plaintext') {
                break;
            }

            if ($tag === 'script') {
                [$end, $closingLength] = $this->scriptEnd($html, $offset);
                $result .= substr($html, $copied, $offset - $copied);
                $result .= $this->placeholder(substr($html, $offset, $end - $offset));
                $copied = $end;
                $offset = $end + $closingLength;
            }
            elseif (in_array($tag, ['style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes'], true)) {
                // HTML raw-text/RCDATA elements cannot contain actual script elements.
                if (!preg_match('~</' . $tag . '\s*>~i', $html, $end, PREG_OFFSET_CAPTURE, $offset)) {
                    break;
                }
                $offset = $end[0][1] + strlen($end[0][0]);
            }
        }

        return $result . substr($html, $copied);
    }

    /**
     * Respect HTML's escaped and double-escaped script states, including legacy
     * <!-- <script> ... </script> --> wrappers. JavaScript quotes do not affect
     * HTML script termination.
     *
     * @return array{int, int}
     */
    private function scriptEnd(string $html, int $offset): array
    {
        $state = 'data';

        while (preg_match('~<!--|-->|</?script(?=[\x20\t\r\n\f/>])~i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $token = strtolower($match[0][0]);
            $start = $match[0][1];
            $offset = $start + strlen($match[0][0]);

            if ($token === '<!--' && $state === 'data') {
                $state = 'escaped';
            }
            elseif ($token === '-->') {
                $state = 'data';
            }
            elseif ($token === '<script' && $state === 'escaped') {
                $state = 'double';
            }
            elseif ($token === '</script') {
                if ($state === 'double') {
                    $state = 'escaped';
                }
                elseif (preg_match('~\G</script(?=[\x20\t\r\n\f/>])(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*>~i', $html, $closing, 0, $start)) {
                    return [$start, strlen($closing[0])];
                }
            }
        }

        return [strlen($html), 0];
    }
}
