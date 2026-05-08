<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Base controller class
 *
 * @since  2.5
 */
class DisplayController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $default_view = 'comments';

    /**
     * Method to display a view.
     *
     * @param boolean $cachable If true, the view output will be cached
     * @param array $urlparams An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
     *
     * @return bool|\JControllerLegacy|BaseController A Controller object to support chaining.
     *
     * @throws \Exception
     * @since    2.5
     */
	public function display($cachable = false, $urlparams = array())
	{
		$view   = $this->input->get('view', $this->default_view);
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');

		// Check for edit form.
		if ((string)$view === 'comment' && (string)$layout === 'edit' && !$this->checkEditId('com_r3dcomments.edit.comment', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
			$this->setRedirect(Route::_('index.php?option=com_r3dcomments&view=comments', false));

			return false;
		}

		return parent::display();
	}
}
