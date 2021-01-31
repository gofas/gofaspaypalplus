<?php
/**
 * Módulo PayPal Plus para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @seehttps://gofas.net/?p=8294
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=7858
 * @version		1.2.0
 */

session_start();

$invoiceID = $invoiceID;
$approval_url = $_SESSION['approval_url'];
$userID = $_SESSION['userID'];
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$email = $_SESSION['email'];
$phone = $_SESSION['phone'];
$payerTaxId = $_SESSION['payerTaxId'];
$payerTaxIdType = $_SESSION['payerTaxIdType'];
$wh_remembered_cards = $_SESSION['wh_remembered_cards'];

$systemUrl = $_SESSION['systemUrl'];
$pp_mode = $_SESSION['pp_mode'];
$debug = $_SESSION['debug'];
$payButton = $_SESSION['payButton'];

$i_result .= '<script type="text/javascript" src="'.$systemUrl.'/assets/js/jquery.min.js"></script>';
$i_result .= '<script type="text/javascript" src="'.$systemUrl.'/modules/gateways/gofaspaypalplus/gppp.js?v='.time().'"></script>';
$i_result .= '<script src="https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js" type="text/javascript"></script>';
$i_result .= '<div id="ppplus"> </div>';
$i_result .= '<script type="text/javascript">
	var ppp = PAYPAL.apps.PPP({
		"placeholder": "ppplus",
      	"approvalUrl": "'.$approval_url.'",
      	"mode": "'.$pp_mode.'",
		"payerFirstName": "'.$firstname.'",
     	"payerLastName": "'.$lastname.'",
		"payerEmail": "'.$email.'",
		"payerPhone": "'.$phone.'",
     	"payerTaxId": "'.$payerTaxId.'",
      	"payerTaxIdType": "'.$payerTaxIdType.'",
		"rememberedCards": "'.$wh_remembered_cards.'",
      	"buttonLocation": "outside",
		"enableContinue":"continueButton",
		"disableContinue":"continueButton",
      	"preselection": "paypal",
     	"language": "pt_BR",
      	"country": "BR",
     	"disallowRememberedCards":false,      	
      	"iframeHeight": "450",
      	"useraction": "continue",
   	});
	</script>';
	//
	$i_result .= '<input type="hidden" style="dysplay:none;" id="system_url" value="'.$systemUrl.'"></input>';
	$i_result .= '<input type="hidden" style="dysplay:none;" id="user_id" value="'.$userID.'"></input>';
	$i_result .= '<input type="hidden" style="dysplay:none;" id="invoice_id" value="'.$invoiceID.'"></input>';
	$i_result .= '<input type="hidden" style="dysplay:none;" id="approval_url" value="'.$approval_url.'"></input>';
	$i_result .= '<input type="hidden" style="dysplay:none;" id="debug" value="'.$debug.'"></input>';
	
	
$i_result .= $payButton;
$i_result .= '<div id="lightbox"><span id="lightboxspan"></span> </div>';
echo $i_result;