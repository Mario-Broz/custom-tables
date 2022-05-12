<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;
use CustomTables\Ordering;
use CustomTables\DataTypes\Tree;

use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

$sitelib=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
require_once($sitelib.'layout.php');

class CustomTablesModelCatalog extends JModelLegacy
{
	var $ct;
	var $params;
	var $blockExternalVars;
	var $layout; //very strange parameter
	var $showcartitemsonly;
	var $showcartitemsprefix;

	function __construct()
	{
		parent::__construct();
		$this->ct = new CT;
		$this->showcartitemsprefix='customtables_';
	}

    function setFrmt($frmt)
    {
        $this->ct->Env->frmt=$frmt;
    }

    function load(&$params,$blockExternalVars=false,$layout='')
	{
		$this->blockExternalVars=$blockExternalVars;

		//get params
		if($this->blockExternalVars or (isset($params) and count($params)>1))
		{
			$this->params=$params;
		}
		else
		{
			$app		= Factory::getApplication();
			$this->params = $app->getParams();
		}
		
		$this->ct->Env->menu_params = $this->params;

		$this->layout = $layout; //Very strange parameter

		$forceitemid=$this->params->get('forceitemid');
		if(isset($forceitemid) and $forceitemid!='')
		{
			//Find Itemid by alias
			if(((int)$forceitemid)>0)
				$this->ct->Env->Itemid=$forceitemid;
			else
			{
				if($forceitemid!="0")
					$this->ct->Env->Itemid=(int)JoomlaBasicMisc::FindItemidbyAlias($forceitemid);//Accepts menu Itemid and alias
				else
					$this->ct->Env->Itemid=$this->ct->Env->jinput->get('Itemid',0,'INT');
			}
		}
		else
		{
			$this->ct->Env->Itemid = $this->ct->Env->jinput->get('Itemid',0,'INT');
			$forceitemid=null;
		}

		$this->ct->getTable($this->params->get( 'establename' ), $this->params->get('useridfield'));
				
		if($this->ct->Table->tablename=='')
		{
			Factory::getApplication()->enqueueMessage('Table not selected (185).', 'error');
			return false;
		}

		$this->ct->setFilter('', $this->ct->Env->menu_params->get('showpublished'));

		//sorting
		$this->ct->Ordering->parseOrderByParam($this->blockExternalVars,$this->params,$this->ct->Env->Itemid);

		//Limit
		$this->ct->applyLimits($blockExternalVars);
		
		//---------- Filtering
		$this->ct->Filter->addMenuParamFilter();
		if(!$this->blockExternalVars)
		{
			if($this->ct->Env->jinput->get('filter','','STRING'))
				$this->ct->Filter->addWhereExpression($this->ct->Env->jinput->get('filter','','STRING'));
		}

		if(!$this->blockExternalVars)
			$this->ct->Filter->addQueryWhereFilter();

		if($this->params->get( 'showcartitemsonly' )!='')
			$this->showcartitemsonly=(bool)(int)$this->params->get( 'showcartitemsonly' );
		else
			$this->showcartitemsonly=false;
		
		return true;
	}

