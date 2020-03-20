<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				JoomlaBoat.com
/-------------------------------------------------------------------------------------------------------/

	@version		1.6.1
	@build			19th July, 2018
	@created		28th May, 2019
	@package		Custom Tables
	@subpackage		default_head.php
	@author			Ivan Komlev <https://joomlaboat.com>
	@copyright		Copyright (C) 2018. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

/------------------------------------------------------------------------------------------------------*/


// no direct access
defined('_JEXEC') or die('Restricted access');

require_once ('components/com_customtables/libraries/languages.php');

jimport('joomla.application.component.view');


class CustomTablesViewListOfOptions extends JViewLegacy
{
	//var $_name = 'list';
	var $languages;

	function display($tpl=null)
	{

		//$mainframe = JFactory::getApplication();

		//$this->_layout = 'default';


		// Set toolbar items for the page
		CustomtablesHelper::addSubmenu('Options');
		
		$this->addToolBar();
		$this->sidebar = JHtmlSidebar::render();

		$LangMisc	= new ESLanguages;
		$LanguageList=$LangMisc->getLanguageList();
		$this->assignRef('LanguageList',		$LanguageList);

		$document =  JFactory::getDocument();
		$document->setTitle(JText::_('View List Items'));

		$limitstart = JRequest::getVar('limitstart', '0', '', 'int');
		$items		= $this->get('Items');
		$pagination	= $this->get('Pagination');
		$lists		= $this->_getViewLists();
		$user		= JFactory::getUser();

		// Ensure ampersands and double quotes are encoded in item titles
		foreach ($items as $i => $item) {
			$treename = $item->treename;
			$treename = JFilterOutput::ampReplace($treename);
			$treename = str_replace('"', '&quot;', $treename);
			$items[$i]->treename = $treename;
		}

		//Ordering allowed ?
		$ordering = ($lists['order'] == 'm.ordering');

		JHTML::_('behavior.tooltip');

		$this->assignRef('items', $items);
		$this->assignRef('pagination', $pagination);
		$this->assignRef('lists', $lists);
		$this->assignRef('user', $user);

		$this->assignRef('ordering', $ordering);
		$this->assignRef('limitstart', $limitstart);
		$isselectable=true;
		$this->assignRef('isselectable', $isselectable);

		$LangMisc	= new ESLanguages;
		$this->languages=$LangMisc->getLanguageList();

		parent::display($tpl);
	}


	protected function addToolBar()
        {
		JToolBarHelper::title( JText::_( 'Custom Tables - List' ), 'menu.png' );


		JToolBarHelper::addNew('options.add');
		JToolBarHelper::editList('options.edit');

		JToolBarHelper::custom( 'listofoptions.copy', 'copy.png', 'copy_f2.png', 'Copy', true);
		JToolBarHelper::deleteList('', 'listofoptions.delete');

        }



	function &_getViewLists()
	{
		$mainframe = JFactory::getApplication();
		$db		= JFactory::getDBO();

		$context			= 'com_customtables.listofoptions.';

		$filter_order		= $mainframe->getUserStateFromRequest($context."filter_order",		'filter_order',		'optionname',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest($context."filter_order_Dir",	'filter_order_Dir',	'ASC',			'word' );

		if($filter_order!='id' and $filter_order!='optionname')
			$filter_order='id';

		$filter_rootparent	= $mainframe->getUserStateFromRequest($context."filter_rootparent",'filter_rootparent','','int' );

		$levellimit		= $mainframe->getUserStateFromRequest($context."levellimit",		'levellimit',		10,				'int' );
		$search			= $mainframe->getUserStateFromRequest($context."search",			'search',			'',				'string' );
		$search			= JString::strtolower( $search );

		// level limit filter
		$lists['levellist']	= JHTML::_('select.integerlist',    1, 20, 1, 'levellimit', 'size="1" onchange="document.adminForm.submit();"', $levellimit );



		// Category List
		$javascript = 'onchange="document.adminForm.submit();"';


		$ListModel = $this->getModel();
		$available_rootparents=$ListModel->getAllRootParents();
		$lists['rootparent']=JHTML::_('select.genericlist', $available_rootparents, 'filter_rootparent', $javascript ,'id','optionname', $filter_rootparent);

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;



		$lists['order']		= $filter_order;

		// search filter
		$lists['search']= $search;

		return $lists;
	}
}
