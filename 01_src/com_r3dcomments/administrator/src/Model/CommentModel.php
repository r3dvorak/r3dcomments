<?php
/**
 * @package     com_r3dcomments
 * @version     5.3.3
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Model;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\UCM\UCMType;
use Joomla\Component\R3dcomments\Administrator\Helper\R3dcommentsHelper;

/**
 * R3dcomments model
 */
class CommentModel extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_R3DCOMMENTS';

	/**
	 * Method to get the record form.
	 *
	 * @param    array $data An optional array of data for the form to interogate
	 * @param bool $loadData
	 *
	 * @return bool A JForm object on success, false on failure
     * @throws \Exception
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form
		$form = $this->loadForm('com_r3dcomments.comment', 'comment', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form
	 *
	 * @return	mixed	The data for the form
     * @throws  \Exception
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data
		$data = Factory::getApplication()->getUserState('com_r3dcomments.edit.comment.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

        return $data;
	}

	/**
	 * Prepare and sanitise the table prior to saving
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '') {
				$db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->qn('#__r3dcomments'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max+1;
			}
		}
	}

	/**
	 * Method to initialize member variables used by batch methods and other methods like saveorder()
	 *
	 * @return  void
	 *
     * @throws \Exception
	 * @since   3.8.2
	 */
	public function initBatch()
	{
		if ($this->batchSet === null)
		{
			$this->batchSet = true;

			// Get current user
			$this->user = Factory::getApplication()->getIdentity();

			// Get table
			$this->table = $this->getTable();

			// Get table class name
			$tc = explode('\\', \get_class($this->table));
			$this->tableClassName = end($tc);

			if ($this->typeAlias === null) {
				$this->typeAlias = '';
			}

			// Get UCM Type data
			$this->contentType = new UCMType;
			$this->type = $this->contentType->getTypeByTable($this->tableClassName)
				?: $this->contentType->getTypeByAlias($this->typeAlias);
		}
	}

    /**
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function save($data)
    {
        foreach ($this->getForm()->getFieldset() as $field) {
            if ($field->type === 'Calendar') {
                $format = $field->getAttribute('format');
                if ($data[$field->fieldname] === '') {
                    $data[$field->fieldname] = null;
                } elseif ($format) {
                    $phpFormat = R3dcommentsHelper::convertStrftimeToDateTimeFormat($format);
                    $date = \DateTime::createFromFormat($phpFormat, $data[$field->fieldname]);
                    if ($date !== false) {
                        $data[$field->fieldname] = $date->format($phpFormat);
                    }
                }
            }
            if ($field->type === 'Tag' && isset($data[$field->fieldname]) && is_array($data[$field->fieldname])) {
                $data[$field->fieldname] = implode(',', $data[$field->fieldname]);
            }
        }
        return parent::save($data);
    }
}
