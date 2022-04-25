<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\DataTypes\Tree;

class CT_FieldTypeTag_image
{
	static public function getImageSRClayoutview($option_list,$rowValue,array $params,&$imagesrc,&$imagetag)//,$onlylink=false)
	{
		if(strpos($rowValue,'-')!==false)
			$rowValue=str_replace('-','',$rowValue);

		$conf = JFactory::getConfig();
		$sitename = $conf->get('config.sitename');

		$option=$option_list[0];

		$ImageFolder_=CustomTablesImageMethods::getImageFolder($params);
	
		$ImageFolderWeb=str_replace(DIRECTORY_SEPARATOR,'/',$ImageFolder_);
		$ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder_);

		$imagesrc='';
		$imagetag='';
		
		if($option=='' or $option=='_esthumb' or $option=='_thumb')
		{
			$prefix='_esthumb';

			$imagefile_ext='jpg';
			$imagefileweb=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			$imagefile=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
			if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile))
			{
				$imagetag='<img src="'.$imagefileweb.'" width="150" height="150" alt="'.$sitename.'" title="'.$sitename.'" />';
				$imagesrc=$imagefileweb;
				return true;
			}
			return false;
		}
		elseif($option=='_original')
		{
			$prefix='_original';
			$imagefile_ext='jpg';
			$imgname=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

			$imgMethods= new CustomTablesImageMethods;

			$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$imgname);

			if($imagefile_ext!='')
			{
				$imagefileweb=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
				$imagetag='<img src="'.$imagefileweb.'" alt="'.$sitename.'" title="'.$sitename.'" />';

				$imagesrc=$imagefileweb;//$prefix.'_'.$rowValue.'.'.$imagefile_ext;
				return true;
			}
			return false;
		}

		$prefix=$option;
		$imgMethods= new CustomTablesImageMethods;
		$imgname=$ImageFolder.DIRECTORY_SEPARATOR.$prefix.'_'.$rowValue;

		$imagefile_ext=$imgMethods->getImageExtention(JPATH_SITE.DIRECTORY_SEPARATOR.$imgname);
		//--- WARNING - ERROR -- REAL EXT NEEDED - IT COMES FROM OPTIONS
		$imagefile=JURI::root(false).$ImageFolderWeb.'/'.$prefix.'_'.$rowValue.'.'.$imagefile_ext;
		$imagesizes=$imgMethods->getCustomImageOptions($params[0]);
        
		foreach($imagesizes as $img)
		{
			if($img[0]==$option)
			{
				if($imagefile!='')
				{
					$imagetag='<img src="'.$imagefile.'" '.($img[1]>0 ? 'width="'.$img[1].'"' : '').' '.($img[2]>0 ? 'height="'.$img[2].'"' : '').' alt="'.$sitename.'" title="'.$sitename.'" />';
					$imagesrc=$imagefile;

					return true;
				}
			}
		}
		return false;
	}

    static public function get_image_type_value(&$field, $listing_id)
    {
		$value=0;
		$imagemethods=new CustomTablesImageMethods;

		$ImageFolder=CustomTablesImageMethods::getImageFolder($field->params);

		$jinput=JFactory::getApplication()->input;
        $fileid = $jinput->post->get($field->comesfieldname, '','STRING' );

		if($listing_id==0)
		{
			$value=$imagemethods->UploadSingleImage(0, $fileid,$field->realfieldname,JPATH_SITE.DIRECTORY_SEPARATOR
				.$ImageFolder,$field->params,$field->ct->Table->realtablename,$field->ct->Table->realidfieldname);
		}
		else
		{
			$to_delete = $jinput->post->get($field->comesfieldname.'_delete', '','CMD' );
			$ExistingImage=Tree::isRecordExist($listing_id,'id', $field->realfieldname, $field->ct->Table->realtablename);

			if($to_delete=='true')
			{
				if($ExistingImage>0)
				{
					$imagemethods->DeleteExistingSingleImage(
										$ExistingImage,
										JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder,
										$field->params[0],
										$field->ct->Table->realtablename,
										$field->realfieldname,
										$field->ct->Table->realidfieldname);
				}
 
				return $value;
			}
			else
			{
				$value=$imagemethods->UploadSingleImage($ExistingImage,$fileid, $field->realfieldname,
					JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder,$field->params,$field->ct->Table->realtablename,$field->ct->Table->realidfieldname);
			}
		}

		if($value == -1 or $value == 2)
		{
			// -1 if file extension not supported
			// 2 if file already exists
			JFactory::getApplication()->enqueueMessage('Could not upload image file.', 'error');
		}
        elseif($value != 0)
			return $value;

        return null;
    }

    public static function renderImageFieldBox(&$field, $prefix, &$row,$class,$optinal_parameter)
	{
		$ImageFolder=CustomTablesImageMethods::getImageFolder($field->params);

        $imagefile='';
        $isShortcut=false;
		$imagesrc=CT_FieldTypeTag_image::getImageSRC($row,$field->realfieldname,$ImageFolder,$imagefile,$isShortcut);

    	$result='<div class="esUploadFileBox" style="vertical-align:top;">';

		if($imagefile!='')
			$result.=CT_FieldTypeTag_image::renderImageAndDeleteOption($field,$prefix,$imagesrc,$isShortcut);
    
        $result.=CT_FieldTypeTag_image::renderUploader($field,$prefix);

   		$result.='</div>';
       	return $result;

	}

    protected static function renderImageAndDeleteOption(&$field,$prefix,$imagesrc,$isShortcut)
    {
        //$style='margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;';
        $result='<div style="" id="ct_uploadedfile_box_'.$field->fieldname.'">'
			.'<img src="'.$imagesrc.'" width="150" /><br/>';

		if(!$field->isrequired)
			$result.='<input type="checkbox" name="'.$prefix.$field->fieldname.'_delete" id="'.$prefix.$field->fieldname.'_delete" value="true">'
				.' Delete '.($isShortcut ? 'Shortcut' : 'Image');

		$result.='</div>';

        return $result;
    }

    protected static function renderUploaderLimitations()
    {
		$max_file_size=JoomlaBasicMisc::file_upload_max_size();
		
		$result='
                <div style="margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;">
				'.JoomlaBasicMisc::JTextExtended( "MIN SIZE" ).': 10px x 10px<br/>
				'.JoomlaBasicMisc::JTextExtended( "MAX SIZE" ).': 1000px x 1000px<br/>
				'.JoomlaBasicMisc::JTextExtended( "COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE" ).': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'<br/>
				'.JoomlaBasicMisc::JTextExtended( "FORMAT" ).': JPEG, GIF, PNG, WEBP
				</div>';

		return $result;
    }

    protected static function renderUploader(&$field,$prefix)
    {
        $max_file_size=JoomlaBasicMisc::file_upload_max_size();
        $fileid=JoomlaBasicMisc::generateRandomString();
        $jinput=JFactory::getApplication()->input;

        $style='margin:10px; border:lightgrey 1px solid;border-radius:10px;padding:10px;display:inline-block;vertical-align:top;';

                $element_id='ct_ubloadfile_box_'.$field->fieldname;
                $result='
                <div style="'.$style.'"'.($field->isrequired ? ' class="inputbox required"' : '').' id="'.$element_id.'">
                	<div id="ct_fileuploader_'.$field->fieldname.'"></div>
                    <div id="ct_eventsmessage_'.$field->fieldname.'"></div>
                	<script>
                        UploadFileCount=1;
                        AutoSubmitForm=false;
                        esUploaderFormID="eseditForm";
                        ct_eventsmessage_element="ct_eventsmessage";
                        tempFileName="'.$fileid.'";
                        fieldValueInputBox="'.$prefix.$field->fieldname.'";
                    	var urlstr="'.JURI::root(true).'/index.php?option=com_customtables&view=fileuploader&tmpl=component&'
							.$field->fieldname.'_fileid='.$fileid.'&Itemid='.$field->ct->Env->Itemid.'&fieldname='.$field->fieldname.'";
                    	ct_getUploader('.$field->id.',urlstr,'.$max_file_size.',"jpg jpeg png gif svg webp","eseditForm",false,"ct_fileuploader_'
							.$field->fieldname.'","ct_eventsmessage_'.$field->fieldname.'","'.$fileid.'","'.$prefix.$field->fieldname.'","ct_ubloadedfile_box_'.$field->fieldname.'");

                    </script>
                    <input type="hidden" name="'.$prefix.$field->fieldname.'" id="'.$prefix.$field->fieldname.'" value=""'.($field->isrequired ? ' class="required"' : '').' />
			'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
                </div>
                ';

        return $result;

    }

    public static function getImageSRC($row,$realFieldName,$ImageFolder,&$imagefile,&$isShortcut)
    {
		$isShortcut=false;
		if(isset($row[$realFieldName]))
		{
			$img=$row[$realFieldName];
			if(strpos($img,'-')!==false)
			{
				$isShortcut=true;
				$img=str_replace('-','',$img);
			}

			$imagefile_=$ImageFolder.DIRECTORY_SEPARATOR.'_esthumb_'.$img;
			$imagesrc_=str_replace(DIRECTORY_SEPARATOR,'/',$ImageFolder).'/_esthumb_'.$img;
		}
		else
		{
			$imagefile_='';
			$imagesrc_='';
		}

		if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile_.'.jpg'))
		{
			$imagefile=$imagefile_.'.jpg';
			$imagesrc=$imagesrc_.'.jpg';
		}
		elseif(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile_.'.png'))
		{
			$imagefile=$imagefile_.'.png';
			$imagesrc=$imagesrc_.'.png';
		}
		elseif(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile_.'.webp'))
		{
			$imagefile=$imagefile_.'.webp';
			$imagesrc=$imagesrc_.'.webp';
			$imagesrc=$imagesrc_.'.webp';
		}
		else
		{
			$imagefile='';
			$imagesrc='';
		}

        return JURI::root(false).$imagesrc;
    }

    //Drupal has this implemented fairly elegantly:
    //https://stackoverflow.com/questions/1.6.1.1/php-get-actual-maximum-upload-size

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
}
