<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

//use CustomTables\CTUser;

use \tagProcessor_General;
use \tagProcessor_Item;
use \tagProcessor_If;
use \tagProcessor_Page;
use \tagProcessor_Value;
use \CT_FieldTypeTag_image;
use \CT_FieldTypeTag_file;

use CustomTables\DataTypes\Tree;
use \Joomla\CMS\Factory;
use \JoomlaBasicMisc;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Editor\Editor;
use \JHTML;

use \CTTypes;

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

class Inputbox
{
	var $ct;
	var $field;
	var $esfield;
	var $jinput;
	
	var $cssclass;
	var $cssstyle;
	var $attributes;
	var $onchange;
	
	var $option_list;
	var $place_holder;
	var $prefix;
	var $isTwig;
	
	function __construct(&$ct, &$esfield, array $option_list = [],$isTwig = true, $onchange = '')
	{
		$this->ct = $ct;
		
		$this->isTwig = $isTwig;
		$this->jinput = Factory::getApplication()->input;
		
		// $option_list[0] - CSS Class
		// $option_list[1] - Optional Parameter
		$this->cssclass = $option_list[0] ?? '';
		$this->attributes = $option_list[1] ?? '';
		$this->cssstyle = '';
		$this->onchange = $onchange;

		if(strpos($this->cssclass,':') !== false)//its a style, change it to attribute
    	{
			$this->cssstyle = $this->cssclass;
			$this->cssclass = '';
		}

		if(strpos($this->attributes,'onchange="') !== false and $this->onchange != '')
    	{
			//if the attributes already containe "onchange" parameter then add onchange value to the attributes parameter
			$this->attributes = str_replace('onchange="','onchange="'.$this->onchange,$this->attributes);
		}
		elseif($this->attributes != '')
			$this->attributes .= ' onchange="'.$onchange.'"';
		else
			$this->attributes = 'onchange="'.$onchange.'"';

		$this->esfield = $esfield;
		
		$this->cssclass .= ($this->ct->Env->version < 4 ? ' inputbox' : ' form-control').($this->esfield['isrequired'] ? ' required' : '');
	
		$this->option_list = $option_list;
		$this->place_holder = $esfield['fieldtitle'.$this->ct->Languages->Postfix];
	}
	
