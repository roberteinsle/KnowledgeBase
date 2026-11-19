<?php

namespace App\Modules\Pages\Services;

use DOMDocument;
use DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders user Markdown. Raw HTML in the source is escaped, unsafe link
 * schemes (javascript:, data: …) are dropped. A line "[TOC]" (also the GitLab
 * form "[[_TOC_]]") becomes a table of contents of the headings H1–H3.
 */
final class MarkdownRenderer
{
    public const TOC_MARKER = '[TOC]';

    private ?MarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        return $this->converter()->convert($this->normalizeTocMarker($markdown))->getContent();
    }

    /**
     * Headings H2/H3 of rendered HTML for the "On this page" outline.
     *
     * @return list<array{level: int, id: string, text: string}>
     */
    public function outline(string $html): array
    {
        if (! str_contains($html, 'heading-anchor')) {
            return [];
        }

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_NOERROR | LIBXML_NONET);
        $xpath = new DOMXPath($dom);
        $outline = [];

        foreach ($xpath->query('//h2|//h3') ?: [] as $heading) {
            $anchors = $xpath->query('.//a[contains(@class, "heading-anchor")]', $heading);
            $anchor = $anchors === false ? null : $anchors->item(0);
            if (! $anchor instanceof \DOMElement) {
                continue;
            }
            $text = trim(str_replace($anchor->textContent, '', $heading->textContent));
            if ($text !== '') {
                $outline[] = ['level' => (int) substr($heading->nodeName, 1), 'id' => $anchor->getAttribute('id'), 'text' => $text];
            }
        }

        return $outline;
    }

    /** The rich editor may save the marker escaped ("\[TOC\]"); GitLab uses "[[_TOC_]]". */
    private function normalizeTocMarker(string $markdown): string
    {
        return preg_replace('/^[ \t]*(?:\\\\?\[\\\\?\[?_?TOC_?\\\\?\]\\\\?\]?)[ \t]*$/mi', self::TOC_MARKER, $markdown) ?? $markdown;
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
            'heading_permalink' => [
                'symbol' => '#',
                'html_class' => 'heading-anchor',
                'id_prefix' => 'h',
                'fragment_prefix' => 'h',
                'insert' => 'after',
                'aria_hidden' => true,
            ],
            'table_of_contents' => [
                'html_class' => 'table-of-contents',
                'position' => 'placeholder',
                'placeholder' => self::TOC_MARKER,
                'style' => 'bullet',
                'min_heading_level' => 1,
                'max_heading_level' => 3,
                'normalize' => 'relative',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableOfContentsExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
