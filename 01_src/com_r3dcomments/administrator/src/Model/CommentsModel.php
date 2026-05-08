<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.12
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\Component\R3dcomments\Administrator\Helper\FormHelper;
use Joomla\Component\R3dcomments\Administrator\Helper\R3dcommentsHelper;

class CommentsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = [
                'id', 'a.id',
                'state', 'a.state',
                'ordering', 'a.ordering',

                // custom fields
                'article_title', 'article_title',
                'author_name', 'a.author_name',
                'created', 'a.created',
                'comment', 'a.comment',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = null, $direction = null)
    {
        $app = Factory::getApplication('administrator');

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'int');
        $this->setState('filter.state', $published);

        $this->setState('list.limit', $app->input->get('limit', $app->get('list_limit', 20), 'uint'));
        $this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

        $params = ComponentHelper::getParams('com_r3dcomments');
        $this->setState('params', $params);

        parent::populateState('a.created', 'DESC');
    }

    /**
     * Build SQL Query for backend list
     */
    protected function getListQuery()
    {
        $db    = $this->_db;
        $query = $db->getQuery(true);

        /**
         * ==========================================
         * SELECT
         * ==========================================
         */
        $query->select('a.*');

        // Strip HTML for backend preview
        $query->select('REPLACE(REPLACE(REPLACE(a.comment, "<br>", " "), "<br/>", " "), "<p>", "") AS comment_clean');

        // JOIN: Beitragstitel
        $query->select('c.title AS article_title');
        $query->join('LEFT', $db->quoteName('#__content', 'c') . ' ON c.id = a.item_id');

        // JOIN Benutzer
        $query->select('u.name AS created_by_name');
        $query->join('LEFT', '#__users AS u ON u.id = a.created_by');

        $query->select('m.name AS modified_by_name');
        $query->join('LEFT', '#__users AS m ON m.id = a.modified_by');

        $query->from($db->quoteName('#__r3dcomments', 'a'));

        /**
         * ==========================================
         * FILTER published
         * ==========================================
         */
        $state = $this->getState('filter.state');

        if (is_numeric($state)) {
            $query->where('a.state = ' . (int) $state);
        } elseif ($state !== '*') {
            $query->where('a.state IN (0, 1)');
        }

        /**
         * ==========================================
         * FILTER search
         * ==========================================
         */
        $search = $this->getState('filter.search');

        if (!empty($search)) {

            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where('a.id = ' . $db->quote($id));

            } else {

                $search = $db->quote('%' . $db->escape($search, true) . '%', false);

                $query->where('('
                    . 'c.title LIKE ' . $search
                    . ' OR a.author_name LIKE ' . $search
                    . ' OR a.author_email LIKE ' . $search
                    . ' OR a.comment LIKE ' . $search
                . ')');
            }
        }

        /**
         * ==========================================
         * ORDERING
         * ==========================================
         */
        $orderCol  = $this->state->get('list.ordering', 'a.created');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Inject form field options (pre-existing logic)
     */
    public function getItems()
    {
        Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_r3dcomments/forms');

        $form = $this->loadForm(
            'com_r3dcomments.comment',
            'comment',
            ['control' => 'jform', 'load_data' => true]
        );

        $formHelper = new FormHelper($form);

        return $formHelper
            ->appendFieldOptions(parent::getItems())
            ->getAll();
    }
}

