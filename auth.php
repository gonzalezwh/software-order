<?php
include_once "sql.php";
    /**
        The functions here will eventually be replaced by data 
            stored in SQL (group membership), but for now everything
            is hand-written in. 
        Groups are as follows, see below for access rules:
            1: Admins
            2: Jackie (for adding PO numbers to purchases)
            3: Read Orders 
        The base form for software orders is completely open, and the ajax 
            it uses has no authentication. 
    */
	function getUser(){ 
		$odin = phpCAS::getUser();
		$uhandler = new UserHandler();
		$user = $uhandler->get(array(
			'field'=>'Odin',
			'operator'=>'LIKE',
			'value'=>$odin,
            'assoc'=>'false'));
		return $user;
	}
	function base_user_check($user){
		if(count($user) == 0){
			return FALSE;
		}
		elseif($user[0]->getField('Flag') == 1){
			return FALSE;
		}
		else{
			return TRUE;
		}
	}
	function check_read_access($type){ 
		$user = getUser();
		if(!base_user_check($user)){
			return FALSE;
		}
		$GID = $user[0]->getField('GID');
		$valid_GID = array();
		switch($type){
			case 'software':
				$valid_GID[] = 1;
			case 'os':
				$valid_GID[] = 1;
			case 'vendor':
				$valid_GID[] = 1;
			case 'user':
				$valid_GID[] = 1;
			case 'group':
				$valid_GID[] = 1;
			case 'order':
				$valid_GID[] = 1;
                $valid_GID[] = 2;
                $valid_GID[] = 3;
			case 'authorizer':
				$valid_GID[] = 1;
		}
		if(in_array($GID, $valid_GID)){
			return TRUE;
		}
		else{
			return FALSE;
		}
	}
	function check_write_access($type){
		$user = getUser();
		if(!base_user_check($user)){
			return FALSE;
		}
		$GID = $user[0]->getField('GID');
		$valid_GID = array();
		switch($type){
			case 'software':
				$valid_GID[] = 1;
			case 'os':
				$valid_GID[] = 1;
			case 'vendor':
				$valid_GID[] = 1;
			case 'user':
				$valid_GID[] = 1;
			case 'group':
				$valid_GID[] = 1;
			case 'order':
				$valid_GID[] = 1;
                $valid_GID[] = 2;
			case 'authorizer':
				$valid_GID[] = 1;
		}
		if(in_array($GID, $valid_GID)){
			return TRUE;
		}
		else{
			return FALSE;
		}
	}
?>
