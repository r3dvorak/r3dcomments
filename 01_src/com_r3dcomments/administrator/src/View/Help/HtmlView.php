<?php
/**
 * @package     com_r3dcomments
 * @version     5.3.9
 * @date        2025-11-22
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\View\Help;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected string $title;

    public function display($tpl = null): void
    {
        $this->title = Text::_('COM_R3DCOMMENTS_HELP_TITLE');

        parent::display($tpl);
    }
}
