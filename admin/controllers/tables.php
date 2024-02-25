<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage controllers/tables.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC')) die('Restricted access');

use CustomTables\common;
use CustomTables\CTUser;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Tables Controller
 */
class CustomtablesControllerTables extends FormController
{
	/**
	 * Current or most recently performed task.
	 *
	 * @var    string
	 * @since  12.2
	 * @note   Replaces _task.
	 */
	protected $task;

	public function __construct($config = array())
	{
		$this->view_list = 'Listoftables'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param object $model The model.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 *
	 * @since   2.5
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(common::translate('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Tables', '', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_customtables&view=listoftables' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param string $key The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   12.2
	 */
	public function cancel($key = null)
	{
		// get the referal details
		$this->ref = common::inputGet('ref', 0, 'word');
		$this->refid = common::inputGet('refid', 0, 'int');

		$cancel = parent::cancel($key);

		if ($cancel) {
			if ($this->refid) {
				$redirect = '&view=' . (string)$this->ref . '&layout=edit&id=' . (int)$this->refid;

				// Redirect to the item screen.
				$this->setRedirect(
					Route::_(
						'index.php?option=' . $this->option . $redirect, false
					)
				);
			} elseif ($this->ref) {
				$redirect = '&view=' . (string)$this->ref;

				// Redirect to the list screen.
				$this->setRedirect(
					Route::_(
						'index.php?option=' . $this->option . $redirect, false
					)
				);
			}
		} else {
			// Redirect to the items screen.
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list, false
				)
			);
		}
		return $cancel;
	}

	/**
	 * Method to save a record.
	 *
	 * @param string $key The name of the primary key of the URL variable.
	 * @param string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function save($key = null, $urlVar = null)
	{
		// get the referral details
		$this->ref = common::inputGet('ref', 0, 'word');
		$this->refid = common::inputGet('refid', 0, 'int');

		if ($this->ref || $this->refid) {
			// to make sure the item is checkedin on redirect
			$this->task = 'save';
		}

		$saved = parent::save($key, $urlVar);

		if ($this->refid && $saved) {
			$redirect = '&view=' . (string)$this->ref . '&layout=edit&id=' . (int)$this->refid;

			// Redirect to the item screen.
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . $redirect, false
				)
			);
		} elseif ($this->ref && $saved) {
			$redirect = '&view=' . $this->ref;

			// Redirect to the list screen.
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . $redirect, false
				)
			);
		}
		return $saved;
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param array $data An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array())
	{
		// In the absence of better information, revert to the component permissions.
		return parent::allowAdd($data);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param array $data An array of input data.
	 * @param string $key The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// get user object.
		$user = new CTUser();
		// get record id.
		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;


		if ($recordId) {
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.tables.' . (int)$recordId);
			if (!$permission) {
				if ($user->authorise('core.edit.own', 'com_customtables.tables.' . $recordId)) {
					// Now test the owner is the user.
					$ownerId = (int)isset($data['created_by']) ? $data['created_by'] : 0;
					if (empty($ownerId)) {
						// Need to do a lookup from the model.
						$record = $this->getModel()->getItem($recordId);

						if (empty($record)) {
							return false;
						}
						$ownerId = $record->created_by;
					}

					// If the owner matches 'me' then allow.
					if ($ownerId == $user->id) {
						if ($user->authorise('core.edit.own', 'com_customtables')) {
							return true;
						}
					}
				}
				return false;
			}
		}
		// Since there is no permission, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param integer $recordId The primary key id for the item.
	 * @param string $urlVar The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   12.2
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$tmpl = common::inputGetCmd('tmpl');
		$layout = common::inputGet('layout', 'edit', 'string');

		$ref = common::inputGet('ref', 0, 'string');
		$refid = common::inputGet('refid', 0, 'int');

		// Setup redirect info.

		$append = '';

		if ($refid) {
			$append .= '&ref=' . (string)$ref . '&refid=' . (int)$refid;
		} elseif ($ref) {
			$append .= '&ref=' . (string)$ref;
		}

		if ($tmpl) {
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout) {
			$append .= '&layout=' . $layout;
		}

		if ($recordId) {
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		return $append;
	}
}