	function render($value, &$row)
	{
		$this->field = new Field($this->ct,$this->esfield,$row);
		
		$this->prefix = $this->ct->Env->field_input_prefix . (!$this->ct->isEditForm  ? $row['listing_id'] . '_' : '');

		switch($this->field->type)
		{
			case 'radio':
				return $this->render_radio($value);

			case 'int':
				return $this->render_int($value, $row);

			case 'float':
				return $this->render_float($value, $row);

			case 'phponchange':
				return $value.'<input type="hidden" '
					.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'value="'.$value.'" />';
				
			case 'phponadd':
				return $value.'<input type="hidden" '
					.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'value="'.$value.'" />';

			case 'phponview':
				return $value;

			case 'string':
				return $this->getTextBox($value);

			case 'alias':
				return $this->render_alias($value);

			case 'multilangstring':
				return $this->getMultilangString($row);

			case 'text':
				return $this->render_text($value);

			case 'multilangtext':
				require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'multilangtext.php');
				return $this->render_multilangtext($row);

			case 'checkbox':
				return $this->render_checkbox($value);

			case 'image':
				$image_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_image.php';
				require_once($image_type_file);

				return CT_FieldTypeTag_image::renderImageFieldBox($this->field, $this->prefix,
					$row,$this->cssclass,$this->attributes);
			
			case 'signature':
				return $this->render_signature();
				
			case 'ordering':
				return $this->render_int($value, $row);
				
			case 'file':
				$file_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
				require_once($file_type_file);
				return CT_FieldTypeTag_file::renderFileFieldBox($this->ct,$this->esfield,$row,$this->cssclass);

			case 'userid':
				return $this->getUserBox($row,$value,false);

			case 'user':
				if(count($row)==0)
					$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','STRING');

				return $this->getUserBox($row,$value,true);

			case 'usergroup':
				if(count($row)==0)
					$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','STRING');

				return $this->getUserGroupBox($value);

			case 'usergroups':
				return JHTML::_('ESUserGroups.render',
					$this->prefix.$this->esfield['fieldname'],
					$value,
					$this->field->params
					);

			case 'language':
				if(count($row)!=0 and (int)$row['listing_id']!=0)
				{
					$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','STRING');
				}
				else
				{
					//If it's a new record then default language is the current one
					$langObj=Factory::getLanguage();
					$value=$langObj->getTag();
				}
				$lang_attributes=array(
					'name'=>$this->prefix.$this->esfield['fieldname'],
					'id'=>$this->prefix.$this->esfield['fieldname'],
					'label'=>$this->esfield['fieldtitle'.$this->ct->Languages->Postfix],'readonly'=>false);
								
				return CTTypes::getField('language', $lang_attributes,$value)->input;

			case 'color':
				return $this->render_color($row,$value);

			case 'filelink':

				if(count($row)==0)
					$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','STRING');

				if($value=='')
					$value=$this->esfield['defaultvalue'];

				return JHTML::_('ESFileLink.render',$this->prefix.$this->esfield['fieldname'], $value, $this->cssstyle, $this->cssclass, $this->field->params[0],$this->attributes);

			case 'customtables':
				return $this->render_customtables($row);
							
			case 'sqljoin':
				return $this->render_tablejoin($value);

			case 'records':
				return $this->render_records($value);

			case 'googlemapcoordinates':
				return JHTML::_('GoogleMapCoordinates.render',$this->prefix.$this->esfield['fieldname'], $value);

			case 'email';
				return '<input '
					.'type="text" '
					.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
					.'class="'.$this->cssclass.'" '
					.'value="'.$value.'" maxlength="255" '
					.$this->attributes.' '
					.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'"'
					.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
					.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
					.' />';

			case 'url';
				return $this->render_url($value);

			case 'date';
				return $this->render_date($value);

			case 'time';
				return $this->render_time($row,$value);

			case 'article':
				return JHTML::_('CTArticle.render',
					$this->prefix.$this->esfield['fieldname'],
					$value,
					$this->cssclass,
					$this->field->params
				);

			case 'imagegallery':
				if(isset($row['listing_id']))
					return $this->getImageGallery($row['listing_id']);
				else
					return '';

			case 'filebox':
				if(isset($row['listing_id']))
					return $this->getFileBox($row['listing_id']);

			case 'multilangarticle':
				return $this->render_multilangarticle();
		}
		return '';
	}
	
	protected function prepareAttributes($attributes_,$attributes_str)
	{
		//This function used only once in render(&$this->esfield) function
		//Used for 'color' field type
		
		if($attributes_str!='')
		{
			$atts_=JoomlaBasicMisc::csv_explode(' ',$attributes_str,'"',false);
			foreach($atts_ as $a)
			{
				$pair=explode('=',$a);

				if(count($pair)==2)
				{
					$att=$pair[0];
					if($att=='onchange')
						$att='onChange';

					$attributes_[$att]=$pair[1];
				}
			}
		}
		return $attributes_;
	}
	
	public function getWhereParameter($field)
	{
		$f=str_replace($this->ct->Env->field_prefix,'',$field);

		$list=$this->getWhereParameters();

		foreach($list as $l)
		{
			$p=explode('=',$l);
			if($p[0]==$f and isset($p[1]))
				return $p[1];
		}
		return '';
	}

	protected function getWhereParameters()
	{
		$value=$this->ct->Env->jinput->get('where','','BASE64');;
		$b=base64_decode($value);
		$b=str_replace(' or ',' and ',$b);
		$b=str_replace(' OR ',' and ',$b);
		$b=str_replace(' AND ',' and ',$b);
		$list=explode(' and ',$b);
		return $list;
	}
	
	protected function getMultilangString(&$row)
	{
		$result='';
		if(isset($this->option_list[4]))
		{
			$language=$this->option_list[4];

			$firstlanguage=true;
			foreach($this->ct->Languages->LanguageList as $lang)
			{
				if($firstlanguage)
				{
					$postfix='';
					$firstlanguage=false;
				}
				else
					$postfix='_'.$lang->sef;
					
				if($language==$lang->sef)
				{
					//show single edit box
					return $this->getMultilangStringItem($row,$postfix,$lang->sef);
				}
			}
		}
		
		//show all languages	
		$result.='<div class="form-horizontal">';

		$firstlanguage=true;
		foreach($this->ct->Languages->LanguageList as $lang)
		{
			if($firstlanguage)
			{
				$postfix='';
				$firstlanguage=false;
			}
			else
				$postfix='_'.$lang->sef;

			$result.='
			<div class="control-group">
				<div class="control-label">'.$lang->caption.'</div>
				<div class="controls">'.$this->getMultilangStringItem($row,$postfix,$lang->sef).'</div>
			</div>';
		}
		$result.='</div>';
		return $result;
	}
	
	protected function getMultilangStringItem(&$row,$postfix,$langsef)
	{
							$attributes_='';
							$addDynamicEvent=false;
							
							if(strpos($this->attributes,'onchange="ct_UpdateSingleValue(')!==false)//its like a keyword
							{
								$addDynamicEvent=true;
							}
							else
								$attributes_=$this->attributes;
								
								if(count($row)==0)
									$value=$this->jinput->get($this->prefix.$this->field->fieldname.$postfix,'','STRING');
								else
									$value=isset($row[$this->esfield['realfieldname'].$postfix]) ? $row[$this->field->realfieldname.$postfix] : null;

								if($addDynamicEvent)
									$attributes_=' onchange="ct_UpdateSingleValue(\''.$this->ct->Env->WebsiteRoot.'\','.$this->ct->Env->Itemid.',\''.$this->esfield['fieldname'].$postfix.'\','.$row['listing_id'].',\''.$langsef.'\')"';
									
								$result='<input type="text" '
									.'name="'.$this->prefix.$this->field->fieldname.$postfix.'" '
									.'id="'.$this->prefix.$this->field->fieldname.$postfix.'" '
									.'class="'.$this->cssclass.'" '
									.'value="'.$value.'" '
									.'data-label="'.$this->field->title.'" '
									.'data-valuerule="'.str_replace('"','&quot;',$this->field->valuerule).'" '
									.'data-valuerulecaption="'.str_replace('"','&quot;',$this->field->valuerulecaption).'" '
									.((int)$this->field->params[0] > 0 ? 'maxlength="'.(int)$this->field->params[0].'" ' : 'maxlength="255" ')
									.$attributes_.' />';

		return $result;
	}
	
	protected function getTextBox($value)
	{
		$autocomplete = false;
		if(isset($this->option_list[2]) and $this->option_list[2]=='autocomplete')
			$autocomplete = true;
		
		$result = '<input type="text" '
								.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
								.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
								.'label="'.$this->esfield['fieldname'].'" '
								.($autocomplete ? 'list="'.$this->prefix.$this->esfield['fieldname'].'_datalist" ' : '')
								.'class="'.$this->cssclass.'" '
								.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
								.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
								.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
								.'value="'.$value.'" '.((int)$this->field->params[0] > 0 ? 'maxlength="'.(int)$this->field->params[0].'"' : 'maxlength="255"').' '.$this->attributes.' />';
								
		if($autocomplete)
		{
			$db = Factory::getDBO();

			$query='SELECT '.$this->esfield['realfieldname'].' FROM '.$this->ct->Table->realtablename.' GROUP BY '.$this->field->realfieldname
				.' ORDER BY '.$this->field->realfieldname;
				
			$db->setQuery($query);
			$records=$db->loadColumn();
			
			$result.='<datalist id="'.$this->prefix.$this->field->fieldname.'_datalist">'
				.(count($records) > 0 ? '<option value="'.implode('"><option value="',$records).'">' : '')
				.'</datalist>';
		}

		return $result;
	}
	
	protected function getUserBox(&$row,$value,$require_authorization)
	{
		$result='';

		$user = Factory::getUser();
		if($user->id==0)
			return '';

		$attributes='class="'.$this->cssclass.'" '.$this->attributes;

		$usergroup=$this->field->params[0];

		tagProcessor_General::process($this->ct,$usergroup,$row,'',1);
		tagProcessor_Item::process($this->ct,$row,$usergroup,'','',0);
		tagProcessor_If::process($this->ct,$usergroup,$row,'',0);
		tagProcessor_Page::process($this->ct,$usergroup);
		tagProcessor_Value::processValues($this->ct,$row,$usergroup,'[]');
		
		$where='';
		if(isset($this->field->params[3]))
			$where='INSTR(name,"'.$this->field->params[3].'")';

		if($require_authorization)
		{
			$result.=JHTML::_('ESUser.render',$this->prefix.$this->field->fieldname, $value, '', $attributes, $usergroup,'',$where);//check this, it should be disabled to edit
		}
		else
		{
			$result.=JHTML::_('ESUser.render',$this->prefix.$this->field->fieldname, $value, '', $attributes, $usergroup,'',$where);
		}
		return $result;
	}

	protected function getUserGroupBox($value)
	{
		$result='';

		$user = Factory::getUser();
		if($user->id==0)
			return '';

		$attributes='class="'.$this->cssclass.'" '.$this->attributes;

		$where='';
		$availableusergroups_list = ($this->field->params[0] == '' ? [] : $this->field->params);
		
		if(count($availableusergroups_list) == 0)
		{
			$query->where('#__usergroups.title!='.$db->quote('Super Users'));
		}
		else
		{
			$where = [];
			foreach($availableusergroups_list as $availableusergroup)
			{
				if($availableusergroup != '')
					$where[] = '#__usergroups.title='.$db->quote($availableusergroup);
			}
			$where = '('.implode(' OR ',$where).')';
		}

		$result.=JHTML::_('ESUserGroup.render',$this->prefix.$this->field->fieldname, $value, '', $attributes, $where);

		return $result;
	}

	protected function getImageGallery($listing_id)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_gallery.php');

		$htmlout='';

		$getGalleryRows=CT_FieldTypeTag_imagegallery::getGalleryRows($this->ct->Table->tablename,$this->field->fieldname,$listing_id);

		$htmlout.='
		';

		$image_prefix='';

		if(isset($pair[1]) and (int)$pair[1]<250)
			$img_width=(int)$pair[1];
		else
			$img_width=250;

		if($image_prefix=='')
			$img_width=100;

		$imagesrclist=array();
		$imagetaglist=array();

		if(CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows, $image_prefix,$listing_id,$this->field->fieldname,
			$this->field->params,$imagesrclist,$imagetaglist,$this->ct->Table->tableid))
		{
			$imagesrclist_arr=explode(';',$imagesrclist);

			$htmlout.='<div style="width:100%;overflow:scroll;border:1px dotted grey;background-image: url(\''.URI::root(true).'/components/com_customtables/libraries/customtables/media/images/icons/bg.png\');">

		<table cellpadding="3"><tbody><tr>';

		foreach($imagesrclist_arr as $img)
		{
			$htmlout.='<td align="center" valign="top">';
			$htmlout.='<a href="'.$img.'" target="_blank"><img src="'.$img.'" width="'.$img_width.'" />';
			$htmlout.='</td>';
		}

		$htmlout.='</tr></tbody></table>

		</div>';

		}
		else
		{
			return 'No Images';
		}

		$htmlout.='
		';



		return $htmlout;



	}//function

	protected function getFileBox($listing_id)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_filebox.php');

		$FileBoxRows=CT_FieldTypeTag_filebox::getFileBoxRows($this->ct->Table->tablename,$this->field->fieldname,$listing_id);

		if(count($FileBoxRows) > 0)
		{
			$vlu = CT_FieldTypeTag_filebox::process($FileBoxRows, $this->field, $listing_id,['','icon-filename-link','32','_blank','ol']);
			return '<div style="width:100%;overflow:scroll;background-image: url(\'components/com_customtables/libraries/customtables/media/images/icons/bg.png\');">'.$vlu.'</div>';
		}
		else
			return 'No Files';
	}
	
	protected function render_multilangtext(&$row)
	{
		$RequiredLabel = 'Field is required';
		
		$result='';
						
		$firstlanguage=true;
		foreach($this->ct->Languages->LanguageList as $lang)
		{
			if($firstlanguage)
			{
				$postfix='';
				$firstlanguage=false;
			}
			else
				$postfix='_'.$lang->sef;
								
			$fieldname=$this->esfield['fieldname'].$postfix;
								
			if(count($row)==0)
				$value=$this->jinput->get($this->ct->Env->field_prefix.$fieldname,'','STRING');
			else
			{
				if(array_key_exists($this->ct->Env->field_prefix . $fieldname, $row))
				{
					$value=$row[$this->ct->Env->field_prefix.$fieldname];
				}
				else
				{
					Factory::getApplication()->enqueueMessage('Field "'.$this->ct->Env->field_prefix.$fieldname.'" not yet created. Go to /Custom Tables/Database schema/Checks to create that field.', 'error');
					$value = '';
				}
			}
								
			$result.=($this->esfield['isrequired'] ? ' '.$RequiredLabel : '');
								
			$result.='<div id="'.$fieldname.'_div" class="multilangtext">';
                                
			if($this->field->params[0]=='rich')
			{
				$result.='<span class="language_label_rich">'.$lang->caption.'</span>';
									
				$w=500;
				$h=200;
				$c=0;
				$l=0;

				$editor_name = Factory::getApplication()->get('editor');
				$editor = Editor::getInstance($editor_name);
									
				$fname=$this->prefix.$fieldname;
				$result.='<div>'.$editor->display($fname,$value, $w, $h, $c, $l).'</div>';
			}
			else
			{
				$result.='<textarea filter="raw" name="'.$this->prefix.$fieldname.'" '
					.'id="'.$this->prefix.$fieldname.'" '
					.'class="'.$this->cssclass.' '.($this->esfield['isrequired'] ? 'required' : '').'">'.$value.'</textarea>'
					.'<span class="language_label">'.$lang->caption.'</span>';
				
				$result.=($this->esfield['isrequired'] ? ' '.$RequiredLabel : '');
			}
								
			$result.= '</div>';
		}

		return $result;                            
	}
	
	protected function render_multilangarticle()
	{
		$result = '
		<table>
			<tbody>';

		$firstlanguage=true;
		foreach($this->ct->Languages->LanguageList as $lang)
		{
			if($firstlanguage)
			{
				$postfix='';
				$firstlanguage=false;
			}
			else
				$postfix='_'.$lang->sef;

			$fieldname=$this->esfield['fieldname'].$postfix;

			if(count($row)==0)
				$value=$this->jinput->get($this->ct->Env->field_prefix.$fieldname,'','STRING');
			else
				$value=$row[$this->esfield['realfieldname'].$postfix];

			$result.='
				<tr>
					<td>'.$lang->caption.'</td>
					<td>:</td>
					<td>';

			$result.=JHTML::_('CTArticle.render',
					$this->prefix.$fieldname,
					$value,
					$this->cssclass,
					$this->field->params
					);

					$result.='</td>
				</tr>';
		}
		$result.='</body></table>';
		
		return $result;
	}
	
	protected function render_time(&$row, &$value)
	{
		$result = '';
		
		if(count($row)==0)
			$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','CMD');

		if($value=='')
			$value=$this->esfield['defaultvalue'];
		else
			$value=(int)$value;
	
		$time_attributes = ($this->attributes!='' ? ' ' : '')
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" ';

		$result.=JHTML::_('CTTime.render',$this->prefix.$this->esfield['fieldname'], $value, $this->cssclass, $time_attributes, $this->field->params,$this->option_list);
		
		return $result;
	}
	
	protected function render_date(&$value)
	{
		$result = '';
		
		if($value=="0000-00-00")
			$value='';

		$attributes_=[];
		$attributes_['class']=$this->cssclass;
		$attributes_['placeholder']=$this->place_holder;
		$attributes_['onChange']='" '
			.'data-label="'.$this->place_holder.'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']); // closing quote is not needed because 
			//public static function calendar($value, $name, $element_id, $format = '%Y-%m-%d', $attribs = array())  will add it.
									
		$attributes_['required']=($this->esfield['isrequired'] ? 'required' : ''); //not working, don't know why.

		$result.=JHTML::calendar($value, $this->prefix.$this->esfield['fieldname'], $this->prefix.$this->esfield['fieldname'],
			'%Y-%m-%d',$attributes_);
		
		return $result;
	}
	
	protected function render_signature()
	{
		$width = $this->field->params[0] ?? 300;
		$height = $this->field->params[1] ?? 150;
		$folder = $this->field->params[2] ?? 'images';
		$format = $this->field->params[3] ?? 'svg';
		if($format == 'svg-db')
			$format = 'svg';
		
		//https://github.com/szimek/signature_pad/blob/gh-pages/js/app.js
		//https://stackoverflow.com/questions/46514484/send-signature-pad-to-php-post-method
		//		class="wrapper"
		$result ='
<div class="ctSignature_flexrow" style="width:'.$width.'px;height:'.$height.'px;padding:0;">
	<div style="position:relative;display: flex;padding:0;">
		<canvas style="background-color: #ffffff;padding:0;width:'.$width.'px;height:'.$height.'px;" '
			.'id="'.$this->prefix.$this->field->fieldname.'_canvas" '
			.'class="uneditable-input '.$this->cssclass.'" '
			.$this->attributes
			.' >
		</canvas>
		<div class="ctSignature_clear"><button type="button" class="close" id="'.$this->prefix.$this->field->fieldname.'_clear">×</button></div>';
		$result .='
	</div>
</div>

<input type="text" style="display:none;" name="'.$this->prefix.$this->field->fieldname.'" id="'.$this->prefix.$this->field->fieldname.'" value="" '
			.'data-label="'.$this->field->title.'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->field->valuerule).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->field->valuerulecaption).'" > 
<script>
	ctInputbox_signature("'.$this->prefix.$this->field->fieldname.'",'.((int)$width).','.((int)$height).',"'.$format.'");
</script>
';
		return $result;
	}
	
	protected function render_url(&$value)
	{
		$result = '';
		$filters=array();
		$filters[]='url';

		if(isset($this->field->params[1]) and $this->field->params[1]=='true')
			$filters[]='https';

		if(isset($this->field->params[2]) and $this->field->params[2]!='')
			$filters[]='domain:'.$this->field->params[2];

		$result.='<input '
			.'type="text" '
			.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'class="'.$this->cssclass.'" '
			.'value="'.$value.'" maxlength="1024" '
			.'data-sanitizers="trim" '
			.'data-filters="'.implode(',',$filters).'" '
			.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
			.$this->attributes
			.' />';
		
		return $result;
	}
	
	protected function render_records(&$value)
	{
		$result = '';
		
		//records : table, [fieldname || layout:layoutname], [selector: multi || single], filter, |datalength|
							
		if(count($this->field->params)<1)
			$result.='table not specified';

		if(count($this->field->params)<2)
			$result.='field or layout not specified';

		if(count($this->field->params)<3)
			$result.='selector not specified';

		$esr_table=$this->field->params[0];
		if(isset($this->field->params[1]))
			$esr_field=$this->field->params[1];
		else
			$esr_field='';

		if(isset($this->field->params[2]))
			$esr_selector=$this->field->params[2];
		else
			$esr_selector='';

		if(count($this->field->params)>3)
			$esr_filter=$this->field->params[3];
		else
			$esr_filter='';

		if(isset($this->field->params[4]))
			$dynamic_filter=$this->field->params[4];
		else
			$dynamic_filter='';

		if(isset($this->field->params[5]))
			$sortbyfield=$this->field->params[5];
		else
			$sortbyfield='';
								
		$records_attributes = ($this->attributes!='' ? ' ' : '')
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" ';
								
		$result.=JHTML::_('ESRecords.render',
			$this->field->params,
			$this->prefix.$this->esfield['fieldname'],
			$value,
			$esr_table,
			$esr_field,
			$esr_selector,
			$esr_filter,
			'',
			$this->cssclass.' ct_improved_selectbox',
			$records_attributes,
			$dynamic_filter,
			$sortbyfield,
			$this->ct->Languages->Postfix,
			$this->place_holder
		);
		
		return $result;
	}
	
	protected function render_tablejoin(&$value)
	{
		$result = '';
	
		//CT Example: [house:RedHouses,onChange('Alert("Value Changed")'),city=London]

		//$this->option_list[0] - CSS Class
		//$this->option_list[1] - Optional Attributes
	
		if($this->isTwig)
		{
			//Twig Tag
			//Twig Example: [house:RedHouses,onChange('Alert("Value Changed")'),city=London]

			$result.=JHTML::_('CTTableJoin.render',
				$this->prefix.$this->esfield['fieldname'],
				$this->ct,
				$this->esfield,
				$value,
				$this->place_holder,
				$this->cssclass,
				$this->attributes,
				$this->option_list);
		}
		else
		{
			//CT Tag
			if(isset($this->option_list[2]) and $this->option_list[2]!='')
				$this->field->params[2]=$this->option_list[2];//Overwrites field type filter parameter.
							
			$sqljoin_attributes = $this->attributes . ' '
				.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
				.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" ';
							
			$result.=JHTML::_('ESSQLJoin.render',
				$this->field->params,
				$value,
				false,
				$this->ct->Languages->Postfix,
				$this->prefix.$this->esfield['fieldname'],
				$this->place_holder,
				$this->cssclass,
				$sqljoin_attributes);
		}
		
		return $result;
	}
	
	protected function render_customtables(&$row)
	{
		$result = '';
		
		if(!isset($this->field->params[1]))
			return 'selector not specified';

		$optionname=$this->field->params[0];
		$parentid=Tree::getOptionIdFull($optionname);

		//$this->field->params[0] is structure parent
		//$this->field->params[1] is selector type (multi or single)
		//$this->field->params[2] is data length
		//$this->field->params[3] is requirementdepth

		if($this->field->params[1]=='multi')
		{
			$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],null,'STRING');
			if(!isset($value))
			{
				$value='';
				if($this->esfield['defaultvalue']!='')
					$value=','.$this->field->params[0].'.'.$this->esfield['defaultvalue'].'.,';
			}

			if(isset($row[$this->esfield['realfieldname']]))
				$value=$row[$this->esfield['realfieldname']];

			$result.=JHTML::_('MultiSelector.render',
				$this->prefix,
				$parentid,$optionname,
				$this->ct->Languages->Postfix,
				$this->ct->Table->tablename,
				$this->esfield['fieldname'],
				$value,
				'',
				$this->place_holder);
		}
		elseif($this->field->params[1]=='single')
		{
			$v=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],null,'STRING');

			if(!isset($v))
			{
				$v='';
				if($this->esfield['defaultvalue']!='')
					$v=','.$this->field->params[0].'.'.$this->esfield['defaultvalue'].'.,';
			}

			if(isset($row[$this->esfield['realfieldname']]))
				$v=$row[$this->esfield['realfieldname']];

			$result.='<div style="float:left;">';
			$result.=JHTML::_('ESComboTree.render',
				$this->prefix,
				$this->ct->Table->tablename,
				$this->esfield['fieldname'],
				$optionname,
				$this->ct->Languages->Postfix,
				$v,
				'',
				'',
				'',
				'',
				$this->esfield['isrequired'],
				(isset($this->field->params[3]) ? (int)$this->field->params[3] : 1),
				$this->place_holder,
				$this->esfield['valuerule'],
				$this->esfield['valuerulecaption']
				);

			$result.='</div>';
		}
		else
			$result.='selector not specified';
		
		return $result;
	}
	
	protected function render_color(&$row,$value)
	{
		$result = '';
		
		if(count($row)==0)
			$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','ALNUM');

		if($value=='')
			$value=$this->esfield['defaultvalue'];

		if($value=='')
			$value='';

		$att=array(
			'name'=>$this->prefix.$this->esfield['fieldname'],
			'id'=>$this->prefix.$this->esfield['fieldname'],
			'label'=>$this->esfield['fieldtitle'.$this->ct->Languages->Postfix]);
		
		if($this->option_list[0]=='transparent')
		{
			$att['format']='rgba';
			$att['keywords']='transparent,initial,inherit';
		
			//convert value to rgba: rgba(255, 0, 255, 0.1)
		
			$colors=array();
		
			if(strlen($value)>=6)
			{
				$colors[]=hexdec(substr($value, 0,2));
				$colors[]=hexdec(substr($value, 2,2));
				$colors[]=hexdec(substr($value, 4,2));
			}
		
			if(strlen($value)==8)
			{
				$a=hexdec(substr($value, 6,2));
				$colors[]=round($a/255,2);
			}
			$value='rgba('.implode(',',$colors).')';
		}

		$array_attributes=$this->prepareAttributes($att,$this->attributes);
							
		$inputbox=CTTypes::getField('color', $array_attributes,$value)->input;
							
		//Add onChange attribute if not added
		$onChangeAttribute='';
		foreach ($array_attributes as $key => $value)
		{
			if ('onChange' == $key)
			{
				$onChangeAttribute='onChange="'.$value.'"';
				break;
			}
		}
		
		if($onChangeAttribute!='' and strpos($inputbox,'onChange')===false)
			$inputbox=str_replace('<input ','<input '.$onChangeAttribute,$inputbox);
		
		$result.=$inputbox;
		
		return $result;
	}
	
	protected function render_alias(&$value)
	{
		$result = '';
		
		$result.='<input type="text" '
			.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'label="'.$this->esfield['fieldname'].'" '
			.'class="'.$this->cssclass.'" '
			.' '.$this->attributes
			.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
			.'value="'.$value.'" '.((int)$this->field->params[0] > 0 ? 'maxlength="'.(int)$this->field->params[0].'"' : 'maxlength="255"').' '.$this->attributes.' />';
		
		return $result;
	}
	
	protected function render_radio(&$value)
	{
		$result = '';
		
		$result.='<table style="border:none;"><tr>';
							$i=0;
							foreach($this->field->params as $radiovalue)
							{
								$v=trim($radiovalue);
								$result.='<td valign="middle"><input type="radio"
									name="'.$this->prefix.$this->esfield['fieldname'].'"
									id="'.$this->prefix.$this->esfield['fieldname'].'_'.$i.'"
									value="'.$v.'" '
								.($value==$v ? ' checked="checked" ' : '')
								.' /></td>';
								$result.='<td valign="middle"><label for="'.$this->prefix.$this->esfield['fieldname'].'_'.$i.'">'.$v.'</label></td>';
								$i++;
							}
							$result.='</tr></table>';

		
		return $result;
	}
	
	protected function render_int(&$value, &$row)
	{
		$result = '';
		
		if(count($row)==0)
			$value=$this->jinput->get($this->ct->Env->field_prefix.$this->esfield['fieldname'],'','ALNUM');
							
		if($value=='')
			$value=(int)$this->esfield['defaultvalue'];
		else
			$value=(int)$value;
							
		$result.='<input '
			.'type="text" '
			.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'label="'.$this->esfield['fieldname'].'" '
			.'class="'.$this->cssclass.'" '
			.$this->attributes.' '
			.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
			.'value="'.$value.'" />';
		
		return $result;
	}
	
	protected function render_float($value, &$row)
	{
		$result = '';
		
		if(count($row)==0)
			$value = $this->ct->Env->jinput->getCmd($this->ct->Env->field_prefix.$this->esfield['fieldname'],'');
							
		if($value=='')
			$value=(float)$this->esfield['defaultvalue'];
		else
			$value=(float)$value;
							
		$result.='<input '
			.'type="text" '
			.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
			.'class="'.$this->cssclass.'" '
			.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
			.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
			.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
			.$this->attributes.' ';

		$decimals = intval($this->field->params[0]);
		if($decimals<0)
			$decimals=0;

		if(isset($values[2]) and $values[2]=='smart')
			$result.='onkeypress="ESsmart_float(this,event,'.$decimals.')" ';

		$result.='value="'.$value.'" />';
		return $result;
	}
	
	protected function render_text(&$value)
	{
		$result = '';
		$fname=$this->prefix.$this->esfield['fieldname'];
		
		//if(strpos($this->attributes,'onchange="')!==false)
			//$attributes = str_replace('onchange="','onchange="'.$this->onchange,$this->attributes);// onchange event already exists add one before
		//else
			//$attributes = $this->attributes.' onchange="'.$onchange;

		if(in_array('rich',$this->field->params))
		{
			$w=500;
			$h=200;
			$c=0;
			$l=0;

			$editor_name = Factory::getApplication()->get('editor');
			$editor = Editor::getInstance($editor_name);

			$result.='<div>'.$editor->display($fname,$value, $w, $h, $c, $l).'</div>';
		}
		else
		{
			$result.='<textarea name="'.$fname.'" '
					.'id="'.$fname.'" '
					.'class="'.$this->cssclass.'" '
					.$this->attributes.' '
					.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'"'
					.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
					.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
					.'>'.$value.'</textarea>';
		}

		if(in_array('spellcheck',$this->field->params))
		{
			$file_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables'.DIRECTORY_SEPARATOR . 'thirdparty'
				. DIRECTORY_SEPARATOR . 'jsc' . DIRECTORY_SEPARATOR . 'include.js';
									
			if(file_exists($file_path))
			{
				$document = Factory::getDocument();
				$document->addCustomTag('<script src="'.URI::root(true).'/components/com_customtables/thirdparty/jsc/include.js"></script>');
				$document->addCustomTag('<script type="text/javascript">$Spelling.SpellCheckAsYouType("'.$fname.'");</script>');
				$document->addCustomTag('<script type="text/javascript">$Spelling.DefaultDictionary = "English";</script>');
			}
		}
		
		return $result;
	}
	
	protected function render_checkbox(&$value)
	{
		$result = '';
		
		$format="";
		if(isset($this->option_list[2]) and $this->option_list[2]=='yesno')
			$format="yesno";
								
		if($format=="yesno")
		{
			$element_id=$this->prefix.$this->esfield['fieldname'];
			if($this->ct->Env->version < 4)
			{
				$result.='<fieldset id="'.$this->prefix.$this->esfield['fieldname'].'" class="'.$this->cssclass.' btn-group radio btn-group-yesno" '
					.'style="border:none !important;background:none !important;">';

				$result.='<div style="position: absolute;visibility:hidden !important; display:none !important;">'
							.'<input type="radio" '
										.'id="'.$element_id.'0" '
										.'name="'.$element_id.'" '
										.'value="1" '
										.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
										.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
										.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
										.$this->attributes.' '
										.((int)$value==1 ? ' checked="checked" ' : '')
							.' >'
						.'</div>'
						.'<label class="btn'.((int)$value==1 ? ' active btn-success' : '').'" for="'.$element_id.'0" id="'.$element_id.'0_label" >'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES').'</label>';
										
				$result.='<div style="position: absolute;visibility:hidden !important; display:none !important;">'
						.'<input type="radio" '
										.'id="'.$element_id.'1" '
										.'name="'.$element_id.'" '
										.$this->attributes.' '
										.'value="0" '
										.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
										.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
										.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
										.((int)$value==0 ? ' checked="checked" ' : '')
							.' >'
						.'</div>'
						.'<label class="btn'.((int)$value==0 ? ' active btn-danger' : '').'" for="'.$element_id.'1" id="'.$element_id.'1_label">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO').'</label>';

				$result.='</fieldset>';
			}
			else
			{
				$result.='<div class="switcher">'
							.'<input type="radio" '
								.'id="'.$element_id.'0" '
								.'name="'.$element_id.'" '
								.$this->attributes.' '
								.'value="0" '
								.'class="active " '
								.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
								.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
								.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
								.((int)$value==0 ? ' checked="checked" ' : '')
							.' >'
							.'<label for="'.$element_id.'0">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO').'</label>'
							.'<input type="radio" '
								.'id="'.$element_id.'1" '
								.'name="'.$element_id.'" '
								.$this->attributes.' '
								.'value="1" '
								.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
								.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
								.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
								.((int)$value==1 ? ' checked="checked" ' : '')
							.' >'
							.'<label for="'.$element_id.'1">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES').'</label>'
							.'<span class="toggle-outside"><span class="toggle-inside"></span></span>'
						.'</div>';
			}
		}
		else
		{
			if($this->ct->Env->version < 4)
			{
				$onchange=$this->prefix.$this->esfield['fieldname'].'_off.value=(this.checked === true ? 0 : 1);';// this is to save unchecked value as well.
				
				if(strpos($this->attributes,'onchange="')!==false)
					$check_attributes = str_replace('onchange="','onchange="'.$onchange,$this->attributes);// onchange event already exists add one before
				else
					$check_attributes = $this->attributes.'onchange="'.$onchange;
				
				$result.='<input type="checkbox" '
											.'id="'.$this->prefix.$this->esfield['fieldname'].'" '
											.'name="'.$this->prefix.$this->esfield['fieldname'].'" '
											.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
											.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
											.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
											.($value ? ' checked="checked" ' : '')
											.($this->cssstyle != '' ? ' class="'.$this->cssstyle.'" ' : '')
											.($this->cssclass != '' ? ' class="'.$this->cssclass.'" ' : '')
											.($check_attributes != '' ? ' '.$check_attributes : '')
											.'>'
						.'<input type="hidden"'
											.' id="'.$this->prefix.$this->esfield['fieldname'].'_off" '
											.' name="'.$this->prefix.$this->esfield['fieldname'].'_off" '
											.((int)$value == 1? ' value="0" ' : 'value="1"')
											.' >';
			}
			else
			{
				$element_id=$this->prefix.$this->esfield['fieldname'];
				
				$result.='<div class="switcher">'
							.'<input type="radio" '
								.'id="'.$element_id.'0" '
								.'name="'.$element_id.'" '
								.'value="0" '
								.'class="active " '
								.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
								.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
								.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
								.((int)$value==0 ? ' checked="checked" ' : '')
							.' >'
							.'<label for="'.$element_id.'0">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO').'</label>'
							.'<input type="radio" '
								.'id="'.$element_id.'1" '
								.'name="'.$element_id.'" '
								.'value="1" '
								.'data-label="'.$this->esfield['fieldtitle'.$this->ct->Languages->Postfix].'" '
								.'data-valuerule="'.str_replace('"','&quot;',$this->esfield['valuerule']).'" '
								.'data-valuerulecaption="'.str_replace('"','&quot;',$this->esfield['valuerulecaption']).'" '
								.((int)$value==1 ? ' checked="checked" ' : '').' >'
							.'<label for="'.$element_id.'1">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES').'</label>'
							.'<span class="toggle-outside"><span class="toggle-inside"></span></span>'
							.'<input type="hidden"'
											.' id="'.$this->prefix.$this->esfield['fieldname'].'_off" '
											.' name="'.$this->prefix.$this->esfield['fieldname'].'_off" '
											.((int)$value == 1 ? ' value="0" ' : 'value="1"')
											.' >'
						.'</div>'
						.'
						<script>
							document.getElementById("'.$element_id.'0").onchange = function(){if(this.checked === true)'.$this->prefix.$this->esfield['fieldname'].'_off.value=1;'.$this->onchange.'};
							document.getElementById("'.$element_id.'1").onchange = function(){if(this.checked === true)'.$this->prefix.$this->esfield['fieldname'].'_off.value=0;'.$this->onchange.'};
						</script>
						
						';
			}
		}
		
		return $result;
	}
}
