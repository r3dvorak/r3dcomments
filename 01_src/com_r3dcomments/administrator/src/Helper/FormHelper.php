<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class FormHelper
{
    protected $form;
    protected $items = [];
    protected $fieldOptionKeys = [];

    /**
     * @param $form
     */
    public function __construct($form)
    {
        $this->form = $form;
    }

    /**
     * Get the field options from the form fields
     *
     * @return  array   $fieldOptions   An array with the field options
     */
    public function getFieldOptions()
    {
        $fieldOptions = [];
        foreach ($this->form->getXml()->fieldset->children() as $field) {
            $fieldColumn = (string)$field['name'];
            foreach ($field->children() as $option) {
                $key = (string) $option['value'];
                $value = (string) $option;
                if (!in_array($key, $this->fieldOptionKeys, true)) {
                    $this->fieldOptionKeys[] = $key;
                }
                $fieldOptions[$fieldColumn][$key] = $value;
            }
        }

        return $fieldOptions;
    }

    /**
     * Append options from the form to the items
     *
     * @param $items
     *
     * @return self
     */
    public function appendFieldOptions($items)
    {
        $this->items = $items;
        $fieldOptions = $this->getFieldOptions();
        foreach ($this->items as $i => $item) {
            if (empty($item)) {
                continue;
            }
            foreach ($item as $key => $value) {
                if ((string)$key === 'state') {
                    continue;
                }
                if (!in_array($item->{$key}, $this->fieldOptionKeys, true)) {
                    continue;
                }
                // If this field has options
                if (!isset($fieldOptions[$key][$value])) {
                    continue;
                }
                // Update the item key with the field option
                $item->{$key} = Text::_($fieldOptions[$key][$value]);
            }

            $this->items[$i] = $item;
        }

        return $this;
    }

    /**
     * Get one item
     *
     * @return null
     */
    public function getOne()
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        return $this->items;
    }
}
