<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.3.11
	@build			2nd May, 2016
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		details_above.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file

defined('_JEXEC') or die('Restricted access');

$form = $displayData->getForm();

$fields = array(
	'name',
	'user',
	'department'
);

?>
<div class="form-inline form-inline-header">
	<?php foreach($fields as $field){
		echo $form->renderField($field);
	} ?>
</div>
