<?php
/**
 * @package     pkg_r3dcomments
 * @version     6.1.29
 * @date        2026-05-08
 * @author      Richard Dvorak, <dev@r3d.de> - https://extensions.r3d.de
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

class R3dcommentsInstallerScript extends InstallerScript
{
    protected $minimumPhp = '8.2';
    protected $minimumJoomla = '5.0';

    /**
     * Preflight – läuft vor Installation/Update
     */
    public function preflight($type, $parent)
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }

        return true;
    }

    /**
     * Postflight – läuft nach Installation/Update
     */
    public function postflight($type, PackageAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $this->ensureIpHashColumn();

        // Optional: Success message
        Factory::getApplication()->enqueueMessage(
            'R3D Comments wurde erfolgreich installiert.',
            'success'
        );

        return true;
    }

    private function ensureIpHashColumn(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__r3dcomments');

            $query = 'SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote('ip_hash');
            $db->setQuery($query);
            $exists = (bool) $db->loadResult();

            if ($exists) {
                return;
            }

            $alter = 'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD COLUMN ' . $db->quoteName('ip_hash') . ' CHAR(64) NULL AFTER ' . $db->quoteName('ip');
            $db->setQuery($alter);
            $db->execute();
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'R3D Comments DB migration warning: ' . $e->getMessage(),
                'warning'
            );
        }
    }
}

