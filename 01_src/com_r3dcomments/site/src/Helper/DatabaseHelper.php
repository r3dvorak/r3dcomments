<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\Mysqli\MysqliQuery;

/**
 * R3dcomments database helper
 */
class DatabaseHelper
{
	/**
	 * Build the search query from the columns
	 *
	 * @param	string		        $searchPhrase	    Search for this phrase
	 * @param	array		        $searchColumns	    The columns in the DB to look up
	 * @param   MysqliQuery         $query              The query
	 *
	 * @return	MysqliQuery		    $query			    The query (search filters applied)
	 */
    public static function buildSearchQuery(string $searchPhrase, array $searchColumns, MysqliQuery $query): MysqliQuery
    {
        $db = Factory::getDbo();

        $where = [];

        foreach ($searchColumns as $i => $searchColumn) {
            $where[] = $db->qn($searchColumn) . ' LIKE ' . $db->q('%' . $db->escape($searchPhrase, true) . '%');
        }

        if (!empty($where)) {
	        $query->where('(' . implode(' OR ', $where) . ')');
        }

        return $query;
    }
}
