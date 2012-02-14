<?php

class Controller_Main extends Controller {

	public function action_index()
	{
		echo View::factory('main') -> render();
	}

}
