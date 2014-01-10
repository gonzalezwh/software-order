<?php
    //error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    //ini_set('display_errors', 1);
    ob_start(); 
    include_once('cas_init.php');
    function formerror($text){
        print 'An error has occured: "'.$text.'"';
        print '<br>Please contact the OIT Helpdesk at 5-HELP with this error.';
        die();
    }
    include_once('sql.php');
    $shandler = new SoftwareHandler();
    $oshandler = new OSHandler();
    $oslist = $oshandler->get();
    $vhandler = new VendorHandler();
    $vendors = $vhandler->get();
    $ahandler = new AuthorizerHandler();
    $software_list = $shandler->get(array(
        'field'=>'Historic',
        'operator'=>'LIKE',
        'value'=>'0',
    ));
    $software_order = array();
    //Validation rules:
    //All fields must exist in request
    $required_fields = array('os','serials','users','name','odin','phone','email','department','location','authorizer','index','notes','software');
    if(array_keys($_POST) !== $required_fields){
        formerror('One or more required form fields are missing.'); 
    }
    //Not-empty fields must have a value
    $not_empty_fields = array('os','serials','name','odin','phone','email');
    foreach($not_empty_fields as $field){
        if($_POST[$field] == ""){
            formerror("$field cannot be empty.");
        }
    }
    //Software list cannot be empty
    if(count($_POST['software']) == 0){
        formerror('Software list is empty.'); 
    }
    //Software must match OS and software must be valid
    $software = array();
    $subtotal = 0;
    for($i=0;$i<count($_POST['software']);++$i){
        $sid = $_POST['software'][$i];
        if(!isset($software_list[$sid])){
            formerror('Unable to find software at SID='.$sid); 
        }
        $current = $software_list[$sid]->getFields();
        $software[] = $current;
        if($_POST['os'] != '3' && $current['OSID'] !== '3' && $_POST['os'] !== $current['OSID']){
            formerror('Software does not match OS at SID='.$sid); 
        }
        $subtotal += $current['Price'];
    }
    //If order is not free, authorizer/index cannot be empty
    if($subtotal > 0){
        if($_POST['authorizer'] == ""){
            formerror('Non-free order with no authorizer information.');
        }
        //Authorizer must also exist in table
        $authorizer = $ahandler->get(array(
            'field'=>'AID',
            'operator'=>'LIKE',
            'value'=>$_POST['authorizer'],
        ));
        if(count($authorizer) == 0){
            formerror('Authorizer not found at AID='.$_POST['authorizer']);
        }
        else{
            $authorizer = $authorizer[$_POST['authorizer']];
        }
    }
    //Data is all validated now, we can continue to make a ticket and assemble a PDF
    //RT Ticket first:
    include 'library/rt_api.php';
    include 'library/form_submit.php';
	include 'library/rt_account.inc';
    //This is hard-coded because the OS table is usually used for software,
    //but (Windows/Mac) isn't really an operating system for a PC.
    if($_POST['os'] == '3'){
        $os = 'Virtual Machine';
    }
    else{
        $os = $oslist[$_POST['os']]->getField('Name');
    }
    $info = array(
        'Submitted by'=>$odin,
        'Contact Info'=>'',
        '---------------'=>'',
        'Full Name'=>$_POST['name'],
        'ODIN Username'=>$_POST['odin'],
        'Phone Number'=>$_POST['phone'],
        'E-mail Adderss'=>$_POST['email'],
        'Department'=>$_POST['department'],
        'Delviery Location'=>$_POST['location'],
        'Notes'=>$_POST['notes'],
        ''=>'',
        'Order Information'=>'',
        '----------------'=>'',
        'Computer Serial(s)'=>$_POST['serials'],
        'Computer User(s):'=>$_POST['users'],
        'Operating System'=>$os,
    );
    $serials = explode(',',$_POST['serials']);
    foreach($software as $i=>$current){
        $vendor = $vendors[$current['VID']]->getField('Name');
        $info[$i+1] = $vendor." ".$current['Name']." - $".$current['Price'];
    }
    $total = $subtotal*count($serials);
    $info['Total Cost'] = '$'.$subtotal.' x '.count($serials).' = $'.$total;
    $info['-'] = '';
	$ticket = new Ticket();
    if($subtotal <= 0){
        $ticket->Subject = 'Free Software Order for '.$_POST['odin'];
    }
    else{
        $info['Authorizer'] = $authorizer->getField('Odin');
        $info['Authorizer Department'] = $authorizer->getField('Department');
        $info['Account Index'] = $_POST['index'];
        $ticket->Cc = $authorizer->getField('Odin').'@pdx.edu';
        $ticket->Subject = 'Software Order for '.$_POST['odin'];
	}
	$ticket->id = 'ticket/new';
	$ticket->Status = 'new';
	$ticket->Queue = 'uss-software-order';
	$ticket->Requestor = $_POST['name'] . " <" . $_POST['email'] . ">";
    $ticket->setBody($subject, $requester, $info, $keywords);
	$id = form_submit($api, $ticket);
	
    //reply to the Authorizer if this is a paid order
    if($subtotal > 0){
			$text = "Hello,
		 Please review the software order below, and reply to this message stating if you approve or decline this purchase.
		 Remember to verify that the listed Account Index is valid. If none was entered you'll need to provide us with one.
		 This software order request will not be processed until you respond to this message.
		 
		 OIT Helpdesk
		 5-4357
				
		 Software order:".$ticket->Text;
			if($id) $api->reply($id,$text);
    }
    
    
    //Now that RT is done, make SQL rows for the order!
    if($subtotal > 0){ //we don't make rows for free software.
        $ohandler = new OrderHandler();
        foreach($serials as $serial){
            foreach($software as $i=>$current){
                if($current['Price'] == 0){
                    continue; //we don't make rows for free software.
                }
                $vendor = $vendors[$current['VID']]->getField('Name');
                $SoftwareName = $vendor." ".$current['Name'];
                $SID = $current['SID'];
                $fields = array( 
                    'RTNumber'=>$id, 
                    'DateOrdered'=>date('y-m-d'), 
                    'SoftwareName'=>$vendor." ".$current['Name'], 
                    'CompOSName'=>$os, 
                   // 'SID'=>$current['SID'], 
                    'ReqOdin'=>$odin, 
                    'CompSerial'=>$serial, 
                    'Price'=>$current['Price'],
                    'Authorizer'=>$info['Authorizer'], //RT already got this SQL data
                    'AccountIndex'=>$_POST['index'],
                );
                $order = new Order($fields);
                $ohandler->add($order);
            }
        }
    }
    //send them to receipt, redirect to prevent multiple submissions
    $kv = array();
    foreach ($_POST as $key=>$value) {
        if($key == 'software'){
            foreach($value as $i){
                $kv[] = "software[]=$i";
            }
        }
        else{
            $value = urlencode($value); //newlines in the Notes were breaking the script
            $kv[] = "$key=$value";
        }
    }
    $kv[] = "ticketid=$id";
    $query_string = join("&", $kv); 
    header('Location: receipt.php?'.$query_string);
?>