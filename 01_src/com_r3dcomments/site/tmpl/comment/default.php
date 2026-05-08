<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>
<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1>
			<?php if ($this->escape($this->params->get('page_heading'))) : ?>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			<?php else : ?>
				<?php echo $this->escape($this->params->get('page_title')); ?>
			<?php endif; ?>
        </h1>
    </div>
<?php endif; ?>

<form action="<?php echo Route::_('index.php?option=com_r3dcomments'); ?>"
      method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('parent_id'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('parent_id'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('context'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('context'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('item_id'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('item_id'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('user_id'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('user_id'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('author_name'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('author_name'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('author_email'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('author_email'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('comment'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('comment'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('quoted_comment_id'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('quoted_comment_id'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('fields'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('fields'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('params_reserved_by_joomla'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('params_reserved_by_joomla'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('ip'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('ip'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('created'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('created'); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('modified'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('modified'); ?>
				</div>
			</div>
        </div>
    </div>
    <div class="btn-toolbar">
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <?php echo Text::_('COM_R3DCOMMENTS_COMMENT_SUBMIT_BUTTON_TEXT'); ?>
            </button>
        </div>
    </div>
    <input type="hidden" name="task" value="comment.sendinformation">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
