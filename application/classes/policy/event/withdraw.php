<?php defined('SYSPATH') or die('No direct access allowed.');

class Policy_Event_Withdraw extends Policy {

	const START_TIME_PASSED = 1;
	{
		// No cancelling on past events
	}

}