<?php
/**
 * @version     5.2.11
 * @package     com_r3dcomments
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use RuntimeException;

/**
 * Front-end model for handling a single comment
 */
class CommentModel extends FormModel
{
    /**
     * Empty item (not used in frontend)
     */
    public function getItem($pk = null): object
    {
        $pk = $pk ?: (int) $this->getState('comment.id');

        // Cache initialisieren
        if (!isset($this->_item)) {
            $this->_item = [];
        }

        // Bereits geladen?
        if (isset($this->_item[$pk])) {
            return $this->_item[$pk];
        }

        // Tabelle laden
        $table = $this->getTable('Comment', 'Administrator');

        if (!$table->load($pk)) {
            return (object) [];
        }

        // In stdClass umwandeln (das ist der wichtige Schritt!)
        $item = (object) $table->getProperties();

        // Cache speichern
        $this->_item[$pk] = $item;

        return $item;
    }


    protected function populateState($ordering = null, $direction = null)
    {
        $app = Factory::getApplication();

        $id = $app->getInput()->getInt('id', 0);

        $this->setState('comment.id', $id);

        parent::populateState($ordering, $direction);
    }


    public function getForm($data = [], $loadData = true)
    {
        Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_r3dcomments/forms');

        $form = $this->loadForm(
            'com_r3dcomments.comment',
            'comment',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState(
            'com_r3dcomments.edit.comment.data',
            []
        );

        if (empty($data)) {
            $id   = (int) $this->getState('comment.id');
            $item = $this->getItem($id);

            if ($item) {
                $data = (array) $item;
            }
        }

        return $data;
    }


    public function validate($form, $data, $group = null)
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->guest) {
            $form->setFieldAttribute('author_name', 'required', 'false');
            $form->setFieldAttribute('author_email', 'required', 'false');
        }

        return parent::validate($form, $data, $group);
    }

    /**
     * Save a comment (with reply logic)
     */
    public function save($data)
    {
        $app   = Factory::getApplication();
        $user  = $app->getIdentity();
        $input = $app->getInput();

        $data['context'] = $data['context'] ?? $input->getString('context', 'com_content.article');
        $data['item_id'] = (int) ($data['item_id'] ?? $input->getInt('item_id', 0));

        if (!$data['item_id']) {
            throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_NO_ITEM_ID'), 400);
        }

        $now = Factory::getDate()->toSql();

        $commentText = trim((string) ($data['comment'] ?? ''));
        if ($commentText === '') {
            throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_COMMENT_REQUIRED'), 400);
        }


        /**
         * ===========================================
         * USER / GUEST HANDLING
         * ===========================================
         */
        if ($user->guest)
        {
            $authorName  = trim((string) ($data['author_name'] ?? ''));
            $authorEmail = trim((string) ($data['author_email'] ?? ''));

            if ($authorName === '' || $authorEmail === '') {
                throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_GUEST_NAME_EMAIL_REQUIRED'), 400);
            }

            $data['user_id']      = 0;
            $data['created_by']   = 0;
            $data['modified_by']  = 0;

            $data['author_name']  = $authorName;
            $data['author_email'] = $authorEmail;

            $data['state'] = 0;
        }
        else
        {
            $displayName = trim((string) $user->name) !== ''
                ? (string) $user->name
                : (string) $user->username;

            $data['user_id']      = (int) $user->id;
            $data['created_by']   = (int) $user->id;
            $data['modified_by']  = (int) $user->id;

            $data['author_name']  = $displayName;
            $data['author_email'] = (string) $user->email;

            $data['state'] = 1;
        }


        if (empty($data['created'])) {
            $data['created'] = $now;
        }
        $data['modified'] = $now;


        /**
         * ===========================================
         * FIXED REPLY LOGIC (Edit-safe)
         * ===========================================
         */

        $isEdit = !empty($data['id']);

        if ($isEdit)
        {
            // existierenden Kommentar laden
            $existing = $this->getTable('Comment', 'Administrator');
            $existing->load((int) $data['id']);

            // Struktur NIEMALS überschreiben
            $data['parent_id']         = (int) $existing->parent_id;
            $data['quoted_comment_id'] = (int) $existing->quoted_comment_id;
        }
        else
        {
            // Neuer Kommentar
            $data['parent_id']         = (int) ($data['parent_id'] ?? 0);
            $data['quoted_comment_id'] = (int) ($data['quoted_comment_id'] ?? 0);

            if ($data['parent_id'] > 0)
            {
                $parentTable = $this->getTable('Comment', 'Administrator');

                if (!$parentTable->load($data['parent_id'])) {
                    throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_PARENT_NOT_FOUND'), 400);
                }

                if ((int) $parentTable->item_id !== (int) $data['item_id']) {
                    throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_PARENT_WRONG_ITEM'), 400);
                }

                if ((int) $parentTable->parent_id !== 0) {
                    throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_MAX_REPLY_DEPTH_REACHED'), 400);
                }

                if ($data['quoted_comment_id'] === 0) {
                    $data['quoted_comment_id'] = $data['parent_id'];
                }
            }
        }


        /**
         * ===========================================
         * IP HANDLING
         * ===========================================
         */
        $data['ip'] = $this->getProcessedIp();
        $data['ip_hash'] = $this->buildIpHash();


        /**
         * ===========================================
         * SAVE
         * ===========================================
         */

        // Force state for logged-in users
        if (!$user->guest) {
            $data['state'] = 1;
        }

        $table = $this->getTable('Comment', 'Administrator');

        if (!$table->save($data)) {
            throw new RuntimeException($table->getError() ?: Text::_('COM_R3DCOMMENTS_ERROR_SAVE_FAILED'), 500);
        }

        return (int) $table->id;
    }


    /**
     * IP anonymisation
     */
    protected function getProcessedIp(): ?string
    {
        $server = $this->getState('_server', $_SERVER);
        $ip     = $server['REMOTE_ADDR'] ?? '';

        if (!$ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts    = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            if (count($parts) > 1) {
                $parts[count($parts) - 1] = '0000';
            }
            return implode(':', $parts);
        }

        return null;
    }

    /**
     * Build deterministic IP hash for rate-limiting without storing raw IP.
     */
    protected function buildIpHash(): ?string
    {
        $server = $this->getState('_server', $_SERVER);
        $ip     = trim((string) ($server['REMOTE_ADDR'] ?? ''));

        if ($ip === '') {
            return null;
        }

        $secret = (string) Factory::getConfig()->get('secret', '');

        if ($secret === '') {
            return hash('sha256', $ip);
        }

        return hash_hmac('sha256', $ip, $secret);
    }
}
