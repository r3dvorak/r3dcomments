<?php
/**
 * @package     com_r3dcomments
 * @version     5.2.2
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;

/**
 * Model for JComments import.
 */
class ImportModel extends BaseDatabaseModel
{
    /**
     * The database object.
     *
     * @var    DatabaseDriver
     * @since  5.2.2
     */
    private $db;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   5.2.2
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->db = $this->getDbo();
    }

    /**
     * Performs the JComments import.
     *
     * @param   bool  $dryRun  If true, no changes are made to the database.
     *
     * @return  array  Log messages.
     *
     * @throws  \Exception
     * @since   5.2.2
     */
    public function import(bool $dryRun = false): array
    {
        $log = [];

        $oldTable = '#__jcomments';
        $newTable = '#__r3dcomments';

        $oldTable = $this->db->replacePrefix($oldTable);
        $newTable = $this->db->replacePrefix($newTable);

        $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_START', $oldTable, $newTable);
        if ($dryRun) {
            $log[] = Text::_('COM_R3DCOMMENTS_IMPORT_LOG_DRY_RUN');
        }

        // 1. Get all comments from JComments
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($oldTable)
            ->order('id ASC'); // Process parents before children
        $jcomments = $this->db->setQuery($query)->loadObjectList();

        if (empty($jcomments)) {
            $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_NO_COMMENTS', $oldTable);
            return $log;
        }

        $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_FOUND_COMMENTS', count($jcomments), $oldTable);

        if (!$dryRun) {
            // 2. Clear the new table to avoid duplicates on re-runs
            $this->db->setQuery('TRUNCATE TABLE ' . $this->db->quoteName($newTable))->execute();
            $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_TABLE_CLEARED', $newTable);
        }

        $importedCount = 0;
        $oldIdToNewIdMap = []; // To map parent-child relationships

        // 3. Loop and insert
        foreach ($jcomments as $oldComment) {
            $newComment = new \stdClass();

            $newComment->parent_id    = 0;
            $newComment->context      = $oldComment->object_group === 'com_content' ? 'com_content.article' : $oldComment->object_group;
            $newComment->item_id      = (int) $oldComment->object_id;
            $newComment->user_id      = (int) $oldComment->userid;
            $newComment->author_name  = $oldComment->name;
            $newComment->author_email = $oldComment->email;
            $newComment->comment      = $oldComment->comment;
            $newComment->ip           = $oldComment->ip;
            $newComment->created      = $oldComment->date;
            $newComment->created_by   = (int) $oldComment->userid;

            if ($oldComment->deleted == 1) {
                $newComment->state = -2; // Trashed
            } elseif ($oldComment->published == 1) {
                $newComment->state = 1; // Published
            } else {
                $newComment->state = 0; // Unpublished
            }

            if (!$dryRun) {
                $this->db->insertObject($newTable, $newComment, 'id');
                $newId = $newComment->id;
                $oldIdToNewIdMap[$oldComment->id] = $newId;
            } else {
                $newId = 'DRY_RUN_ID_' . $oldComment->id;
            }

            $importedCount++;
            $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_IMPORTED_ID', $oldComment->id, $newId);
        }

        $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_PHASE1_COMPLETE', $importedCount);
        $log[] = Text::_('COM_R3DCOMMENTS_IMPORT_LOG_PHASE2_START');

        // 4. Second pass: Update parent_id for replies
        $updatedChildrenCount = 0;
        foreach ($jcomments as $oldComment) {
            if ($oldComment->parent > 0 && isset($oldIdToNewIdMap[$oldComment->id]) && isset($oldIdToNewIdMap[$oldComment->parent])) {
                $newChildId  = $oldIdToNewIdMap[$oldComment->id];
                $newParentId = $oldIdToNewIdMap[$oldComment->parent];

                if (!$dryRun) {
                    $updateQuery = $this->db->getQuery(true)->update($this->db->quoteName($newTable))->set($this->db->quoteName('parent_id') . ' = ' . (int) $newParentId)->where($this->db->quoteName('id') . ' = ' . (int) $newChildId);
                    $this->db->setQuery($updateQuery)->execute();
                }
                $updatedChildrenCount++;
            }
        }

        $log[] = Text::sprintf('COM_R3DCOMMENTS_IMPORT_LOG_PHASE2_COMPLETE', $updatedChildrenCount);
        $log[] = Text::_('COM_R3DCOMMENTS_IMPORT_LOG_FINISHED');

        return $log;
    }
}