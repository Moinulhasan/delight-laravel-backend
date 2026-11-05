<?php

namespace App\Support;

class YouTube
{
    private const ID = '[A-Za-z0-9_-]{11}';

    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];

    /**
     * The 11-character ID from anything an editor might paste: a share link (youtu.be/ID?si=...),
     * a browser link, an embed, Shorts or Live link, or the bare ID. Null for anything else,
     * including lookalike links on other sites.
     */
    public static function idFrom(?string $input): ?string
    {
        $input = trim((string) $input);

        if (preg_match('/^'.self::ID.'$/', $input)) {
            return $input;
        }

        $url = parse_url(preg_match('#^https?://#i', $input) ? $input : "https://{$input}");
        $host = strtolower($url['host'] ?? '');
        $path = $url['path'] ?? '';

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            return preg_match('#^/('.self::ID.')/?$#', $path, $match) ? $match[1] : null;
        }

        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        if (preg_match('#^/(?:embed|shorts|live|v)/('.self::ID.')/?$#', $path, $match)) {
            return $match[1];
        }

        parse_str($url['query'] ?? '', $query);
        $id = $query['v'] ?? null;

        return $path === '/watch' && is_string($id) && preg_match('/^'.self::ID.'$/', $id) ? $id : null;
    }
}
