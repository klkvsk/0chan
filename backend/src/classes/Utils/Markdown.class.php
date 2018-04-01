<?php

class Markdown {
    public static function format($message)
    {
        if (empty($message)) {
            return '';
        }

        $message = htmlspecialchars($message);

        $takeouts = [
            [
                'type'      => 'code',
                'regexp'    => '/```([\s\S]+?)```/',
                'replace'   => '<pre>$1</pre>',
            ],
            [
                'type'      => 'inline',
                'regexp'    => '/`([\s\S]+?)`/',
                'replace'   => '<code>$1</code>',
            ],
            [
                'type'      => 'link',
                'regexp'    => '@((https?|ftp)://(\w[\w\-\.]+\w)([:\d]+)?(/(?:[\w\+\?\(\)\#\[\]\-/=_%&\.\,;~]+[\w/])?)?)@',
                'replace'   => '<a href="$1" target="_blank">$1</a>',
            ],
            [
                'type'      => 'postref',
                'regexp'    => '/&gt;&gt;([0-9]+)/',
                'replace'   => '<a data-post="$1">&gt;&gt;$1</a>',
            ],
        ];

        $takeoutStore = [];

        foreach ($takeouts as $takeout) {
            $message = preg_replace_callback(
                $takeout['regexp'],
                function ($match) use ($takeout, &$takeoutStore) {
                    $id = '[[' . uniqid($takeout['type']) . ']]';
                    $takeoutStore[$id] = str_replace('$1', $match[1], $takeout['replace']);
                    return $id;
                },
                $message
            );
        }

        $message = preg_replace(   '/(^|\s)--($|\s)/', '$1&mdash;$2', $message);
        $message = preg_replace('/^&gt;\s*(.+)$/m',   '<blockquote>&gt; $1</blockquote>', $message);
        $message = preg_replace('/%%([\s\S]+?)%%/', '<mark>$1</mark>', $message);
        $message = preg_replace('/\*\*([^\*]+)\*\*/', '<b>$1</b>', $message);
        $message = preg_replace(  '/\*([^\*]+)\*/',   '<i>$1</i>', $message);
        $message = preg_replace('/(?<!\S)\-(?!\s)(.+?)(?<!\s)\-(?!\S)/u',  '<del>$1</del>', $message);
        $message = preg_replace(   '/`(.+?)`/', '<code>$1</code>', $message);
        $message = preg_replace(   '/(?<![\w\/])(0x[a-f0-9]{8})(?!\w)/', '<address>$1</address>', $message);

        $message = nl2br($message);

        foreach ($takeoutStore as $find => $replace) {
            $message = str_replace($find, $replace, $message);
        }

        return $message;
    }
}