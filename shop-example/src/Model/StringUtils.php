<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample\Model;

class StringUtils
{
    /**
     * Lower-cases some text.
     */
    public function lower(string $text): string
    {
        return strtolower($text);
    }

    /**
     * Upper-cases some text.
     */
    public function upper(string $text): string
    {
        return strtoupper($text);
    }
}
