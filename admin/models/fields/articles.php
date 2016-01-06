<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.1.0
	@build			6th January, 2016
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		articles.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Articles Form Field class for the Costbenefitprojection component
 */
class JFormFieldArticles extends JFormFieldList
{
	/**
	 * The articles field type.
	 *
	 * @var		string
	 */
	public $type = 'articles'; 
	/**
	 * Override to add new button
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.2
	 */
	protected function getInput()
	{
		// [7952] see if we should add buttons
		$setButton = $this->getAttribute('button');
		// [7954] get html
		$html = parent::getInput();
		// [7956] if true set button
		if ($setButton === 'true')
		{
			$user = JFactory::getUser();
			// [7960] only add if user allowed to create article
			if ($user->authorise('core.create', 'com_costbenefitprojection'))
			{
				// [7978] get the input from url
				$jinput = JFactory::getApplication()->input;
				// [7980] get the view name & id
				$values = $jinput->getArray(array(
					'id' => 'int',
					'view' => 'word'
				));
				// [7985] check if new item
				$ref = '';
				if (!is_null($values['id']) && strlen($values['view']))
				{
					// [7989] only load referal if not new item.
					$ref = '&amp;ref=' . $values['view'] . '&amp;refid=' . $values['id'];
				}
				// [7992] build the button
				$button = '<a class="btn btn-small btn-success"
					href="index.php?option=com_costbenefitprojection&amp;view=article&amp;layout=edit'.$ref.'" >
					<span class="icon-new icon-white"></span>' . JText::_('COM_COSTBENEFITPROJECTION_NEW') . '</a>';
				// [7996] return the button attached to input field
				return $html . $button;
			}
		}
		return $html;
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 */
	public function getOptions()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.id','a.title','a.alias'),array('id','article_title','alias')));
		$query->from($db->quoteName('#__content', 'a'));
		$query->where($db->quoteName('a.state') . ' = 1');
		$query->order('a.title ASC');
		$db->setQuery((string)$query);
		$items = $db->loadObjectList();
		$options = array();
		if ($items)
		{
			$options[] = JHtml::_('select.option', '', 'Select an Article');
			foreach($items as $item)
			{
				$options[] = JHtml::_('select.option', $item->id, $item->article_title . ' (' . $item->alias . ')');
			}
		}
		return $options;
	}
}
