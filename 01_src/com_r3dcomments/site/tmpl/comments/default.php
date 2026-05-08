<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.10
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Factory\MVCFactory;

// Kontext und Item-ID
$context = $displayData['context'] ?? '';
$itemId  = (int) ($displayData['item_id'] ?? 0);

if (!$context || !$itemId) {
    return;
}

// Kommentar-Baum laden
$model = MVCFactory::getInstance('Comments', 'R3dcommentsModel');
$model->setState('filter.context', $context);
$model->setState('filter.item_id',  $itemId);
$items = $model->getItems();

// Formular laden
$modelForm = MVCFactory::getInstance('Comment', 'R3dcommentsModel');
$form = $modelForm->getForm();

$user = Factory::getApplication()->getIdentity();

/**
 * Helper: Prüfen ob User Kommentar bearbeiten darf
 */
$canEdit = function($comment) use ($user)
{
    if (!$user || !$user->id) return false;

    // global edit
    if ($user->authorise('core.edit', 'com_r3dcomments.comment.' . $comment->id))
        return true;

    // own edit
    if (
        (int)$comment->created_by === (int)$user->id &&
        $user->authorise('core.edit.own', 'com_r3dcomments.comment.' . $comment->id)
    ) return true;

    return false;
};

/**
 * Helper: Quote-Text finden
 */
$findCommentText = function($id) use ($items)
{
    foreach ($items as $root)
    {
        if ((int)$root->id === (int)$id)
            return strip_tags($root->comment);

        if (!empty($root->children))
        {
            foreach ($root->children as $child)
            {
                if ((int)$child->id === (int)$id)
                    return strip_tags($child->comment);
            }
        }
    }

    return '';
};
?>

<div class="r3dcomments-wrapper">
    
    <!-- ====================== -->
    <!-- LISTE DER KOMMENTARE   -->
    <!-- ====================== -->
    <?php if (!empty($items)) : ?>

        <?php foreach ($items as $root) : ?>
        <div class="uk-background-muted uk-border-rounded uk-padding-small uk-margin-small-bottom">

            <div class="uk-text-meta uk-margin-small-bottom">
                <strong><?= htmlspecialchars($root->author_name); ?></strong>
                <span class="uk-text-muted">• <?= $root->created; ?></span>

                <?php if ($canEdit($root)) : ?>
                    <a href="<?= Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . $root->id); ?>"
                       class="uk-button uk-button-text uk-margin-small-left">
                       ✎ Bearbeiten x
                    </a>
                <?php endif; ?>
            </div>

            <div class="uk-margin-small-bottom">
                <?= $root->comment; ?>
            </div>

            <a href="#" class="r3d-reply-btn uk-link-muted"
               data-parent="<?= $root->id; ?>"
               data-quote="<?= htmlspecialchars($root->comment, ENT_QUOTES); ?>">
               ⮩ Antworten
            </a>

            <!-- Antworten -->
            <?php if (!empty($root->children)) : ?>
            <div class="uk-margin-left uk-margin-small-top">

                <?php foreach ($root->children as $child) : ?>
                <div class="uk-background-default uk-border-rounded uk-padding-small uk-margin-small-bottom">

                    <div class="uk-text-meta uk-margin-small-bottom">
                        <strong><?= htmlspecialchars($child->author_name); ?></strong>
                        <span class="uk-text-muted">• <?= $child->created; ?></span>

                        <?php if ($canEdit($child)) : ?>
                            <a href="<?= Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . $child->id); ?>"
                               class="uk-button uk-button-text uk-margin-small-left">
                               ✎ Bearbeiten
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="uk-margin-small-bottom">
                        <?= $child->comment; ?>
                    </div>

                    <a href="#" class="r3d-reply-btn uk-link-muted"
                       data-parent="<?= $root->id; ?>"
                       data-quote="<?= htmlspecialchars($child->comment, ENT_QUOTES); ?>">
                       ⮩ Antworten
                    </a>
                </div>
                <?php endforeach; ?>

            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

    <?php else : ?>
        <p>Keine Kommentare vorhanden.</p>
    <?php endif; ?>


    <!-- ====================== -->
    <!-- KOMMENTAR-FORMULAR     -->
    <!-- ====================== -->
    <div class="uk-margin-top">
        <h4>Kommentar schreiben</h4>

        <div id="r3d-reply-indicator"
             class="uk-alert-primary uk-padding-small uk-border-rounded"
             style="display:none;">
            <strong>Antwort auf:</strong>
            <div id="r3d-reply-preview"
                 class="uk-margin-small-top uk-text-small"></div>
            <a href="#" id="r3d-reply-cancel" class="uk-text-small">Abbrechen</a>
        </div>

        <form action="<?= Uri::root(); ?>index.php?option=com_r3dcomments&task=comment.save"
              method="post" id="r3dcomment-form">

            <?php foreach ($form->getFieldset() as $field) : ?>
                <?= $field->renderField(); ?>
            <?php endforeach; ?>

            <input type="hidden" name="jform[item_id]" value="<?= $itemId; ?>">
            <input type="hidden" name="jform[context]" value="<?= $context; ?>">
            <input type="hidden" name="jform[parent_id]" id="r3d-parent" value="0">
            <input type="hidden" name="jform[quoted_comment_id]" id="r3d-quote-id" value="0">

            <button type="submit"
                    class="uk-button uk-button-primary uk-margin-small-top">
                Absenden
            </button>

            <?= HTMLHelper::_('form.token'); ?>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const replyBox     = document.getElementById('r3d-reply-indicator');
    const replyPreview = document.getElementById('r3d-reply-preview');
    const cancelBtn    = document.getElementById('r3d-reply-cancel');
    const parentField  = document.getElementById('r3d-parent');
    const quoteField   = document.getElementById('r3d-quote-id');

    // Antworten anklicken
    document.querySelectorAll('.r3d-reply-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();

            parentField.value = btn.dataset.parent;
            quoteField.value  = btn.dataset.parent;

            replyPreview.textContent = btn.dataset.quote;
            replyBox.style.display = 'block';

            document.getElementById('r3dcomment-form')
                .scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Antwort abbrechen
    cancelBtn.addEventListener('click', e => {
        e.preventDefault();
        replyBox.style.display = 'none';
        replyPreview.textContent = '';
        parentField.value = 0;
        quoteField.value = 0;
    });

});
</script>
