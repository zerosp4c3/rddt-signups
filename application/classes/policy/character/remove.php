<?php defined('SYSPATH') or die('No direct access allowed.');

class Policy_Character_Remove extends Policy {

	public function execute(Model_ACL_User $user, array $extras = NULL)
	{
		// This is not a very good policy yet
		return TRUE;
	}

}