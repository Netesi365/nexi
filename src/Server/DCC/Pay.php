<?php

namespace Netesi365\Nexi\Server\DCC;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Pay extends Nexirequest
{
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $xpayNonce = '', string $ticket = '', string $importoDCC = '', string $divisaDCC = '', string $taxchange = 'SI') : string {
		try {
			if (empty($codTrans) || empty($importo) || empty($divisa) || empty($xpayNonce) || empty($ticket) || empty($importoDCC) || empty($divisaDCC)) {
				throw new \Exception('Missing a parameter',002);
			}
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . "ticket=" . $ticket . "tassoDiCambioAccettato=" . $taxchange . "timeStamp=" . $timeStamp . $this->secret);
			$client = new Client(['base_uri' => $this->url, RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()]);
			$params = array(
				'apiKey' => $this->apiKey,
				'ticket' => $ticket,
				'xpayNonce' => $xpayNonce,
				'importo' => $importo,
				'divisa' => $divisa,
				'codiceTransazione' => $codTrans,
				'importoDCC' => $importoDCC,
				'divisaDCC' => $divisaDCC,
				'tassoDiCambioAccettato' => $taxchange,
				'timeStamp' => (string) $timeStamp,
				'mac' => $mac,
			);
			$guzrequest = $client->request('POST', '/ecomm/api/etc/pagaDCC', [
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
				$MACresponse = sha1('esito=' . $myresponse['esito'] . 'idOperazione=' . $myresponse['idOperazione'] . 'timeStamp=' . $myresponse['timeStamp'] . $this->secret);
				if ($myresponse['mac'] == $MACresponse) {
					if ($myresponse['esito'] == 'OK') {
						$result = [
							'success' => 1,
							'error' => 0,
							'code' => '0',
							'idOperazione' => $myresponse['idOperazione'],
							'timeStamp' => $myresponse['timeStamp'],
							'mac' => $myresponse['mac'],
							'msg' => $myresponse['esito']
						];
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