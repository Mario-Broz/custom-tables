<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTablesImageMethods;
use ESTables;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use CustomTablesKeywordSearch;
use Joomla\Registry\Registry;
use mysql_xdevapi\Exception;
use CustomTables\CustomPHP\CleanExecute;

class CT
{
	var Languages $Languages;
	var Environment $Env;
	var ?Params $Params;
	var ?Table $Table;
	var ?array $Records;
	var string $GroupBy;
	var ?Ordering $Ordering;
	var ?Filtering $Filter;
	var ?string $alias_fieldname;
	var int $Limit;
	var int $LimitStart;
	var bool $isEditForm;
	var array $editFields;
	var array $LayoutVariables;
	var array $errors;
	var array $messages;

	//Joomla Specific
	var $app;
	var $document;

	function __construct(?Registry $menuParams = null, $blockExternalVars = true, ?string $ModuleId = null, bool $enablePlugin = true)
	{
		$this->errors = [];
		$this->messages = [];

		if (defined('_JEXEC')) {
			$this->app = Factory::getApplication();
			$this->document = $this->app->getDocument();
		}

		$this->Languages = new Languages;
		$this->Env = new Environment($enablePlugin);
		$this->Params = new Params($menuParams, $blockExternalVars, $ModuleId);

		$this->GroupBy = '';
		$this->isEditForm = false;
		$this->LayoutVariables = [];
		$this->editFields = [];

		$this->Limit = 0;
		$this->LimitStart = 0;

		$this->Table = null;
		$this->Records = null;
		$this->Ordering = null;
		$this->Filter = null;
	}

	function isRecordNull(?array $row): bool
	{
		if (is_null($row))
			return true;

		if (!is_array($row))
			return true;

		if (count($row) == 0)
			return true;

		if (!isset($row[$this->Table->realidfieldname]))
			return true;

		$id = $row[$this->Table->realidfieldname];

		if (is_null($id))
			return true;

		if ($id == '')
			return true;

		if (is_numeric($id) and intval($id) == 0)
			return true;

		return false;
	}

	function setParams($menuParams = null, $blockExternalVars = true, ?string $ModuleId = null): void
	{
		$this->Params->setParams($menuParams, $blockExternalVars, $ModuleId);
	}

	function getTable($tableNameOrID, $userIdFieldName = null): void
	{
		$this->Table = new Table($this->Languages, $this->Env, $tableNameOrID, $userIdFieldName);
		$this->Ordering = new Ordering($this->Table, $this->Params);
		$this->prepareSEFLinkBase();
	}

	public function setTable(array $tableRow, $userIdFieldName = null): void
	{
		$this->Table = new Table($this->Languages, $this->Env, 0);
		$this->Table->setTable($tableRow, $userIdFieldName);

		$this->Ordering = new Ordering($this->Table, $this->Params);

		$this->prepareSEFLinkBase();
	}

	protected function prepareSEFLinkBase(): void
	{
		if (is_null($this->Table))
			return;

		if (is_null($this->Table->fields))
			return;

		if (!str_contains($this->Env->current_url, 'option=com_customtables')) {
			foreach ($this->Table->fields as $fld) {
				if ($fld['type'] == 'alias') {
					$this->alias_fieldname = $fld['fieldname'];
					return;
				}
			}
		}
		$this->alias_fieldname = null;
	}

	function setFilter(?string $filter_string = null, int $showpublished = 0): void
	{
		$this->Filter = new Filtering($this, $showpublished);
		if ($filter_string != '')
			$this->Filter->addWhereExpression($filter_string);
	}

