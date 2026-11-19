<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for admin-authored lesson content.
 *
 * Keeps basic formatting tags, drops every attribute except safe links,
 * removes dangerous elements entirely and unwraps any other unknown tag.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'h2', 'h3', 'h4', 'p', 'br', 'hr', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u',
        'code', 'pre', 'blockquote', 'a', 'span', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Removed together with their content. */
    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea',
        'select', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'frame', 'frameset',
    ];

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown tag: keep its (already cleaned) children, drop the tag itself.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($tag === 'a' && preg_match('#^(https?://|mailto:)#i', $href)) {
            $element->setAttribute('href', $href);
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
            $element->setAttribute('target', '_blank');
        }
    }
}
