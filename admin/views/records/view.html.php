<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage views/records/view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Version;

/**
 * Records View class
 */
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'edititem.php');
 
class CustomtablesViewRecords extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	var $tableid;
	
	public function display($tpl = null)
	{
		$version = new Version;
		$this->version = (int)$version->getShortVersion();
		
		$app = JFactory::getApplication();
		
		$this->tableid=$app->input->getint('tableid',0);
		$this->listing_id=$app->input->getint('id',0);
	
		if($this->tableid!=0)
		{
			require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
			
			$table=ESTables::getTableRowByID($this->tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				$this->tableid=0;
				return;
			}
			else
			{
				require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
				$this->tablename=$table->tablename;
				$this->tabletitle=$table->tabletitle;
			}
		}
		
		$paramsArray=$this->getRecordParams();
		
		$this->params= new JRegistry;
		$this->params->loadArray($paramsArray);
		
		$config=array();
		$this->Model = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $this->params);
		$this->Model->load($this->params,true);
		$this->Model->pagelayout=ESLayouts::createDefaultLayout_Edit($this->Model->esfields,false);
		
		$this->row=array();
		$this->esfields=$this->Model->esfields;

		$user =  JFactory::getUser();
		$this->userid = (int)$user->get('id');
		
		$this->langpostfix = $this->Model->langpostfix;
		$this->esfields = $this->Model->esfields;
		$this->row = $this->Model->row;

		$this->state = $this->get('State');
		// get action permissions

		$this->canDo = ContentHelper::getActions('com_customtables', 'tables');
		$this->canCreate = $this->canDo->get('tables.edit');
		$this->canEdit = $this->canDo->get('tables.edit');
		
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref = JFactory::getApplication()->input->get('ref', 0, 'word');
		$this->refid = JFactory::getApplication()->input->get('refid', 0, 'int');
		$this->referral = '';
		if ($this->refid)
		{
			// return to the item that refered to this item
			$this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
		}
		elseif($this->ref)
		{
			// return to the list view that refered to this item
			$this->referral = '&ref='.(string)$this->ref;
		}

		// Set the toolbar
		$this->addToolBar();
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		$this->formLink=JURI::root(false).'administrator/index.php?option=com_customtables&amp;view=records&amp;layout=edit&amp;tableid='.$this->tableid.'&id='.$this->listing_id;
		$this->formName='adminForm';
		$this->formClass='form-validate';
		$this->formAttribute='';

		parent::display($tpl);

		// Set the document
		$this->setDocument();
		
	}

	function getRecordParams()
	{
		$paramsArray=array();
		$paramsArray['listingid']=$this->listing_id;
		$paramsArray['estableid']=$this->tableid;
		$paramsArray['establename']=$this->tablename;
		$paramsArray['publishstatus']=1;

		return $paramsArray;
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		
		$userId	= $this->userid;
		$isNew = $this->listing_id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'), 'pencil-2 article-add');
		// Built the actions for new and existing records.
		/*
		if ($this->refid || $this->ref)
		{
			if ($this->canCreate && $isNew)
			{
				// We can create the record.
				JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canEdit)
			{
				// We can save the record.
				JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// Do not creat but cancel.
				JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// We can close it.
				JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{*/
			if ($isNew)
			{
				// For new records, check the create permission.
				if ($this->canCreate)
				{
					JToolBarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canEdit)
				{
					// We can save the new record
					JToolBarHelper::apply('records.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('records.save', 'JTOOLBAR_SAVE');
					// We can save this record, but check the create permission to see
					// if we can return to make a new one.
					
					if ($this->canCreate)
					{
						JToolBarHelper::custom('records.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
					
				}
				if ($this->canCreate)
				{
					JToolBarHelper::custom('records.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('records.cancel', 'JTOOLBAR_CLOSE');
			}
		//}
		JToolbarHelper::divider();
		// set help url for this view if found
		//$help_url = CustomtablesHelper::getHelpUrl('records');
		//if (CustomtablesHelper::checkString($help_url))
		//{
		//	JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		///}
	}
	
	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = $this->listing_id == 0;
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_($isNew ? 'COM_CUSTOMTABLES_RECORDS_NEW' : 'COM_CUSTOMTABLES_RECORDS_EDIT'));
	}
}
