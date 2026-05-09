<?php
/**
 * @package     mod_r3dcomments
 * @version     6.1.5
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

// Layout-Daten vom Helper (item_id + context)
$context = $displayData['context'] ?? 'com_content.article';
$itemId  = (int) ($displayData['item_id'] ?? 0);

if (!$itemId) {
    // Ohne Item-ID macht das Modul nichts
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
    // Komponente booten und MVC-Factory holen
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
    // Fehler nur im Backend anzeigen
    if ($app->isClient('administrator')) {
        $app->enqueueMessage('R3D Comments module error: ' . $e->getMessage(), 'error');
    }

    return;
}

/**
 * Kleiner ACL-Helper:
 *  - Benutzer darf global editieren ODER
 *  - ist Owner (created_by) UND hat core.edit.own auf Komponentenebene
 */
$canEditComment = function ($comment) use ($user): bool {
    if (!$user || !$user->id) {
        return false;
    }

    // Globaler Edit (Admin, Redakteur etc.)
    if ($user->authorise('core.edit', 'com_r3dcomments')) {
        return true;
    }

    // Eigenen Kommentar bearbeiten dürfen
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
<div class="r3dcomments-wrapper r3dcomments-wrapper-uikit uk-margin-large-top">
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
                margin: 0.75rem 0;
                padding: 0.75rem 1rem;
                border-left: 4px solid #0d6efd;
                border-radius: 0.35rem;
                background: rgba(13, 110, 253, 0.08);
                color: #1f2d3d;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-body blockquote p,
            .r3dcomments-wrapper-standard .r3dcomment-body blockquote p {
                margin: 0;
            }

            .r3dcomments-wrapper-uikit .r3dcomment-body blockquote cite,
            .r3dcomments-wrapper-standard .r3dcomment-body blockquote cite {
                display: block;
                margin-top: 0.5rem;
                font-style: normal;
                font-size: 0.9rem;
                opacity: 0.85;
            }

            .r3dcomments-wrapper-standard #r3dcomment-form button {
                margin-top: 0.5rem;
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
            <div class="r3dcomment-item r3dcomment-item-root uk-card uk-card-default uk-card-body uk-margin">

                <div class="r3dcomment-meta uk-text-small uk-text-muted">
                    <strong><?php echo htmlspecialchars($root->author_name ?: $root->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span> • <?php echo htmlspecialchars($formatDisplayDate((string) $root->created), ENT_QUOTES, 'UTF-8'); ?></span>

                    <?php if ($canEditComment($root)) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $root->id); ?>"
                           class="uk-text-small uk-margin-small-left">
                            <?php echo Text::_('JEDIT'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="r3dcomment-body uk-margin-small-top">
                    <?php echo $root->comment; ?>
                </div>

                <div class="r3dcomment-actions uk-margin-small-top uk-flex uk-flex-between">
                    <div>
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
                </div>

                <?php if (!empty($root->children)) : ?>
                    <div class="r3dcomment-children uk-margin-left uk-margin-small-top">

                        <?php foreach ($root->children as $child) : ?>
                            <div class="r3dcomment-item r3dcomment-item-child uk-card uk-card-small uk-card-body uk-margin-small">

                                <div class="r3dcomment-meta uk-text-small uk-text-muted">
                                    <strong><?php echo htmlspecialchars($child->author_name ?: $child->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span> • <?php echo htmlspecialchars($formatDisplayDate((string) $child->created), ENT_QUOTES, 'UTF-8'); ?></span>

                                    <?php if ($canEditComment($child)) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $child->id); ?>"
                                           class="uk-text-small uk-margin-small-left">
                                            <?php echo Text::_('JEDIT'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="r3dcomment-body uk-margin-small-top">
                                    <?php echo $child->comment; ?>
                                </div>

                                <div class="r3dcomment-actions uk-margin-small-top">
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
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    <?php else : ?>

        <p><?php echo Text::_('COM_R3DCOMMENTS_NO_COMMENTS'); ?></p>

    <?php endif; ?>

    <?php if ($form) : ?>
        <div class="r3dcomment-form-wrap uk-margin-large-top">
            <h4><?php echo Text::_('COM_R3DCOMMENTS_WRITE_COMMENT'); ?></h4>
            <?php if (!$user->guest) : ?>
                <p class="r3dcomment-user-hint">
                    <?php echo Text::sprintf(
                        'COM_R3DCOMMENTS_COMMENTING_AS',
                        htmlspecialchars(trim((string) $user->name) !== '' ? $user->name : $user->username, ENT_QUOTES, 'UTF-8')
                    ); ?>
                </p>
            <?php endif; ?>

            <!-- Reply-Info -->
            <div id="r3d-reply-indicator"
                 class="uk-alert-primary"
                 style="display:none;">
                <strong><?php echo Text::_('COM_R3DCOMMENTS_REPLY_TO_COMMENT'); ?></strong>
                <div id="r3d-reply-preview" class="uk-margin-small-top"></div>
                <a href="#" id="r3d-reply-cancel" class="uk-text-small">
                    <?php echo Text::_('JCANCEL'); ?>
                </a>
            </div>

            <form action="<?php echo Uri::root(); ?>index.php?option=com_r3dcomments&task=comment.save&lang=<?php echo rawurlencode($langForPost); ?>"
                  method="post"
                  id="r3dcomment-form"
                  class="uk-form-stacked uk-margin">

                <?php foreach ($form->getFieldset('frontend') as $field) : ?>
                    <?php
                    // Name / E-Mail nur Gästen anzeigen
                    if (($field->fieldname === 'author_name' || $field->fieldname === 'author_email') && !$user->guest) {
                        continue;
                    }
                    ?>
                    <div class="uk-margin">
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
                <input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()->toString()); ?>">
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
                    <div class="uk-margin">
                        <?php
                        $captcha = Captcha::getInstance($defaultCaptcha, ['namespace' => 'plg_captcha_' . $defaultCaptcha]);
                        echo $captcha ? $captcha->display('captcha', 'jform[captcha]', 'required') : '';
                        ?>
                    </div>
                <?php endif; ?>
                <!-- FIX: angemeldete User direkt veröffentlichen -->
                <input type="hidden" name="jform[state]" value="1">

                <button class="uk-button uk-button-primary uk-margin-small-top r3d-submit-btn">
                    <?php echo Text::_('COM_R3DCOMMENTS_SUBMIT'); ?>
                </button>

                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const replyBox     = document.getElementById('r3d-reply-indicator');
    const replyPreview = document.getElementById('r3d-reply-preview');
    const cancelBtn    = document.getElementById('r3d-reply-cancel');
    const parentField  = document.getElementById('r3d-parent');
    const quoteIdField = document.getElementById('r3d-quote-id');
    const quoteTxtField= document.getElementById('r3d-quote-text');
    const locale       = (document.documentElement.lang || 'de').toLowerCase().startsWith('en')
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

    document.querySelectorAll('.r3d-reply-btn').forEach(btn => {
        if (btn.classList.contains('r3d-quote-btn')) {
            return;
        }

        btn.addEventListener('click', e => {
            e.preventDefault();

            const parentId = btn.dataset.parent;
            const quoteId  = btn.dataset.quoteId || parentId;
            const quote    = btn.dataset.quote;

            parentField.value   = parentId;
            quoteIdField.value  = quoteId;
            quoteTxtField.value = quote;

            replyPreview.innerText = quote;
            replyBox.style.display = 'block';

            document.getElementById('r3dcomment-form')
                .scrollIntoView({ behavior: 'smooth' });
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
                ? `<blockquote><p>${escapedQuote}</p><cite>— ${escapedAuthor}</cite></blockquote><p></p>`
                : `<blockquote><p>${escapedQuote}</p></blockquote><p></p>`;
            const plainQuote = authorName !== ''
                ? `[quote=${authorName}]\n${quoteText}\n[/quote]\n\n`
                : `[quote]\n${quoteText}\n[/quote]\n\n`;

            insertIntoCommentEditor(htmlQuote, plainQuote);
            document.getElementById('r3dcomment-form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', e => {
            e.preventDefault();

            parentField.value   = 0;
            quoteIdField.value  = 0;
            quoteTxtField.value = '';

            replyPreview.innerText = '';
            replyBox.style.display = 'none';
        });
    }

    const systemMessageContainer = document.getElementById('system-message-container');

    if (systemMessageContainer) {
        const alerts = systemMessageContainer.querySelectorAll('joomla-alert, .alert');
        let matchedAlerts = 0;

        alerts.forEach((alert) => {
            const text = alert.textContent.replace(/\s+/g, ' ').trim();
            const alertType = (alert.getAttribute('type') || '').toLowerCase();
            const isSuccessLike = alertType === 'success' || alert.classList.contains('alert-success');
            const isR3dMessage = toastMessages.some((message) => message && text.includes(message));
            const hasR3dModule = document.querySelector('.r3dcomments-wrapper') !== null;
            const shouldToast = isR3dMessage || (hasR3dModule && isSuccessLike);

            if (!shouldToast) {
                return;
            }

            matchedAlerts += 1;

            window.setTimeout(() => {
                alert.classList.add('r3d-toast-hide');
            }, 4200);

            window.setTimeout(() => {
                if (typeof alert.close === 'function') {
                    try {
                        alert.close();
                        return;
                    } catch (error) {
                    }
                }

                alert.remove();
            }, 4550);
        });

        if (matchedAlerts > 0) {
            systemMessageContainer.classList.add('r3d-toast-container');
        }
    }
});
</script>
