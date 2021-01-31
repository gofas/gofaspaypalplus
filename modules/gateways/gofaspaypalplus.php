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

use WHMCS\Database\Capsule; // Since 1.2.0

include __DIR__.'/gofaspaypalplus/configuration.php';


function gofaspaypalplus_link($params){
	
	// if is invoice
	if ( stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') ) {
	// Parametros da configuração do Gateway
	include __DIR__.'/gofaspaypalplus/params.php';
	include __DIR__.'/gofaspaypalplus/functions.php';
	
	
	/**
	 *
	 * Start licensing verification
	 *
	 */

function gppp_check_license( $license_key, $local_key ) {
    $gofas = 'https://gofas.net/cliente/';
    $secret_key = 'H5Ke7mmArYussbcBdZt4yoEiBhQBB8'; // PayPal Plus
    $local_key_days = 7;
    $allowcheckfaildays = 3;
    // ----------------- Start Verification ------------------
    $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $license_key);
    $checkdate = date("Ymd");
    $domain = $_SERVER['SERVER_NAME'];
    $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
    $dirpath = dirname(__FILE__);
    $verifyfilepath = 'modules/servers/licensing/verify.php';
    $local_key_valid = false;
    if ($local_key) {
        $local_key = str_replace("\n", '', $local_key); # Remove the line breaks
        $localdata = substr($local_key, 0, strlen($local_key) - 32); # Extract License Data
        $md5hash = substr($local_key, strlen($local_key) - 32); # Extract MD5 Hash
        if ($md5hash == md5($localdata . $secret_key)) {
            $localdata = strrev($localdata); # Reverse the string
            $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
            $localdata = substr($localdata, 32); # Extract License Data
            $localdata = base64_decode($localdata);
            $local_key_results = unserialize($localdata);
            $originalcheckdate = $local_key_results['checkdate'];
            if ($md5hash == md5($originalcheckdate . $secret_key)) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $local_key_days, date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $local_key_valid = true;
                    $results = $local_key_results;
                    $validdomains = explode(',', $results['validdomain']);
                    if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                        $local_key_valid = false;
                        $local_key_results['status'] = "Invalid";
                        $results = array();
                    }
                    $validips = explode(',', $results['validip']);
                    if (!in_array($usersip, $validips)) {
                        $local_key_valid = false;
                        $local_key_results['status'] = "Invalid";
                        $results = array();
                    }
                    $validdirs = explode(',', $results['validdirectory']);
                    if (!in_array($dirpath, $validdirs)) {
                        $local_key_valid = false;
                        $local_key_results['status'] = "Invalid";
                        $results = array();
                    }
                }
            }
        }
    }
    if (!$local_key_valid) {
        $responseCode = 0;
        $postfields = array(
            'licensekey' => $license_key,
            'domain' => $domain,
            'ip' => $usersip,
            'dir' => $dirpath,
        );
        if ($check_token) $postfields['check_token'] = $check_token;
        $query_string = '';
        foreach ($postfields AS $k=>$v) {
            $query_string .= $k.'='.urlencode($v).'&';
        }
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gofas . $verifyfilepath);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
		elseif(!function_exists('curl_exec')){
			die("Curl PHP Extension Missing");
		}
        if ($responseCode != 200) {
            $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($local_key_days + $allowcheckfaildays), date("Y")));
            if ($originalcheckdate > $localexpiry) {
                $results = $local_key_results;
            } else {
                $results = array();
                $results['status'] = "Invalid";
                $results['description'] = "Remote Check Failed. Response code ".$responseCode;
                return $results;
            }
        } else {
            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
            $results = array();
            foreach ($matches[1] AS $k=>$v) {
                $results[$v] = $matches[2][$k];
            }
        }
        if (!is_array($results)) {
            die("Resposta Inválida do Servidor de Licença");
        }
        if ($results['md5hash']) {
            if ($results['md5hash'] != md5( $secret_key . $check_token )) {
                $results['status'] = "Invalid";
                $results['description'] = "MD5 Checksum Verification Failed";
                return $results;
            }
        }
        if ($results['status'] == "Active") {
            $results['checkdate'] = $checkdate;
            $data_encoded = serialize($results);
            $data_encoded = base64_encode($data_encoded);
            $data_encoded = md5($checkdate . $secret_key) . $data_encoded;
            $data_encoded = strrev($data_encoded);
            $data_encoded = $data_encoded . md5($data_encoded . $secret_key);
            $data_encoded = wordwrap($data_encoded, 80, "\n", true);
            $results['local_key'] = $data_encoded;
        }
        $results['remotecheck'] = true;
    }
    unset($postfields,$data,$matches,$gofas,$checkdate,$usersip,$local_key_days,$allowcheckfaildays,$md5hash);
	return $results;
}

