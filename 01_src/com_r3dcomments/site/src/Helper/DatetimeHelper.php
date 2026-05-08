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

/**
 * R3dcomments datetime helper
 */
class DatetimeHelper
{
    /**
     * @param string $format
     * @return string
     */
    public static function convertStrftimeToDateTimeFormat(string $format): string
    {
        $replacements = [
            '%a' => 'D', '%A' => 'l', '%d' => 'd', '%e' => 'j', '%j' => 'z',
            '%u' => 'N', '%w' => 'w', '%U' => 'W', '%V' => 'W', '%W' => 'W',
            '%b' => 'M', '%B' => 'F', '%m' => 'm', '%C' => 'y', '%g' => 'y',
            '%G' => 'o', '%y' => 'y', '%Y' => 'Y', '%H' => 'H', '%I' => 'h',
            '%l' => 'g', '%M' => 'i', '%p' => 'A', '%P' => 'a', '%r' => 'h:i:s A',
            '%R' => 'H:i', '%S' => 's', '%T' => 'H:i:s', '%X' => 'H:i:s', '%z' => 'O',
            '%Z' => 'T', '%%' => '%'
        ];

        return strtr($format, $replacements);
    }

    /**
     * @param string $value
     * @param string $strftimeFormat
     * @return string
     */
    public static function convertFromStrftimeFormat(string $value, string $strftimeFormat): string
    {
        $phpFormat = self::convertStrftimeToDateTimeFormat($strftimeFormat);
        $datetime = \DateTime::createFromFormat($phpFormat, $value);

        if (!$datetime) {
            return '';
        }

        return $datetime->format($phpFormat);
    }
}