	function getRecords($all = false, $limit = 0): bool
	{
		$where = count($this->Filter->where) > 0 ? ' WHERE ' . implode(' AND ', $this->Filter->where) : '';
		$where = str_replace('\\', '', $where); //Just to make sure that there is nothing weird in the query

		if ($this->getNumberOfRecords($where) == -1)
			return false;

		$query = $this->buildQuery($where);

		if ($this->Table->recordcount > 0) {

			if ($limit > 0) {
				$this->Records = database::loadAssocList($query, 0, $limit);
				$this->Limit = $limit;
			} else {
				$the_limit = $this->Limit;

				if ($all) {
					if ($the_limit > 0)
						$this->Records = database::loadAssocList($query, 0, 20000);
					else
						$this->Records = database::loadAssocList($query);
				} else {
					if ($the_limit > 20000)
						$the_limit = 20000;

					if ($the_limit == 0)
						$the_limit = 20000; //or we will run out of memory

					if ($this->Table->recordcount < $this->LimitStart or $this->Table->recordcount < $the_limit)
						$this->LimitStart = 0;

					try {

						$this->Records = @database::loadAssocList($query, $this->LimitStart, $the_limit);
					} catch (\Exception $e) {
						echo $query;
						echo $e->getMessage();
						return false;
					}
				}
			}
		} else
			$this->Records = [];

		if ($this->Limit == 0)
			$this->Limit = 20000;

		return true;
	}

	function getNumberOfRecords(string $where = ''): int
	{
		$query_check_table = 'SHOW TABLES LIKE ' . database::quote(database::realTableName($this->Table->realtablename));
		$rows = database::loadObjectList($query_check_table);
		if (count($rows) == 0)
			return -1;

		$query_analytical = 'SELECT COUNT(' . $this->Table->tablerow['realidfieldname'] . ') AS count FROM ' . $this->Table->realtablename . ' ' . $where;

		try {
			$rows = database::loadObjectList($query_analytical);
		} catch (Exception $e) {
			echo 'Database error happened';
			echo $e->getMessage();
			return 0;
		}

		if (count($rows) == 0)
			$this->Table->recordcount = -1;
		else
			$this->Table->recordcount = intval($rows[0]->count);

		return $this->Table->recordcount;
	}

	function buildQuery($where): ?string
	{
		$ordering = $this->GroupBy != '' ? [$this->GroupBy] : [];

		if (is_null($this->Table) or is_null($this->Table->tablerow)) {
			$this->errors[] = 'Table not set.';
			return null;
		}

		if ($this->Ordering->ordering_processed_string !== null) {
			$this->Ordering->parseOrderByString();
		}

		$selects = $this->Table->selects;

		if ($this->Ordering->orderby !== null) {
			if ($this->Ordering->selects !== null)
				$selects[] = $this->Ordering->selects;

			$ordering[] = $this->Ordering->orderby;
		}

		$query = 'SELECT ' . implode(',', $selects) . ' FROM ' . $this->Table->realtablename . ' ';
		$query .= $where;
		$query .= ' GROUP BY ' . $this->Table->realtablename . '.' . $this->Table->realidfieldname;

		if (count($ordering) > 0)
			$query .= ' ORDER BY ' . implode(',', $ordering);

		return $query;
	}

