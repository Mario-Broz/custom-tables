 <?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditFiles extends JViewLegacy
{
	function display($tpl = null)
	{
		$mainframe = JFactory::getApplication('site');
                
		$user = JFactory::getUser();
		$userid = $user->get('id');
		if((int)$userid==0)
			return false;
		
		$this->Model = $this->getModel();
				
		parent::display($tpl);
	}
}
