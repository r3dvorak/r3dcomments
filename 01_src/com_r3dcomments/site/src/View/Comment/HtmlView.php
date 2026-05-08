<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.7
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\View\Comment;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $state;

    public function display($tpl = null)
    {
        $this->state = $this->get('State');
        $this->item  = $this->get('Item');
        $this->form  = $this->get('Form');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // ALWAYS force edit layout when controller calls comment.edit
        $this->setLayout('edit');

        $user = Factory::getApplication()->getIdentity();

        // DEBUG: Sicherer HTML-Kommentar
        echo "<!-- DEBUG_VIEW ";
        echo "item_id=" . ($this->item->id ?? 'NULL') . " ";
        echo "created_by=" . ($this->item->created_by ?? 'NULL') . " ";
        echo "user_id=" . $user->id . " ";
        echo "auth_edit_global=" . ($user->authorise('core.edit', 'com_r3dcomments') ? '1' : '0') . " ";
        echo "auth_edit_own_item=" . ($user->authorise('core.edit.own', 'com_r3dcomments.comment.' . ($this->item->id ?? 0)) ? '1' : '0') . " ";
        echo "-->";

        // ACL korrekt berechnen
        $canEdit = $user->authorise('core.edit', 'com_r3dcomments.comment.' . ($this->item->id ?? 0));
        $canEditOwn =
            $user->id > 0 &&
            $this->item->created_by == $user->id &&
            $user->authorise('core.edit.own', 'com_r3dcomments.comment.' . ($this->item->id ?? 0));

        if (!$canEdit && !$canEditOwn) {
            throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::display($tpl);
    }
}
