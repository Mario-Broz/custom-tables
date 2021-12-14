<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\SearchInputBox;
use CustomTables\CTUser;

use \JoomlaBasicMisc;
use \JESPagination;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

use \JHTML;

class Twig_Html_Tags
{
	var $ct;
	var $jinput;

	function __construct(&$ct)
	{
		$this->ct = $ct;
		$this->jinput=Factory::getApplication()->input;
	}
	
	function add($Alias_or_ItemId = '')
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$usergroups = $this->ct->Env->user->get('groups');
		if(!$this->ct->Env->isUserAdministrator and !in_array($add_userGroup,$usergroups))
			return ''; //Not permitted
		
		if(isset($this->ct->Env->menu_params))
            $add_userGroup=(int)$this->ct->Env->menu_params->get( 'addusergroups' );
        else
            $add_userGroup=0;

        //$isEditable=CTUser::checkIfRecordBelongsToUser($this->ct,$edit_userGroup);

		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return ''; //Not permitted
		
		if((int)$Alias_or_ItemId > 0)
			$link='/index.php?option=com_customtables&view=edititem&returnto='.$this->ct->Env->encoded_current_url.'&Itemid='.$Alias_or_ItemId;
		if($Alias_or_ItemId != '')
			$link='/index.php/'.$Alias_or_ItemId.'?returnto='.$this->ct->Env->encoded_current_url;
		else
			$link='/index.php?option=com_customtables&view=edititem&returnto='.$this->ct->Env->encoded_current_url.'&Itemid='.$this->ct->Env->Itemid;

		if($this->ct->Env->jinput->getCmd('tmpl','')!='')
			$link.='&tmpl='.$jinput->get('tmpl','','CMD');
                    
		$vlu='<a href="'.URI::root(true).$link.'" id="ctToolBarAddNew'.$this->ct->Table->tableid.'" class="toolbarIcons">'
			.'<img src="'.URI::root(true).'/components/com_customtables/images/new.png" alt="Add New" title="Add New" /></a>';
			
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function importcsv()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		if(isset($this->ct->Env->menu_params))
            $add_userGroup=(int)$this->ct->Env->menu_params->get( 'addusergroups' );
        else
            $add_userGroup=0;

		$usergroups = $this->ct->Env->user->get('groups');
		if(!$this->ct->Env->isUserAdministrator and !in_array($add_userGroup,$usergroups))
			return ''; //Not permitted

		$document = Factory::getDocument();
		
		if($this->ct->Env->version < 4)
		{
			$document->addCustomTag('<script src="'.URI::root(true).'/media/jui/js/jquery.min.js"></script>');
			$document->addCustomTag('<script src="'.URI::root(true).'/media/jui/js/bootstrap.min.js"></script>');
		}
		
