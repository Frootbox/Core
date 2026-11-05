<?php

declare(strict_types=1);

namespace Frootbox\Ext\Core\FrootboxDe\Hub;

/**
 * The status document of version 1, as Frootbox 2 sends it from
 * status.show: technical values only, no names, emails, contents or
 * addresses of pages. What Frootbox 1 does not know is null, frootbox.de
 * shows it as "not available": the queue, drafts and comments do not exist
 * here, and the cron of Frootbox 1 runs no scheduled jobs yet.
 */
class StatusDocument
{
    private const CONTENT_TABLES = ['pages', 'blocks', 'content_elements', 'content_texts', 'content_widgets'];

    public function __construct(
        private readonly \Frootbox\Db\Db $db,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $backup = $this->backup();

        return [
            'schema' => 1,
            'system' => ['name' => Hub::SYSTEM, 'version' => Hub::version()],
            'php' => ['version' => PHP_VERSION],
            'database' => $this->database(),
            'extensions' => $this->extensions(),
            'cron' => null,
            'queue' => null,
            'backup' => [
                'last' => $backup === null ? null : date(DATE_ATOM, $backup),
                'ageHours' => $backup === null ? null : intdiv(max(0, time() - $backup), 3600),
            ],
            'storage' => $this->storage(),
            'drafts' => null,
            'comments' => null,
            'lastEdit' => $this->lastEdit(),
        ];
    }

    /**
     * @return array{kind: string, version: string|null}|null
     */
    private function database(): ?array
    {
        try {
            $version = (string) $this->db->query('SELECT VERSION()')->fetchColumn();
        }
        catch (\Throwable) {
            return null;
        }

        $kind = stripos($version, 'mariadb') !== false ? 'mariadb' : 'mysql';
        $number = preg_match('~^\d+(\.\d+)*~', $version, $match) === 1 ? $match[0] : null;

        return ['kind' => $kind, 'version' => $number];
    }

    /**
     * @return list<array{name: string, version: string|null}>
     */
    private function extensions(): array
    {
        try {
            $rows = $this->db->query('SELECT vendorId, extensionId, version FROM extensions WHERE isactive = 1 ORDER BY vendorId, extensionId')->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Throwable) {
            return [];
        }

        $list = [];

        foreach ($rows as $row) {
            $list[] = [
                'name' => $row['vendorId'] . '/' . $row['extensionId'],
                'version' => $row['version'] === null || $row['version'] === '' ? null : (string) $row['version'],
            ];
        }

        return $list;
    }

    /**
     * Frootbox 1 dumps the database when someone signs in to the backend,
     * into files/backup/database/. The newest file counts.
     */
    private function backup(): ?int
    {
        $files = glob(FILES_DIR . 'backup/database/*-sql-export.sql');
        $newest = null;

        foreach ($files === false ? [] : $files as $file) {
            $time = filemtime($file);

            if ($time !== false && ($newest === null || $time > $newest)) {
                $newest = $time;
            }
        }

        return $newest;
    }

    /**
     * @return array{free: int|null, total: int|null}
     */
    private function storage(): array
    {
        $free = @disk_free_space(FILES_DIR);
        $total = @disk_total_space(FILES_DIR);

        return ['free' => $free === false ? null : (int) $free, 'total' => $total === false ? null : (int) $total];
    }

    /**
     * The newest "updated" of pages and contents.
     */
    private function lastEdit(): ?string
    {
        $newest = null;

        foreach (self::CONTENT_TABLES as $table) {
            try {
                $value = $this->db->query('SELECT MAX(updated) FROM `' . $table . '`')->fetchColumn();
            }
            catch (\Throwable) {
                continue;
            }

            if (is_string($value) && $value !== '' && ($newest === null || $value > $newest)) {
                $newest = $value;
            }
        }

        if ($newest === null) {
            return null;
        }

        $time = strtotime($newest);

        return $time === false ? null : date(DATE_ATOM, $time);
    }
}
