<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.2
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $log = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->log = $app->getUserState('com_r3dcomments.import.log', []);
        $app->setUserState('com_r3dcomments.import.log', []); // Log nach Anzeige löschen

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     */
    protected function addToolbar()
    {
        $this->document->setTitle(Text::_('COM_R3DCOMMENTS_TITLE_IMPORT'));

        $toolbar = Toolbar::getInstance('toolbar');
        $toolbar->appendButton('Custom', '<button class="btn btn-primary" onclick="Joomla.submitbutton(\'import.run\')"><span class="icon-upload" aria-hidden="true"></span> '
            . Text::_('COM_R3DCOMMENTS_IMPORT_START_BUTTON') . '</button>');
    }
}
