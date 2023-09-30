<?php

namespace Netesi365\Nexi\Others;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class AmazonPay extends Nexirequest
{
	public function action(string $codTrans, string $importo, string $divisa, $params = array(), $otherparams = array()){
		try {
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . 'importo=' . $importo . "divisa=" . $divisa . "timeStamp=" . $timeStamp . $this->secret);
			$client = new Client(['base_uri' => $this->url, RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()]);
			$params = [
					'apiKey' => $this->apiKey,
					'codiceTransazione' => $codTrans,
					'importo' => $importo,
					'divisa' => $divisa,
					'amazonpay' => $params,
					'parametriAggiuntivi' => $otherparams,
					'timeStamp' => (string) $timeStamp,
					'mac' => $mac
			];
			$guzrequest = $client->request('POST', '/ecomm/api/paga/amazonpay', [
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