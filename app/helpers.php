<?php

if (!function_exists('highlightText')) {
    function highlightText($text, $search)
    {
        if (!$search) {
            return e($text);
        }

        $highlighted = preg_replace(
            '/(' . preg_quote($search, '/') . ')/i',
            '<span class="highlight">$1</span>',
            e($text)
        );

        return $highlighted;
    }
}
