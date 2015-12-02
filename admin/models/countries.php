<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.0.9
	@build			2nd December, 2015
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		countries.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Countries Model
 */
class CostbenefitprojectionModelCountries extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.name','name',
				'a.user','user',
				'a.currency','currency',
				'a.codethree','codethree',
				'a.codetwo','codetwo',
				'a.working_days','working_days'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$name = $this->getUserStateFromRequest($this->context . '.filter.name', 'filter_name');
		$this->setState('filter.name', $name);

		$user = $this->getUserStateFromRequest($this->context . '.filter.user', 'filter_user');
		$this->setState('filter.user', $user);

		$currency = $this->getUserStateFromRequest($this->context . '.filter.currency', 'filter_currency');
		$this->setState('filter.currency', $currency);

		$codethree = $this->getUserStateFromRequest($this->context . '.filter.codethree', 'filter_codethree');
		$this->setState('filter.codethree', $codethree);

		$codetwo = $this->getUserStateFromRequest($this->context . '.filter.codetwo', 'filter_codetwo');
		$this->setState('filter.codetwo', $codetwo);

		$working_days = $this->getUserStateFromRequest($this->context . '.filter.working_days', 'filter_working_days');
		$this->setState('filter.working_days', $working_days);
        
		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);
        
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
        
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
        
		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{ 
		// [10545] check in items
		$this->checkInNow();

		// load parent items
		$items = parent::getItems();

		// [10620] set values to display correctly.
		if (CostbenefitprojectionHelper::checkArray($items))
		{
			// [10623] get user object.
			$user = JFactory::getUser();
			foreach ($items as $nr => &$item)
			{
				$access = ($user->authorise('country.access', 'com_costbenefitprojection.country.' . (int) $item->id) && $user->authorise('country.access', 'com_costbenefitprojection'));
				if (!$access)
				{
					unset($items[$nr]);
					continue;
				}

			}
		} 
        
		// return items
		return $items;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// [7406] Get the user object.
		$user = JFactory::getUser();
		// [7408] Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// [7411] Select some fields
		$query->select('a.*');

		// [7418] From the costbenefitprojection_item table
		$query->from($db->quoteName('#__costbenefitprojection_country', 'a'));

		// Filter the countries (admin sees all)
		if (!$user->authorise('core.options', 'com_costbenefitprojection'))
		{
			$is = CostbenefitprojectionHelper::userIs($user->id);
			if (3 == $is)
			{
				// only load this users countries
				$query->where('a.user = '. (int) $user->id);
			}
			else
			{
				// don't allow user to see any countries
				$query->where('a.id = -4');
			}
		}

		// [7559] From the users table.
		$query->select($db->quoteName('g.name','user_name'));
		$query->join('LEFT', $db->quoteName('#__users', 'g') . ' ON (' . $db->quoteName('a.user') . ' = ' . $db->quoteName('g.id') . ')');

		// [7559] From the costbenefitprojection_currency table.
		$query->select($db->quoteName('h.name','currency_name'));
		$query->join('LEFT', $db->quoteName('#__costbenefitprojection_currency', 'h') . ' ON (' . $db->quoteName('a.currency') . ' = ' . $db->quoteName('h.codethree') . ')');

		// [7432] Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}

		// [7444] Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
		// [7447] Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('a.access = ' . (int) $access);
		}
		// [7452] Implement View Level Access
		if (!$user->authorise('core.options', 'com_costbenefitprojection'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');
		}
		// [7529] Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.name LIKE '.$search.' OR a.user LIKE '.$search.' OR g.name LIKE '.$search.' OR a.currency LIKE '.$search.' OR h.name LIKE '.$search.' OR a.codethree LIKE '.$search.' OR a.codetwo LIKE '.$search.' OR a.working_days LIKE '.$search.')');
			}
		}

		// [7763] Filter by currency.
		if ($currency = $this->getState('filter.currency'))
		{
			$query->where('a.currency = ' . $db->quote($db->escape($currency, true)));
		}

		// [7488] Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');	
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	* Method to get list export data.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExportData($pks)
	{
		// [7196] setup the query
		if (CostbenefitprojectionHelper::checkArray($pks))
		{
			// [7199] Get the user object.
			$user = JFactory::getUser();
			// [7201] Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// [7204] Select some fields
			$query->select('a.*');

			// [7206] From the costbenefitprojection_country table
			$query->from($db->quoteName('#__costbenefitprojection_country', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');

			// Filter the countries (admin sees all)
		if (!$user->authorise('core.options', 'com_costbenefitprojection'))
		{
			$is = CostbenefitprojectionHelper::userIs($user->id);
			if (3 == $is)
			{
				// only load this users countries
				$query->where('a.user = '. (int) $user->id);
			}
			else
			{
				// don't allow user to see any countries
				$query->where('a.id = -4');
			}
		}
			// [7216] Implement View Level Access
			if (!$user->authorise('core.options', 'com_costbenefitprojection'))
			{
				$groups = implode(',', $user->getAuthorisedViewLevels());
				$query->where('a.access IN (' . $groups . ')');
			}

			// [7223] Order the results by ordering
			$query->order('a.ordering  ASC');

			// [7225] Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// [10620] set values to display correctly.
				if (CostbenefitprojectionHelper::checkArray($items))
				{
					// [10623] get user object.
					$user = JFactory::getUser();
					foreach ($items as $nr => &$item)
					{
						$access = ($user->authorise('country.access', 'com_costbenefitprojection.country.' . (int) $item->id) && $user->authorise('country.access', 'com_costbenefitprojection'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						// [10833] unset the values we don't want exported.
						unset($item->asset_id);
						unset($item->checked_out);
						unset($item->checked_out_time);
					}
				}
				// [10842] Add headers to items array.
				$headers = $this->getExImPortHeaders();
				if (CostbenefitprojectionHelper::checkObject($headers))
				{
					array_unshift($items,$headers);
				}
				return $items;
			}
		}
		return false;
	}

	/**
	* Method to get header.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExImPortHeaders()
	{
		// [7245] Get a db connection.
		$db = JFactory::getDbo();
		// [7247] get the columns
		$columns = $db->getTableColumns("#__costbenefitprojection_country");
		if (CostbenefitprojectionHelper::checkArray($columns))
		{
			// [7251] remove the headers you don't import/export.
			unset($columns['asset_id']);
			unset($columns['checked_out']);
			unset($columns['checked_out_time']);
			$headers = new stdClass();
			foreach ($columns as $column => $type)
			{
				$headers->{$column} = $column;
			}
			return $headers;
		}
		return false;
	} 
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// [10168] Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.name');
		$id .= ':' . $this->getState('filter.user');
		$id .= ':' . $this->getState('filter.currency');
		$id .= ':' . $this->getState('filter.codethree');
		$id .= ':' . $this->getState('filter.codetwo');
		$id .= ':' . $this->getState('filter.working_days');

		return parent::getStoreId($id);
	}

	/**
	* Build an SQL query to checkin all items left checked out longer then a set time.
	*
	* @return  a bool
	*
	*/
	protected function checkInNow()
	{
		// [10561] Get set check in time
		$time = JComponentHelper::getParams('com_costbenefitprojection')->get('check_in');
		
		if ($time)
		{

			// [10566] Get a db connection.
			$db = JFactory::getDbo();
			// [10568] reset query
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__costbenefitprojection_country'));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				// [10576] Get Yesterdays date
				$date = JFactory::getDate()->modify($time)->toSql();
				// [10578] reset query
				$query = $db->getQuery(true);

				// [10580] Fields to update.
				$fields = array(
					$db->quoteName('checked_out_time') . '=\'0000-00-00 00:00:00\'',
					$db->quoteName('checked_out') . '=0'
				);

				// [10585] Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('checked_out') . '!=0', 
					$db->quoteName('checked_out_time') . '<\''.$date.'\''
				);

				// [10590] Check table
				$query->update($db->quoteName('#__costbenefitprojection_country'))->set($fields)->where($conditions); 

				$db->setQuery($query);

				$db->execute();
			}
		}

		return false;
	}
}
