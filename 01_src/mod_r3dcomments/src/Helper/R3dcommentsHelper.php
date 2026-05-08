<?php
/**
 * @package     mod_r3dcomments
 * @version     6.1.3
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU GPL v2 or later
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace R3d\Module\R3dcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\Database\ParameterType;

/**
 * Helper-Klasse für das R3D Comments Modul.
 */
class R3dcommentsHelper
{
    /**
     * Resolve the current article context from the active request/menu state.
     *
     * @return array<string, int|string>|null
     */
    public static function resolveCurrentPageContext(): ?array
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        $option = $input->getCmd('option');
        $view   = $input->getCmd('view');
        $id     = $input->getInt('id') ?: $input->getInt('a_id');

        if ($option === 'com_content' && $view === 'article' && $id > 0) {
            return static::buildArticleContext($id);
        }

        $active = $app->getMenu()->getActive();

        if (!$active || empty($active->query) || !is_array($active->query)) {
            return null;
        }

        $activeOption = $active->query['option'] ?? '';
        $activeView   = $active->query['view'] ?? '';
        $activeId     = (int) ($active->query['id'] ?? 0);

        if ($activeOption === 'com_content' && $activeView === 'article' && $activeId > 0) {
            return static::buildArticleContext($activeId);
        }

        return null;
    }

    /**
     * Prüft alle Bedingungen und gibt den Kommentarblock zurück.
     *
     * @param   object  $item    Das Artikel-Objekt.
     * @param   object  $params  Die Parameter (aus dem Modul).
     *
     * @return  string  Das gerenderte HTML oder ein leerer String.
     */
    public static function renderCommentBlock($item, $params): string
    {
        // Artikel-Objekt validieren
        if (!isset($item->id) || !isset($item->catid)) {
            return '';
        }

        // Kategorien aus den Modul-Parametern lesen
        $allowed = (array) $params->get('allowed_categories', []);

        // Wenn keine Kategorien gewählt sind, als sichere Install-Default auf allen Kategorien anzeigen.
        if (empty($allowed)) {
            $allowed = [(string) $item->catid];
        }

        // Kategorie- und Subkategorie-Prüfung
        $includeSubcats = $params->get('include_subcats', 1);
        $isAllowed = in_array((string) $item->catid, $allowed, true);

        // Wenn die direkte Kategorie nicht übereinstimmt UND Unterkategorien eingeschlossen werden sollen
        if (!$isAllowed && (int) $includeSubcats === 1) {
            $categories = Categories::getInstance('content');
            $category = $categories->get($item->catid);

            if ($category) {
                // Prüfen, ob die Kategorie des Artikels ein Kind einer der erlaubten Kategorien ist.
                foreach ($allowed as $allowedCatId) {
                    $parentCategory = $categories->get((int) $allowedCatId);
                    if ($parentCategory) {
                        // getChildren(true) gibt ein flaches Array aller Kind-IDs zurück
                        $childrenIds = array_keys($parentCategory->getChildren(true));
                        if (in_array((int) $item->catid, $childrenIds)) {
                            $isAllowed = true;
                            break; // Schleife beenden, da eine Übereinstimmung gefunden wurde
                        }
                    }
                }
            }
        }

        // Multilingual support: allow associated categories as equivalent targets.
        if (!$isAllowed && !empty($allowed)) {
            $isAllowed = static::isAssociatedCategoryAllowed((int) $item->catid, $allowed);
        }

        if (!$isAllowed) {
            return '';
        }

        // Alle Prüfungen bestanden, Layout vorbereiten
        $renderMode = (string) $params->get('render_mode', 'standard');
        $layoutName = $renderMode === 'standard' ? 'standard' : 'default';

        $displayData = [
            'item_id'     => (int) $item->id,
            'context'     => 'com_content.article',
            'render_mode' => $renderMode,
        ];

        return static::renderLayoutWithOverride($layoutName, $displayData);
    }

    /**
     * Build the article context payload used by the module renderer.
     *
     * @param   int  $articleId
     *
     * @return array<string, int|string>|null
     */
    protected static function buildArticleContext(int $articleId): ?array
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select([$db->quoteName('id'), $db->quoteName('catid')])
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = ' . (int) $articleId);

            $article = $db->setQuery($query)->loadObject();
        } catch (\Throwable $e) {
            return null;
        }

        if (!$article || empty($article->id)) {
            return null;
        }

        return [
            'id'      => (int) $article->id,
            'catid'   => (int) $article->catid,
            'context' => 'com_content.article',
            'item_id' => (int) $article->id,
        ];
    }

    /**
     * Render module layout and honor template overrides in /templates/<tpl>/html/mod_r3dcomments/.
     *
     * @param   string                    $layoutName
     * @param   array<string, int|string> $displayData
     *
     * @return  string
     */
    protected static function renderLayoutWithOverride(string $layoutName, array $displayData): string
    {
        $app = Factory::getApplication();
        $template = $app->getTemplate(true)->template;

        $overridePath = JPATH_SITE . '/templates/' . $template . '/html/mod_r3dcomments/' . $layoutName . '.php';
        $modulePath   = JPATH_SITE . '/modules/mod_r3dcomments/tmpl/' . $layoutName . '.php';
        $layoutPath   = is_file($overridePath) ? $overridePath : $modulePath;

        if (!is_file($layoutPath)) {
            return '';
        }

        ob_start();
        include $layoutPath;

        return (string) ob_get_clean();
    }

    /**
     * Checks whether the current category is associated with one of the allowed categories.
     */
    protected static function isAssociatedCategoryAllowed(int $currentCategoryId, array $allowedCategoryIds): bool
    {
        if ($currentCategoryId <= 0 || empty($allowedCategoryIds)) {
            return false;
        }

        $allowedInts = array_values(array_unique(array_map('intval', $allowedCategoryIds)));
        $allowedInts = array_filter($allowedInts, static fn (int $id): bool => $id > 0);

        if (empty($allowedInts)) {
            return false;
        }

        if (in_array($currentCategoryId, $allowedInts, true)) {
            return true;
        }

        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('a2.id'))
                ->from($db->quoteName('#__associations', 'a1'))
                ->innerJoin(
                    $db->quoteName('#__associations', 'a2')
                    . ' ON ' . $db->quoteName('a1.key') . ' = ' . $db->quoteName('a2.key')
                )
                ->where($db->quoteName('a1.context') . ' = ' . $db->quote('com_categories.item'))
                ->where($db->quoteName('a2.context') . ' = ' . $db->quote('com_categories.item'))
                ->where($db->quoteName('a1.id') . ' = :categoryId')
                ->bind(':categoryId', $currentCategoryId, ParameterType::INTEGER);

            $associatedIds = array_map(
                'intval',
                (array) $db->setQuery($query)->loadColumn()
            );
        } catch (\Throwable $e) {
            return false;
        }

        foreach ($associatedIds as $associatedId) {
            if (in_array($associatedId, $allowedInts, true)) {
                return true;
            }
        }

        return false;
    }
}
