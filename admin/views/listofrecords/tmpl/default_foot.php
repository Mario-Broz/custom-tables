<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
if (!defined('_JEXEC')) die('Restricted access');

$colspan = 9 + count($this->ct->Languages->LanguageList);

?>
<tr>
    <td colspan="<?php echo $colspan; ?>"><?php echo $this->pagination->getListFooter(); ?></td>
</tr>
