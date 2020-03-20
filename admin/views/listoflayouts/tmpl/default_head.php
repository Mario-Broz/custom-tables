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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>
<tr>
	<?php if ($this->canEdit&& $this->canState): ?>
		<th width="1%" class="nowrap center hidden-phone">
			<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->listDirn, $this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
		</th>
		<th width="20" class="nowrap center">
			<?php echo JHtml::_('grid.checkall'); ?>
		</th>
	<?php else: ?>
		<th width="20" class="nowrap center hidden-phone">
			&#9662;
		</th>
		<th width="20" class="nowrap center">
			&#9632;
		</th>
	<?php endif; ?>
	<th class="nowrap" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_LAYOUTNAME_LABEL', 'layoutname', $this->listDirn, $this->listOrder); ?>
	</th>
	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_LABEL'); ?>
	</th>
	<th class="nowrap hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_LABEL'); ?>
	</th>
	<?php if ($this->canState): ?>
		<th width="10" class="nowrap center" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_STATUS', 'published', $this->listDirn, $this->listOrder); ?>
		</th>
	<?php else: ?>
		<th width="10" class="nowrap center" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_STATUS'); ?>
		</th>
	<?php endif; ?>
	<th width="5" class="nowrap center hidden-phone" >
			<?php echo JHtml::_('grid.sort', 'COM_CUSTOMTABLES_LAYOUTS_ID', 'id', $this->listDirn, $this->listOrder); ?>
	</th>
	<th width="5" class="nowrap center hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_SIZE'); ?>
	</th>

	<th width="5" class="nowrap center hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_MODIFIEDBY'); ?>
	</th>

	<th width="5" class="nowrap center hidden-phone" >
			<?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_MODIFIED'); ?>
	</th>

</tr>
