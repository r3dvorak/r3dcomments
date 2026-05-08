<?php
/**
 * @version     5.3.3
 * @package     com_r3dcomments
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */
namespace Joomla\Component\R3dcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;

class SubscriptionModel extends BaseDatabaseModel
{
    /**
     * Toggle subscription for a user + content item.
     *
     * @param int    $userId
     * @param string $context
     * @param int    $itemId
     *
     * @return bool  true = subscribed, false = unsubscribed
     */
    public function toggleSubscription(int $userId, string $context, int $itemId): bool
    {
        $db = Factory::getDbo();

        // Prüfen, ob der Eintrag existiert
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__r3dcomments_subscriptions')
            ->where('user_id = ' . (int) $userId)
            ->where('context = ' . $db->quote($context))
            ->where('item_id = ' . (int) $itemId);

        $db->setQuery($query);
        $exists = (bool) $db->loadResult();

        if ($exists)
        {
            // DELETE = abbestellen
            $delete = $db->getQuery(true)
                ->delete('#__r3dcomments_subscriptions')
                ->where('user_id = ' . (int) $userId)
                ->where('context = ' . $db->quote($context))
                ->where('item_id = ' . (int) $itemId);

            $db->setQuery($delete)->execute();
            return false;
        }

        // INSERT = abonnieren
        $now = Factory::getDate()->toSql();

        $insert = $db->getQuery(true)
            ->insert($db->quoteName('#__r3dcomments_subscriptions'))
            ->columns([
                $db->quoteName('user_id'),
                $db->quoteName('context'),
                $db->quoteName('item_id'),
                $db->quoteName('created'),
            ])
            ->values(
                (int) $userId . ', ' .
                $db->quote($context) . ', ' .
                (int) $itemId . ', ' .
                $db->quote($now)
            );

        $db->setQuery($insert)->execute();
        return true;
    }
}
