<?php
/**
 * @package     com_r3dcomments
 * @version     6.1.21
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvorak, <dev@r3d.de> - https://extensions.r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\Helper;

defined('_JEXEC') or die;

/**
 * Render comment markup in a controlled way.
 */
final class MarkupHelper
{
    public static function renderCommentBody(string $comment, bool $guestMode = false): string
    {
        $comment = self::normalizeLineEndings($comment);

        if ($comment === '') {
            return '';
        }

        if ($guestMode) {
            $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
            $comment = self::renderBbcode($comment, true, true);
            $comment = nl2br($comment, false);

            return $comment;
        }

        return self::renderBbcode($comment, false, false);
    }

    public static function renderPreviewText(string $comment): string
    {
        $comment = self::normalizeLineEndings($comment);

        if ($comment === '') {
            return '';
        }

        $comment = preg_replace_callback(
            '~\[quote(?:=([^\]\r\n]+))?\](.*?)\[/quote\]~is',
            static function (array $matches): string {
                $author = trim((string) ($matches[1] ?? ''));
                $body = self::renderPreviewText((string) ($matches[2] ?? ''));

                if ($author !== '') {
                    return $author . ': ' . $body;
                }

                return $body;
            },
            $comment
        );

        $comment = preg_replace('~\[(b|i|u|s)\](.*?)\[/\1\]~is', '$2', $comment);
        $comment = preg_replace('~\[br\s*/?\]~i', ' ', $comment);
        $comment = strip_tags($comment);
        $comment = html_entity_decode($comment, ENT_QUOTES, 'UTF-8');
        $comment = preg_replace("/\s+/u", ' ', $comment);

        return trim((string) $comment);
    }

    private static function renderBbcode(string $comment, bool $guestMode, bool $alreadyEscaped): string
    {
        $comment = preg_replace('~\[br\s*/?\]~i', '<br>', $comment);
        $comment = preg_replace('~\[(b|i|u|s)\](.*?)\[/\1\]~is', '<$1>$2</$1>', $comment);

        $comment = preg_replace_callback(
            '~\[quote(?:=([^\]\r\n]+))?\](.*?)\[/quote\]~is',
            static function (array $matches) use ($guestMode, $alreadyEscaped): string {
                $author = trim((string) ($matches[1] ?? ''));
                $body   = self::renderBbcode(self::normalizeLineEndings((string) ($matches[2] ?? '')), $guestMode, $alreadyEscaped);

                $cite = $author !== ''
                    ? '<cite>— ' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</cite>'
                    : '';

                return '<blockquote><p>' . $body . '</p>' . $cite . '</blockquote><p></p>';
            },
            $comment
        );

        return $comment;
    }

    private static function normalizeLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }
}
