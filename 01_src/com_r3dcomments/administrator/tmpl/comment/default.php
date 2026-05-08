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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \Joomla\Component\R3dcomments\Administrator\View\Comment\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_contenthistory');
$wa->useScript('keepalive')
   ->useScript('form.validate')
   ->useScript('com_contenthistory.admin-history-versions');
?>
<form action="<?php echo Route::_('index.php?option=com_r3dcomments&layout=edit&id=' . $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
	<div class="row-fluid">
		<div class="span10 form-horizontal">
            <fieldset class="adminform">
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('id'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('id'); ?>
					</div>
				</div>
            	<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('created_by'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('created_by'); ?>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('state'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('state'); ?>
					</div>
				</div>
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
						<?php echo $this->form->getLabel('modified_by'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('modified_by'); ?>
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
            </fieldset>
    	</div>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
	<div id="validation-form-failed" data-backend-detail="comment" data-message="<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>">
	</div>
</form>
