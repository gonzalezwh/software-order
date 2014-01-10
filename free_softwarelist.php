<?php
    include_once('cas_init.php');
	include_once APP_DIR."/library/fpdf.php";
	include_once "sql.php";
	$shandler = new SoftwareHandler();
	$softwares = $shandler->get(array(
        'condition_tree'=>array(
            'field'=>'Historic',
            'operator'=>'=',
            'value'=>'0',
            'AND'=>array(
                'field'=>'Price',
                'operator'=>'=',
                'value'=>'0',
            )
        ),
        'assoc'=>'false',
        'orderby'=>'VID',
    ));
	$vhandler = new VendorHandler();
    $vendors = $vhandler->get();
	$oshandler = new OSHandler();
    $oslist = $oshandler->get();
	$pdf = new FPDF();
	$pdf->AddPage('P','Letter');
	$pdf->SetFont('Helvetica','',16);
	$pdf->SetFillColor(0,0,0);
	$pdf->Rect(0,0,500,20,'F');
	$pdf->Image('img/logo.png',2,2);
	$pdf->SetTextColor(255,255,255);
	$pdf->Text(67,10,'OIT Free Software List');
	$pdf->SetTextColor(0,0,0);
	$pdf->SetY(25);
	$pdf->SetFontSize(12);
	$text = "The following software is for PSU-owned machines only. \n";
	foreach($softwares as $software){
		extract($software->getFields());
		$vendor = $vendors[$VID];
		$os = $oslist[$OSID];
		$os_name = $os->getField('Name');
        $vendor_name = $vendor->getField('Name');
		$text .= "$vendor_name $Name for $os_name - $$Price\n";
	}
	$pdf->MultiCell(500,10,$text);
	$pdf->Output();
?>