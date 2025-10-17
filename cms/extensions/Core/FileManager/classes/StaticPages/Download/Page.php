<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\StaticPages\Download;

class Page
{
    /**
     *
     */
    public function inline(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch file
        $file = $files->fetchById($get->get('f'));

        if ($file->isPrivate()) {
            http_response_code(401);
            die("Unauthorized");
        }

        $path = FILES_DIR . $file->getPath();

        if ($fp = fopen($path, "rb")) {
            $size = filesize($path);
            $length = $size;
            $start = 0;
            $end = $size - 1;
            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-$length");
            if (isset($_SERVER['HTTP_RANGE'])) {
                $c_start = $start;
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                if ($range == '-') {
                    $c_start = $size - substr($range, 1);
                } else {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: ".$length);
            $buffer = 1024 * 8;
            while(!feof($fp) && ($p = ftell($fp)) <= $end) {
                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(0);
                echo fread($fp, $buffer);
                flush();
            }
            fclose($fp);
            exit();
        } else {
            die('file not found');
        }
    }

    /**
     * 
     */
    public function serve(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch file
        $file = $files->fetchById($get->get('f'));

        if ($file->isPrivate()) {
            header('Location: ' . SERVER_PATH_PROTOCOL);
            exit;
        }

        header('Content-type: ' . $file->getType());
        header('Content-Disposition: attachment; filename="' . $file->getNameClean() . '"');
        
        readfile(FILES_DIR . $file->getPath());
        exit;
    }

    /**
     *
     */
    public function stream(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch file
        $file = $files->fetchById($get->get('f'));

        if ($file->isPrivate()) {
            http_response_code(401);
            die("Unauthorized");
        }

        /*
        header('Content-type: ' . $file->getType());
        header('Content-Disposition: attachment; filename="' . $file->getNameClean() . '"');

        readfile(FILES_DIR . $file->getPath());
        exit;
        */

        $ext = strtolower(pathinfo(FILES_DIR . $file->getPath(), PATHINFO_EXTENSION));

        $map = [
            'mp4'  => 'video/mp4',        // H.264 + AAC wird von allen Browsern unterstützt
            'm4v'  => 'video/x-m4v',
            'webm' => 'video/webm',
            'ogv'  => 'video/ogg',
            'mov'  => 'video/quicktime',
            'avi'  => 'video/x-msvideo',
            'wmv'  => 'video/x-ms-wmv',
            'flv'  => 'video/x-flv'
        ];

        $mimeType = $map[$ext] ?? 'application/octet-stream';

        $size = filesize(FILES_DIR . $file->getPath());
        $length = $size;
        $start  = 0;
        $end    = $size - 1;

        // Prüfen, ob ein Range-Header geschickt wurde
        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);

            if ($matches[1] !== '') $start = intval($matches[1]);
            if ($matches[2] !== '') $end   = intval($matches[2]);

            $length = $end - $start + 1;

            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$size");
        }

        header("Accept-Ranges: bytes");
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename=' . $file->getNameClean());
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $length);
        ob_clean();
        flush();

        $fp = fopen(FILES_DIR . $file->getPath(), 'rb');
        fseek($fp, $start);
        $bufferSize = 1024 * 8; // 8KB Buffer

        while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
            if ($pos + $bufferSize > $end) {
                $bufferSize = $end - $pos + 1;
            }
            echo fread($fp, $bufferSize);
            flush();
        }

        // readfile(FILES_DIR . $file->getPath());
        exit;
    }
}
