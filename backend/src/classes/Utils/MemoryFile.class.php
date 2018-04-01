<?php

class MemoryFile {

    protected static $files = [];

    protected $filename = null;
    protected $position = 0;

    public static function registerWrapper() {
        stream_wrapper_register('mem', __CLASS__);
    }

    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->filename = $url["host"];
        $this->position = 0;

        if (!isset(self::$files[$this->filename])) {
            self::$files[$this->filename] = '';
        }

        return true;
    }

    function stream_read($count)
    {
        $ret = substr(self::$files[$this->filename], $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    function stream_write($data)
    {
        $left = substr(self::$files[$this->filename], 0, $this->position);
        $right = substr(self::$files[$this->filename], $this->position + strlen($data));
        self::$files[$this->filename] = $left . $data . $right;
        $this->position += strlen($data);
        return strlen($data);
    }

    function stream_tell()
    {
        return $this->position;
    }

    function stream_eof()
    {
        return $this->position >= strlen(self::$files[$this->filename]);
    }

    function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen(self::$files[$this->filename]) && $offset >= 0) {
                    $this->position = $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                    $this->position += $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case SEEK_END:
                if (strlen(self::$files[$this->filename]) + $offset >= 0) {
                    $this->position = strlen(self::$files[$this->filename]) + $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            default:
                return false;
        }
    }

    function stream_metadata($path, $option, $var)
    {
        if($option == STREAM_META_TOUCH) {
            $url = parse_url($path);
            $filename = $url["host"];
            if(!isset(self::$files[$filename])) {
                self::$files[$filename] = '';
            }
            return true;
        }
        return false;
    }

    function stream_stat() {
        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => strlen(self::$files[$this->filename]),
            'atime' => 0,
            'mtime' => 0,
            'blksize' => 0,
            'blocks' => 0
        ];
    }
}