	function getRecordsByKeyword(): void
	{
		$moduleId = common::inputGetInt('moduleid', 0);
		if ($moduleId != 0) {
			$keywordSearch = common::inputGetString('eskeysearch_' . $moduleId, '');
			if ($keywordSearch != '') {
				require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR
					. 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'filter' . DIRECTORY_SEPARATOR . 'keywordsearch.php');

				$KeywordSearcher = new CustomTablesKeywordSearch($this);

				$KeywordSearcher->groupby = $this->GroupBy;
				$KeywordSearcher->esordering = $this->Ordering->ordering_processed_string;

				$this->Records = $KeywordSearcher->getRowsByKeywords(
					$keywordSearch,
					$this->Table->recordcount,
					(int)$this->app->getState('limit'),
					$this->LimitStart
				);

				if ($this->Table->recordcount < $this->LimitStart)
					$this->LimitStart = 0;
			}
		}
	}

	function getRecordList(): array
	{
		if ($this->Table->recordlist !== null)
			return $this->Table->recordlist;

		$recordList = [];

		foreach ($this->Records as $row)
			$recordList[] = $row[$this->Table->realidfieldname];

		$this->Table->recordlist = $recordList;
		return $recordList;
	}

	function applyLimits($limit = 0): void
	{
		if ($limit != 0) {
			$this->Limit = $limit;
			$this->LimitStart = 0;
			return;
		}

		if (defined('_JEXEC')) {
			$limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
			$this->Limit = $this->app->getUserState($limit_var, 0);
		} else
			$this->Limit = 0;

		//Grouping
		if ($this->Params->groupBy != '')
			$this->GroupBy = Fields::getRealFieldName($this->Params->groupBy, $this->Table->fields);
		else
			$this->GroupBy = '';

		if ($this->Params->blockExternalVars) {
			if ((int)$this->Params->limit > 0) {
				$this->Limit = (int)$this->Params->limit;
				$this->LimitStart = common::inputGetInt('start', 0);
				$this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
			} else {
				$this->Limit = 0;
				$this->LimitStart = 0;
			}
		} else {
			$this->LimitStart = common::inputGetInt('start', 0);

			if (defined('_JEXEC')) {
				$limit_var = 'com_customtables.limit_' . $this->Params->ItemId;
				$this->Limit = $this->app->getUserState($limit_var, 0);
			} else
				$this->Limit = 0;

			if ($this->Limit == 0 and (int)$this->Params->limit > 0) {
				$this->Limit = (int)$this->Params->limit;
			}

			// In case limit has been changed, adjust it
			$this->LimitStart = ($this->Limit != 0 ? (floor($this->LimitStart / $this->Limit) * $this->Limit) : 0);
		}
	}

	function loadJSAndCSS(): void
	{
		if ($this->Params->ModuleId === null or (int)$this->Params->ModuleId == 0) {
			//JQuery and Bootstrap
			if ($this->Env->version < 4) {
				$this->document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/jquery.min.js"></script>');
				$this->document->addCustomTag('<script src="' . URI::root(true) . '/media/jui/js/bootstrap.min.js"></script>');
			} else {
				if (defined('_JEXEC')) {
					HTMLHelper::_('jquery.framework');
					$this->document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/media/system/css/fields/switcher.css">');
				}
			}
		}

		if (defined('_JEXEC')) {
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/jquery.uploadfile.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/jquery.form.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/base64.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/catalog.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/edit.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/esmulti.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/modal.js"></script>');
			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/uploader.js"></script>');

			$this->document->addCustomTag('<script src="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.js"></script>');
			$this->document->addCustomTag('<link rel="stylesheet" href="' . URI::root(true) . '/components/com_customtables/libraries/virtualselect/virtual-select.min.css" />');
		}

		if (defined('_JEXEC')) {
			$params = ComponentHelper::getParams('com_customtables');
			$googleMapAPIKey = $params->get('googlemapapikey');

			if ($googleMapAPIKey !== null and $googleMapAPIKey != '')
				$this->document->addCustomTag('<script src="https://maps.google.com/maps/api/js?key=' . $googleMapAPIKey . '&sensor=false"></script>');

			$this->document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/combotree.js"></script>');
			$this->document->addCustomTag('<script>let ctWebsiteRoot = "' . $this->Env->WebsiteRoot . '";</script>');

			if ($this->Params->ModuleId == null)
				$this->document->addCustomTag('<script>ctItemId = "' . $this->Params->ItemId . '";</script>');

			//Styles
			$this->document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/style.css" type="text/css" rel="stylesheet" >');
			$this->document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/modal.css" type="text/css" rel="stylesheet" >');
			$this->document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/uploadfile.css" rel="stylesheet">');

			$this->document->addCustomTag('<link href="' . URI::root(true) . '/media/system/css/fields/calendar.min.css" rel="stylesheet" />');
			$this->document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar-locales/date/gregorian/date-helper.min.js" defer></script>');
			$this->document->addCustomTag('<script src="' . URI::root(true) . '/media/system/js/fields/calendar.min.js" defer></script>');

			Text::script('COM_CUSTOMTABLES_JS_SELECT_RECORDS');
			Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE1');
			Text::script('COM_CUSTOMTABLES_JS_SELECT_DO_U_WANT_TO_DELETE');
			Text::script('COM_CUSTOMTABLES_JS_NOTHING_TO_SAVE');
			Text::script('COM_CUSTOMTABLES_JS_SESSION_EXPIRED');
			Text::script('COM_CUSTOMTABLES_SELECT');
			Text::script('COM_CUSTOMTABLES_SELECT_NOTHING');
			Text::script('COM_CUSTOMTABLES_ADD');
			Text::script('COM_CUSTOMTABLES_REQUIRED');
			Text::script('COM_CUSTOMTABLES_NOT_SELECTED');
			Text::script('COM_CUSTOMTABLES_JS_EMAIL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_URL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SECURE_URL_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED');
			Text::script('COM_CUSTOMTABLES_JS_HOSTNAME_INVALID');
			Text::script('COM_CUSTOMTABLES_JS_SIGNATURE_REQUIRED');
		}
	}

	public function deleteSingleRecord($listing_id): int
	{
		//delete images if exist
		$imageMethods = new CustomTablesImageMethods;

		$query = 'SELECT * FROM ' . $this->Table->realtablename . ' WHERE ' . $this->Table->realidfieldname . '=' . database::quote($listing_id);
		$rows = database::loadAssocList($query);

		if (count($rows) == 0)
			return -1;

		$row = $rows[0];

		foreach ($this->Table->fields as $fieldrow) {
			$field = new Field($this, $fieldrow, $row);

			if ($field->type == 'image') {
				$ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

				//delete single image
				if ($row[$field->realfieldname] !== null) {
					$imageMethods->DeleteExistingSingleImage(
						$row[$field->realfieldname],
						$ImageFolder_,
						$field->params[0],
						$this->Table->realtablename,
						$field->realfieldname,
						$this->Table->realidfieldname
					);
				}
			} elseif ($field->type == 'imagegallery') {
				$ImageFolder_ = CustomTablesImageMethods::getImageFolder($field->params);

				//delete gallery images if exist
				$galleryName = $field->fieldname;
				$photoTableName = '#__customtables_gallery_' . $this->Table->tablename . '_' . $galleryName;

				$query = 'SELECT photoid FROM ' . $photoTableName . ' WHERE listingid=' . database::quote($listing_id);
				$photoRows = database::loadObjectList($query);
				$imageGalleryPrefix = 'g';

				foreach ($photoRows as $photoRow) {
					$imageMethods->DeleteExistingGalleryImage(
						$ImageFolder_,
						$imageGalleryPrefix,
						$this->Table->tableid,
						$galleryName,
						$photoRow->photoid,
						$field->params[0],
						true
					);
				}
			}
		}

		$query = 'DELETE FROM ' . $this->Table->realtablename . ' WHERE ' . $this->Table->realidfieldname . '=' . database::quote($listing_id);
		database::setQuery($query);
		$this->Table->saveLog($listing_id, 5);
		$new_row = array();

		if ($this->Env->advancedTagProcessor)
			CleanExecute::executeCustomPHPfile($this->Table->tablerow['customphp'], $new_row, $row);

		return 1;
	}

	public function setPublishStatusSingleRecord($listing_id, $status): int
	{
		if (!$this->Table->published_field_found)
			return -1;

		$query = 'UPDATE ' . $this->Table->realtablename . ' SET published=' . (int)$status . ' WHERE ' . $this->Table->realidfieldname . '=' . database::quote($listing_id);
		database::setQuery($query);

		if ($status == 1)
			$this->Table->saveLog($listing_id, 3);
		else
			$this->Table->saveLog($listing_id, 4);

		$this->RefreshSingleRecord($listing_id, 0);
		return 1;
	}

	public function RefreshSingleRecord($listing_id, $save_log): int
	{
		$query = 'SELECT ' . implode(',', $this->Table->selects) . ' FROM ' . $this->Table->realtablename
			. ' WHERE ' . $this->Table->realidfieldname . '=' . database::quote($listing_id) . ' LIMIT 1';

		$rows = database::loadAssocList($query);

		if (count($rows) == 0)
			return -1;

		$row = $rows[0];
		$saveField = new SaveFieldQuerySet($this, $row, false);

		//Apply default values
		foreach ($this->Table->fields as $fieldRow) {

			if (!$saveField->checkIfFieldAlreadyInTheList($fieldRow['realfieldname'])) {
				$saveField->applyDefaults($fieldRow);
				//if ($saveFieldSet !== null) {
				//$saveField->saveQuery[] = $saveFieldSet;
				//}
			}
		}

		if (count($saveField->row_new) > 0)
			database::update($this->Table->realtablename, $saveField->row_new, [$this->Table->realidfieldname => $listing_id]);

		//End of Apply default values

		common::inputSet("listing_id", $listing_id);

		if ($this->Env->advancedTagProcessor)
			CleanExecute::doPHPonChange($this, $row);

		//update MD5s
		$this->updateMD5($listing_id);

		if ($save_log == 1)
			$this->Table->saveLog($listing_id, 10);

		//TODO use $saveField->saveField
		//$this->updateDefaultValues($row);

		if ($this->Env->advancedTagProcessor)
			CleanExecute::executeCustomPHPfile($this->Table->tablerow['customphp'], $row, $row);

		//Send email note if applicable
		if ($this->Params->onRecordAddSendEmail == 3 and ($this->Params->onRecordSaveSendEmailTo != '' or $this->Params->onRecordAddSendEmailTo != '')) {
			//check conditions

			if ($saveField->checkSendEmailConditions($listing_id, $this->Params->sendEmailCondition)) {
				//Send email conditions met
				$saveField->sendEmailIfAddressSet($listing_id, $row);//,$new_username,$new_password);
			}
		}
		return 1;
	}

	protected function updateMD5(string $listing_id): void
	{
		//TODO: Use savefield
		foreach ($this->Table->fields as $fieldrow) {
			if ($fieldrow['type'] == 'md5') {
				$fieldsToCount = explode(',', str_replace('"', '', $fieldrow['typeparams']));//only field names, nothing else

				$fields = array();
				foreach ($fieldsToCount as $f) {
					//to make sure that field exists
					foreach ($this->Table->fields as $fieldrow2) {
						if ($fieldrow2['fieldname'] == $f and $fieldrow['fieldname'] != $f)
							$fields[] = 'COALESCE(' . $fieldrow2['realfieldname'] . ')';
					}
				}

				if (count($fields) > 1) {
					database::setQuery('UPDATE ' . $this->Table->realtablename . ' SET '
						. database::quoteName($fieldrow['realfieldname']) . '=MD5(CONCAT_WS(' . implode(',', $fields) . ')) WHERE '
						. database::quoteName($this->Table->realidfieldname) . '=' . database::quote($listing_id)
					);
				}
			}
		}
	}

	public function CheckAuthorization(int $action = 1): bool
	{
		$listing_id = $this->Params->listing_id;
		$userIdField = $this->Params->userIdField;

		if ($action == 0)
			return true;

		if ($action == 5) //force edit
		{
			$action = 1;
		} else {
			if ($action == 1 and $listing_id == 0)
				$action = 4; //add new
		}

		if ($this->Params->guestCanAddNew == 1)
			return true;

		if ($this->Params->guestCanAddNew == -1 and $listing_id == 0)
			return false;

		//check is authorized or not
		if ($action == 1)
			$userGroup = $this->Params->editUserGroups;
		elseif ($action == 2)
			$userGroup = $this->Params->publishUserGroups;
		elseif ($action == 3)
			$userGroup = $this->Params->deleteUserGroups;
		elseif ($action == 4)
			$userGroup = $this->Params->addUserGroups;
		else
			$userGroup = null;

		if ($this->Env->user->id === null)
			return false;

		if ($this->Env->user->isUserAdministrator) {
			//Super Users have access to everything
			return true;
		}

		if ($listing_id === null or $listing_id === "" or $listing_id == 0 or $userIdField == '')
			return $this->Env->user->checkUserGroupAccess($userGroup);

		$theAnswerIs = $this->checkIfItemBelongsToUser($listing_id, $userIdField);

		if (!$theAnswerIs)
			return $this->Env->user->checkUserGroupAccess($userGroup);

		return true;
	}

	public function checkIfItemBelongsToUser(string $listing_id, string $userIdField): bool
	{
		$wheres = $this->UserIDField_BuildWheres($userIdField, $listing_id);
		$query = 'SELECT c.' . $this->Table->realidfieldname . ' FROM ' . $this->Table->realtablename . ' AS c WHERE ' . implode(' AND ', $wheres) . ' LIMIT 1';

		if (database::getNumRowsOnly($query) == 1) {
			return true;
		}
		return false;
	}

	public function UserIDField_BuildWheres(string $userIdField, string $listing_id): array
	{
		$wheres = [];

		$statement_items = common::ExplodeSmartParams($userIdField); //"and" and "or" as separators

		$wheres_owner = array();

		foreach ($statement_items as $item) {
			$field = $item[1];
			if (!str_contains($field, '.')) {
				//example: user
				//check if the record belong to the current user
				$user_field_row = Fields::FieldRowByName($field, $this->Table->fields);
				$wheres_owner[] = [$item[0], $user_field_row['realfieldname'] . '=' . $this->Env->user->id];
			} else {
				//example: parents(children).user
				$statement_parts = explode('.', $field);
				if (count($statement_parts) != 2) {
					$this->errors[] = common::translate('COM_CUSTOMTABLES_MENUITEM_USERID_FIELD_ERROR');
					return [];
				}

				$table_parts = explode('(', $statement_parts[0]);
				if (count($table_parts) != 2) {
					$this->errors[] = common::translate('COM_CUSTOMTABLES_MENUITEM_USERID_FIELD_ERROR');
					return [];
				}

				$parent_tablename = $table_parts[0];
				$parent_join_field = str_replace(')', '', $table_parts[1]);
				$parent_user_field = $statement_parts[1];

				$parent_table_row = ESTables::getTableRowByName($parent_tablename);

				if (!is_object($parent_table_row)) {
					$this->errors[] = common::translate('COM_CUSTOMTABLES_MENUITEM_TABLENOTFOUND_ERROR');
					return [];
				}

				$parent_table_fields = Fields::getFields($parent_table_row->id);

				$parent_join_field_row = Fields::FieldRowByName($parent_join_field, $parent_table_fields);

				if (count($parent_join_field_row) == 0) {
					$this->errors[] = common::translate('COM_CUSTOMTABLES_MENUITEM_TABLENOTFOUND_ERROR');
					return [];
				}

				if ($parent_join_field_row['type'] != 'sqljoin' and $parent_join_field_row['type'] != 'records') {
					$this->errors[] = common::translate('Menu Item - "UserID Field name" parameter has an error: Wrong join field type "' . $parent_join_field_row['type'] . '". Accepted types: "sqljoin" and "records" .');
					return [];
				}

				//User field

				$parent_user_field_row = Fields::FieldRowByName($parent_user_field, $parent_table_fields);

				if (count($parent_user_field_row) == 0) {
					$this->errors[] = common::translate('Menu Item - "UserID Field name" parameter has an error: User field "' . $parent_user_field . '" not found.');
					return [];
				}

				if ($parent_user_field_row['type'] != 'userid' and $parent_user_field_row['type'] != 'user') {
					$this->errors[] = common::translate('Menu Item - "UserID Field name" parameter has an error: Wrong user field type "' . $parent_join_field_row['type'] . '". Accepted types: "userid" and "user" .');
					return [];
				}

				$parent_wheres = [];

				$parent_wheres[] = 'p.' . $parent_user_field_row['realfieldname'] . '=' . $this->Env->user->id;

				$fieldType = $parent_join_field_row['type'];
				if ($fieldType != 'sqljoin' and $fieldType != 'records')
					return [];

				if ($fieldType == 'sqljoin')
					$parent_wheres[] = 'p.' . $parent_join_field_row['realfieldname'] . '=c.listing_id';

				if ($fieldType == 'records')
					$parent_wheres[] = 'INSTR(p.' . $parent_join_field_row['realfieldname'] . ',CONCAT(",",c.' . $this->Table->realidfieldname . ',","))';

				$q = '(SELECT p.' . $parent_table_row->realidfieldname . ' FROM ' . $parent_table_row->realtablename . ' AS p WHERE ' . implode(' AND ', $parent_wheres) . ' LIMIT 1) IS NOT NULL';

				$wheres_owner[] = [$item[0], $q];
			}
		}

		$wheres_owner_str = '';
		$index = 0;
		foreach ($wheres_owner as $field) {
			if ($index == 0)
				$wheres_owner_str .= $field[1];
			else
				$wheres_owner_str .= ' ' . strtoupper($field[0]) . ' ' . $field[1];

			$index += 1;
		}

		if ($listing_id != '' and $listing_id != 0)
			$wheres[] = $this->Table->realidfieldname . '=' . database::quote($listing_id);

		if ($wheres_owner_str != '')
			$wheres[] = '(' . $wheres_owner_str . ')';

		return $wheres;
	}
}