		$document->addCustomTag('<link href="'.URI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
        $document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
        $document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
        $document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/js/uploader.js"></script>');
		$max_file_size=JoomlaBasicMisc::file_upload_max_size();
                    
		$fileid = JoomlaBasicMisc::generateRandomString();
        $fieldid = '9999';//some unique number
        $objectname='importcsv';

		JHtml::_('behavior.formvalidator');
    
        $vlu = '<div>
                    <div id="ct_fileuploader_'.$objectname.'"></div>
                    <div id="ct_eventsmessage_'.$objectname.'"></div>
                    <form action="" name="ctUploadCSVForm" id="ctUploadCSVForm">
                	<script>
                        UploadFileCount=1;

                    	var urlstr="/index.php?option=com_customtables&amp;view=fileuploader&amp;tmpl=component&'
                        .'tableid='.$this->ct->Table->tableid.'&'
                        .'task=importcsv&'
                        .$objectname.'_fileid='.$fileid.'&Itemid='.$this->ct->Env->Itemid.'&fieldname='.$objectname.'";
                        
                    	ct_getUploader('.$fieldid.',urlstr,'.$max_file_size.',"csv","ctUploadCSVForm",true,"ct_fileuploader_'.$objectname.'","ct_eventsmessage_'.$objectname.'","'.$fileid.'","'
                        .$this->ct->Env->field_input_prefix.$objectname.'","ct_uploadedfile_box_'.$objectname.'");
                    </script>
                    <input type="hidden" name="'.$this->ct->Env->field_input_prefix.$objectname.'" id="'.$this->ct->Env->field_input_prefix.$objectname.'" value="" />
			'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                    </form>
                </div>
';
		
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function pagination()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		if($this->ct->Table->recordcount <= $this->ct->Limit)
			return '';
		
		$pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit);
		$vlu = '<div class="pagination">'.$pagination->getPagesLinks("").'</div>';
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function limit($the_step = 1)
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$pagination = new JESPagination($this->ct->Table->recordcount, $this->ct->LimitStart, $this->ct->Limit);
		$vlu = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW' ).': '.$pagination->getLimitBox($the_step);
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function orderby()
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$vlu = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ORDER_BY' ).': '.OrderingHTML::getOrderBox($this->ct->Ordering);
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
		
	function goback($label='Go Back', $image_icon='components/com_customtables/images/arrow_rtl.png', $attribute='',  $returnto = '')
	{
		if($this->ct->Env->print==1)
            $gobackbutton='';
				
		if($returnto == '')
			$returnto = base64_decode($this->jinput->get('returnto','','BASE64'));
		
		if($returnto == '')
			return '';
		
		if($attribute == '')
			$attribute = 'class="ct_goback"';
		
		$vlu = '<a href="'.$returnto.'" '.$attribute.'><div>'.$label.'</div></a>';
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	protected function getAvailableModes()
	{
		$available_modes=array();
        
        $user = Factory::getUser();
		if($user->id!=0)
        {
            $publish_userGroup=(int)$this->ct->Env->menu_params->get( 'publishusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($publish_userGroup))
            {
                $available_modes[]='publish';
                $available_modes[]='unpublish';
            }
            
            $edit_userGroup=(int)$this->ct->Env->menu_params->get( 'editusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($edit_userGroup))
                $available_modes[]='refresh';
                
            $delete_userGroup=(int)$this->ct->Env->menu_params->get( 'deleteusergroups' );
            if(JoomlaBasicMisc::checkUserGroupAccess($delete_userGroup))
                $available_modes[]='delete';
        }
		return $available_modes;
	}
	
	function batch($buttons = [])
	{
		if($this->ct->Env->print==1 or ($this->ct->Env->frmt!='html' and $this->ct->Env->frmt!=''))
			return '';
		
		$available_modes = $this->getAvailableModes();
		if(count($available_modes) == 0)
			return '';
		
		$buttons_array = [];
		if(is_array($buttons))
			$buttons_array = $buttons;
		else
			$buttons_array = explode(',',$buttons);
		
		if(count($buttons_array) == 0)
			$buttons_array = $available_modes;
		
		$html_buttons = [];
		
		foreach($buttons_array as $mode)
		{
			if($mode == 'checkbox')
			{
				$html_buttons[] = '<input type="checkbox" id="esCheckboxAll'.$this->ct->Table->tableid.'" onChange="esCheckboxAllclicked('.$this->ct->Table->tableid.')" />';
			}
			else
			{
				if(in_array($mode,$available_modes))
				{
					$rid='esToolBar_'.$mode.'_box_'.$this->ct->Table->tableid;
					$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_'.strtoupper($mode).'_SELECTED' );
					$img='<img src="'.URI::root(true).'/components/com_customtables/images/'.$mode.'.png" border="0" alt="'.$alt.'" title="'.$alt.'" />';
					$link='javascript:esToolBarDO("'.$mode.'", '.$this->ct->Table->tableid.')';
					$html_buttons[] = '<div id="'.$rid.'" class="toolbarIcons"><a href=\''.$link.'\'>'.$img.'</a></div>';
				}
			}
		}
		
		if(count($html_buttons) == 0)
			return '';
		
		$vlu = implode('',$html_buttons);
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function toolbar($buttons)
	{
	}
	
	function print($class='ctEditFormButton btn button')
	{
		$link=$this->ct->Env->current_url.(strpos($this->ct->Env->current_url,'?')===false ? '?' : '&').'tmpl=component&amp;print=1';

		if($this->jinput->getInt('moduleid',0)!=0)
		{
			//search module

			$moduleid = $this->jinput->getInt('moduleid',0);
			$link.='&amp;moduleid='.$moduleid;

			//keyword search
			$inputbox_name='eskeysearch_'.$moduleid ;
			$link.='&amp;'.$inputbox_name.'='.$this->jinput->getString($inputbox_name,'');
		}

		if($this->ct->Env->print==1)
		{
			$vlu='<p><a href="#" onclick="window.print();return false;"><img src="'.URI::root(true).'/components/com_customtables/images/printButton.png" alt="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT').'"  /></a></p>';
		}
		else
		{
			$vlu='<input type="button" class="'.$class.'" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT' ).'" onClick=\'window.open("'.$link.'","win2","status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no"); return false; \'> ';
        }
			
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	protected function getFieldTitles($list_of_fields)
    {
        $fieldtitles=array();
        foreach($list_of_fields as $fieldname)
        {
			if($fieldname=='_id')
				$fieldtitles[] = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID');
			else						
			{
				foreach($this->ct->Table->fields as $fld)
				{
					if($fld['fieldname']==$fieldname)
					{
						$fieldtitles[]=$fld['fieldtitle'.$this->ct->Languages->Postfix];
						break;
					}
				}
			}
        }
        return $fieldtitles;
    }
	
	protected function prepareSearchElement($fld)
    {
		if(isset($fld['fields']) and count($fld['fields'])>0)
        {
			return 'es_search_box_'.$fld['fieldname'].':'.implode(';',$fld['fields']).':';
        }
        else
        {
			if($fld['type']=='customtables')
            {
				$exparams=explode(',',$fld['typeparams']);
    			if(count($exparams)>1)
    			{
					$esroot=$exparams[0];
    				return 'es_search_box_combotree_'.$this->ct->Table->tablename.'_'.$fld['fieldname'].'_1:'.$fld['fieldname'].':'.$esroot;
    			}
			}
    		else
    			return 'es_search_box_'.$fld['fieldname'].':'.$fld['fieldname'].':';
		}
		
        return '';       
    }
	
	function search($list_of_fields_string_or_array, $class = '', $reload = false, $improved = false)
	{
		if($this->ct->Env->print == 1 or $this->ct->Env->frmt == 'csv')
			return '';
				
		if(is_array($list_of_fields_string_or_array))
			$list_of_fields_string_array = $list_of_fields_string_or_array;
		else
			$list_of_fields_string_array = explode(',',$list_of_fields_string_or_array);
		
		if(count($list_of_fields_string_array) == 0)
		{
			Factory::getApplication()->enqueueMessage('Search box: Please specify a field name.', 'error');
			return '';
		}
		
		//Clean list of fields
		$list_of_fields=[];
		foreach($list_of_fields_string_array as $field_name_string)
		{
			if($field_name_string=='_id')
			{
				$list_of_fields[] = '_id';
			}
			else
			{
				//Check if field name is exist in selected table
				$fld = Fields::FieldRowByName($field_name_string,$this->ct->Table->fields);
				if(count($fld)>0)
					$list_of_fields[]=$field_name_string;
			}
		}

		if(count($list_of_fields) == 0)
		{
			Factory::getApplication()->enqueueMessage('Search box: Field name "'.implode(',',$list_of_fields_string_or_array).'" not found.', 'error');
			return '';
		}
		
		$vlu='Search field name is wrong';
		
		require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR.'libraries'
			. DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'searchinputbox.php');
			
		$SearchBox = new SearchInputBox($this->ct, 'esSearchBox');
		
		$fld=[];
						
		$first_fld=$fld;
		$first_field_type='';
							
		foreach($list_of_fields as $field_name_string)
		{
			if($field_name_string=='_id')
			{
				$fld=array(
					'fieldname' => '_id',
					'type' => '_id',
					'typeparams' => '',
					'fieldtitle'.$this->ct->Languages->Postfix => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ID')
				);
			}
			else
			{
				//Date search no implemented yet. It will be range search
				$fld = Fields::FieldRowByName($field_name_string,$this->ct->Table->fields);
				if($fld['type']=='date')
				{
					$fld['typeparams']='date';
					$fld['type']='range';
				}
			}

			if($first_field_type == '')
			{
				$first_field_type = $fld['type'];
				$first_fld = $fld;
			}
			else
			{
				// If field types are mixed then use string search
				if($first_field_type != $fld['type'])
					$first_field_type = 'string';
			}
		}

		$first_fld['type']=$first_field_type;

		if(count($list_of_fields)>1)
		{
			$first_fld['fields']=$list_of_fields;
			$first_fld['typeparams']='';
		}

		//Add control elements
		$fieldtitles=$this->getFieldTitles($list_of_fields);
		$field_title=implode(' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OR' ).' ',$fieldtitles);

		$cssclass='ctSearchBox';
		if($class!='')
			$cssclass.=' '.$class;
		
		if($improved)
			$cssclass.=' ct_improved_selectbox';

		$default_Action = $reload ? ' onChange="ctSearchBoxDo();"' : ' ';//action should be a space not empty or this.value=this.value    

		$objectname = $first_fld['fieldname'];
							
		$vlu = $SearchBox->renderFieldBox('es_search_box_',$objectname,$first_fld,
			$cssclass,'0',
			'',false,'',$default_Action,$field_title);//action should be a space not empty or 
		//0 because its not an edit box and we pass onChange value even " " is the value;
			
		//$vlu=str_replace('"','&&&&quote&&&&',$vlu);
								
		$field2search = $this->prepareSearchElement($first_fld);
		$vlu.= '<input type=\'hidden\' ctSearchBoxField=\''.$field2search.'\' />';
		
		return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function searchbutton($class_ = '')
	{
		if($this->ct->Env->print==1 or $this->ct->Env->frmt=='csv')
			return '';
		
		$class = 'ctSearchBox';
		
		if(isset($class_) and $class_!='')
			$class.=' '.$class_;
		else
			$class.=' btn button-apply btn-primary';
                    
        //JavascriptFunction
        $vlu= '<input type=\'button\' value=\'SEARCH\' class=\''.$class.'\' onClick=\'ctSearchBoxDo()\' />';
       
        return new \Twig\Markup($vlu, 'UTF-8' );
	}
	
	function message($html, $type = 'Message')
	{
		Factory::getApplication()->enqueueMessage($html, $type);
		
		return null;
	}
	
	function navigation($list_type = 'list', $ul_css_class = '')
	{
		$PathValue = $this->CleanNavigationPath($this->ct->Filter->PathValue);
		if(count($PathValue)==0)
			return '';
		elseif($list_type=='' or $list_type=='list')
		{
			$vlu = '<ul'.($ul_css_class != '' ? ' class="'.$ul_css_class.'"' : '').'><li>'.implode('</li><li>',$PathValue).'</li></ul>';
			return new \Twig\Markup($vlu, 'UTF-8' );
		}
		elseif($list_type=='comma')
			return implode(',',$PathValue);
		else
			return 'navigation: Unknown list type';
	}
	
	protected function CleanNavigationPath($thePath)
	{
		//Returns a list of unique search path criteria - eleminates duplicates
		$newPath=array();
		if(count($thePath)==0)
			return $newPath;

		for($i=count($thePath)-1;$i>=0;$i--)
		{
			$item=$thePath[$i];
			if(count($newPath)==0)
				$newPath[]=$item;
			else
			{
				$found=false;
				foreach($newPath as $newitem)
				{
					if(!(strpos($newitem,$item)===false))
					{
						$found=true;
						break;
					}
				}

				if(!$found)
					$newPath[]=$item;
			}
		}
		return array_reverse ($newPath);
	}
	
	function format($format, $link_type = 'anchor', $image = '', $imagesize = '', $menu_item_alias = '', $csv_column_separator = ',')
	{
		//$csv_column_separator parameter is only for csv output format
		
        if($this->ct->Env->frmt=='' or $this->ct->Env->frmt=='html')
        {
			if($menu_item_alias != '')
			{
				$menu_item=JoomlaBasicMisc::FindMenuItemRowByAlias($menu_item_alias);//Accepts menu Itemid and alias
				if($menu_item!=0)
				{
					$menu_item_id=(int)$menu_item['id'];
					$link=$menu_item['link'];
				}
					
				$link.='&Itemid='.$menu_item_id;//.'&returnto='.$returnto;
			}
			else
			{
				$link=JoomlaBasicMisc::deleteURLQueryOption($this->ct->Env->current_url, 'frmt');
			}
				
			$link = Route::_($link);
				
   			//check if format supported
   			$allowed_formats=['csv','json','xml','xlsx','pdf','image'];
   			if($format=='' or !in_array($format,$allowed_formats))
				$format='csv';
				
   			$link.=(strpos($link,'?')===false ? '?' : '&').'frmt='.$format.'&clean=1';
   			$vlu='';
			
			if($format == 'csv' and $csv_column_separator != ',')
				$link.='&sep='.$csv_column_separator;

   			if($link_type=='anchor' or $link_type=='')
   			{
   				$allowed_sizes=['16','32','48'];
   				if($imagesize=='' or !in_array($imagesize,$allowed_sizes))
   					$imagesize=32;

   				if($format=='image')
   					$format_image='jpg';
   				else
   					$format_image=$format;

   				if($image=='')
   					$image='/components/com_customtables/images/fileformats/'.$imagesize.'px/'.$format_image.'.png';

   				$alt='Download '.strtoupper($format).' file';
   				//add image anchor link
   				$vlu = '<a href="'.$link.'" class="toolbarIcons" id="ctToolBarExport2CSV" target="_blank"><img src="'.$image.'" alt="'.$alt.'" title="'.$alt.'" width="'.$imagesize.'" height="'.$imagesize.'"></a>';
				return new \Twig\Markup($vlu, 'UTF-8' );
   			}
   			elseif($link_type == '_value' or $link_type == 'linkonly')
   			{
   				//link only
				return $link;
   			}
		}
        
		return '';
	}
	
}