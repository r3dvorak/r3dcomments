<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;

/**
 * The form field implementation
 */
class CreatedbyField extends ListField
{
	/**
	 * The form field type
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'createdby';

	/**
	 * Method to get the field input markup
	 *
	 * @return	string	The field input markup
	 * @since	1.6
	 */
	protected function getInput()
	{
		$user = Factory::getUser();

		$userExists = true;

		if ($this->value) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from('#__users')
				->where($db->qn('id') . ' = ' . $db->q($this->value));
			$db->setQuery($query);
			$userId = $db->loadResult();
			if ($userId) {
				$user = Factory::getUser($this->value);
			} else {
				$userExists = false;
				$this->value = $user->id;
			}
		} else {
			$this->value = $user->id;
		}

        $html = '';
		if ($userExists) {
			$html = $user->name . " (" . $user->username . ")";
		}

		$html .= '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '" />';

		return $html;
	}
}
