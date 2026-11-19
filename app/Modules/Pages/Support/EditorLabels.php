<?php

namespace App\Modules\Pages\Support;

/**
 * Translated labels for the JavaScript editors ("/" menu, placeholders).
 */
final class EditorLabels
{
    /** @return array<string, string> */
    public static function slash(): array
    {
        return [
            'text' => __('Text'),
            'h1' => __('Heading 1'),
            'h2' => __('Heading 2'),
            'h3' => __('Heading 3'),
            'bulletList' => __('Bullet list'),
            'orderedList' => __('Numbered list'),
            'taskList' => __('Task list'),
            'blockquote' => __('Quote'),
            'codeBlock' => __('Code block'),
            'table' => __('Table'),
            'hr' => __('Divider'),
            'toc' => __('Table of contents'),
            'noResults' => __('No matching block'),
        ];
    }

    /** @return array<string, string> */
    public static function placeholders(): array
    {
        return [
            'text' => __('text'),
            'code' => __('code'),
            'heading' => __('Heading'),
            'item' => __('List item'),
            'quote' => __('Quote'),
            'link' => __('link'),
        ];
    }
}
