<?php
/**
 * @package     pkg_r3dcomments
 * @version     6.1.3
 * @date        2026-05-08
 * @author      Richard Dvorak, <dev@r3d.de> - https://www.r3d.de
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerScript;

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

        // Optional: Success message
        Factory::getApplication()->enqueueMessage(
            'R3D Comments wurde erfolgreich installiert.',
            'success'
        );

        return true;
    }
}





