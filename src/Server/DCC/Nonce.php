<?php

namespace Netesi365\Nexi\Server\DCC;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Nonce extends Nexirequest
{
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $pan = '', string $scadenza = '', string $cvv = '', string $returnUrl = '') : string {
		try {
			if (empty($codTrans) || empty($importo) || empty($divisa) || empty($pan) || empty($scadenza) || empty($cvv) || empty($returnUrl)) {
				throw new \Exception('Missing a parameter',002);
			}
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . "divisa=" . $divisa . "importo=" . $importo . "timeStamp=" . $timeStamp . $this->secret);
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
			$guzrequest = $client->request('POST', '/ecomm/api/hostedPayments/creaNonce', [
				'connect_timeout' => 1.5,
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body'=>json_encode($params,JSON_UNESCAPED_UNICODE)
			]);
			$guzresponse = $guzrequest->getBody()->getContents();
			$myresponse = json_decode($guzresponse, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$MACresponse = sha1('esito=' . $myresponse['esito'] . 'idOperazione=' . $myresponse['idOperazione'] . 'xpayNonce=' . $myresponse['xpayNonce'] . 'timeStamp=' . $myresponse['timeStamp'] . $this->secret);
				if ($myresponse['mac'] == $MACresponse) {
					if ($myresponse['esito'] == 'OK') {
						if ($myresponse['xpayNonce'] != null) {
							$result = [
								'success' => 1,
								'error' => 0,
								'code' => '0',
								'idOperazione' => (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
								'timeStamp' => (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
								'mac' => (!empty($myresponse['mac']) ? $myresponse['mac'] : ''),
								'msg' => $myresponse['esito'],
								'data' => NULL
							];
						}
						else {
							$result = [
								'success' => 1,
								'error' => 0,
								'code' => '0',
								'idOperazione' => (!empty($myresponse['idOperazione']) ? $myresponse['idOperazione'] : ''),
								'timeStamp' => (!empty($myresponse['timeStamp']) ? $myresponse['timeStamp'] : ''),
								'mac' => (!empty($myresponse['mac']) ? $myresponse['mac'] : ''),
								'msg' => $myresponse['esito'],
								'data' => (!empty($myresponse['html']) ? $myresponse['html'] : '')
							];
						}
					}
					else {
						throw new \Exception($myresponse['errore']['messaggio'], $myresponse['errore']['codice']);
					}
				}
				else {
					throw new \Exception('Failed Mac verification code', 002);
				}
			}
			else {
				throw new \Exception('Wrong response', 001);
			}
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
		$response = json_encode($result);
		return $response;
	}
}