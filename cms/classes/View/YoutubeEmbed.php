<?php

namespace Frootbox\View;

class YoutubeEmbed
{
    /**
     * Convert shared video links to player URLs; leave existing embeds untouched.
     */
    public static function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));

        if ($parts === false || empty($parts['host'])
            || !in_array(strtolower($parts['scheme'] ?? 'https'), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            return $url;
        }

        $host = preg_replace('#^www\.#', '', strtolower($parts['host']));
        $path = trim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $parameters);

        if ($host === 'youtu.be') {
            $videoId = $path;
        }
        elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === 'watch') {
                $videoId = $parameters['v'] ?? null;
            }
            elseif (preg_match('#^(?:shorts|live)/([A-Za-z0-9_-]{11})$#', $path, $match)) {
                $videoId = $match[1];
            }
        }

        if (!isset($videoId) || !is_string($videoId) || !preg_match('#^[A-Za-z0-9_-]{11}$#', $videoId)) {
            return $url;
        }

        // Share timestamps use t; the embedded player expects start in seconds.
        parse_str($parts['fragment'] ?? '', $fragment);
        $timestamp = $parameters['t'] ?? $fragment['t'] ?? null;

        if (!isset($parameters['start']) && is_string($timestamp)) {
            if (ctype_digit($timestamp)) {
                $parameters['start'] = $timestamp;
            }
            elseif (preg_match('#^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$#', $timestamp, $match) && $timestamp !== '') {
                $parameters['start'] = (int) ($match[1] ?? 0) * 3600
                    + (int) ($match[2] ?? 0) * 60 + (int) ($match[3] ?? 0);
            }
        }

        unset($parameters['v'], $parameters['si'], $parameters['feature'], $parameters['t']);
        $parameters = array_filter($parameters, 'is_scalar');
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $embedHost = $host === 'youtube-nocookie.com' ? 'www.youtube-nocookie.com' : 'www.youtube.com';

        return 'https://' . $embedHost . '/embed/' . $videoId . ($query !== '' ? '?' . $query : '');
    }
}
