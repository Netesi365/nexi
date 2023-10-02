<?php

namespace Netesi365\Nexi\Server;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Secure3DPay extends Nexirequest
{
	public function check(string $params = '') : string {
		try {
			if (!is_array(json_decode($params, true))) {
				throw new \Exception('Params is not a JSON',001);
			}
			$request = json_decode($params, true);		
			if (empty($request['esito']) || empty($request['messaggio']) || empty($request['idOperazione']) || empty($request['xpayNonce']) || empty($request['timeStamp']) || empty($request['mac'])) {
				throw new \Exception('Missing a parameter',002);
			}
			if($request['esito'] != "OK"){
				throw new \Exception('3D-Secure:' . $request['esito'] .'-'. $request['messaggio'],003);			
			}
			$macCalculated = sha1('esito=' . $request['esito'] .'idOperazione=' . $request['idOperazione'] .'xpayNonce=' . $request['xpayNonce'] .'timeStamp=' . $request['timeStamp'] . $this->secret);
			if ($macCalculated != $request['mac']) {
				throw new \Exception('Errore MAC:' . $macCalculated .' differs from'. $request['mac'],004);
			}
			$result = [
					'success' => 1,
					'error' => 0,
					'code' => '0',
					'idOperazione' => $request['idOperazione'],
					'timeStamp' => $request['timeStamp'],
					'msg' => 'OK'
			];
		}
		catch (\Exception $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => $e->getCode(),
					'idOperazione' => $request['idOperazione'],
					'timeStamp' => $request['timeStamp'],
					'msg' => $e->getMessage()
			];
		}
		$response = json_encode($result);
		return $response;
	}
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $xpayNonce = '') : string {
		try {
			if (empty($codTrans) || empty($importo) || empty($divisa) || empty($xpayNonce)) {
				throw new \Exception('Missing a parameter',002);
			}
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans  . 'importo=' . $importo .  'divisa=' . $divisa . 'xpayNonce=' . $xpayNonce . 'timeStamp=' . $timeStamp . $this->secret);
			$client = new Client(['base_uri' => $this->url, RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()]);
			$params = array(
				'apiKey' => $this->apiKey,
				'codiceTransazione' => $codTrans,
				'importo' => $importo,
				'divisa' => $divisa,
				'xpayNonce' => $xpayNonce,
				'timeStamp' => (string) $timeStamp,
				'mac' => $mac
			);
			$guzrequest = $client->request('POST', '/ecomm/api/paga/paga3DS', [
				'connect_timeout' => 1.5,
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body'=>json_encode($params,JSON_UNESCAPED_UNICODE)
			]);
			$guzresponse = $guzrequest->getBody()->getContents();
			$myresponse = json_decode($guzresponse, true);
			if ($myresponse['esito'] == 'OK') {
				$macCalculated2 = sha1('esito=' . $myresponse['esito'] . 'idOperazione=' . $myresponse['idOperazione'] . 'timeStamp=' . $myresponse['timeStamp'] . $this->secret);
				if ($macCalculated2 != $myresponse['mac']) {
					throw new \Exception('Errore MAC:' . $macCalculated2 .' differs from'. $myresponse['mac'],004);
				}
				$result = [
					'success' => 1,
					'error' => 0,
					'code' => '0',
					'idOperazione' => $myresponse['idOperazione'],
					'codiceAutorizzazione' => $myresponse['codiceAutorizzazione'],
					'timeStamp' => $myresponse['timeStamp'],
					'msg' => 'OK'
				];
			}
			else {
				throw new \Exception($myresponse['errore']['messaggio'], $myresponse['errore']['codice']);
			}
		}
		catch (\Exception $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => $e->getCode(),
					'idOperazione' => $myresponse['idOperazione'],
					'timeStamp' => $myresponse['timeStamp'],
					'msg' => $e->getMessage()
			];
		}
		catch (BadResponseException $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => 0,
					'idOperazione' => '',
					'timeStamp' => $timeStamp,
					'msg' => $e->getResponse()->getBody()->getContents()
			];
		}
		$response = json_encode($result);
		return $response;
	}
}