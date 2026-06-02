<?php
/**
 * @package     com_r3dcomments
 * @version     5.3.9
 * @date        2025-11-22
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <h1 class="page-title">
                <?php echo Text::_('COM_R3DCOMMENTS_HELP_TITLE'); ?>
            </h1>

            <p class="lead">
                <?php echo Text::_('COM_R3DCOMMENTS_HELP_INTRO'); ?>
            </p>

            <h2><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION1_TITLE'); ?></h2>
            <ol>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION1_STEP1'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION1_STEP2'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION1_STEP3'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION1_STEP4'); ?></li>
            </ol>

            <h2><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION2_TITLE'); ?></h2>
            <ol>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION2_STEP1'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION2_STEP2'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION2_STEP3'); ?></li>
            </ol>

            <h2><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION3_TITLE'); ?></h2>
            <ol>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION3_STEP1'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION3_STEP2'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION3_STEP3'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_SECTION3_STEP4'); ?></li>
            </ol>

            <hr>

            <h3><?php echo Text::_('COM_R3DCOMMENTS_HELP_CHECKS_TITLE'); ?></h3>
            <p><?php echo Text::_('COM_R3DCOMMENTS_HELP_CHECKS_INTRO'); ?></p>

            <ul>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_CHECKS_1'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_CHECKS_2'); ?></li>
                <li><?php echo Text::_('COM_R3DCOMMENTS_HELP_CHECKS_3'); ?></li>
            </ul>

        </div>
    </div>
</div>
