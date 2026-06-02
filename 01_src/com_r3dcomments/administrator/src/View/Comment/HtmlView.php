<?php
/**
 * @package     com_r3dcomments
 * @version     5.0.0
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Administrator\View\Comment;

// No direct access
defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\R3dcomments\Administrator\Helper\R3dcommentsHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Layout\FileLayout;

/**
 * R3dcomments detail view
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		if (count($errors = $this->get('Errors')))
		{
            throw new Exception(implode("\n", $errors));
		}

        $document = Factory::getDocument();
		$wa = $document->getWebAssetManager();
		$wa->registerAndUseStyle('my-style', 'components/com_r3dcomments/assets/css/r3dcomments.css');
		$wa->registerAndUseScript('my-script', 'components/com_r3dcomments/assets/js/detail.js');

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user		= Factory::getApplication()->getIdentity();
		$isNew		= ($this->item->id == 0);

        if (isset($this->item->checked_out))
		{
		    $checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        }
		else
		{
            $checkedOut = false;
        }

		$canDo = R3dcommentsHelper::getActions();

		$title = Text::_('COM_R3DCOMMENTS_TITLE_COMMENT');
		$icon = 'fa fa-file-alt';

		$layout = new FileLayout('joomla.toolbar.title');
		$html = $layout->render([
			'title' => $title,
			'icon' => $icon
		]);

		$app = Factory::getApplication();
		$app->JComponentTitle = str_replace('icon-', '', $html);
		$title = strip_tags($title) . ' - ' . $app->get('sitename') . ' - ' . Text::_('JADMINISTRATION');
		Factory::getDocument()->setTitle($title);

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo['core.edit'] || ($canDo['core.create'])))
		{
			ToolbarHelper::apply('comment.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('comment.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo['core.create']))
		{
			ToolbarHelper::custom('comment.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo['core.create'])
		{
			ToolbarHelper::custom('comment.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('comment.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('comment.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
