<?php defined('SYSPATH') or die('No direct access allowed.');class Policy_Login extends Policy {	const LOGGED_IN = 1;		public function execute(Model_ACL_User $user, array $array = NULL)
{		// If already logged in, you obviously can't do it again		if ( ! Auth::instance()->logged_in())		{			return TRUE;		}		else		{			return self::LOGGED_IN;		}				return FALSE;	}}