<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.2
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
?>

<form action="<?php echo Route::_('index.php?option=com_r3dcomments&view=import'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <div id="j-main-container">
        <div class="alert alert-info">
            <?php echo Text::_('COM_R3DCOMMENTS_IMPORT_DESCRIPTION'); ?>
        </div>

        <div class="control-group">
            <div class="controls">
                <input type="checkbox" name="dryrun" id="dryrun" value="1" />
                <label for="dryrun"><?php echo Text::_('COM_R3DCOMMENTS_IMPORT_DRY_RUN_LABEL'); ?></label>
            </div>
        </div>

        <?php if (!empty($this->log)) : ?>
            <h3><?php echo Text::_('COM_R3DCOMMENTS_IMPORT_LOG_TITLE'); ?></h3>
            <pre class="alert alert-light"><?php echo implode("\n", $this->log); ?></pre>
        <?php endif; ?>
    </div>

    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>