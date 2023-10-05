<?php

namespace Netesi365\Nexi\Server\Secure3D;

use Netesi365\Nexi\NexiRequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Auth extends NexiRequest
{
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $pan = '', string $scadenza = '', string $cvv = '', string $returnUrl = '') : string {
		try {
			if (empty($codTrans) || empty($importo) || empty($divisa) || empty($pan) || empty($scadenza) || empty($cvv) || empty($returnUrl)) {
				throw new \Exception('Missing a parameter',002);
			}
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . "divisa=" . $divisa . 'importo=' . $importo . "timeStamp=" . $timeStamp . $this->secret);
			$client = new Client(['base_uri' => $this->url, RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()]);
			$params = array(
				'apiKey' => $this->apiKey,
				'pan' => $pan,
				'scadenza' => $scadenza,
				'cvv' => $cvv,
				'importo' => $importo,
				'divisa' => $divisa,
				'codiceTransazione' => $codTrans,
				'urlRisposta' => $returnUrl,
				'timeStamp' => (string) $timeStamp,
				'mac' => $mac,
			);
			$guzrequest = $client->request('POST', '/ecomm/api/paga/autenticazione3DS', [
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
				$result = [
					'success' => 1,
					'error' => 0,
					'code' => '0',
					'idOperazione' => (string) (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
					'timeStamp' => (string) (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
					'msg' => (string) $myresponse['esito'],
					'data' => (string) (!empty($myresponse['html']) ? $myresponse['html'] : '')
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
					'idOperazione' => (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
					'timeStamp' => (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
					'msg' => $e->getMessage(),
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
					'msg' => $e->getResponse()->getBody()->getContents(),
					'data' => NULL
			];
		}
		$response = json_encode($result);
		return $response;
	}
}