<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

$sitelib = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;
require_once($sitelib . 'layout.php');

class CustomTablesModelCatalog extends JModelLegacy
{
    var CT $ct;

    function __construct()
    {
        parent::__construct();
    }

    function cart_emptycart()
    {
        $app = Factory::getApplication();
        $this->ct->Env->jinput->cookie->set($this->showcartitemsprefix . $this->ct->Table->tablename, '', time() - 3600, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
        return true;
    }

    function cart_deleteitem()
    {
        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", '');
        if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
            return false;

        $this->cart_setitemcount(0);

        return true;
    }

    function cart_setitemcount($itemcount = -1)
    {
        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", '');
        if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
            return false;

        if ($itemcount == -1)
            $itemcount = $this->ct->Env->jinput->getInt('itemcount', 0);

        $cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix . $this->ct->Table->tablename);

        if (isset($cookieValue)) {
            $items = explode(';', $cookieValue);
            $cnt = count($items);
            $found = false;
            for ($i = 0; $i < $cnt; $i++) {
                $pair = explode(',', $items[$i]);
                if (count($pair) != 2)
                    unset($items[$i]); //delete the shit
                else {
                    if ((int)$pair[0] == $listing_id) {
                        if ($itemcount == 0) {
                            unset($items[$i]); //delete item
                            $found = true;
                        } else {
                            //update counter
                            $pair[1] = $itemcount;
                            $items[$i] = implode(',', $pair);
                            $found = true;
                        }
                    }
                }
            }//for

            if (!$found)
                $items[] = $listing_id . ',' . $itemcount; // add new item

            $items = array_values($items);
        } else
            $items = array($listing_id . ',' . $itemcount); //add new

        $nc = implode(';', $items);
        setcookie($this->showcartitemsprefix . $this->ct->Table->tablename, $nc, time() + 3600 * 24);
        return true;
    }

    function cart_form_addtocart($itemcount = -1)
    {
        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", '');
        if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
            return false;

        if ($itemcount == -1)
            $itemcount = $this->ct->Env->jinput->getInt('itemcount', 0);

        $cookieValue = $this->ct->Env->jinput->cookie->get($this->showcartitemsprefix . $this->ct->Table->tablename);

        if (isset($cookieValue)) {
            $items = explode(';', $cookieValue);
            $cnt = count($items);
            $found = false;
            for ($i = 0; $i < $cnt; $i++) {
                $pair = explode(',', $items[$i]);
                if (count($pair) != 2)
                    unset($items[$i]); //delete it
                else {
                    if ((int)$pair[0] == $listing_id) {
                        $new_itemcount = (int)$pair[1] + $itemcount;
                        if ($new_itemcount == 0) {
                            unset($items[$i]); //delete item
                            $found = true;
                        } else {
                            //update counter
                            $pair[1] = $new_itemcount;
                            $items[$i] = implode(',', $pair);
                            $found = true;
                        }
                    }
                }
            }//for

            if (!$found)
                $items[] = $listing_id . ',' . $itemcount; // add new item

            $items = array_values($items);
        } else
            $items = array($listing_id . ',' . $itemcount); //add new

        $nc = implode(';', $items);
        setcookie($this->showcartitemsprefix . $this->ct->Table->tablename, $nc, time() + 3600 * 24);
        return true;
    }

    function cart_addtocart()
    {
        $listing_id = $this->ct->Env->jinput->getCmd("listing_id", '');
        if ($listing_id == '' or (is_numeric($listing_id) and $listing_id == 0))
            return false;

        $cookieValue = $this->ct->Env->jinput->cookie->getVar($this->showcartitemsprefix . $this->ct->Table->tablename);

        if (isset($cookieValue)) {
            $items = explode(';', $cookieValue);
            $cnt = count($items);
            $found = false;
            for ($i = 0; $i < $cnt; $i++) {
                $pair = explode(',', $items[$i]);
                if (count($pair) != 2)
                    unset($items[$i]); //delete the shit
                else {
                    if ((int)$pair[0] == $listing_id) {
                        //update counter
                        $pair[1] = ((int)$pair[1]) + 1;
                        $items[$i] = implode(',', $pair);
                        $found = true;
                    }
                }
            }

            if (!$found)
                $items[] = $listing_id . ',1'; // add new item

            $items = array_values($items);
        } else
            $items = array($listing_id . ',1'); //add new

        $nc = implode(';', $items);

        $this->ct->Env->jinput->cookie->set($this->showcartitemsprefix . $this->ct->Table->tablename, $nc, time() + 3600 * 24,
            $this->ct->app->get('cookie_path', '/'), $this->ct->app->get('cookie_domain'), $this->ct->app->isSSLConnection());

        return true;
    }

}
