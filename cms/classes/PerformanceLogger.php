<?php

namespace Frootbox;

/** Optional, request-local timings. Never records query strings, cookies or bodies. */
class PerformanceLogger
{
    private bool $enabled = false;
    private string $directory = '';
    private int $started;
    private int $phaseStarted;
    private string $phase = 'bootstrap';
    private array $phases = [];
    private array $elements = [];
    private array $context = [];
    private int $retentionDays;
    private int $maxBytes;
    private bool $finished = false;

    public function __construct(array $options, int $started, string $documentRoot)
    {
        $this->started = $this->phaseStarted = $started;
        $this->retentionDays = max(1, (int) ($options['retentionDays'] ?? 14));
        $this->maxBytes = max(1, (int) ($options['maxDailyBytes'] ?? 52428800));

        if (empty($options['enabled'])) {
            return;
        }

        // The directory must be provisioned explicitly, outside the public document root.
        $directory = (!empty($options['directory']) ? realpath($options['directory']) : false);
        $root = realpath($documentRoot);
        if (!$directory || !$root || $directory === $root
            || strpos($directory . '/', rtrim($root, '/') . '/') === 0
            || !is_dir($directory) || !is_writable($directory)) {
            error_log('Performance logging disabled: configure a writable directory outside the document root.');
            return;
        }

        $this->directory = $directory;
        $this->enabled = true;
        $this->context = [
            'request_id' => bin2hex(random_bytes(12)),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'release' => (string) ($options['release'] ?? ''),
            'cache' => 'disabled',
        ];
        if (!headers_sent()) {
            header('X-Request-ID: ' . $this->context['request_id']);
        }
        register_shutdown_function([$this, 'finish']);
    }

    public function phase(string $name): void
    {
        if (!$this->enabled) {
            return;
        }
        $now = hrtime(true);
        $this->phases[$this->phase] = ($this->phases[$this->phase] ?? 0) + ($now - $this->phaseStarted) / 1e6;
        $this->phase = $name;
        $this->phaseStarted = $now;
    }

    public function context(array $values): void
    {
        if ($this->enabled) {
            $this->context = array_merge($this->context, array_intersect_key($values, array_flip(['page_id', 'language', 'cache'])));
        }
    }

    public function element(int $id, string $class, string $action, int $started): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->elements[] = [
            'id' => $id, 'class' => $class, 'action' => $action,
            'ms' => round((hrtime(true) - $started) / 1e6, 3),
        ];
        // Keep only the slowest 20 elements to bound each log record.
        usort($this->elements, static fn ($a, $b) => $b['ms'] <=> $a['ms']);
        $this->elements = array_slice($this->elements, 0, 20);
    }

    public function finish(): void
    {
        if (!$this->enabled || $this->finished) {
            return;
        }
        $this->finished = true;
        $error = error_get_last();
        $fatal = $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
        $this->phase('shutdown');
        $record = array_merge($this->context, [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'status' => http_response_code() ?: ($fatal ? 500 : 200),
            'fatal' => (bool) $fatal,
            'editor' => defined('IS_EDITOR') && IS_EDITOR,
            'logged_in' => defined('IS_LOGGED_IN') && IS_LOGGED_IN,
            'total_ms' => round((hrtime(true) - $this->started) / 1e6, 3),
            'phases_ms' => array_map(static fn ($ms) => round($ms, 3), $this->phases),
            'slowest_elements' => $this->elements,
            'peak_memory_bytes' => memory_get_peak_usage(true),
        ]);
        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($line === false) {
            return;
        }

        // Logging must not change the website response, even on disk/permission failures.
        try {
            $this->write($line . "\n");
        }
        catch (\Throwable $exception) {
            error_log('Performance log write failed.');
        }
    }

    private function write(string $line): void
    {
        $file = $this->directory . '/performance-' . gmdate('Y-m-d') . '.jsonl';
        $stream = @fopen($file, 'ab');
        if ($stream === false) {
            error_log('Performance log could not be opened.');
            return;
        }
        try {
            if (!@flock($stream, LOCK_EX | LOCK_NB)) {
                return; // Do not delay a request waiting for the logger.
            }
            $size = fstat($stream)['size'];
            if ($size + strlen($line) <= $this->maxBytes) {
                @fwrite($stream, $line);
            }
            if ($size === 0) {
                @chmod($file, 0600);
                $cutoff = gmdate('Y-m-d', time() - ($this->retentionDays - 1) * 86400);
                foreach (glob($this->directory . '/performance-????-??-??.jsonl') ?: [] as $oldFile) {
                    if (preg_match('/^performance-(\d{4}-\d{2}-\d{2})\.jsonl$/', basename($oldFile), $match)
                        && $match[1] < $cutoff) {
                        @unlink($oldFile);
                    }
                }
            }
        }
        finally {
            fclose($stream);
        }
    }
}
