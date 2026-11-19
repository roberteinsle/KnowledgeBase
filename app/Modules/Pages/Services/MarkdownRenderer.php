<?php

namespace App\Modules\Pages\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders user Markdown. Raw HTML in the source is escaped, unsafe link
 * schemes (javascript:, data: …) are dropped.
 */
final class MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        return $this->converter()->convert($markdown)->getContent();
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
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
