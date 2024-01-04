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

class InputBox_float extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetFloat($this->ct->Env->field_prefix . $this->field->fieldname, null);//, 'create-edit-record');
			if ($value === null)
				$value = (float)$defaultValue;
		}

		$this->attributes['type'] = 'text';

		$decimals = intval($this->field->params[0]);
		if ($decimals < 0)
			$decimals = 0;

		if (isset($this->field->params[2]) and $this->field->params[2] == 'smart')
			$this->attributes['onchange'] = (($this->attributes['onchange'] ?? '') == '' ? '' : $this->attributes['onchange'] . ' ') . 'onkeypress="ESsmart_float(this,event,' . $decimals . ')';

		$this->attributes['value'] = htmlspecialchars($value ?? '');

		return '<input ' . self::attributes2String($this->attributes) . ' />';
	}
}