<?php

namespace Netesi365\Nexi\Server;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class 3DSecure extends Nexirequest
{
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $pan = '', string $scadenza = '', string $cvv = '', string $returnUrl = '') : string {
		try {
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . 'importo=' . $importo . "divisa=" . $divisa . "timeStamp=" . $timeStamp . $this->secret);
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
			$myresponse = $guzresponse;
		}
		catch (BadResponseException $e) {
			$myresponse = $e->getResponse()->getBody()->getContents();
		}
		return $myresponse;
	}
}