// Get local_key
foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gppplocalkey') -> get( array( 'setting', 'value', 'created_at' ) ) as $local_key_info ) {
	$local_key_setting		= $local_key_info->setting;
	$local_key_value		= $local_key_info->value;
	$local_key_created_at	= $local_key_info->created_at;
}
if ($debug) {
	$gppp_log = array();
}
if ( $local_key_value ) {
	$local_key	= $local_key_value;
	if($debug) {
		$gppp_log['local_key_status']	= "Local Key Exist\n";
		$gppp_log['local_key']	= substr($local_key_value, 0, 25). "(...)". substr($local_key_value, -25). "\n";
		$gppp_log['created_at']	= $local_key_created_at. "\n";
	}
}
elseif( !$local_key_value ) {
	$local_key = false;
	if($debug) { $gppp_log['status']	= "Local Key Not Exist\n"; }
}
/**
 * Validate license key information
 */
$results = gppp_check_license( $license_key, $local_key, $debug );
if ($debug) { 
	$gppp_log['license_info']	= "License Info\n";
	$gppp_log['status']			= $results['status']."\n";
	$gppp_log['registeredname']	= $results['registeredname']."\n";
	$gppp_log['companyname']		= $results['companyname']."\n";
	$gppp_log['email']			= $results['email']."\n";
	$gppp_log['productname']		= $results['productname']."\n";
	$gppp_log['regdate']			= $results['regdate']."\n";
	$gppp_log['nextduedate']		= $results['nextduedate']."\n";
	$gppp_log['billingcycle']	= $results['billingcycle']."\n";
	$gppp_log['validdomain']		= $results['validdomain']."\n";
	$gppp_log['validip']			= $results['validip']."\n";
	$gppp_log['validdirectory']	= $results['validdirectory']."\n";
	$gppp_log['checkdate']		= $results['checkdate']."\n";
}
// Interpret response
if ($results['status'] === "Active") {
    $local_key_data = $results['local_key'];
	if ( !$local_key_value ) {
		if($debug) { $gppp_log['validation']	= "Licença válida. Primeira local_key gravada\n"; }
		try {
			Capsule::table('tblconfiguration')->insert(array('setting' => 'gppplocalkey', 'value' => $local_key_data, 'created_at' =>  date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s") ));
		
		 	if($debug) { $gppp_log['inserction']	= "Coluna gppplocalkey inserida\n"; }
		}
		catch (\Exception $e) {
   			$e->getMessage();
		}
	}
	elseif ( $local_key_value and $local_key_data ) {
		try {
			Capsule::table('tblconfiguration')->where( 'setting', 'gppplocalkey')->update(array('value' => $local_key_data, 'created_at' =>  $local_key_created_at , 'updated_at' => date("Y-m-d H:i:s")));
					
			if($debug) { $gppp_log['update']	= "Licença válida, local_key atualizada\n"; }		
		}
		catch (\Exception $e) {
    		echo $e->getMessage();
		}
	}
}
elseif ($results['status'] === "Invalid" ) {
	$error = "<b><span style='color: red;'>Gofas PayPal Plus: Licença Inválida</span></b>";
	$gppp_log['validation']	= $error;
	//die("Licença Inválida");
}
elseif ($results['status'] === "Expired" ) {
	$error = "<b><span style='color: red;'>Gofas PayPal Plus: Licença Expirada</span></b>";
	$gppp_log['validation']	= $error;
	//die("Licença Expirada");
}
elseif ($results['status'] === "Suspended" ) {
	$error = "<b><span style='color: red;'>Gofas PayPal Plus: Licença Suspensa</span></b>";
	$gppp_log['validation']	= $error;
	//die("Licença Suspensa");		
}
else {
	$error = "<b><span style='color: red;'>Gofas PayPal Plus: Resposta Inválida do Servidor de Licença</span></b>";
	$gppp_log['validation']	= $error;
	//die("Resposta Inválida.");  
}
if ($debug) {
	echo "<pre>";
	print_r($gppp_log);
	logModuleCall( 'gofaspaypalplus', 'check license', 'check license remotely', false, $gppp_log, false);
	echo '</pre>';
}
	/**
	 *
	 * End licensing verification
	 *
	 */
	
	if ($debug and !$GATerror){
			echo'<pre style="height:200px;"><b>Todas a sconfigurações do módulo.</b><br/>';
			print_r($params);
			echo "<br/></pre>";
		}
	
	// Verifica instalação
	if ( !Capsule::schema()->hasTable('gofaspaypalplus') ) {
    	try {
		Capsule::schema()->create('gofaspaypalplus', function($table) {
			// incremented id
        	$table->increments('id');
       		// unique column
        	$table->integer('user_id');
        	$table->string('payer_id');
        	$table->string('remembered_cards');
			$table->string('api_clientid');
    	});
	
		} catch (\Exception $e) {
    		$error .= "Não foi possível criar a tabela do módulo no banco de dados: {$e->getMessage()}";
		}
	}
	
	// update 0.1.5 -> 0.1.6
	if( !Capsule::schema()->hasColumn('gofaspaypalplus', 'api_clientid') ) {
		try {
			Capsule::schema()->table('gofaspaypalplus', function($table) {
				$table->string('api_clientid');
				});
		} catch (\Exception $e) {
    		$error .= "Não foi possível criar a coluna 'apiclientid' na tabela do módulo: {$e->getMessage()}";
		}
	}
	
	/**
	*
	* Obtem o access_token
	*
	**/
	if(!$error) {
		$GATcurl = curl_init($pp_host.'/v1/oauth2/token'); 
		curl_setopt($GATcurl, CURLOPT_POST, true); 
		curl_setopt($GATcurl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($GATcurl, CURLOPT_USERPWD, $client_id .':'. $client_secret);
		curl_setopt($GATcurl, CURLOPT_HEADER, false); 
		curl_setopt($GATcurl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($GATcurl, CURLOPT_POSTFIELDS, "grant_type=client_credentials"); 
		
		$GATresponse = curl_exec( $GATcurl );
		$GATerror = curl_error( $GATcurl );
	    $GATinfo = curl_getinfo( $GATcurl );
		curl_close( $GATcurl ); // close cURL handler
	    
		$GATarrayResponse = json_decode( $GATresponse ); // Convert the result from JSON format to a PHP array 
		$access_token = $GATarrayResponse->access_token; // Access Token
		
		session_start();
		$_SESSION['access_token'] = $access_token;
		
		if ($GATerror) {$error .= $GATerror;} // Erro
		if ($GATarrayResponse->error) {$error .= $GATarrayResponse->error_description;}
		
		if ($debug and !$GATerror){
			echo'<br/><pre><b>Resultado da solicitação do Token (API PayPal).</b><br/>';
			echo 'Código de resposta: '.$GATinfo['http_code'];
			echo '<br/>Resposta crua: '.$GATresponse;
			echo '<br/>Token: '.$access_token;
			echo '<br/>Tempo levado: ' . $GATinfo['total_time']*1000 . 'ms';
			echo "<br/></pre>";
		} elseif ($debug and $GATerror){
			echo'<pre><b>ERRO na solicitação do Token (API PayPal).</b><br/>';
			echo 'Código de resposta: '.$GATinfo['http_code'];
			echo '<br/>Resposta crua: '.$GATresponse;
			//echo '<br/>Resposta decodificada: '.print_r($GATarrayResponse);
			echo '<br/>Erro: '.$GATerror;
			echo "<br/></pre>";
		}
	}
		/** 
		*
		* Lista perfis de pagamento existentes
		*
		*/
		if ($access_token) {
			$LWEPcurl = curl_init($pp_host.'/v1/payment-experience/web-profiles'); 
			curl_setopt($LWEPcurl, CURLOPT_POST, false);
			curl_setopt($LWEPcurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($LWEPcurl, CURLOPT_HEADER, false);
			curl_setopt($LWEPcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($LWEPcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json',
					'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
					)); 
		
			$LWEPresponse = curl_exec( $LWEPcurl );
			$LWEPerror = curl_error( $LWEPcurl );
	    	$LWEPinfo = curl_getinfo( $LWEPcurl );
			curl_close( $LWEPcurl ); // close cURL handler
	    
			$LWEParrayResponse = json_decode( $LWEPresponse, TRUE ); // Convert the result from JSON format to a PHP array
			
			function search_key($LWEParrayResponse, $key, $value){
				$results = array();
				if (is_array($LWEParrayResponse)) {
					if (isset($LWEParrayResponse[$key]) && $LWEParrayResponse[$key] == $value) {
						$results[] = $LWEParrayResponse;
					}
				foreach ($LWEParrayResponse as $subarray) {
					$results = array_merge($results, search_key($subarray, $key, $value));
					}
				}
				return $results;
			}
			$LWEParrayResponseClean = search_key($LWEParrayResponse, 'name', $profile_name);
			
			$experience_profile_name = $LWEParrayResponseClean['0']['name']; // Experience Profile Name
			$experience_profile_id = $LWEParrayResponseClean['0']['id']; // Experience Profile ID
			
			if ( $LWEPerror ) { $error .= $LWEPerror; } // Erro
			if ($LWEParrayResponse->error) {$error .= $LWEParrayResponse->error_description;}
			
			if ($debug and !$LWEPerror){
				echo'<pre><b>Resultado da listagem de perfis de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$LWEPinfo['http_code'];
				echo '<br/>ID do Perfil de Experiência: '.$experience_profile_id;
				echo '<br/>Nome do Perfil de Experiência: '.$experience_profile_name;
				echo '<br/> KEY: '.$key ;
				echo '<br/>Resposta crua: '.$LWEPresponse;
				//echo '<br/>Resposta decodificada: '; print_r($LWEParrayResponse);
				echo "<br/></pre>";
			
			} elseif ($debug and $LWEPerror){
				echo'<pre><b>ERRO na listagem de perfis de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$LWEPinfo['http_code'];
				echo '<br/>Resposta crua: '.$LWEPresponse;
				echo '<br/>Erro: '.$LWEPerror;
				echo "<br/></pre>";
			}
		}
		/** 
		*
		* Cria perfil de pagamento, se não existe nenhum ou o existente é diferente do padrão
		*
		*/
		if(!$experience_profile_name and $access_token and !$error) {
			$CWEPcurl = curl_init($pp_host.'/v1/payment-experience/web-profiles'); 
			curl_setopt($CWEPcurl, CURLOPT_POST, true);
			curl_setopt($CWEPcurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($CWEPcurl, CURLOPT_HEADER, false);
			curl_setopt($CWEPcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($CWEPcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json',
					'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
					));
	
			curl_setopt($CWEPcurl, CURLOPT_POSTFIELDS, $experience_profile); 
		
			$CWEPresponse = curl_exec( $CWEPcurl );
			$CWEPerror = curl_error( $CWEPcurl );
	    	$CWEPinfo = curl_getinfo( $CWEPcurl );
			curl_close( $CWEPcurl ); // close cURL handler
	    
			$CWEParrayResponse = json_decode( $CWEPresponse, TRUE ); // Convert the result from JSON format to a PHP array
			$experience_profile_id = $CWEParrayResponse['id']; // Experience Profile ID
			$experience_profile_name = $CWEParrayResponse['name']; // Experience Profile Name
			
			if ( $CWEPerror ) { $error .= $CWEPerror; } // Erro
			if ($CWEParrayResponse->error) {$error .= $CWEParrayResponse->error_description;}
		
			if ($debug and !$CWEPerror){
				echo'<pre><b>Resultado da criação do perfil de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$CWEPinfo['http_code'];
				echo '<br/>Perfil de Experiência: '.$experience_profile_id;
				echo '<br/>Resposta crua: '.$CWEPresponse;
				//echo '<br/>Resposta decodificada: '; print_r($CWEParrayResponse);
				echo "<br/></pre>";
			
			} elseif ($debug and $CWEPerror){
				echo'<pre><b>ERRO na criação do perfil de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$CWEPinfo['http_code'];
				echo '<br/>Resposta crua: '.$CWEPresponse;
				echo '<br/>Erro: '.$CWEPerror;
				echo "<br/></pre>";
			}
		}
		/**
		*
		* Verifica transações associadas à fatura
		*
		*/
		//$transID = 'PAY-4VU26865HE813411LLDIKHBI'; // Remover
		if( $transID and $trans_desc === 'Cliente acessou a fatura.' and $trans_gateway === 'gofaspaypalplus' and !$error ) {
		
		$VTRANScurl = curl_init( $pp_host.'/v1/payments/payment/'.$transID ); // Get payment
		curl_setopt($VTRANScurl, CURLOPT_POST, false);
		curl_setopt($VTRANScurl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($VTRANScurl, CURLOPT_HEADER, false);
		curl_setopt($VTRANScurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($VTRANScurl, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer '.$access_token,
				'Accept: application/json',
				'Content-Type: application/json',
				'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
				));
		
		$VTRANSresponse = curl_exec( $VTRANScurl );
		$VTRANSerror = curl_error( $VTRANScurl );
	    $VTRANSinfo = curl_getinfo( $VTRANScurl );
		curl_close( $VTRANScurl ); // close cURL handler
	    
		$VTRANSarrayResponse = json_decode( $VTRANSresponse, TRUE ); // JSON to PHP array
			
		$payment_state				= $VTRANSarrayResponse['state'];
		$invoice_number				= $VTRANSarrayResponse['transactions']['0']['invoice_number'];
		$invoice_amount				= $VTRANSarrayResponse['transactions']['0']['amount']['total'];
		
		if ( $payment_state === "created" and (string)$invoice_number === (string)$invoiceID and (string)$invoice_amount === (string)$invoiceAmount ) {
			$paymentId					= (string)$VTRANSarrayResponse['id'];
			$_SESSION['payment_id'] 	= $paymentId;
			$approval_url				= $VTRANSarrayResponse['links']['2']['href'];
		}
		
		elseif ( ( $payment_state === "pending" or $payment_state === "approved" ) and (string)$invoice_number === (string)$invoiceID and (string)$invoice_amount === (string)$invoiceAmount ) {
			$result_1 .= '
			<p style="color:green; font-weight: bold;">Pagamento em análise, aguarde a confirmação por email.</p>';
			$stop = true;
		}
					
		// Erros
		if ( $VTRANSerror ) {
			$error .= $VTRANSerror;
		} 
			
		if ( $VTRANSarrayResponse->error ) {
			$error .= $VTRANSarrayResponse->error_description;
		}
		
		if ($debug and !$VTRANSerror){
				echo'<pre><b>Resultado da verificação da transação (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$VTRANSinfo['http_code'];
				//echo '<br/>Perfil de Experiência: '.$experience_profile_id;
				echo '<br/>Resposta crua: '.$VTRANSresponse;
				//echo '<br/>Resposta decodificada: '; print_r($CWEParrayResponse);
				echo "<br/></pre>";
			
			} elseif ($debug and $VTRANSerror){
				echo'<pre><b>ERRO na verificação da transação (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$VTRANSinfo['http_code'];
				echo '<br/>Resposta crua: '.$VTRANSresponse;
				echo '<br/>Erro: '.$VTRANSerror;
				echo "<br/></pre>";
			}
	}
		
		/**
		*
		* Verifica webhook
		*
		*/
		if( $access_token and !$error ) {
			$VWHKcurl = curl_init($pp_host.'/v1/notifications/webhooks'); 
			curl_setopt($VWHKcurl, CURLOPT_POST, false);
			curl_setopt($VWHKcurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($VWHKcurl, CURLOPT_HEADER, false);
			curl_setopt($VWHKcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($VWHKcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json',
					'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
					));
		
			$VWHKresponse = curl_exec( $VWHKcurl );
			$VWHKerror = curl_error( $VWHKcurl );
	    	$VWHKinfo = curl_getinfo( $VWHKcurl );
			curl_close( $VWHKcurl ); // close cURL handler
	    
			$VWHKarrayResponse = json_decode( $VWHKresponse, TRUE ); // JSON to PHP array
			$webhook_id = $VWHKarrayResponse['webhooks']['0']['id']; // webhook ID
			$webhook_url = $VWHKarrayResponse['webhooks']['0']['url']; // webhook url
			
			// Erros
			if ( $VWHKerror ) { $error .= $VWHKerror; } 
			if ( $VWHKarrayResponse->error ) { $error .= $VWHKarrayResponse->error_description;}
		
			if ($debug and !$VWHKerror){
				echo'<pre style="height:150px;"><b>Verificação por webhooks existentes - API PayPal.</b><br/>';
				echo 'Código de resposta: '.$VWHKinfo['http_code'];
				echo '<br/>Webhook ID: '.$webhook_id;
				echo '<br/>Webhook URL: '.$webhook_url;
				echo '<br/>Resposta crua: '.$VWHKresponse;
				echo '<br/>Resposta decodificada: '; print_r( $VWHKarrayResponse );
				if (empty($webhook_id)) { echo 'Webhook ID vazia'; }
				else { echo 'Webhook ID Não é vazia'; }
				echo "<br/></pre>";
			
			} elseif ($debug and $VWHKerror){
				echo'<pre><b>ERRO na Verificação por webhooks existentes - API PayPal.</b><br/>';
				echo 'Código de resposta: '.$VWHKinfo['http_code'];
				echo '<br/>Resposta crua: '.$VWHKresponse;
				echo '<br/>Erro: '.$VWHKerror;
				echo "<br/></pre>";
			}
		}
		
		/**
		*
		* Cria webhook
		*
		*/
		
		if( ( empty( $webhook_url ) or $webhook_url !== $callback_url ) and $access_token and !$error ) {
			
			$webhook_data = '{
				"url": "'.$callback_url.'",
  				"event_types": [
									
					{
      					"name": "PAYMENT.SALE.COMPLETED"
    				},
					{
      					"name": "PAYMENT.SALE.DENIED"
    				},
					{
      					"name": "PAYMENT.SALE.REFUNDED"
    				},
					{
      					"name": "PAYMENT.SALE.REVERSED"
    				}
  				]
			}';
			// all event types: https://gist.github.com/mauriciogofas/fb7dd0e27c0fd89944d64a01bea3eb4f
			
			$CWHKcurl = curl_init($pp_host.'/v1/notifications/webhooks'); 
			curl_setopt($CWHKcurl, CURLOPT_POST, true);
			curl_setopt($CWHKcurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($CWHKcurl, CURLOPT_HEADER, false);
			curl_setopt($CWHKcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($CWHKcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json',
					'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
					));
	
			curl_setopt( $CWHKcurl, CURLOPT_POSTFIELDS, $webhook_data); 
		
			$CWHKresponse = curl_exec( $CWHKcurl );
			$CWHKerror = curl_error( $CWHKcurl );
	    	$CWHKinfo = curl_getinfo( $CWHKcurl );
			curl_close( $CWHKcurl ); // close cURL handler
	    
			$CWHKarrayResponse = json_decode( $CWHKresponse, TRUE ); // JSON to PHP array
			$webhook_id = $CWHKarrayResponse['id']; // webhook ID
			$webhook_url = $CWHKarrayResponse['url']; // webhook url
			
			if ( $CWHKerror ) { $error .= $CWHKerror; } // Erro
			if ($CWHKarrayResponse->error) {$error .= $CWHKarrayResponse->error_description;}
		
			if ($debug and !$CWHKerror){
				echo'<pre><b>Resultado da criação de novo webhook - API PayPal.</b><br/>';
				echo 'Código de resposta: '.$CWHKinfo['http_code'];
				echo '<br/>Webhook ID: '.$webhook_id;
				echo '<br/>Webhook URL: '.$webhook_url;
				echo '<br/>Resposta crua: '.$CWHKresponse;
				echo '<br/>Resposta decodificada: '; print_r( $CWHKarrayResponse );
				echo '<br>', $callback_url;
				echo "<br/></pre>";
			
			} elseif ($debug and $CWHKerror){
				echo'<pre><b>ERRO na criação de novo webhook - API PayPal.</b><br/>';
				echo 'Código de resposta: '.$CWHKinfo['http_code'];
				echo '<br/>Resposta crua: '.$CWHKresponse;
				echo '<br/>Erro: '.$CWHKerror;
				echo "<br/></pre>";
			}
		}
		
		
		/**
		*
		* Criar pagamento
		*
		*/
		// Json para gerar o pagamento
		$payment = '{
			"intent": "sale",
			"experience_profile_id": "'.$experience_profile_id.'",
			"payer":{
				"payment_method": "paypal"
				},
				"transactions":[
				{
					"amount":{
						"currency": "BRL",
						"total": "'.$invoiceAmount.'",
						"details":{
							"shipping": "0",
							"subtotal": "'.$invoiceAmount.'",
							"shipping_discount": "0.00",
							"insurance": "0.00",
							"handling_fee": "0.00",
							"tax": "0.00"
							}
						},
					"description": "'.$invoiceDescription.'",
					"payment_options":{
						"allowed_payment_method": "IMMEDIATE_PAY"
						},
						"invoice_number": "'.$invoiceID.'",
						"item_list":{
							"shipping_address":{
								"recipient_name": "'.$firstname.' '.$lastname.'",
								"line1": "'.$address1.'",
								"line2": "'.$address2.'",
								"city": "'.$city.'",
								"country_code": "BR",
								"postal_code": "'.$postcode.'",
								"state": "'.$state.'",
								"phone": "'.(string)$phone.'"
								},
							"items":[
								{
								"name": "'.$companyName.'",
								"description": "'.$invoiceDescription.'",
								"quantity": "1",
								"price": "'.$invoiceAmount.'",
								"tax": "0.00",
								"currency": "BRL"
								}
							]
						}
					}
				],
				"redirect_urls":{
					"return_url": "'.$systemUrl.'",
      				"cancel_url": "'.$systemUrl.'"
					}
			}';
   
   			/*
			*
			* envia solicitação para criar um pagamento
			*
			*/
			if( $access_token and $experience_profile_id and !$approval_url and !$paymentId and !$stop and !$error) {
				$CPcurl = curl_init($pp_host.'/v1/payments/payment/'); 
				curl_setopt($CPcurl, CURLOPT_POST, true);
				curl_setopt($CPcurl, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($CPcurl, CURLOPT_HEADER, false);
				curl_setopt($CPcurl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($CPcurl, CURLOPT_HTTPHEADER, array(
						'Authorization: Bearer '.$access_token,
						'Accept: application/json',
						'Content-Type: application/json',
						'PayPal-Partner-Attribution-Id: WHMCS_Ecom_PPPlus'
						));
	
				curl_setopt($CPcurl, CURLOPT_POSTFIELDS, $payment); 
		
				$CPresponse = curl_exec( $CPcurl );
				$CPerror = curl_error( $CPcurl );
	    		$CPinfo = curl_getinfo( $CPcurl );
				curl_close( $CPcurl ); // close cURL handler
	    
				$CParrayResponse = json_decode( $CPresponse, TRUE ); // Convert the result from JSON format to a PHP array 
				$paymentId = $CParrayResponse['id']; // ID do pagamento, usado para efetuar o pagamento
				$_SESSION['payment_id'] 	= $paymentId;
				$approval_url = $CParrayResponse['links']['1']['href']; // URL do pagamento, usado para montar o iframe
				
				// Grava transação no WHMCS
				if ( stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') ) {
					gppp_add_trans( $userID, $invoiceID, $paymentId, $whmcsAdmin, $debug );
				}
				if($CPerror) {$error .= $CPerror;} // Erro
				if ($CParrayResponse->error) {$error .= $CParrayResponse->error_description;}
		
				if ($debug and !$CPerror){
					echo'<pre><b>Resultado da solicitação de criação de pagamento (API PayPal).</b><br/>';
					echo 'Código de resposta: '.$CPinfo['http_code'];
					echo '<br/> Approval URL: '.$approval_url;
					echo '<br/>Resposta crua: '.$CPresponse;
					//echo '<br/>Resposta decodificada: '; print_r($CParrayResponse);
					echo "<br/></pre>";
				} elseif ($debug and $CPerror){
					echo'<pre><b>ERRO na solicitação de criação de pagamento (API PayPal).</b><br/>';
					echo 'Código de resposta: '.$CPinfo['http_code'];
					echo '<br/>Resposta crua: '.$CPresponse;
					echo '<br/>Erro: '.$CPerror;
					echo "<br/></pre>";
				}
			}
			/**
			*
			* JavaScript debug
			* 
			*/
			
			$rememberedCards_data = Capsule::table('gofaspaypalplus')
                        ->where('user_id', $userID )
						->select('user_id','remembered_cards', 'api_clientid')
                        ->get();
			$rememberedCardsApiClientID = end($rememberedCards_data)->api_clientid;
			
			if ( $rememberedCardsApiClientID === $client_id ) {		
			
				$rememberedCards					= end($rememberedCards_data)->remembered_cards;			
				$_SESSION['wh_remembered_cards']	= $rememberedCards;
			}
			elseif ( $rememberedCardsApiClientID !== $client_id ) {
				$rememberedCards					= null;			
				$_SESSION['wh_remembered_cards']	= null;
			}
			
			if ($debug) {
				echo'<pre><b>Resultados da execução do pagamento via AJAX / JavaScript.</b><br/>';
				echo '<span id="gpppjsdebug"></span><br/>';
				echo 'Resposta Crua: <span id="gpppjsdebug2"></span><br/>';
				echo 'Payer ID: <span id="gpppjsdebugPayerId"></span><br/>';
				echo 'Remembered Cards: <span id="gpppjsdebugrememberedCards"></span><br/>';
				echo 'WH Remeb Cards: # '.$rememberedCards.' # '.end($rememberedCards_data)->user_id.' # '.$rememberedCardsApiClientID.'<br/>';
				echo 'Retorno da execução de pagamento: <span id="executeReturn"></span></pre>';
			}
			/*
			*
			* Resultado impresso na área Visível na fatura/checkout
			*
			*/
			// payerTaxId & payerTaxIdType

			if (!$payerTaxId_2) {
				$payerTaxId = $payerTaxId_1;
				$payerTaxIdType = $payerTaxIdType_1;
				
			} elseif ($payerTaxId_2) {
				$payerTaxId = $payerTaxId_2;
				$payerTaxIdType = $payerTaxIdType_2;
			}
			$result .= $css;
			$result .= '<script type="text/javascript" src="'.$systemUrl.'/assets/js/jquery.min.js" charset="UTF-8"></script>';
			$result .= '<script type="text/javascript" src="'.$systemUrl.'/modules/gateways/gofaspaypalplus/gppp.js?v=1.2.1" charset="UTF-8"></script>';
			$result .= '<script src="https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js" type="text/javascript" charset="UTF-8"></script>';
			$result .= '<div id="ppplus"></div>';
			$result .= '<script type="text/javascript">
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
					"rememberedCards": "'.$rememberedCards.'",
      				"buttonLocation": "outside",
					"enableContinue":"continueButton",
					"disableContinue":"continueButton",
      				"preselection": "paypal",
     				"language": "pt_BR",
      				"country": "BR",
     				"disallowRememberedCards":false,      				
      				"iframeHeight": "470",
      				"useraction": "continue",
   				});
				</script>';
				//
				$result .= '<input type="hidden" style="dysplay:none;" id="system_url" value="'.$systemUrl.'"></input>';
				$result .= '<input type="hidden" style="dysplay:none;" id="user_id" value="'.$userID.'"></input>';
				$result .= '<input type="hidden" style="dysplay:none;" id="invoice_id" value="'.$invoiceID.'"></input>';
				$result .= '<input type="hidden" style="dysplay:none;" id="approval_url" value="'.$approval_url.'"></input>';
				$result .= '<input type="hidden" style="dysplay:none;" id="debug" value="'.$debug.'"></input>';
				
				
			$result .= $payButton;			
			$result .= '<div id="lightbox"><span id="lightboxspan"></span> </div>';
	}
	
	//
	if ( !$error and !$stop ) {
		return $result;
		
	}
	elseif ( !$error and $stop ) {
		return $result_1;
		
	}
	elseif ( $error and !$emailonError) {
		return $error;
		
	}
}

// Refund - Since 1.2.0
include __DIR__.'/gofaspaypalplus/refund.php';