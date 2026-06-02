<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\View\Comments;

// No direct access
defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;

/**
 * R3dcomments list view
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $items = [];

    /**
     * The access helper
     *
     * @var    AccessHelper
     */
    protected $access;

    /**
     * The authorization status
     *
     * @var    array
     */
    protected $authorised;

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.6
	 */
	protected $state;

	/**
	 * The component params
	 */
	protected $params;

	/**
	 * The ID of the item
	 */
    protected $item_id;

	/**
	 * @param null $tpl
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
        $app = Factory::getApplication();
        $user = Factory::getApplication()->getIdentity();

        $this->params = $app->getParams('com_r3dcomments');
        $this->item_id = $app->input->getInt('Itemid');
        
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        $this->authorised = [
            'create' => $user->authorise('core.create', 'com_r3dcomments'),
            'edit' => $user->authorise('core.edit', 'com_r3dcomments')
        ];

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        $this->setupDocument();

        parent::display($tpl);
	}

    /**
     * @return  void
     * @throws \Exception
     */
    protected function setupDocument()
    {
        $document = Factory::getDocument();
        if ($document === null) {
            return;
        }
	    $wa = $document->getWebAssetManager();
	    $wa->registerAndUseStyle('com_r3dcomments.site.style', 'com_r3dcomments/css/r3dcomments.site.css');
	    $wa->registerAndUseScript('com_r3dcomments.site.list.script', 'com_r3dcomments/js/list.site.js');

        $app = Factory::getApplication();
        if ($app === null) {
            return;
        }
        $menus = $app->getMenu();
        if ($menus === null) {
            return;
        }

        $menu = $menus->getActive();
        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('JGLOBAL_ARTICLES'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ((int)$app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ((int)$app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }
}
