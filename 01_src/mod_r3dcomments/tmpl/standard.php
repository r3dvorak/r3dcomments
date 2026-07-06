<?php
/**
 * @package     mod_r3dcomments
 * @version     6.1.10
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU GPL v2 or later
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Captcha\Captcha;

$context = $displayData['context'] ?? 'com_content.article';
$itemId  = (int) ($displayData['item_id'] ?? 0);

if (!$itemId) {
    return;
}

$app  = Factory::getApplication();
$user = $app->getIdentity();
$requestLang = $app->input->getCmd('lang', '');
$langForPost = $requestLang !== '' ? $requestLang : strtolower(substr((string) Factory::getLanguage()->getTag(), 0, 2));
$componentParams = $app->getParams('com_r3dcomments');
$honeypotEnabled = (int) $componentParams->get('guest_honeypot_enabled', 1) === 1;
$honeypotField   = trim((string) $componentParams->get('guest_honeypot_field', 'website'));
$captchaMode     = (string) $componentParams->get('guest_captcha_mode', 'off');
$defaultCaptcha  = (string) Factory::getConfig()->get('captcha', '');
$showGuestCaptcha = $user->guest && $captchaMode === 'always' && $defaultCaptcha !== '' && $defaultCaptcha !== '0';
$isEnglish = str_starts_with(strtolower((string) Factory::getLanguage()->getTag()), 'en');
$translateFallback = static function (string $key, string $de, string $en) use ($isEnglish): string {
    $text = Text::_($key);

    return $text !== $key ? $text : ($isEnglish ? $en : $de);
};

try {
    /** @var \Joomla\Component\R3dcomments\Site\Extension\R3dcommentsComponent $component */
    $component  = $app->bootComponent('com_r3dcomments');
    $mvcFactory = $component->getMVCFactory();

    /** @var \Joomla\Component\R3dcomments\Site\Model\CommentsModel $commentsModel */
    $commentsModel = $mvcFactory->createModel('Comments', 'Site', ['ignore_request' => true]);
    $commentsModel->setState('filter.context', $context);
    $commentsModel->setState('filter.item_id',  $itemId);
    $items = $commentsModel->getItems();

    /** @var \Joomla\Component\R3dcomments\Site\Model\CommentModel $commentModel */
    $commentModel = $mvcFactory->createModel('Comment', 'Site', ['ignore_request' => true]);
    $form         = $commentModel->getForm();
} catch (\Throwable $e) {
    if ($app->isClient('administrator')) {
        $app->enqueueMessage('R3D Comments module error: ' . $e->getMessage(), 'error');
    }

    return;
}

$canEditComment = function ($comment) use ($user): bool {
    if (!$user || !$user->id) {
        return false;
    }

    if ($user->authorise('core.edit', 'com_r3dcomments')) {
        return true;
    }

    if (
        (int) $comment->created_by === (int) $user->id
        && $user->authorise('core.edit.own', 'com_r3dcomments')
    ) {
        return true;
    }

    return false;
};

// Render dates in Joomla/user timezone instead of raw DB/UTC value.
$formatDisplayDate = static function (?string $rawDate) use ($app): string {
    if (!$rawDate) {
        return '';
    }

    try {
        $date = Factory::getDate($rawDate, 'UTC');
        $tz = $app->getIdentity()->getParam('timezone');
        if (!$tz) {
            $tz = Factory::getConfig()->get('offset', 'UTC');
        }
        if ($tz) {
            $date->setTimezone(new \DateTimeZone((string) $tz));
        }
        return $date->format(Text::_('DATE_FORMAT_LC2'), true);
    } catch (\Throwable $e) {
        return (string) $rawDate;
    }
};

static $r3dcommentsInlineStylesPrinted = false;

