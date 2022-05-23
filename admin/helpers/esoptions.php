<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// Check to ensure this file is included in Joomla!
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');


class JHTMLESOptions
{

    public static function options($currentoptionid, $control_name, $value)
    {

        $db = Factory::getDBO();

        $query = 'SELECT id, optionname '
            . ' FROM #__customtables_options '
            . ' WHERE id!=' . (int)$currentoptionid
            . ' ORDER BY optionname';
        $db->setQuery($query);
        $optionlist = $db->loadAssocList();
        if (!$optionlist) $optionlist = array();

        $optionlist[] = array('id' => '0', 'optionname' => '- ROOT');

        return JHTML::_('select.genericlist', $optionlist, $control_name, 'class="inputbox"', 'id', 'optionname', $value);


    }


}
