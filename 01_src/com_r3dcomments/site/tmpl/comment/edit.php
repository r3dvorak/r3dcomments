<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.8
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU GPL v2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$form = $this->form;
$item = $this->item;
$user = $this->getCurrentUser();

// Zurück zum Artikel
$cancelUrl = Route::_('index.php?option=com_content&view=article&id=' . (int) $item->item_id);
?>

<div class="r3dcomments-edit-wrapper uk-margin-large-top">

    <h3 class="uk-heading-line uk-text-bold">
        <span><?php echo Text::_('COM_R3DCOMMENTS_EDIT_COMMENT_TITLE'); ?></span>
    </h3>

    <!-- Fehlermeldungen (falls Model->validate etwas liefert) -->
    <div id="system-message-container" aria-live="polite"></div>

    <form action="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.save'); ?>"
          method="post"
          id="r3dcomment-edit-form"
          class="uk-form-stacked">

        <!-- COMMENT TEXTAREA -->
        <div class="uk-margin">
            <?php echo $form->renderField('comment'); ?>
        </div>

        <!-- Hidden fields required for saving -->
        <input type="hidden" name="jform[id]" value="<?php echo (int) $item->id; ?>" />
        <input type="hidden" name="jform[item_id]" value="<?php echo (int) $item->item_id; ?>" />
        <input type="hidden" name="jform[context]" value="<?php echo htmlspecialchars($item->context, ENT_QUOTES, 'UTF-8'); ?>" />

        <div class="uk-margin">
            <button type="submit" class="uk-button uk-button-primary uk-margin-small-right">
                <?php echo Text::_('JSAVE'); ?>
            </button>

            <a href="<?php echo $cancelUrl; ?>" class="uk-button uk-button-default">
                <?php echo Text::_('JCANCEL'); ?>
            </a>
        </div>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
