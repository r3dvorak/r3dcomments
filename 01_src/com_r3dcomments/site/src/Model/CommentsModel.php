<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.10
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class CommentsModel extends ListModel
{
    /**
     * Build query to load comment items
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = Factory::getApplication()->getIdentity();
        $uid   = (int) $user->id;

        /**
         * ============================================
         * SELECT (inkl. quoted_comment_text)
         * ============================================
         */
        $query->select('a.*');
        $query->select('q.comment AS quoted_comment_text');

        $query->from($db->quoteName('#__r3dcomments', 'a'));

        // FIXED JOIN (einziger Fix!)
        $query->join(
            'LEFT',
            $db->quoteName('#__r3dcomments', 'q')
            . ' ON q.id = a.quoted_comment_id AND a.quoted_comment_id > 0'
        );

        /**
         * ============================================
         * VISIBILITY / MODERATION:
         * - Everyone sees state = 1
         * - Current user sees own state = 0
         * ============================================
         */
        $visibility = [];

        // Everyone sees published
        $visibility[] = 'a.state = 1';

        // Logged-in users see their own moderated comments
        if ($uid > 0) {
            $visibility[] = "(a.state = 0 AND a.created_by = " . $db->quote($uid) . ")";
        }

        $query->where('(' . implode(' OR ', $visibility) . ')');

        /**
         * ============================================
         * FILTERS: context + item_id
         * ============================================
         */
        $context = $this->getState('filter.context');
        $itemId  = (int) $this->getState('filter.item_id');

        if ($context) {
            $query->where($db->quoteName('a.context') . ' = ' . $db->quote($context));
        }

        if ($itemId) {
            $query->where($db->quoteName('a.item_id') . ' = ' . (int) $itemId);
        }

        /**
         * Sort:
         * - root first
         * - replies second
         * - chronological
         */
        $query->order('a.parent_id ASC, a.created ASC, a.id ASC');

        return $query;
    }

    /**
     * Build nested comment tree (1 level only)
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (!$items) {
            return [];
        }

        // Index items by ID
        $indexed = [];
        foreach ($items as $item) {
            $item->children = [];
            $indexed[$item->id] = $item;
        }

        // Build tree
        $tree = [];

        foreach ($indexed as $item) {
            if ((int) $item->parent_id === 0) {
                $tree[] = $item;
            } else {
                if (isset($indexed[$item->parent_id])) {
                    $indexed[$item->parent_id]->children[] = $item;
                }
            }
        }

        return $tree;
    }
}
