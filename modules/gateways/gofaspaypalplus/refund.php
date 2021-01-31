<?php
/**
 * Módulo PayPal Plus para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=8294
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=7858
 * @version		1.2.1
 */

function gofaspaypalplus_refund($params) {
	
	$refund_result = array();
	
	// Parametros da configuração do Gateway
	$sandbox				= $params['sandboxmode'];
	if ( $params['sandboxmode'] === "on" ) {
		$client_id			= $params['clientidsandbox'];
		$client_secret		= $params['clientsecretsandbox'];
		$pp_host			= 'https://api.sandbox.paypal.com';
		$pp_mode			= 'sandbox';
	}
	elseif( $params['sandboxmode'] !== "on" ) {
		$client_id			= $params['clientid'];
		$client_secret		= $params['clientsecret'];
		$pp_host			= 'https://api.paypal.com';
		$pp_mode			= 'live';
	}
	
	if ( $params['debugmode'] === "on" ) {
		$debug				= true;
	}
	else {
		$debug				= false;
	}
	
	$refund_result[] = 'pp_host: ' . $pp_host;
    
    
	// Transaction Parameters
    $transaction_id_refund	= $params['transid'];
    $refund_amount			= $params['amount'];
    $currency_code			= $params['currency'];
	$invoice_id				= $params['invoiceid'];
	
	/*
	 *
	 * Call to PayPal
	 * Obtain o access_token
	 *
	 */
	
		$getTokenCurl = curl_init($pp_host.'/v1/oauth2/token'); 
		curl_setopt($getTokenCurl, CURLOPT_POST, true); 
		curl_setopt($getTokenCurl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($getTokenCurl, CURLOPT_USERPWD, $client_id .':'. $client_secret);
		curl_setopt($getTokenCurl, CURLOPT_HEADER, false); 
		curl_setopt($getTokenCurl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($getTokenCurl, CURLOPT_POSTFIELDS, "grant_type=client_credentials"); 
		
		$getTokenResponse = curl_exec( $getTokenCurl );
		$getTokenError = curl_error( $getTokenCurl );
	    $getTokenInfo = curl_getinfo( $getTokenCurl );
		curl_close( $getTokenCurl ); // close cURL handler
	    
		$getTokenArrayResponse = json_decode( $getTokenResponse ); // Convert the result from JSON format to a PHP array 
		$access_token = $getTokenArrayResponse->access_token; // Access Token
		
		// Erro
		if ($getTokenError) {
			$error 				.= $getTokenError;
			$refund_result[]	= $error;
		} 
		if ($getTokenArrayResponse->error) {
			$error				.= $getTokenArrayResponse->error_description;
			$refund_result[]	= $error;
		}
		
		$refund_result[] = $getTokenArrayResponse; // Debug
		
		/** 
		*
		* Proccess Refund
		*
		*/
		
		 $refund_data = '{
			"amount": {
				"total": "'.(string)$refund_amount.'",
				"currency": "'.(string)$currency_code.'"
			},
			"invoice_number": "'.(string)$invoice_id.'"
			}'; 
		
		$refund_data	= '{}';
		
		$refund_result['refund_data'] = $refund_data; // Debug
		
		if( $access_token and !$error ) {
			$refundCurl = curl_init( $pp_host.'/v1/payments/sale/'.$transaction_id_refund.'/refund/'); 
			curl_setopt($refundCurl, CURLOPT_POST, true);
			curl_setopt($refundCurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($refundCurl, CURLOPT_HEADER, true);
			curl_setopt($refundCurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($refundCurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json',
					'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
					));
	
			curl_setopt($refundCurl, CURLOPT_POSTFIELDS, $refund_data ); 
		
			$refund_response		= curl_exec( $refundCurl );
			$refundError			= curl_error( $refundCurl );
	    	$refundInfo				= curl_getinfo( $refundCurl );
			$refund_info_http_code	= (string)$refundInfo['http_code'];
			
			if ($refund_info_http_code === "400" ) {
				$error = true;
			}
			if ($refund_info_http_code === "200" ) {
				$error = false;
			}
			
			curl_close( $refundCurl ); // close cURL handler
			$refund_result['refund_info']	= $refundInfo; // Debug
	    	$refund_result['http_code']		= $refundInfo['http_code']; // Debug
			
			$refund_array_response	= json_decode( $refund_response ); // Convert the result from JSON format to a PHP array
			$refundTransactionId	= $refund_array_response['id']; // Refund ID
			$refund_state			= $refund_array_response['state']; 
			
			// Erro
			if ($refundError) {
				$error				.= $refundError;
				$refund_result['error']	= $error;
			} 
			if ($refund_array_response->error) {
				$error				.= $refund_array_response->error_description;
				$refund_result['error2']	= $error;
			}
		
			$refund_result['refund_response'] = $refund_response; // Debug
			$refund_result['refund_array_response'] = $refund_array_response; // Debug
		}
	
		// Results
		if($error) {
			$response_data	= $error;
			$status			= 'error';
			
		}
		elseif (!$error) { 
			$response_data = $refund_result;
			$status			= 'success';
		} 
	
	// Debug
	//if($debug) {
		$logModuleCall = logModuleCall( 'gofaspaypalplus', 'refund', $params, false, $refund_result, false);
	//}
	// perform API call to initiate refund and interpret result
    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => $status,
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $response_data,
        // Unique Transaction ID for the refund transaction
        'transid' => $transaction_id_refund,
        // Optional fee amount for the fee value refunded
        'fees' => $fee_amount,
    );
}