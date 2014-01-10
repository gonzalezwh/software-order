<?php
    include_once('../cas_init.php');
    include_once('../auth.php');
    if(!check_read_access('order')){
    	die('Your account does not have access to this page.');
    }
	include_once APP_DIR."/library/fpdf.php";
	include_once "sql.php";
    $po = explode(",", $_GET['po']);
    $jv = explode(",", $_GET['jv']);
    $oetc = explode(",", $_GET['oetc']);
    $condition_tree = array();
    function constructTree($field,$values,$index){
        if($values[$index] == NULL){
            $values[$index] = "%%";
        }
        $tree = array(
            'field'=>$field,
            'operator'=>'LIKE',
            'value'=>$values[$index],
        );
        if(isset($values[$index+1])){
            $tree['OR'] = constructTree($field,$values,$index+1);
        }
        return $tree;
    }
    $po_condition = constructTree('PONumber',$po,0);
    $jv_condition = constructTree('JVNumber',$jv,0);
    $oetc_condition = constructTree('OETCNumber',$oetc,0);
    $condition_tree = $po_condition;
    $jv_condition['AND'] = $oetc_condition;
    $condition_tree['AND'] = $jv_condition;
	$ohandler = new OrderHandler();
	$orders = $ohandler->get(array(
        'condition_tree'=>$condition_tree,
        'orderby'=>'DateInstalled',
    ));
    $pdf = new FPDF();
	$pdf->AddPage('L','Letter');
	$pdf->SetFont('Helvetica','',16);
	$pdf->Image('../img/psulogo_horiz_std.gif',2,2);
	$pdf->SetTextColor(0,0,0);
	$pdf->Text(70,15,'OIT Software Order List');
	$pdf->SetTextColor(0,0,0);
	$pdf->SetY(25);
	$pdf->SetFontSize(10);
	$text = '';
    $pdf->Cell(25, 10, 'Date Installed',1);
    $pdf->Cell(75, 10, 'Software',1);
    $pdf->Cell(20, 10, 'RT#',1);
    $pdf->Cell(20, 10, 'PO#',1);
    $pdf->Cell(20, 10, 'OETC#',1);
    $pdf->Cell(20, 10, 'Price',1);
    $pdf->Cell(35, 10, 'AcctIndex',1);
    $pdf->Cell(20, 10, 'Authorizer',1);
    $pdf->Ln();
	foreach($orders as $order){
		extract($order->getFields());
        $pdf->Cell(25, 10, $DateInstalled,1);
        $pdf->Cell(75, 10, $SoftwareName,1);
        $pdf->Cell(20, 10, $RTNumber,1);
        $pdf->Cell(20, 10, $PONumber,1);
        $pdf->Cell(20, 10, $OETCNumber,1);
        $pdf->Cell(20, 10, $Price,1);
        $pdf->Cell(35, 10, $AccountIndex,1);
        $pdf->Cell(20, 10, $Authorizer,1);
        $pdf->Ln();
	}
	$pdf->Output();
?>