?>
<div class="r3dcomments-wrapper r3dcomments-wrapper-standard">
    <?php if (!$r3dcommentsInlineStylesPrinted) : ?>
        <style>
            .r3dcomments-wrapper-uikit .r3dcomment-form-wrap,
            .r3dcomments-wrapper-standard .r3dcomment-form-wrap {
                margin-top: 2.5rem;
                padding-top: 1.75rem;
                border-top: 1px solid rgba(0, 0, 0, 0.12);
            }

            .r3dcomments-wrapper-uikit .r3dcomment-children,
            .r3dcomments-wrapper-standard .r3dcomment-children {
                margin-top: 1rem;
                margin-left: 1.5rem;
                padding-left: 1rem;
                border-left: 3px solid rgba(0, 0, 0, 0.12);
            }

            .r3dcomments-wrapper-uikit .r3dcomment-item-child,
            .r3dcomments-wrapper-standard .r3dcomment-item-child {
                margin-bottom: 0.75rem;
                background: rgba(0, 0, 0, 0.02);
            }

            .r3dcomments-wrapper-uikit .r3dcomment-item-root,
            .r3dcomments-wrapper-standard .r3dcomment-item-root {
                margin-bottom: 2rem;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-item-child:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-item-child:last-child {
                margin-bottom: 0;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-meta,
            .r3dcomments-wrapper-uikit .r3dcomment-actions,
            .r3dcomments-wrapper-uikit .r3dcomment-body,
            .r3dcomments-wrapper-standard .r3dcomment-meta,
            .r3dcomments-wrapper-standard .r3dcomment-actions,
            .r3dcomments-wrapper-standard .r3dcomment-body,
            .r3dcomments-wrapper-standard .r3dcomment-field,
            .r3dcomments-wrapper-standard .r3dcomment-reply-indicator {
                margin-bottom: 0.75rem;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-meta:last-child,
            .r3dcomments-wrapper-uikit .r3dcomment-actions:last-child,
            .r3dcomments-wrapper-uikit .r3dcomment-body:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-meta:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-actions:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-body:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-field:last-child,
            .r3dcomments-wrapper-standard .r3dcomment-reply-indicator:last-child {
                margin-bottom: 0;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-actions,
            .r3dcomments-wrapper-standard .r3dcomment-actions {
                margin-top: 1rem;
            }

            .r3dcomments-wrapper-uikit .r3d-reply-btn,
            .r3dcomments-wrapper-uikit .r3d-quote-btn,
            .r3dcomments-wrapper-standard .r3d-reply-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.45rem 0.85rem;
                border: 1px solid rgba(13, 110, 253, 0.28);
                border-radius: 999px;
                background: rgba(13, 110, 253, 0.08);
                color: #0b5ed7;
                font-size: 0.95rem;
                font-weight: 600;
                line-height: 1.2;
                text-decoration: none;
                transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            }

            .r3dcomments-wrapper-uikit .r3d-reply-btn:hover,
            .r3dcomments-wrapper-uikit .r3d-reply-btn:focus,
            .r3dcomments-wrapper-uikit .r3d-quote-btn:hover,
            .r3dcomments-wrapper-uikit .r3d-quote-btn:focus,
            .r3dcomments-wrapper-standard .r3d-reply-btn:hover,
            .r3dcomments-wrapper-standard .r3d-reply-btn:focus {
                background: rgba(13, 110, 253, 0.14);
                border-color: rgba(13, 110, 253, 0.45);
                color: #084298;
                text-decoration: none;
            }

            .r3dcomments-wrapper-uikit .r3d-submit-btn,
            .r3dcomments-wrapper-standard .r3d-submit-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 9rem;
                padding: 0.75rem 1.2rem;
                border: 1px solid #0b5ed7;
                border-radius: 0.85rem;
                background: #0b5ed7;
                color: #fff;
                font-size: 1rem;
                font-weight: 700;
                line-height: 1.2;
                text-decoration: none;
                box-shadow: 0 10px 24px rgba(11, 94, 215, 0.18);
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease;
                cursor: pointer;
            }

            .r3dcomments-wrapper-uikit .r3d-submit-btn:hover,
            .r3dcomments-wrapper-uikit .r3d-submit-btn:focus,
            .r3dcomments-wrapper-standard .r3d-submit-btn:hover,
            .r3dcomments-wrapper-standard .r3d-submit-btn:focus {
                background: #084298;
                border-color: #084298;
                color: #fff;
                transform: translateY(-1px);
                box-shadow: 0 12px 28px rgba(11, 94, 215, 0.24);
            }

            .r3dcomments-wrapper-uikit .r3dcomment-form-wrap > h4,
            .r3dcomments-wrapper-standard .r3dcomment-form-wrap > h4 {
                margin-bottom: 1.25rem;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-user-hint,
            .r3dcomments-wrapper-standard .r3dcomment-user-hint {
                margin: -0.5rem 0 1rem;
                color: rgba(0, 0, 0, 0.68);
                font-size: 0.95rem;
            }

            .r3dcomments-wrapper-standard .r3dcomment-item {
                margin: 0 0 1.25rem;
                padding: 1rem;
                border: 1px solid rgba(0, 0, 0, 0.12);
                border-radius: 0.5rem;
                background: #fff;
            }

            .r3dcomments-wrapper-standard .r3dcomment-reply-indicator {
                padding: 0.75rem 1rem;
                border-left: 3px solid #0d6efd;
                background: rgba(13, 110, 253, 0.08);
            }

            .r3dcomments-wrapper-uikit .r3dcomment-body blockquote,
            .r3dcomments-wrapper-standard .r3dcomment-body blockquote {
                margin: 0 0 0.02rem;
                padding: 0.06rem 0 0.06rem 0.5rem;
                border-left: 1px solid rgba(0, 0, 0, 0.2);
                border-radius: 0;
                background: transparent;
                color: #1f2d3d;
                font-size: 16px;
                line-height: 28px;
                font-style: italic;
                font-weight: 400;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-body blockquote p,
            .r3dcomments-wrapper-standard .r3dcomment-body blockquote p {
                margin: 0;
                font-size: inherit;
                line-height: inherit;
                font-style: inherit;
                font-weight: inherit;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-body blockquote cite,
            .r3dcomments-wrapper-standard .r3dcomment-body blockquote cite {
                display: block;
                margin-top: 0.05rem;
                margin-left: 0.25rem;
                font-style: italic;
                font-weight: 400;
                font-size: inherit;
                line-height: inherit;
                opacity: 0.8;
            }

            .r3dcomments-wrapper-standard #r3dcomment-form button {
                margin-top: 0.5rem;
            }
            .r3dcomments-wrapper .r3d-preview-btn {
                margin-left: 0.5rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 9rem;
                padding: 0.75rem 1.2rem;
                border: 1px solid #198754;
                border-radius: 0.85rem;
                background: #198754;
                color: #fff;
                font-size: 1rem;
                font-weight: 700;
                line-height: 1.2;
                text-decoration: none;
                box-shadow: 0 10px 24px rgba(25, 135, 84, 0.18);
                transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease;
                cursor: pointer;
            }
            .r3dcomments-wrapper .r3d-preview-btn:hover,
            .r3dcomments-wrapper .r3d-preview-btn:focus {
                background: #157347;
                border-color: #157347;
                color: #fff;
                transform: translateY(-1px);
                box-shadow: 0 12px 28px rgba(25, 135, 84, 0.24);
            }
            .r3d-preview-modal {
                position: fixed;
                inset: 0;
                z-index: 2100;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(0, 0, 0, 0.45);
                padding: 1rem;
            }
            .r3d-preview-modal.is-open {
                display: flex;
            }
            .r3d-preview-dialog {
                width: min(760px, 100%);
                max-height: 86vh;
                overflow: auto;
                background: #fff;
                border-radius: 0.8rem;
                padding: 1rem 1.2rem;
                box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
            }
            #r3d-preview-content blockquote {
                margin: 0 0 0.02rem;
                padding: 0.06rem 0 0.06rem 0.5rem;
                border-left: 1px solid rgba(0, 0, 0, 0.2);
                border-radius: 0;
                background: transparent;
                color: #1f2d3d;
                font-size: 16px;
                line-height: 28px;
                font-style: italic;
                font-weight: 400;
            }
            #r3d-preview-content blockquote p {
                margin: 0;
                font-size: inherit;
                line-height: inherit;
                font-style: inherit;
                font-weight: inherit;
            }
            #r3d-preview-content blockquote cite {
                display: block;
                margin-top: 0.05rem;
                margin-left: 0.25rem;
                font-style: italic;
                font-weight: 400;
                font-size: inherit;
                line-height: inherit;
                opacity: 0.8;
            }
            .r3d-preview-close-btn {
                border: 1px solid #6c757d;
                background: #fff;
                color: #495057;
                border-radius: 0.6rem;
                font-weight: 600;
                padding: 0.5rem 1rem;
            }
            .r3d-preview-close-btn:hover,
            .r3d-preview-close-btn:focus {
                background: #f8f9fa;
                color: #212529;
                border-color: #495057;
            }
            #r3d-toast-host {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 2200;
                width: min(28rem, calc(100vw - 2rem));
                pointer-events: none;
            }
            #r3d-toast-host > * {
                pointer-events: auto;
                margin-bottom: 0.75rem;
            }

            #system-message-container.r3d-toast-container {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 2000;
                width: min(28rem, calc(100vw - 2rem));
                pointer-events: none;
            }

            #system-message-container.r3d-toast-container > * {
                pointer-events: auto;
            }

            #system-message-container.r3d-toast-container joomla-alert,
            #system-message-container.r3d-toast-container .alert {
                margin: 0 0 0.75rem;
                border-radius: 0.9rem;
                box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
                animation: r3d-toast-in 0.2s ease-out;
            }

            #system-message-container.r3d-toast-container .r3d-toast-hide {
                animation: r3d-toast-out 0.25s ease-in forwards;
            }

            @keyframes r3d-toast-in {
                from {
                    opacity: 0;
                    transform: translate3d(0, -0.75rem, 0);
                }

                to {
                    opacity: 1;
                    transform: translate3d(0, 0, 0);
                }
            }

            @keyframes r3d-toast-out {
                from {
                    opacity: 1;
                    transform: translate3d(0, 0, 0);
                }

                to {
                    opacity: 0;
                    transform: translate3d(0, -0.5rem, 0);
                }
            }
        </style>
        <?php $r3dcommentsInlineStylesPrinted = true; ?>
    <?php endif; ?>
    <h3><?php echo Text::_('COM_R3DCOMMENTS_COMMENTS_HEADING'); ?></h3>

    <?php if (!empty($items)) : ?>
        <?php foreach ($items as $root) : ?>
            <article class="r3dcomment-item r3dcomment-item-root">
                <header class="r3dcomment-meta">
                    <strong><?php echo htmlspecialchars($root->author_name ?: $root->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span> | <?php echo htmlspecialchars($formatDisplayDate((string) $root->created), ENT_QUOTES, 'UTF-8'); ?></span>

                    <?php if ($canEditComment($root)) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $root->id); ?>">
                            <?php echo Text::_('JEDIT'); ?>
                        </a>
                    <?php endif; ?>
                </header>

                <div class="r3dcomment-body">
                    <?php echo $root->comment; ?>
                </div>

                <div class="r3dcomment-actions">
                    <button type="button"
                       class="r3d-reply-btn"
                       data-parent="<?php echo (int) $root->id; ?>"
                       data-quote-id="<?php echo (int) $root->id; ?>"
                       data-quote="<?php echo htmlspecialchars(strip_tags($root->comment), ENT_QUOTES, 'UTF-8'); ?>">
                        ↳ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_REPLY', 'Antworten', 'Reply'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="button"
                       class="r3d-reply-btn r3d-quote-btn"
                       data-parent="<?php echo (int) $root->id; ?>"
                       data-quote-id="<?php echo (int) $root->id; ?>">
                        “ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_QUOTE', 'Zitat', 'Quote'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>

                <?php if (!empty($root->children)) : ?>
                    <div class="r3dcomment-children">
                        <?php foreach ($root->children as $child) : ?>
                            <article class="r3dcomment-item r3dcomment-item-child">
                                <header class="r3dcomment-meta">
                                    <strong><?php echo htmlspecialchars($child->author_name ?: $child->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span> | <?php echo htmlspecialchars($formatDisplayDate((string) $child->created), ENT_QUOTES, 'UTF-8'); ?></span>

                                    <?php if ($canEditComment($child)) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $child->id); ?>">
                                            <?php echo Text::_('JEDIT'); ?>
                                        </a>
                                    <?php endif; ?>
                                </header>

                                <div class="r3dcomment-body">
                                    <?php echo $child->comment; ?>
                                </div>

                                <div class="r3dcomment-actions">
                                    <button type="button"
                                       class="r3d-reply-btn"
                                       data-parent="<?php echo (int) $root->id; ?>"
                                       data-quote-id="<?php echo (int) $child->id; ?>"
                                       data-quote="<?php echo htmlspecialchars(strip_tags($child->comment), ENT_QUOTES, 'UTF-8'); ?>">
                                        ↳ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_REPLY', 'Antworten', 'Reply'), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                    <button type="button"
                                       class="r3d-reply-btn r3d-quote-btn"
                                       data-parent="<?php echo (int) $root->id; ?>"
                                       data-quote-id="<?php echo (int) $child->id; ?>">
                                        “ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_QUOTE', 'Zitat', 'Quote'), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?php echo Text::_('COM_R3DCOMMENTS_NO_COMMENTS'); ?></p>
    <?php endif; ?>

    <?php if ($form) : ?>
        <div class="r3dcomment-form-wrap">
            <h4><?php echo Text::_('COM_R3DCOMMENTS_WRITE_COMMENT'); ?></h4>
            <?php if (!$user->guest) : ?>
                <p class="r3dcomment-user-hint">
                    <?php echo Text::sprintf(
                        'COM_R3DCOMMENTS_COMMENTING_AS',
                        htmlspecialchars(trim((string) $user->name) !== '' ? $user->name : $user->username, ENT_QUOTES, 'UTF-8')
                    ); ?>
                </p>
            <?php endif; ?>

            <div id="r3d-reply-indicator"
                 class="r3dcomment-reply-indicator"
                 style="display:none;">
                <strong><?php echo Text::_('COM_R3DCOMMENTS_REPLY_TO_COMMENT'); ?></strong>
                <div id="r3d-reply-preview"></div>
                <a href="#" id="r3d-reply-cancel">
                    <?php echo Text::_('JCANCEL'); ?>
                </a>
            </div>

            <form action="<?php echo Uri::root(); ?>index.php?option=com_r3dcomments&task=comment.save&lang=<?php echo rawurlencode($langForPost); ?>"
                  method="post"
                  id="r3dcomment-form">

                <?php foreach ($form->getFieldset('frontend') as $field) : ?>
                    <?php if (($field->fieldname === 'author_name' || $field->fieldname === 'author_email') && !$user->guest) {
                        continue;
                    } ?>
                    <div class="r3dcomment-field">
                        <?php if ($field->fieldname === 'author_name') : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <label for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($translateFallback('JGLOBAL_NAME', 'Name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                </div>
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php elseif ($field->fieldname === 'author_email') : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <label for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($translateFallback('JGLOBAL_EMAIL', 'E-Mail-Adresse', 'Email address'), ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                </div>
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php elseif ($field->fieldname === 'comment') : ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <label id="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>-lbl"
                                           for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>"
                                           class="required">
                                        <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_COMMENT_COMMENT_LBL', 'Kommentar', 'Comment'), ENT_QUOTES, 'UTF-8'); ?><span class="star" aria-hidden="true">&#160;*</span>
                                    </label>
                                </div>
                                <div class="controls">
                                    <?php
                                    $postedForm = (array) $app->input->get('jform', [], 'array');
                                    $commentValue = (string) ($postedForm['comment'] ?? '');
                                    if ($user->guest) {
                                        ?>
                                        <textarea
                                            name="jform[comment]"
                                            id="jform_comment"
                                            class="uk-textarea"
                                            rows="8"
                                            required
                                        ><?php echo htmlspecialchars($commentValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        <?php
                                    } else {
                                        $editorName = (string) Factory::getConfig()->get('editor', 'jce');
                                        $editor = Editor::getInstance($editorName);
                                        echo $editor->display(
                                            'jform[comment]',
                                            $commentValue,
                                            '100%',
                                            '280',
                                            '60',
                                            '12',
                                            true,
                                            'jform_comment',
                                            null,
                                            null,
                                            ['readonly' => false]
                                        );
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <input type="hidden" name="jform[item_id]" value="<?php echo (int) $itemId; ?>">
                <input type="hidden" name="jform[context]" value="<?php echo htmlspecialchars($context, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars(Uri::getInstance()->toString(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="jform[parent_id]" id="r3d-parent" value="0">
                <input type="hidden" name="jform[quoted_comment_id]" id="r3d-quote-id" value="0">
                <input type="hidden" name="jform[quoted_comment_text]" id="r3d-quote-text" value="">
                <input type="hidden" name="jform[form_started_at]" value="<?php echo time(); ?>">
                <?php if ($honeypotEnabled && $honeypotField !== '') : ?>
                    <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        <label for="jform_<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo Text::_('COM_R3DCOMMENTS_HONEYPOT_LABEL'); ?>
                        </label>
                        <input type="text"
                               id="jform_<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>"
                               name="jform[<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>]"
                               value=""
                               autocomplete="off"
                               tabindex="-1">
                    </div>
                <?php endif; ?>
                <?php if ($showGuestCaptcha) : ?>
                    <div class="r3dcomment-field">
                        <?php
                        $captcha = Captcha::getInstance($defaultCaptcha, ['namespace' => 'plg_captcha_' . $defaultCaptcha]);
                        echo $captcha ? $captcha->display('captcha', 'jform[captcha]', 'required') : '';
                        ?>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="jform[state]" value="1">

                <button type="submit" class="r3d-submit-btn">
                    <?php echo Text::_('COM_R3DCOMMENTS_SUBMIT'); ?>
                </button>
                <button type="button" class="uk-button uk-margin-small-top r3d-preview-btn" id="r3d-preview-open">
                    Preview
                </button>

                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    <?php endif; ?>

    <div id="r3d-preview-modal" class="r3d-preview-modal" aria-hidden="true">
        <div class="r3d-preview-dialog">
            <h4>Comment preview</h4>
            <div id="r3d-preview-content"></div>
            <div class="uk-margin-top">
                <button type="button" class="uk-button uk-button-default r3d-preview-close-btn" id="r3d-preview-close">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const formEl = document.getElementById('r3dcomment-form');
    const replyBox = document.getElementById('r3d-reply-indicator');
    const replyPreview = document.getElementById('r3d-reply-preview');
    const cancelBtn = document.getElementById('r3d-reply-cancel');
    const previewOpenBtn = document.getElementById('r3d-preview-open');
    const previewCloseBtn = document.getElementById('r3d-preview-close');
    const previewModal = document.getElementById('r3d-preview-modal');
    const previewContent = document.getElementById('r3d-preview-content');
    const parentField = document.getElementById('r3d-parent');
    const quoteIdField = document.getElementById('r3d-quote-id');
    const quoteTxtField = document.getElementById('r3d-quote-text');
    const locale = (document.documentElement.lang || 'de').toLowerCase().startsWith('en')
        ? {
            reply: 'Reply',
            quote: 'Quote',
            commentLabel: 'Comment',
            subscribe: 'Subscribe to article',
            unsubscribe: 'Unsubscribe from article'
        }
        : {
            reply: 'Antworten',
            quote: 'Zitat',
            commentLabel: 'Kommentar',
            subscribe: 'Beitrag abonnieren',
            unsubscribe: 'Beitrag nicht mehr abonnieren'
        };
    const isGuest = <?php echo $user->guest ? 'true' : 'false'; ?>;
    const toastMessages = [
        <?php echo json_encode(Text::_('COM_R3DCOMMENTS_DATA_FORM_INFORMATION_RECEIVED')); ?>,
        <?php echo json_encode(Text::_('COM_R3DCOMMENTS_DATA_FORM_INFORMATION_RECEIVED_PENDING')); ?>,
        <?php echo json_encode(Text::_('COM_R3DCOMMENTS_ERROR_GUEST_RATE_LIMIT')); ?>
    ];
    const guestNameInput = formEl ? formEl.querySelector('input[name="jform[author_name]"]') : null;
    const guestEmailInput = formEl ? formEl.querySelector('input[name="jform[author_email]"]') : null;

    document.querySelectorAll('.r3d-reply-btn').forEach((btn) => {
        const text = btn.textContent.replace(/\s+/g, ' ').trim();

        if (text.includes('COM_R3DCOMMENTS_REPLY')) {
            btn.textContent = '↳ ' + locale.reply;
        }
    });
    document.querySelectorAll('.r3d-quote-btn').forEach((btn) => {
        const text = btn.textContent.replace(/\s+/g, ' ').trim();

        if (text.includes('COM_R3DCOMMENTS_QUOTE')) {
            btn.textContent = '“ ' + locale.quote;
        }
    });

    const commentLabel = document.querySelector('label[for=\"jform_comment\"]');

    if (commentLabel && commentLabel.textContent.includes('COM_R3DCOMMENTS_COMMENT_COMMENT_LBL')) {
        const starMarkup = commentLabel.querySelector('.star')?.outerHTML || '';
        commentLabel.innerHTML = locale.commentLabel + starMarkup;
    }

    const subscriptionLink = document.querySelector('.r3d-subscription-control a');

    if (subscriptionLink) {
        const text = subscriptionLink.textContent.replace(/\s+/g, ' ').trim();
        const iconMarkup = subscriptionLink.querySelector('span')?.outerHTML || '';
        const shouldLocalize =
            text.includes('Beitrag abonnieren')
            || text.includes('Beitrag nicht mehr abonnieren')
            || text.includes('MOD_R3DCOMMENTS_SUBSCRIBE')
            || text.includes('MOD_R3DCOMMENTS_UNSUBSCRIBE');

        if (shouldLocalize) {
            const localizedText = subscriptionLink.querySelector('.icon-remove')
                ? locale.unsubscribe
                : locale.subscribe;

            subscriptionLink.innerHTML = iconMarkup + ' ' + localizedText;
        }
    }

    document.querySelectorAll('.r3d-reply-btn').forEach((btn) => {
        if (btn.classList.contains('r3d-quote-btn')) {
            return;
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const parentId = btn.dataset.parent;
            const quoteId = btn.dataset.quoteId || parentId;

            parentField.value = parentId;
            quoteIdField.value = quoteId;
            quoteTxtField.value = '';

            replyPreview.innerText = '';
            replyBox.style.display = 'block';

            document.getElementById('r3dcomment-form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    const escapeHtml = (value) => {
        const lt = String.fromCharCode(60);
        const gt = String.fromCharCode(62);

        return value
            .replace(/&/g, '&amp;')
            .split(lt).join('&lt;')
            .split(gt).join('&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const insertIntoCommentEditor = (html, fallbackText) => {
        if (!isGuest && window.tinymce) {
            const editor = tinymce.get('jform_comment');

            if (editor) {
                editor.focus();
                editor.insertContent(html);
                return;
            }
        }

        const textarea = document.getElementById('jform_comment');

        if (textarea) {
            textarea.value = (textarea.value ? textarea.value + '\n\n' : '') + fallbackText;
            textarea.focus();
        }
    };
    const getCurrentCommentValue = () => {
        if (!isGuest && window.tinymce) {
            const editor = tinymce.get('jform_comment');
            if (editor) {
                return editor.getContent() || '';
            }
        }
        const textarea = document.getElementById('jform_comment');
        return textarea ? textarea.value : '';
    };
    const appendGuestPreviewNodes = (container, rawValue) => {
        const value = String(rawValue || '');
        const decodeEntities = (text) => text
            .replace(/&amp;mdash;/gi, '-')
            .replace(/&mdash;/gi, '-')
            .replace(/&amp;ndash;/gi, '-')
            .replace(/&ndash;/gi, '-')
            .replace(/&quot;/gi, '"')
            .replace(/&#039;/gi, "'")
            .replace(/&amp;/gi, '&');
        const appendTextBlock = (target, text) => {
            const clean = text.replace(/\[\/?quote(?:=[^\]]+)?\]/gi, '').trim();
            if (clean === '') {
                return;
            }
            const p = document.createElement('p');
            if (target && target.tagName === 'BLOCKQUOTE') {
                p.style.margin = '0';
                p.style.fontSize = 'inherit';
                p.style.lineHeight = 'inherit';
                p.style.fontStyle = 'inherit';
                p.style.fontWeight = 'inherit';
            }
            p.innerHTML = escapeHtml(clean).replace(/\n/g, '<br>');
            target.appendChild(p);
        };
        const renderWithPlaceholders = (target, text, blocks) => {
            const tokenPattern = /__R3D_QUOTE_(\d+)__/g;
            let last = 0;
            let m;
            while ((m = tokenPattern.exec(text)) !== null) {
                appendTextBlock(target, text.slice(last, m.index));
                const idx = parseInt(m[1], 10);
                const block = blocks[idx];
                if (block) {
                    const quoteEl = document.createElement('blockquote');
                    renderWithPlaceholders(quoteEl, block.body, blocks);
                    if (block.author !== '') {
                        const cite = document.createElement('cite');
                        cite.textContent = '- ' + block.author;
                        cite.style.marginTop = '0.05rem';
                        cite.style.marginLeft = '0.25rem';
                        cite.style.fontStyle = 'italic';
                        cite.style.fontWeight = '400';
                        cite.style.fontSize = 'inherit';
                        cite.style.lineHeight = 'inherit';
                        cite.style.opacity = '0.8';
                        quoteEl.appendChild(cite);
                    }
                    quoteEl.style.margin = '0 0 0.02rem';
                    quoteEl.style.padding = '0.06rem 0 0.06rem 0.5rem';
                    quoteEl.style.borderLeft = '1px solid rgba(0, 0, 0, 0.2)';
                    quoteEl.style.borderRadius = '0';
                    quoteEl.style.background = 'transparent';
                    quoteEl.style.color = '#1f2d3d';
                    quoteEl.style.fontSize = '16px';
                    quoteEl.style.lineHeight = '28px';
                    quoteEl.style.fontStyle = 'italic';
                    quoteEl.style.fontWeight = '400';
                    target.appendChild(quoteEl);
                }
                last = m.index + m[0].length;
            }
            appendTextBlock(target, text.slice(last));
        };

        let working = decodeEntities(value);
        const blocks = [];
        const innerQuotePattern = /\[quote=([^\]]+)\]((?:(?!\[quote=|\[\/quote\])[\s\S])*)\[\/quote\]/gi;
        let guard = 0;
        let replaced = true;

        while (replaced && guard < 100) {
            replaced = false;
            working = working.replace(innerQuotePattern, (full, author, body) => {
                replaced = true;
                const token = `__R3D_QUOTE_${blocks.length}__`;
                blocks.push({
                    author: String(author || '').trim(),
                    body: String(body || '').trim()
                });
                return token;
            });
            guard++;
        }

        renderWithPlaceholders(container, working, blocks);

        if (!container.hasChildNodes()) {
            appendTextBlock(container, decodeEntities(value));
        }
    };

    document.querySelectorAll('.r3d-quote-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const parentId = btn.dataset.parent || '0';
            const quoteId = btn.dataset.quoteId || parentId;
            const commentItem = btn.closest('.r3dcomment-item');
            const bodyNode = commentItem ? commentItem.querySelector('.r3dcomment-body') : null;
            const authorNode = commentItem ? commentItem.querySelector('.r3dcomment-meta strong') : null;
            const authorName = authorNode ? authorNode.textContent.trim() : '';
            const fullText = bodyNode ? bodyNode.innerText.trim() : '';
            const selection = window.getSelection ? window.getSelection() : null;
            let selectedText = selection ? selection.toString().trim() : '';

            if (selection && bodyNode && selectedText !== '') {
                const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

                if (!range || !bodyNode.contains(range.commonAncestorContainer)) {
                    selectedText = '';
                }
            }

            const quoteText = selectedText !== '' ? selectedText : fullText;

            if (quoteText === '') {
                return;
            }

            parentField.value = parentId;
            quoteIdField.value = quoteId;
            quoteTxtField.value = quoteText;
            // For quote action we insert directly into the input/editor and do not duplicate it in preview.
            replyPreview.innerText = '';
            replyBox.style.display = 'none';

            const escapedQuote = escapeHtml(quoteText);
            const escapedAuthor = escapeHtml(authorName);
            const htmlQuote = escapedAuthor !== ''
                ? `<blockquote style="margin:0 0 0.02rem;padding:0.06rem 0 0.06rem 0.5rem;border-left:1px solid rgba(0,0,0,0.2);background:transparent;color:#1f2d3d;font-size:16px;line-height:28px;font-style:italic;font-weight:400;"><p style="margin:0;font-size:inherit;line-height:inherit;font-style:inherit;font-weight:inherit;">${escapedQuote}</p><cite style="display:block;margin-top:0.05rem;margin-left:0.25rem;font-style:italic;font-weight:400;opacity:0.8;font-size:inherit;line-height:inherit;">- ${escapedAuthor}</cite></blockquote>`
                : `<blockquote style="margin:0 0 0.02rem;padding:0.06rem 0 0.06rem 0.5rem;border-left:1px solid rgba(0,0,0,0.2);background:transparent;color:#1f2d3d;font-size:16px;line-height:28px;font-style:italic;font-weight:400;"><p style="margin:0;font-size:inherit;line-height:inherit;font-style:inherit;font-weight:inherit;">${escapedQuote}</p></blockquote>`;
            const plainQuote = authorName !== ''
                ? `[quote=${authorName}]\n${quoteText}\n[/quote]\n\n`
                : `[quote]\n${quoteText}\n[/quote]\n\n`;

            insertIntoCommentEditor(htmlQuote, plainQuote);
            document.getElementById('r3dcomment-form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();

            parentField.value = 0;
            quoteIdField.value = 0;
            quoteTxtField.value = '';

            replyPreview.innerText = '';
            replyBox.style.display = 'none';
        });
    }

    if (previewOpenBtn && previewModal && previewContent) {
        previewOpenBtn.addEventListener('click', () => {
            const content = getCurrentCommentValue();
            previewContent.innerHTML = '';

            if (content && content.trim() !== '') {
                if (isGuest) {
                    const guestText = document.createElement('div');
                    appendGuestPreviewNodes(guestText, content);
                    previewContent.appendChild(guestText);
                } else {
                    const richText = document.createElement('div');
                    richText.innerHTML = content;
                    previewContent.appendChild(richText);
                }
            } else {
                const empty = document.createElement('em');
                empty.textContent = 'No content yet.';
                previewContent.appendChild(empty);
            }

            previewModal.classList.add('is-open');
            previewModal.setAttribute('aria-hidden', 'false');
        });
    }
    if (previewCloseBtn && previewModal) {
        previewCloseBtn.addEventListener('click', () => {
            previewModal.classList.remove('is-open');
            previewModal.setAttribute('aria-hidden', 'true');
        });
    }

    const processToasts = () => {
        const host = document.getElementById('r3d-toast-host') || (() => {
            const h = document.createElement('div');
            h.id = 'r3d-toast-host';
            document.body.appendChild(h);
            return h;
        })();
        const candidates = document.querySelectorAll('#system-message-container joomla-alert, #system-message-container .alert, joomla-alert');
        candidates.forEach((alert) => {
            if (alert.dataset && alert.dataset.r3dToasted === '1') {
                return;
            }
            const text = (alert.textContent || '').replace(/\s+/g, ' ').trim();
            const alertType = (alert.getAttribute('type') || '').toLowerCase();
            const isSuccessLike = alertType === 'success' || alert.classList.contains('alert-success') || alert.classList.contains('alert-message');
            const isR3dMessage = toastMessages.some((message) => message && text.includes(message));
            if (!(isR3dMessage || isSuccessLike)) {
                return;
            }
            if (alert.dataset) {
                alert.dataset.r3dToasted = '1';
            }
            host.appendChild(alert);
            window.setTimeout(() => alert.classList.add('r3d-toast-hide'), 4200);
            window.setTimeout(() => {
                if (typeof alert.close === 'function') {
                    try { alert.close(); return; } catch (error) {}
                }
                alert.remove();
            }, 4550);
        });
    };

    const showInlineToast = (message, type = 'warning') => {
        const host = document.getElementById('r3d-toast-host') || (() => {
            const h = document.createElement('div');
            h.id = 'r3d-toast-host';
            document.body.appendChild(h);
            return h;
        })();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        host.appendChild(alert);
        window.setTimeout(() => alert.classList.add('r3d-toast-hide'), 4200);
        window.setTimeout(() => alert.remove(), 4550);
    };

    if (formEl && isGuest) {
        formEl.addEventListener('submit', (e) => {
            const nameValue = (guestNameInput?.value || '').trim();
            const emailValue = (guestEmailInput?.value || '').trim();
            const emailLooksValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue);

            if (!nameValue || !emailValue || !emailLooksValid) {
                e.preventDefault();
                if (!nameValue && guestNameInput) {
                    guestNameInput.focus();
                } else if (guestEmailInput) {
                    guestEmailInput.focus();
                }
                showInlineToast('Please enter a valid name and e-mail address before submitting.', 'danger');
            }
        });
    }

    processToasts();
    window.setTimeout(processToasts, 250);
});
</script>
