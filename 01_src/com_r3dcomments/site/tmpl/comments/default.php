<?php
/**
 * @package     com_r3dcomments
 * @version     6.1.12
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvorak, <dev@r3d.de> - https://www.r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var array<string,mixed> $displayData */
$context = (string) ($displayData['context'] ?? '');
$itemId  = (int) ($displayData['item_id'] ?? 0);

if ($context === '' || $itemId <= 0) {
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

$items = [];
$form = null;

try {
    /** @var \Joomla\Component\R3dcomments\Site\Extension\R3dcommentsComponent $component */
    $component = $app->bootComponent('com_r3dcomments');
    $mvcFactory = $component->getMVCFactory();

    /** @var \Joomla\Component\R3dcomments\Site\Model\CommentsModel $commentsModel */
    $commentsModel = $mvcFactory->createModel('Comments', 'Site', ['ignore_request' => true]);
    $commentsModel->setState('filter.context', $context);
    $commentsModel->setState('filter.item_id', $itemId);
    $items = $commentsModel->getItems();

    /** @var \Joomla\Component\R3dcomments\Site\Model\CommentModel $commentModel */
    $commentModel = $mvcFactory->createModel('Comment', 'Site', ['ignore_request' => true]);
    $form = $commentModel->getForm();
} catch (\Throwable $e) {
    if ($app->isClient('administrator')) {
        $app->enqueueMessage('R3D Comments component template error: ' . $e->getMessage(), 'error');
    }

    return;
}

$canEditComment = static function ($comment) use ($user): bool {
    if (!$user || !$user->id) {
        return false;
    }

    if ($user->authorise('core.edit', 'com_r3dcomments')) {
        return true;
    }

    return (int) $comment->created_by === (int) $user->id
        && $user->authorise('core.edit.own', 'com_r3dcomments');
};

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
?>
<div class="r3dcomments-wrapper r3dcomments-wrapper-uikit uk-margin-large-top">
    <h3><?php echo Text::_('COM_R3DCOMMENTS_COMMENTS_HEADING'); ?></h3>

    <?php if (!empty($items)) : ?>
        <?php foreach ($items as $root) : ?>
            <div class="r3dcomment-item r3dcomment-item-root uk-card uk-card-default uk-card-body uk-margin">
                <div class="r3dcomment-meta uk-text-small uk-text-muted">
                    <strong><?php echo htmlspecialchars($root->author_name ?: (string) $root->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span> • <?php echo htmlspecialchars($formatDisplayDate((string) $root->created), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($canEditComment($root)) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $root->id); ?>"
                           class="uk-text-small uk-margin-small-left"><?php echo Text::_('JEDIT'); ?></a>
                    <?php endif; ?>
                </div>

                <div class="r3dcomment-body uk-margin-small-top"><?php echo $root->comment; ?></div>

                <div class="r3dcomment-actions uk-margin-small-top">
                    <button type="button" class="r3d-reply-btn"
                            data-parent="<?php echo (int) $root->id; ?>"
                            data-quote-id="<?php echo (int) $root->id; ?>"
                            data-quote="<?php echo htmlspecialchars(strip_tags((string) $root->comment), ENT_QUOTES, 'UTF-8'); ?>">
                        ↳ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_REPLY', 'Antworten', 'Reply'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="button" class="r3d-reply-btn r3d-quote-btn"
                            data-parent="<?php echo (int) $root->id; ?>"
                            data-quote-id="<?php echo (int) $root->id; ?>">
                        “ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_QUOTE', 'Zitat', 'Quote'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>

                <?php if (!empty($root->children)) : ?>
                    <div class="r3dcomment-children uk-margin-left uk-margin-small-top">
                        <?php foreach ($root->children as $child) : ?>
                            <div class="r3dcomment-item r3dcomment-item-child uk-card uk-card-small uk-card-body uk-margin-small">
                                <div class="r3dcomment-meta uk-text-small uk-text-muted">
                                    <strong><?php echo htmlspecialchars($child->author_name ?: (string) $child->user_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span> • <?php echo htmlspecialchars($formatDisplayDate((string) $child->created), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($canEditComment($child)) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . (int) $child->id); ?>"
                                           class="uk-text-small uk-margin-small-left"><?php echo Text::_('JEDIT'); ?></a>
                                    <?php endif; ?>
                                </div>

                                <div class="r3dcomment-body uk-margin-small-top"><?php echo $child->comment; ?></div>

                                <div class="r3dcomment-actions uk-margin-small-top">
                                    <button type="button" class="r3d-reply-btn"
                                            data-parent="<?php echo (int) $root->id; ?>"
                                            data-quote-id="<?php echo (int) $child->id; ?>"
                                            data-quote="<?php echo htmlspecialchars(strip_tags((string) $child->comment), ENT_QUOTES, 'UTF-8'); ?>">
                                        ↳ <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_REPLY', 'Antworten', 'Reply'), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                    <button type="button" class="r3d-reply-btn r3d-quote-btn"
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

            <div id="r3d-reply-indicator" class="uk-alert-primary" style="display:none;">
                <strong><?php echo Text::_('COM_R3DCOMMENTS_REPLY_TO_COMMENT'); ?></strong>
                <div id="r3d-reply-preview" class="uk-margin-small-top"></div>
                <a href="#" id="r3d-reply-cancel" class="uk-text-small"><?php echo Text::_('JCANCEL'); ?></a>
            </div>

            <form action="<?php echo Uri::root(); ?>index.php?option=com_r3dcomments&task=comment.save&lang=<?php echo rawurlencode($langForPost); ?>"
                  method="post" id="r3dcomment-form" class="uk-form-stacked uk-margin">
                <?php foreach ($form->getFieldset('frontend') as $field) : ?>
                    <?php if (($field->fieldname === 'author_name' || $field->fieldname === 'author_email') && !$user->guest) {
                        continue;
                    } ?>
                    <div class="uk-margin">
                        <?php if ($field->fieldname === 'author_name') : ?>
                            <label for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($translateFallback('JGLOBAL_NAME', 'Name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php echo $field->input; ?>
                        <?php elseif ($field->fieldname === 'author_email') : ?>
                            <label for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($translateFallback('JGLOBAL_EMAIL', 'E-Mail-Adresse', 'Email address'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php echo $field->input; ?>
                        <?php elseif ($field->fieldname === 'comment') : ?>
                            <label id="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>-lbl" for="<?php echo htmlspecialchars($field->id, ENT_QUOTES, 'UTF-8'); ?>" class="required">
                                <?php echo htmlspecialchars($translateFallback('COM_R3DCOMMENTS_COMMENT_COMMENT_LBL', 'Kommentar', 'Comment'), ENT_QUOTES, 'UTF-8'); ?><span class="star" aria-hidden="true">&#160;*</span>
                            </label>
                            <?php
                            $postedForm = (array) $app->input->get('jform', [], 'array');
                            $commentValue = (string) ($postedForm['comment'] ?? '');
                            if ($user->guest) {
                                ?>
                                <textarea name="jform[comment]" id="jform_comment" class="uk-textarea" rows="8" required><?php echo htmlspecialchars($commentValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <?php
                            } else {
                                $editorName = (string) Factory::getConfig()->get('editor', 'jce');
                                $editor = Editor::getInstance($editorName);
                                echo $editor->display('jform[comment]', $commentValue, '100%', '280', '60', '12', true, 'jform_comment', null, null, ['readonly' => false]);
                            }
                            ?>
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
                        <label for="jform_<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_R3DCOMMENTS_HONEYPOT_LABEL'); ?></label>
                        <input type="text" id="jform_<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>"
                               name="jform[<?php echo htmlspecialchars($honeypotField, ENT_QUOTES, 'UTF-8'); ?>]"
                               value="" autocomplete="off" tabindex="-1">
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

                <input type="hidden" name="jform[state]" value="1">
                <button class="uk-button uk-button-primary uk-margin-small-top"><?php echo Text::_('COM_R3DCOMMENTS_SUBMIT'); ?></button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const replyBox = document.getElementById('r3d-reply-indicator');
    const replyPreview = document.getElementById('r3d-reply-preview');
    const cancelBtn = document.getElementById('r3d-reply-cancel');
    const parentField = document.getElementById('r3d-parent');
    const quoteIdField = document.getElementById('r3d-quote-id');
    const quoteTxtField = document.getElementById('r3d-quote-text');

    const isGuest = <?php echo $user->guest ? 'true' : 'false'; ?>;

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

    document.querySelectorAll('.r3d-reply-btn').forEach((btn) => {
        if (btn.classList.contains('r3d-quote-btn')) {
            return;
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const parentId = btn.dataset.parent;
            const quoteId = btn.dataset.quoteId || parentId;
            const quote = btn.dataset.quote || '';

            parentField.value = parentId;
            quoteIdField.value = quoteId;
            quoteTxtField.value = quote;
            replyPreview.innerText = quote;
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
            const quoteText = fullText;

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
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            parentField.value = 0;
            quoteIdField.value = 0;
            quoteTxtField.value = '';
            replyPreview.innerText = '';
            replyBox.style.display = 'none';
        });
    }
});
</script>

