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
use WHMCS\Database\Capsule;

// Telemetria: checagem de versão (sempre identificado) e confirmação de pagamento (gated por consentimento)
if(!function_exists('gppp_module_version')){
	function gppp_module_version(){
		return '2.1.0';
	}
}
if(!function_exists('gppp_whmcs_url')){
	function gppp_whmcs_url(){
		foreach( Capsule::table('tblconfiguration')->where('setting','=','SystemURL')->get(['value']) as $data1 ){
			$SystemURL = $data1->value;
		}
		return $SystemURL;
	}
}
if(!function_exists('gppp_sysinfo')){
	function gppp_sysinfo(){
		foreach( Capsule::table('tblconfiguration')->where('setting','=','Version')->get(['value']) as $data1 ){
			$Version = $data1->value;
		}
		foreach( Capsule::table('tblconfiguration')->where('setting','=','CronPHPVersion')->get(['value']) as $data1 ){
			$PHPVersion = $data1->value;
		}
		return '&whmcs_version='.$Version.'&php_version='.$PHPVersion;
	}
}
if(!function_exists('gppp_current_admin')){
	function gppp_current_admin(){
		$currentUser = new \WHMCS\Authentication\CurrentUser;
		$admin = json_decode(json_encode($currentUser->admin()),true);
		return $admin;
	}
}
if(!function_exists('gppp_setup_admin')){
	// Resolve o administrador configurado no campo 'admin' (ID numerico) para uso no charge, quando nao ha sessao ativa (webhook)
	function gppp_setup_admin($admin_id){
		$admin_id = $admin_id ?: 1;
		foreach( Capsule::table('tbladmins')->where('id','=',$admin_id)->get(['email','firstname','lastname']) as $row ){
			return ['email'=>$row->email,'firstname'=>$row->firstname,'lastname'=>$row->lastname];
		}
		return ['email'=>'','firstname'=>'','lastname'=>''];
	}
}
if(!function_exists('gppp_get_version')){
	function gppp_get_version(){
		$current_admin = gppp_current_admin();
		$query = '?software_id=8294&install_url='.gppp_whmcs_url().'&current_version='.gppp_module_version().'&installer_email='.$current_admin['email'].'&installer_firstname='.$current_admin['firstname'].'&installer_lastname='.$current_admin['lastname'].'&action=verify'.gppp_sysinfo();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_URL, 'https://gofas.net/br/updates/stats.php'.$query);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		// Throttle: no maximo uma vez por dia
		try {
			$exists = Capsule::table('tblconfiguration')->where('setting','gppp_last_verify')->exists();
			if($exists){
				Capsule::table('tblconfiguration')->where('setting','gppp_last_verify')->update(['value'=>date('Y-m-d H:i:s')]);
			}
			else {
				Capsule::table('tblconfiguration')->insert(['setting'=>'gppp_last_verify','value'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
			}
		}
		catch (\Exception $e){}
		return ['response'=>$response,'http_code'=>$http_status];
	}
}
if(!function_exists('gppp_verify_throttled')){
	function gppp_verify_throttled(){
		$last = null;
		foreach( Capsule::table('tblconfiguration')->where('setting','gppp_last_verify')->get(['value']) as $row ){
			$last = $row->value;
		}
		if(!$last || strtotime($last) < strtotime('-1 day')){
			return gppp_get_version();
		}
		return null;
	}
}
if(!function_exists('gppp_update_stats')){
	function gppp_update_stats($admin_id, $sandbox){
		if($sandbox){
			return;
		}
		if(empty(getGatewayVariables('gofaspaypalplus')['consent_stats'])){
			$anon_version = gppp_module_version();
			$anon_id = 'gppp-v'.$anon_version;
			$install_url = $anon_id;
			$installer_email = $anon_id.'@gofas.net';
			$installer_firstname = 'gppp';
			$installer_lastname = 'v'.$anon_version;
		}
		else{
			$setup_admin = gppp_setup_admin($admin_id);
			$install_url = gppp_whmcs_url();
			$installer_email = $setup_admin['email'];
			$installer_firstname = $setup_admin['firstname'];
			$installer_lastname = $setup_admin['lastname'];
		}
		$query = '?software_id=8294&install_url='.$install_url.'&current_version='.gppp_module_version().'&installer_email='.$installer_email.'&installer_firstname='.$installer_firstname.'&installer_lastname='.$installer_lastname.'&action=charge'.gppp_sysinfo();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_URL, 'https://gofas.net/br/updates/stats.php'.$query);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['query'=>$query,'response'=>$response,'http_code'=>$http_status];
	}
}

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
