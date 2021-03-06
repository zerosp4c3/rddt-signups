<?php defined('SYSPATH') or die('No direct access allowed.');

class Controller_Character extends Abstract_Controller_Website {

	public function action_index()
	{
		// Load array of this user's character list from the database
		$characters = ORM::factory('character')->where('user_id', '=', $this->user->id)->and_where('visibility', '=', '1')->order_by('name', 'ASC')->find_all()->as_array();
		
		// Pass character array to the view class
		if (count($characters) !== 0)
		{
			$this->view->characters = $characters;
			$this->view->count = TRUE;
		}
		
	}
	
	public function action_add()
	{
		// Can user add new characters at this time?
		if ( ! $this->user->can('character_add'))
		{
			// Not allowed, get the reason why
			$status = Policy::$last_code;
			
			// Must be logged in to add a character
			if ($status === Policy_Character_Add::NOT_LOGGED_IN)
			{			
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.add.not_logged_in'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				
				// Redirect to login screen; come back once finished
				$this->session->set('follow_login', $this->request->url());
				$this->request->redirect(Route::url('user', array('controller' => 'user', 'action' => 'login')));
			}
			// Unspecified reason for denial
			else if ($status === Policy_Character_Add::NOT_ALLOWED)
			{
				Notices::add('denied', 'msg_info', array('message' => Kohana::message('gw', 'character.add.not_allowed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				
				$this->request->redirect(Route::url('default', array('controller' => 'welcome', 'action' => 'index')));
			}
		}
		
		// Alias for user and profile
		$user = $this->user;
		$profile = $user->profile;
		
		// Is the form submitted correctly w/ CSRF token?
		if ($this->valid_post())
		{
			// Submitted data
			$character_post = Arr::get($this->request->post(), 'character', array());
						
			// Create the character
			$character = ORM::factory('character', array('name' => $character_post['name']));
			
			// Character already exists
			if ($character->loaded())
			{
				// Duplicate
				if ($character->visibility == 1)
				{
					Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'character.add.duplicate'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				}
				// Reactivate previously deleted character
				else
				{
					$character->visibility = 1;
					$character->save();
					
					Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'character.add.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				}
				
				$this->request->redirect(Route::url('character'));
			}
			else
			{
				try
				{
					$character = ORM::factory('character')->create_character($user, $character_post, array('name', 'profession'));
					
					Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'character.add.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
					
					$this->request->redirect(Route::url('character'));
				}
				catch(ORM_Validation_Exception $e)
				{			
					$this->view->errors = $e->errors('character');
					
					// We have no valid Model_Character, so we have to pass the form values back directly
					$this->view->values = $character_post;
				}
			}
		}
	}
	
	public function action_remove()
	{
		// Load character model
		$character = ORM::factory('character', $this->request->param('id'));
		
		if ( ! $this->user->can('character_remove', array('character' => $character)))
		{
			// Not allowed, get the reason why
			$status = Policy::$last_code;
			
			// Unspecified reason for denial
			if ($status === Policy_Remove_Character::NOT_ALLOWED)
			{			
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.remove.not_allowed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));

				$this->request->redirect(Route::url('character'));
			}
			elseif ($status === Policy_Remove_Character::NOT_OWNER)
			{
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.remove.not_owner'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				
				$this->request->redirect(Route::url('character'));
			}
		}
		
		if ( ! $character->loaded())
			die('Failed to load character');
		
		// Remove
		$character->visibility = 0;
		$character->save();
		
		Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'character.remove.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
		
		$this->request->redirect(Route::url('character'));
	}
	
	public function action_edit()
	{
		// Load character model
		$character = ORM::factory('character', $this->request->param('id'));
		
		if ( ! $character->loaded())
			throw new HTTP_Exception_404;
		
		// Is user allowed to edit this character?
		if ( ! $this->user->can('character_edit', array('character' => $character)))
		{
			// Not allowed, get the reason why
			$status = Policy::$last_code;
			
			// Unspecified reason for denial
			if ($status === Policy_Character_Edit::NOT_ALLOWED)
			{			
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.edit.not_allowed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));

				$this->request->redirect(Route::url('character'));
			}
			// Non-administrator tried to edit another user's character
			elseif ($status === Policy_Edit_Character::NOT_OWNER)
			{
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.edit.not_owner'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				
				$this->request->redirect(Route::url('character'));
			}
			// Other denial reason
			else
			{
				Notices::add('info', 'msg_info', array('message' => Kohana::message('gw', 'character.edit.not_allowed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
				
				$this->request->redirect(Route::url('character'));
			}
		}
		
		// Valid csrf, etc
		if ($this->valid_post())
		{
			// Extract character data from $_POST
			$character_post = Arr::get($this->request->post(), 'character', array());
			
			try
			{			
				// Set data to character model and save
				$character->edit_character($character_post);
				
				Notices::add('success', 'msg_info', array('message' => Kohana::message('gw', 'character.edit.success'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
			catch(ORM_Validation_Exception $e)
			{
				$this->view->errors = $e->errors('character');
				
				Notices::add('error', 'msg_info', array('message' => Kohana::message('gw', 'character.edit.failed'), 'is_persistent' => FALSE, 'hash' => Text::random($length = 10)));
			}
		}
		
		// Pass character data to view class
		$this->view->character_data = $character;
	}
}