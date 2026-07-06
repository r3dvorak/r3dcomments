<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.12
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\R3dcomments\Site\Helper\MarkupHelper;

$listOrder = $this->listOrder;
$listDirn  = $this->listDirn;

$saveOrder = ($listOrder == 'a.ordering');

if ($saveOrder && !empty($this->items))
{
    $this->saveOrderingUrl = 'index.php?option=com_r3dcomments&task=comments.saveOrderAjax&tmpl=component&'
        . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_r3dcomments&view=comments'); ?>"
      method="post" name="adminForm" id="adminForm">

    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <table class="table table-striped" id="commentList">
        <thead>
            <tr>
                <!-- Sort -->
                <th width="1%" class="nowrap center">
                    <?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering',
                        $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
                </th>

                <!-- Check-All -->
                <th width="1%" class="center">
                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                </th>

                <!-- Status -->
                <th>
                    <?php echo HTMLHelper::_('grid.sort', 'Status', 'a.state', $listDirn, $listOrder); ?>
                </th>

                <!-- Beitragstitel -->
                <th>
                    <?php echo HTMLHelper::_('grid.sort', 'Beitrag', 'article_title', $listDirn, $listOrder); ?>
                </th>

                <!-- Autorname -->
                <th>
                    <?php echo HTMLHelper::_('grid.sort', 'Autor', 'a.author_name', $listDirn, $listOrder); ?>
                </th>

                <!-- Erstellt -->
                <th>
                    <?php echo HTMLHelper::_('grid.sort', 'Erstellt am', 'a.created', $listDirn, $listOrder); ?>
                </th>

                <!-- Kommentartext -->
                <th>
                    <?php echo HTMLHelper::_('grid.sort', 'Kommentar', 'a.comment', $listDirn, $listOrder); ?>
                </th>

                <!-- ID -->
                <th width="1%" class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>

        <tbody <?php if ($saveOrder) : ?> class="js-draggable"
                data-url="<?php echo $this->saveOrderingUrl; ?>"
                data-direction="<?php echo strtolower($listDirn); ?>"
                data-nested="true"
        <?php endif; ?>>

        <?php foreach ($this->items as $i => $item) : ?>
            <tr class="row<?php echo $i % 2; ?>">

                <!-- ORDER -->
                <td class="order nowrap center">
                    <?php
                    $iconClass = (!$saveOrder) ? 'inactive' : '';
                    ?>
                    <span class="sortable-handler <?php echo $iconClass; ?>">
                        <span class="icon-ellipsis-v"></span>
                    </span>

                    <?php if ($saveOrder) : ?>
                        <input type="text" style="display:none" name="order[]"
                            value="<?php echo $item->ordering; ?>" />
                    <?php endif; ?>
                </td>

                <!-- CHECKBOX -->
                <td class="center">
                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                </td>

                <!-- STATUS -->
                <td>
                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'comments.', true, 'cb'); ?>
                </td>

                <!-- BEITRAGSTITEL (max 60 Zeichen) -->
                <td>
                    <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . $item->id); ?>">
                        <?php echo htmlspecialchars(mb_strimwidth($item->article_title ?? '–', 0, 80, '…')); ?>
                    </a>
                </td>

                <!-- AUTOR-NAME -->
                <td>
                    <?php echo htmlspecialchars($item->author_name ?: '–'); ?>
                </td>

                <!-- ERSTELLT -->
                <td>
                    <?php echo $item->created ?: ''; ?>
                </td>

                <!-- KOMMENTARTEXT (max 200 Zeichen, HTML entfernt) -->
                <td>
                    <a href="<?php echo Route::_('index.php?option=com_r3dcomments&task=comment.edit&id=' . $item->id); ?>">
                        <?php
                        $plain = MarkupHelper::renderPreviewText((string) $item->comment);
                        echo htmlspecialchars(mb_strimwidth($plain, 0, 200, '…'));
                        ?>
                    </a>
                </td>

                <!-- ID -->
                <td>
                    <?php echo $item->id; ?>
                </td>

            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

    <div class="pagination center">
        <?php echo $this->pagination->getListFooter(); ?>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
