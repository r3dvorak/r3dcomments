<?php
/**
 * @package     com_r3dcomments
 * @version     5.3.3
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Controller;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * R3dcomments detail controller
 */
class CommentController extends FormController
{
	protected $view_list = 'comments';
}
