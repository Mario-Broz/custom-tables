<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC')) die('Restricted access');

class InputBox_int extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
		self::inputBoxAddCSSClass($this->attributes, $this->ct->Env->version);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetAlnum($this->ct->Env->field_prefix . $this->field->fieldname, '');
			if ($value == '')
				$value = $defaultValue;
		}

		$this->attributes['type'] = 'text';
		$this->attributes['value'] = htmlspecialchars($value ?? '');
		$this->attributes['maxlength'] = 32;

		return '<input ' . self::attributes2String($this->attributes) . ' />';
	}
}