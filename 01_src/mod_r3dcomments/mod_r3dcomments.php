<?php
/**
 * @package     mod_r3dcomments
 * @version     6.1.3
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU GPL v2 or later
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use R3d\Module\R3dcomments\Site\Helper\R3dcommentsHelper;
use Joomla\CMS\Router\Route;

// Lade die Sprachdatei der Komponente, damit die Schlüssel übersetzt werden können
Factory::getLanguage()->load('com_r3dcomments', JPATH_SITE);

// Lade die Helper-Klasse, falls der Autoloader sie nicht findet
require_once __DIR__ . '/src/Helper/R3dcommentsHelper.php';

$pageContext = R3dcommentsHelper::resolveCurrentPageContext();

if (!$pageContext) {
    return;
}

$itemObject = (object) [
    'id'    => (int) $pageContext['id'],
    'catid' => (int) $pageContext['catid'],
];

// --- NEU: Abonnement-Button/Link anzeigen ---
$user = Factory::getUser();
$itemId = (int) $pageContext['item_id'];
$context = (string) $pageContext['context'];

// Nur für angemeldete Benutzer anzeigen
if (!$user->guest) {
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__r3dcomments_subscriptions'))
        ->where($db->quoteName('user_id') . ' = ' . (int) $user->id)
        ->where($db->quoteName('context') . ' = ' . $db->quote($context))
        ->where($db->quoteName('item_id') . ' = ' . (int) $itemId);

    $db->setQuery($query);
    $isSubscribed = (bool) $db->loadResult();

    $subscribeUrl = Route::_('index.php?option=com_r3dcomments&task=comment.toggleSubscription&context=' . rawurlencode($context) . '&item_id=' . $itemId);
    
    $subscribeText = $isSubscribed
        ? Text::_('MOD_R3DCOMMENTS_UNSUBSCRIBE')
        : Text::_('MOD_R3DCOMMENTS_SUBSCRIBE');
    $subscribeIcon = $isSubscribed ? 'icon-remove' : 'icon-plus'; // Joomla 4/5: 'icon-minus', 'icon-plus'

    echo '<div class="r3d-subscription-control" style="margin-bottom: 15px;">';
    echo '  <a href="' . $subscribeUrl . '" class="btn">';
    echo '    <span class="' . $subscribeIcon . '" aria-hidden="true"></span> ';
    echo      $subscribeText;
    echo '  </a>';
    echo '</div>';
}
// --- Ende: Abonnement-Button/Link ---

// Die statische Render-Funktion des Helpers aufrufen und das Ergebnis ausgeben
// $params ist eine magische Variable, die vom ModuleHelper bereitgestellt wird
echo R3dcommentsHelper::renderCommentBlock($itemObject, $params);
