<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Controller;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * R3dcomments list controller
 */
class CommentsController extends AdminController
{
	/**
	 * Proxy for getModel
	 * @since    1.6
	 *
	 * @param string $name
	 * @param string $prefix
	 * @param array $config
	 *
	 * @return bool
	 */
	public function getModel($name = 'Comment', $prefix = 'Administrator', $config = [])
	{
		return parent::getModel($name, $prefix, ['ignore_request' => true]);
	}
}
