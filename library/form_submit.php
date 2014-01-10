<?php 
	function form_submit($api, $ticket){
		$return_ticket = $api->createTicket($ticket);
		$id = trim($return_ticket->id);
		if (empty($id)) {
			mail("samuel6@pdx.edu", "$subject RT_API Failure", json_encode($ticket), 'From: form_submit@pdx.edu');
		}
		return $id;
	}
?>