<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Slot extends Abstract_Controller_Website {
	
	public function action_index()
	{
		$filter = Arr::get($this->request->query(), 'filter', 'all');
		
		if ($filter == 'all')
		{
			// Send all slot data to the view class
			$slots = ORM::factory('slot')->order_by('name', 'ASC')->find_all();
			
			// Mirror slot data for function that requires them all
			$this->view->all_slots = $slots;
		}
		elseif ($filter != 'num')
		{
			// Send filtered list to the view class
			$slots = ORM::factory('slot')->where('name', 'like', "$filter%")->order_by('name', 'ASC')->find_all();
			
			// All slot data for function that requires full list
			$this->view->all_slots = ORM::factory('slot')->order_by('name', 'ASC')->find_all();
		}
		else
		{
			// Use numeric filter
			$slots = ORM::factory('slot')->where('name', 'RLIKE', '^[^a-zA-Z]')->order_by('name', 'ASC')->find_all();
			
			// All slot data for function that requires full list
			$this->view->all_slots = ORM::factory('slot')->order_by('name', 'ASC')->find_all();
		}
		
		$this->view->slot_data = $slots;
	}
	
	public function action_add()
	{
		// Is user allowed to add slots?
		if ( ! $this->user->can('slot_add'))
		{
			$status = Policy::$last_code;
			
			if ($status === Policy_Slot_Add::NOT_LOGGED_IN)
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.add.not_logged_in'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			else
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.add.not_allwed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			$this->request->redirect(Route::url('slot'));
		}
		
		// Valid csrf, etc.
		if ($this->valid_post())
		{
			// Get relevant data from $_POST
			$slot_post = Arr::get($this->request->post(), 'slot', array());
			
			try
			{
				// Create new record
				$slot = ORM::factory('slot');
				$slot->add_slot($slot_post);
				
				Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'slot.add.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				$this->request->redirect(Route::url('slot'));
			}
			catch(Exception $e)
			{
				$slot = ORM::factory('slot', array('name' => $slot_post['name']));
				
				if ($slot->loaded())
				{
					$slot->visibility = 1;
					$slot->save();

					Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'slot.add.extant'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
					$this->request->redirect(Route::url('slot'));
				}
				else
				{
					// Pass errors and submited data out to the view class
					$this->view->errors = $e->errors();
					$this->view->values = $slot_post;
				}
			}
		}
	}
	
	public function action_edit()
	{
		// Load record to be edited
		$slot = ORM::factory('slot', $this->request->param('id'));
		
		if ( ! $slot->loaded())
			throw new HTTP_Exception_404;
		
		// Can this user edit this slot?
		if ( ! $this->user->can('slot_edit', array('slot' => $slot)))
		{
			$status = Policy::$last_code;
			
			if ($status === Policy_Slot_Add::NOT_LOGGED_IN)
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.edit.not_logged_in'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			else
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.edit.not_allwed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			$this->request->redirect(Route::url('slot'));
		}
		// Valid csrf, etc.
		if ($this->valid_post())
		{
			// Extract relevant data from $_POST
			$slot_post = Arr::get($this->request->post(), 'slot', array());
			
			try
			{
				// If attempting to edit a non-existant slot, throw exception
				if ( ! $slot->loaded())
					throw new Exception('slot didn\'t load');
					
				// Save data
				$slot->edit_slot($slot_post);
				
				Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'slot.edit.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				$this->request->redirect(Route::url('slot'));
			}
			catch(ORM_Validation_Exception $e)
			{
				// Pass errors and submited values out to the view class
				$this->view->errors = $e->errors();
				$this->view->values = $slot_post;
			}
		}
		$this->view->slot_data = $slot;
	}
	
	public function action_remove()
	{
		// Load record to be removed
		$slot = ORM::factory('slot', $this->request->param('id'));
		
		// Can this user edit this slot?
		if ( ! $this->user->can('slot_edit', array('slot' => $slot)))
		{
			$status = Policy::$last_code;
			
			if ($status === Policy_Slot_Add::NOT_LOGGED_IN)
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.remove.not_logged_in'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			else
			{
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'slot.remove.not_allwed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			$this->request->redirect(Route::url('slot'));
		}
		// Don't want to compeltely remove as that would leave gaps in historical data
		$slot->visibility = 0;
		$slot->save();
		
		$this->request->redirect(Route::url('slot'));
	}
	
}