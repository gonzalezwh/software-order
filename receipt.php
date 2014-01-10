<?php
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 1);
    include_once('cas_init.php');
    include 'library/fpdf.php';
    include 'sql.php';
    //prep handlers
    $shandler = new SoftwareHandler();
    $software_list = $shandler->get(array(
        'field'=>'Historic',
        'operator'=>'LIKE',
        'value'=>'0',
    ));
    $oshandler = new OSHandler();
    $oslist = $oshandler->get();
    $vhandler = new VendorHandler();
    $vendors = $vhandler->get();
    $ahandler = new AuthorizerHandler();
    $authorizer = $ahandler->get(array(
    	'field'=>'AID',
    	'operator'=>'=',
    	'value'=>$_GET['authorizer']
    ));
    $authorizer = $authorizer[$_GET['authorizer']];
    //prep data
    $software = array();
    $subtotal = 0;
    for($i=0;$i<count($_GET['software']);++$i){
        $sid = $_GET['software'][$i];
        $current = $software_list[$sid]->getFields();
        $software[] = $current;
        $subtotal += $current['Price'];
    }
    $serials = explode(',',$_GET['serials']);
    $total = $subtotal*count($serials);
    if($_GET['os'] == '3'){
        $os = 'Virtual Machine';
    }
    else{
        $os = $oslist[$_GET['os']]->getField('Name');
    }
    //write pdf
    $pdf = new FPDF();
	$pdf->AddPage('P','Letter');
	$pdf->SetFont('Helvetica','',16);
	$pdf->Image('img/psulogo_horiz_std.gif',2,2);
	$pdf->SetTextColor(0,0,0);
	$pdf->Text(70,15,'OIT Software Order Form');
    $pdf->SetFontSize(10);
	$pdf->Text(160,5,'Submitted: '.date('F jS, Y')."\n");
	$pdf->Text(160,10,'RT#'.$_GET['ticketid']);
	$pdf->SetY(25);
	$pdf->SetFont('Helvetica','BI',12);
	$pdf->MultiCell(200,7,'Submitter Information');
	$pdf->SetFont('Helvetica','',12);
	$text = 'Name: '.$_GET['name']."\n";
	if($_GET['department'] !== ""){
		$text .= 'Department: '.$_GET['department']."\n";
	}
	if($_GET['location'] !== ""){
		$text .= 'Delivery Location: '.$_GET['location']."\n";
	}
	$text .= 'Using login: '.$odin."\n";
	$pdf->MultiCell(200,7,$text);
	$pdf->SetFont('Helvetica','BI',12);
	$text = "Software List \n";
    $pdf->MultiCell(200,10,$text);
    $pdf->SetFont('Helvetica','',12);
    $pdf->Cell(100, 10, 'Name',1);
    $pdf->Cell(100, 10, 'Price',1);
    $pdf->Ln();
    foreach($software as $i=>$current){
        $vendor = $vendors[$current['VID']]->getField('Name');
        $pdf->Cell(100, 10, ($i+1).": $vendor ".$current['Name'],1);
        $pdf->Cell(100, 10, "$".$current['Price'],1);
        $pdf->Ln();
    }
    $text = '';
    $text .= 'Total cost: $'.$subtotal.' x '.count($serials)." = $$total\n";
    $pdf->MultiCell(200,10,$text);
    $text = 'Machine serials: '.$_GET['serials']."\n";
    $text .= "Operating System: ".$os;
	$pdf->MultiCell(200,7,$text);
	if($subtotal > 0){
		$pdf->SetFont('Helvetica','BI',12);
		$text = "\nPurchasing Information\n";
		$pdf->MultiCell(200,7,$text);
		$pdf->SetFont('Helvetica','',12);
		$text = 'Authorizer: '.$authorizer->getField('FullName')."\n";
		$text .= 'Account Index: '.$_GET['index']."\n";
		$pdf->MultiCell(200,7,$text);
		$pdf->SetFont('Helvetica','I',12);
		$text = "\nThis is a record of your software request, but your department will not be charged until your authorizer approves the order. ";
		$text .= "Please print this for your records, you will receive an email shortly with information regarding your order status. If you have any ";
		$text .= "questions contact Nate Henry at nhenry@pdx.edu.";
		$pdf->MultiCell(200,7,$text);
	}
	$pdf->Output();
    $id = 123;
?>