	function getSearchResult($addition_filter='')
	{
		if(!isset($this->ct->Table->tableid))
			return false;
		
		if(!$this->blockExternalVars)
		{
			$moduleid = $this->ct->Env->jinput->get('moduleid',0,'INT');
			if($moduleid!=0)
			{
				$eskeysearch_ = $this->ct->Env->jinput->get('eskeysearch_'.$moduleid,'','STRING');
				if($eskeysearch_!='')
				{
					$this->ct->getRecordsByKeyword($eskeysearch_);
					return true;
				}
			}
		}
		
		if($addition_filter!='')
			$this->ct->Filter->where[] = $addition_filter;

		$this->ct->Table->recordcount = 0;
		
		if($this->layout=='currentuser' or $this->layout=='customcurrentuser')
		{
			//Not sure where this layout used
			if($this->ct->Table->useridfieldname!='')
				$this->ct->Filter->where[] = $this->ct->Table->useridrealfieldname.'='.(int)$this->ct->Env->user->get('id');
		}

		//Shopping Cart

		if($this->showcartitemsonly)
		{
			$cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

			if (isset($cookieValue))
			{
				if($cookieValue=='')
				{
					$this->ct->Filter->where[] = $this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
				}
				else
				{
					$items=explode(';',$cookieValue);
					$warr=array();
					foreach($items as $item)
					{
						$pair=explode(',',$item);
						$warr[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'='.(int)$pair[0];//id must be a number
					}
					$this->ct->Filter->where[] = '('.implode(' OR ', $warr).')';
				}
			}
			else
			{
				//Show only shoping cart items but these is nothing in cookies - show 0 records
				$this->ct->Filter->where[]=$this->ct->Table->realtablename.'.'.$this->ct->Table->tablerow['realidfieldname'].'=0';
			}
		}
		$this->ct->getRecords();
		return true;
	}

	function cart_emptycart()
	{
		$app = Factory::getApplication();
		$this->ct->Env->jinput->cookie->set($this->showcartitemsprefix.$this->ct->Table->tablename, '', time()-3600, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
		return true;
	}

	function cart_deleteitem()
	{
		$listing_id = $this->ct->Env->jinput->getCmd("listing_id",'');
		if($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;
		
		$this->cart_setitemcount(0);

		return true;
	}

	function cart_form_addtocart($itemcount=-1)
	{
		$listing_id = $this->ct->Env->jinput->getCmd("listing_id",'');
		if($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		if($itemcount==-1)
			$itemcount=$this->ct->Env->jinput->getInt('itemcount',0);

		$cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

		if (isset($cookieValue))
		{
			$items=explode(';',$cookieValue);
			$cnt=count($items);
			$found=false;
			for($i=0;$i<$cnt;$i++)
			{
				$pair=explode(',',$items[$i]);
				if(count($pair)!=2)
					unset($items[$i]); //delete the shit
				else
				{
					if((int)$pair[0]==$listing_id)
					{
						$new_itemcount=(int)$pair[1]+$itemcount;
						if($new_itemcount==0)
						{
							unset($items[$i]); //delete item
							$found=true;
						}
						else
						{
							//update counter
							$pair[1]=$new_itemcount;
							$items[$i]=implode(',',$pair);
							$found=true;
						}
					}
				}
			}//for

			if(!$found)
				$items[]=$listing_id.','.$itemcount; // add new item

			$items=array_values($items);
		}
		else
			$items=array($listing_id.','.$itemcount); //add new

		$nc=implode(';',$items);
		setcookie($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24);
		return true;
	}

	function cart_setitemcount($itemcount=-1)
	{
		$listing_id = $this->ct->Env->jinput->getCmd("listing_id",'');
		if($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		$app = Factory::getApplication();

		if($itemcount==-1)
			$itemcount=$this->ct->Env->jinput->getInt('itemcount',0);

		$cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

		if (isset($cookieValue))
		{
			$items=explode(';',$cookieValue);
			$cnt=count($items);
			$found=false;
			for($i=0;$i<$cnt;$i++)
			{
				$pair=explode(',',$items[$i]);
				if(count($pair)!=2)
					unset($items[$i]); //delete the shit
				else
				{
					if((int)$pair[0]==$listing_id)
					{
						if($itemcount==0)
						{
							unset($items[$i]); //delete item
							$found=true;
						}
						else
						{
							//update counter
							$pair[1]=$itemcount;
							$items[$i]=implode(',',$pair);
							$found=true;
						}
					}
				}
			}//for

			if(!$found)
				$items[]=$listing_id.','.$itemcount; // add new item

			$items=array_values($items);
		}
		else
			$items=array($listing_id.','.$itemcount); //add new

		$nc=implode(';',$items);
		setcookie($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24);
		return true;
	}

	function cart_addtocart()
	{
		$listing_id = $this->ct->Env->jinput->getCmd("listing_id",'');
		if($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
			return false;

		$cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix.$this->ct->Table->tablename);

		if (isset($cookieValue))
		{
			$items=explode(';',$cookieValue);
			$cnt=count($items);
			$found=false;
			for($i=0;$i<$cnt;$i++)
			{
				$pair=explode(',',$items[$i]);
				if(count($pair)!=2)
					unset($items[$i]); //delete the shit
				else
				{
					if((int)$pair[0]==$listing_id)
					{
						//update counter
						$pair[1]=((int)$pair[1])+1;
						$items[$i]=implode(',',$pair);
						$found=true;
					}
				}
			}

			if(!$found)
				$items[]=$listing_id.',1'; // add new item

			$items=array_values($items);
		}
		else
			$items=array($listing_id.',1'); //add new

		$nc=implode(';',$items);

		$this->ct->Env->jinput->cookie->set($this->showcartitemsprefix.$this->ct->Table->tablename, $nc, time()+3600*24, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
		return true;
	}

	function CleanUpPath($thePath)
	{
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
}
