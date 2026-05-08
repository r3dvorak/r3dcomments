<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.2
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class ImportController extends BaseController
{
    public function run()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $dryRun = $input->getBool('dryrun', false);

        /** @var \Joomla\Component\R3dcomments\Administrator\Model\ImportModel $model */
        $model = $this->getModel('Import');

        $log = $model->import($dryRun);

        $app->enqueueMessage('Import abgeschlossen.', 'message');

        $app->setUserState('com_r3dcomments.import.log', $log);

        $this->setRedirect('index.php?option=com_r3dcomments&view=import');
    }
}
