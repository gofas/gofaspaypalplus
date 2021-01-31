<?php
/**
 * Módulo PayPal Plus para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=8294
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=7858
 * @version		1.2.0
 */

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }

/**
 *
 * Gravar transação no WHMCS
 * @ggnb_add_trans
 *
 */
function gppp_add_trans( $USERID, $INVOICEID, $paymentId, $whmcsAdmin, $debug) {
	$addtransaction = "addtransaction";
 	$addtransvalues['userid'] = $USERID;
 	$addtransvalues['invoiceid'] = $INVOICEID;
 	$addtransvalues['description'] = 'Cliente acessou a fatura.';
 	$addtransvalues['amountin'] = '0.00';
 	$addtransvalues['fees'] = '0.00';
 	$addtransvalues['paymentmethod'] = 'gofaspaypalplus';
 	$addtransvalues['transid'] = $paymentId;
 	$addtransvalues['date'] = date('d/m/Y');
	$addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);
	
		if ( $debug and $addtransresults['result'] === 'success') {
			echo'<pre class="debug"><b>Transação temporária gravada com sucesso - API WHMCS.</b>';
			//print_r($addtransresults);
			echo'<br/></pre>';
		} elseif ($debug and $addtransresults['result'] !== 'success'){
			echo'<pre class="debug"><p class="erro">Erro ao gravar a transação - API WHMCS.</p>';
			//print_r($addtransresults);
			echo'<br/></pre>';
		}
	if ( $addtransresults['result'] === 'success' ) {
		return 'success';
		
	} elseif ( $debug and $addtransresults['result'] !== 'success') {
		return '<b>Não foi possível gravar a transação no WHMCS.</b>';
	}
}
