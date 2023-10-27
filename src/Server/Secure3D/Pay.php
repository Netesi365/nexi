<?php

namespace Netesi365\Nexi\Server\Secure3D;

use Netesi365\Nexi\NexiRequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Pay extends NexiRequest
{
	public function check(string $params = '') : string {
		try {
			if (!is_array(json_decode($params, true))) {
				throw new \Exception('Params is not a JSON',001);
			}
			$request = json_decode($params, true);		
			if (empty($request['esito']) || empty($request['idOperazione']) || empty($request['xpayNonce']) || empty($request['timeStamp']) || empty($request['mac'])) {
				throw new \Exception('Missing a parameter',002);
			}
			if($request['esito'] != "OK"){
				throw new \Exception('3D-Secure:' . $request['esito'] .'-'. (!empty($request['messaggio']) ? $request['messaggio'] : ''),003);			
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
					'idOperazione' => (!empty($request['idOperazione']) ? $request['idOperazione'] : ''),
					'timeStamp' => (!empty($request['timeStamp']) ? $request['timeStamp'] : ''),
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
				$macCalculated = sha1('esito=' . $myresponse['esito'] . 'idOperazione=' . $myresponse['idOperazione'] . 'timeStamp=' . $myresponse['timeStamp'] . $this->secret);
				if ($macCalculated != $myresponse['mac']) {
					throw new \Exception('Errore MAC:' . $macCalculated .' differs from'. $myresponse['mac'],004);
				}
				$result = [
					'success' => 1,
					'error' => 0,
					'code' => '0',
					'idOperazione' => (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
					'codiceAutorizzazione' => (!empty($myresponse['codiceAutorizzazione']) ? $myresponse['codiceAutorizzazione'] : ''),
					'timeStamp' => (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
					'msg' => 'OK'
				];
			}
			else {
				throw new \Exception($myresponse['errore']['messaggio'], $myresponse['errore']['codice']);
			}
		}
		catch (ConnectException $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => 408,
					'idOperazione' => '',
					'timeStamp' => (!empty($timeStamp) ? $timeStamp : ''),
					'msg' => "Connection Timeout",
					'data' => NULL
			];
		}
		catch (BadResponseException $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => 0,
					'idOperazione' => '',
					'timeStamp' => (!empty($timeStamp) ? $timeStamp : ''),
					'msg' => $e->getResponse()->getBody()->getContents()
			];
		}
		catch (\Exception $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => $e->getCode(),
					'idOperazione' => (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
					'timeStamp' => (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
					'msg' => $e->getMessage()
			];
		}
		$response = json_encode($result);
		return $response;
	}
}