<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldCTUserGroup extends JFormFieldList
{
	public $type = 'CTUserGroup';

	protected function getOptions()//$name, $value, &$node, $control_name)	
    {
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('id,title');
	 	$query->from('#__usergroups');
		$query->order('title');

		$db->setQuery($query);

		$records=$db->loadObjectList();
		
		if($records)
        {
            foreach($records as $record) 
                $options[] = JHtml::_('select.option', $record->id, $record->title);
        }
		return $options;
    }
}
