<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\CTUser;
use \Joomla\CMS\Factory;

$ct = new CT;

$returnto = $ct->Env->jinput->get('returnto', '', 'BASE64');

if ($theview == 'home')
{
	parent::display();
	$ct->Env->jinput->set('homeparent', 'home');
	$ct->Env->jinput->set('view', 'catalog');
}

	$task = $ct->Env->jinput->getCmd('task');
	
	$app = JFactory::getApplication();
	$menu_params=$app->getParams();
	$edit_model = $this->getModel('edititem');
	$edit_model->params=$menu_params;
	$edit_model->id = $ct->Env->jinput->getInt('listing_id');
	
	//Check Authorization
	//3 - to delete
	$PermissionIndexes=['clear'=>3,'delete'=>3,'copy'=>4,'refresh'=>1,'publish'=>2,'unpublish'=>2,'createuser'=>1,'resetpassword'=>1];
	//$PermissionWords=['clear'=>'core.delete','delete'=>'core.delete','copy'=>'core.create','refresh'=>'core.edit','publish'=>'core.edit.state','unpublish'=>'core.edit.state','createuser'=>'core.edit'];
	$PermissionIndex=0;
	//$PermissionWord='';
	//if (array_key_exists($task,$PermissionWords))
		//$PermissionWord=$PermissionWords[$task];
	
	if (array_key_exists($task,$PermissionIndexes))
		$PermissionIndex=$PermissionIndexes[$task];
	
	if($task!='')
	{
		/*
		if (JFactory::getUser()->authorise('core.admin', 'com_helloworld')) 
					<action name="core.create" title="JACTION_CREATE" description="COM_CUSTOMTABLES_ACCESS_CREATE_DESC" />
		<action name="core.edit" title="JACTION_EDIT" description="COM_CUSTOMTABLES_ACCESS_EDIT_DESC" />
		<action name="core.edit.own" title="JACTION_EDITOWN" description="COM_CUSTOMTABLES_ACCESS_EDITOWN_DESC" />
		<action name="core.edit.state" title="JACTION_EDITSTATE" description="COM_CUSTOMTABLES_ACCESS_EDITSTATE_DESC" />
		<action name="core.delete" title="JACTION_DELETE" description="COM_CUSTOMTABLES_ACCESS_DELETE_DESC" />
		<action name="core.update" title="COM_CUSTOMTABLES_REFRESH" description="COM_CUSTOMTABLES_ACCESS_REFRESH_DESC" />
*/
		//if ($edit_model->CheckAuthorizationACL($PermissionWord))
		if ($edit_model->CheckAuthorization($PermissionIndex))
		{
			$redirect=doTheTask($ct,$task,$menu_params,$edit_model,$this);
			//JFactory::getApplication()->enqueueMessage($redirect->msg);
			$this->setRedirect($redirect->link, $redirect->msg, $redirect->status);
		}
		else
		{
			// not authorized
			if ($ct->Env->clean == 1)
				die('not authorized');
			else
			{
				//JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
				$link = $ct->Env->WebsiteRoot . 'index.php?option=com_users&view=login&return=1' . base64_encode(JoomlaBasicMisc::curPageURL());
				$this->setRedirect($link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
				parent::display();
			}
		}
	}
	else
		parent::display();
	
function doTheTask(&$ct,$task,$menu_params,$edit_model,&$this_)	
{
	$encodedreturnto = base64_encode(JoomlaBasicMisc::curPageURL());
	$returnto = $ct->Env->jinput->get('returnto', '', 'BASE64');
	$decodedreturnto = base64_decode($returnto);
	
	//Return link
	if ($returnto != '')
	{
		$link = $decodedreturnto;
		if (strpos($link, 'http:') === false and strpos($link, 'https:') === false)
			$link.= $ct->Env->WebsiteRoot . $link;
	}
	else
		$link = $ct->Env->WebsiteRoot . 'index.php?Itemid=' . $ct->Env->Itemid;
	
	$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
	
	$edit_model->load($menu_params, false);
		
	switch ($task)
	{
		case 'clear':
			//Review this task - its insecure and disbaled
			$model = $this_->getModel('catalog');
			$model->load($menu_params, false);

			if ($model->ct->Records !== null)
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'), 'status' => null);
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'), 'status' => 'error');

		break;

	case 'delete':

		$count = $edit_model->delete();

		if ($count > 0)
		{
			if ($ct->Env->clean == 1)
				die('deleted');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED';
				if($count == 1)
					$msg.='_1';
				
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,$count), 'status' => null);
				//COM_CUSTOMTABLES_RECORDS_DELETED
			}
		}
		elseif($count < 0)
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_DELETED';
				if(abs($count) == 1)
					$msg.='_1';
					
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,abs($count)), 'status' => 'error');
			}
		}

		break;

	case 'copy':
		
		if ($edit_model->copy($msg, $link))
		{
			if ($ct->Env->clean == 1)
				die('copied');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_COPIED'), 'status' => null);
		}
		else
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_COPIED'), 'status' => 'error');
		}

		break;

	case 'refresh':

		$count = $edit_model->Refresh();
		if ($count > 0)
		{
			if ($ct->Env->clean == 1)
				die('refreshed');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_REFRESHED';
				if($count == 1)
					$msg.='_1';
				
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,$count), 'status' => null);
			}
		}
		else
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_REFRESHED';
				if(abs($count) == 1)
					$msg.='_1';
					
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,abs($count)), 'status' => 'error');
			}
		}
		break;

	case 'publish':

		$count = $edit_model->setPublishStatus(1);
		if ($count > 0)
		{
			if ($ct->Env->clean == 1)
				die('published');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED';
				if($count == 1)
					$msg.='_1';
				
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,$count), 'status' => null);
			}
		}
		elseif($count < 0)
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_PUBLISHED';
				if(abs($count) == 1)
					$msg.='_1';
					
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,abs($count)), 'status' => 'error');
			}
		}
		
		break;

	case 'unpublish':
		
		$count = $edit_model->setPublishStatus(0);
		if ($count > 0)
		{
			if ($ct->Env->clean == 1)
				die('unpublished');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED';
				if($count == 1)
					$msg.='_1';
				
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,$count), 'status' => null);
			}
		}
		elseif($count < 0)
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
			{
				$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_NOT_UNPUBLISHED';
				if(abs($count) == 1)
					$msg.='_1';
					
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended($msg,abs($count)), 'status' => 'error');
			}
		}
		
		break;

	case 'createuser':
		
		$ct->getTable($menu_params->get( 'establename' ), null);
		if($ct->Table->tablename=='')
			return (object) array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');
			
		if($ct->Table->useridfieldname == null)
			return (object) array('link' => $link, 'msg' => 'User field not found.', 'status' => 'error');
			
		$listing_id = $ct->Env->jinput->getInt('listing_id');
		$ct->Table->loadRecord($listing_id);
		if($ct->Table->record == null)
		{
			Factory::getApplication()->enqueueMessage('User record ID: "'.$user_listing_id.'" not found.', 'error');
			return (object) array('link' => $link, 'status' => 'error');
		}
			
		$esfield = Fields::getFieldAsocByName($ct->Table->useridfieldname,$ct->Table->tableid);

		if($ct->Table->Try2CreateUserAccount($ct,$esfield))
			return (object) array('link' => $link, 'status' => null);
		else
			return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED'), 'status' => 'error');

		break;
		
	case 'resetpassword':
		
		$ct->getTable($menu_params->get( 'establename' ), null);
		if($ct->Table->tablename=='')
			return (object) array('link' => $link, 'msg' => 'Table not selected.', 'status' => 'error');

		$listing_id = $ct->Env->jinput->getInt('listing_id');
		if(CTUser::ResetPassword($ct,$listing_id))
		{
			if ($ct->Env->clean == 1)
				die('password has been reset');
			else
				return (object) array('link' => $link, 'status' => null);
		}
		else
		{
			if ($ct->Env->clean == 1)
				die('error');
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_USERS_RESET_COMPLETE_ERROR'), 'status' => 'error');
		}

		break;
		
	case 'setorderby':

		$orderby=$ct->Env->jinput->getString('orderby','');
		$orderby=trim(preg_replace("/[^a-zA-Z-+%.: ,_]/", "",$orderby));
		
		$mainframe = Factory::getApplication();

		$mainframe->setUserState('com_customtables.orderby_'.$ct->Env->Itemid,$orderby);
		
		$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'task');
		$link = JoomlaBasicMisc::deleteURLQueryOption($link, 'orderby');
		
		return (object) array('link' => $link, 'msg' => null, 'status' => null);

		break;

	default:
		
		if ($task == 'cart_addtocart' or $task == 'cart_form_addtocart' or $task == 'cart_setitemcount' or $task == 'cart_deleteitem' or $task == 'cart_emptycart')
		{
			$model = $this_->getModel('catalog');
			$model->load($menu_params, false);
			if ($menu_params->get('cart_returnto'))
			{
				$link = $menu_params->get('cart_returnto');
			}
			else
			{
				$theLink = JoomlaBasicMisc::curPageURL();
				$pair = explode('?', $theLink);
				if (isset($pair[1]))
				{
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'task');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'cartprefix');
					$pair[1] = JoomlaBasicMisc::deleteURLQueryOption($pair[1], 'listing_id');
				}

				$link = implode('?', $pair);
			}

			$param_msg = '';
			switch ($task)
			{
			case 'cart_addtocart':
				$result = $model->cart_addtocart();
				if ($menu_params->get('cart_msgitemadded')) $param_msg = $menu_params->get('cart_msgitemadded');
				break;

			case 'cart_form_addtocart':
				$result = $model->cart_form_addtocart();
				if ($menu_params->get('cart_msgitemadded')) $param_msg = $menu_params->get('cart_msgitemadded');
				break;

			case 'cart_setitemcount':
				$result = $model->cart_setitemcount();
				if ($menu_params->get('cart_msgitemupdated')) $param_msg = $menu_params->get('cart_msgitemupdated');
				break;

			case 'cart_deleteitem':
				$result = $model->cart_deleteitem();
				if ($menu_params->get('cart_msgitemdeleted')) $param_msg = $menu_params->get('cart_msgitemdeleted');
				break;

			case 'cart_emptycart':
				$result = $model->cart_emptycart();
				if ($menu_params->get('cart_msgitemupdated')) $param_msg = $menu_params->get('cart_msgitemupdated');
				break;
			}

			if ($result)
			{
				$msg = JFactory::getApplication()->input->getString('msg', null);

				if ($msg == null)
					return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_UPDATED'), 'status' => null);
				elseif ($param_msg != '')
					return (object) array('link' => $link, 'msg' => $param_msg, 'status' => null);
			}
			else
				return (object) array('link' => $link, 'msg' => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOPPING_CART_NOT_UPDATED'), 'status' => 'error');
		}
		break;
	}
}
