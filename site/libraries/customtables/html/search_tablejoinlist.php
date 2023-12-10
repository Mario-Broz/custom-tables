<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class Search_tablejoinlist extends BaseSearch
{
	function __construct(CT &$ct, Field $field, string $moduleName, array $attributes, int $index, string $where, string $whereList, string $objectName)
	{
		parent::__construct($ct, $field, $moduleName, $attributes, $index, $where, $whereList, $objectName);
		BaseInputBox::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render($value): string
	{
		if (str_contains($this->attributes['onchange'], 'onkeypress='))
			$this->attributes['onchange'] .= ' onkeypress="es_SearchBoxKeyPress(event)"';

		$result = '';

		if (count($this->field->params) < 1)
			$result .= 'table not specified';

		if (count($this->field->params) < 2)
			$result .= 'field or layout not specified';

		if (count($this->field->params) < 3)
			$result .= 'selector not specified';

		$esr_table = $this->field->params[0];
		$esr_field = $this->field->params[1];

		if ($this->whereList != '')
			$esr_filter = $this->whereList;
		elseif (count($this->field->params) > 3)
			$esr_filter = $this->field->params[3];
		else
			$esr_filter = '';

		$dynamic_filter = '';

		$sortByField = '';
		if (isset($this->field->params[5]))
			$sortByField = $this->field->params[5];

		/*
		$v = [];
		$v[] = $index;
		$v[] = 'this.value';
		$v[] = '"' . $this->field->fieldname . '"';
		$v[] = '"' . urlencode($where) . '"';
		$v[] = '"' . urlencode($whereList) . '"';
		$v[] = '"' . $this->ct->Languages->Postfix . '"';
		*/

		$this->attributes['id'] = $this->objectName;
		$this->attributes['name'] = $this->objectName;

		if (is_array($value))
			$value = implode(',', $value);

		require_once('inputbox_tablejoin.php');
		require_once('inputbox_tablejoinlist.php');

		$InputBox_TableJoinList = new InputBox_TableJoinList($this->ct, $this->field, null, [], $this->attributes);

		return $InputBox_TableJoinList->renderOld(
			$this->field->params,
			$value,
			$esr_table,
			$esr_field,
			'single',
			$esr_filter,
			$dynamic_filter,
			$sortByField
		);
	}
}