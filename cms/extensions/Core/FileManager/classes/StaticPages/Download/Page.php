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

        /*
        header('Content-type: ' . $file->getType());
        header('Content-Disposition: attachment; filename="' . $file->getNameClean() . '"');

        readfile(FILES_DIR . $file->getPath());
        exit;
        */


        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $file->getNameClean());
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize(FILES_DIR . $file->getPath()));
        ob_clean();
        flush();
        readfile(FILES_DIR . $file->getPath());
        exit;
    }
}
