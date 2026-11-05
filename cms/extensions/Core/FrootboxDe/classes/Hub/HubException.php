<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * Something with frootbox.de went wrong. The message is German and meant
 * for the backend.
 */
final class HubException extends \RuntimeException
{
    public static function unreachable(string $url, string $reason): self
    {
        return new self('frootbox.de ist nicht erreichbar (' . $url . '): ' . $reason);
    }

    public static function refused(int $status, string $detail): self
    {
        return new self('frootbox.de lehnt ab (' . $status . '): ' . $detail);
    }

    public static function badAnswer(string $field): self
    {
        return new self('frootbox.de antwortet unerwartet (' . $field . ').');
    }

    public static function notConnected(): self
    {
        return new self('Die Website ist nicht mit frootbox.de verbunden.');
    }

    public static function connected(): self
    {
        return new self('Die Website ist schon mit frootbox.de verbunden.');
    }

    public static function noRequest(): self
    {
        return new self('Es gibt keine offene Anfrage zu diesem Code. Bitte noch einmal verbinden.');
    }

    public static function local(string $site): self
    {
        return new self('Die Adresse ' . $site . ' ist lokal, frootbox.de kann sie nicht erreichen. Bitte live oder auf einem Testsystem verbinden.');
    }

    public static function badHubUrl(string $url): self
    {
        return new self('Die Adresse von frootbox.de in der Konfiguration ist ungültig: ' . $url);
    }

    public static function notWritable(string $file): self
    {
        return new self('Die Datei ' . basename($file) . ' neben localconfig.php lässt sich nicht schreiben.');
    }

    public static function unreadable(string $file): self
    {
        return new self('Die Datei ' . basename($file) . ' neben localconfig.php ist beschädigt.');
    }
}
