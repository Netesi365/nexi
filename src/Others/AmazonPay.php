<?php

namespace Netesi365\Nexi\Others;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class AmazonPay
{
	protected $url;
	protected $apiKey;
	protected $secret;
	
	public function __construct(Nexirequest $client)
    {
		$this->url = $client->url;
		$this->apiKey = $client->alias;
		$this->secret = $client->keysecret;
    }
	public function action(string $codTrans, string $importo, string $divisa, string $params = ''){
		try {
			$timeStamp = (time()) * 1000;
			$mac = sha1('apiKey=' . $this->apiKey . 'codiceTransazione=' . $codTrans . 'importo=' . $importo . "divisa=" . $divisa . "timeStamp=" . $timeStamp . $this->secret);
			$client = new Client(['base_uri' => $this->url, RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()]);
			$guzrequest = $client->request('POST', '/ecomm/api/paga/amazonpay', [
				'connect_timeout' => 1.5,
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'form_params' => [
					'apiKey' => $this->apiKey,
					'codiceTransazione' => $codTrans,
					'importo' => $importo,
					'divisa' => $divisa,
					'amazonpay' => array(
						'amazonReferenceId' => '',
						'accessToken' => '',
						'softDecline' => '',
						'creaContratto' => ''
					),
					'parametriAggiuntivi' => array(
						'nome' => 'Mario',
						'cognome' => 'Rossi',
						'mail' => "cardHolder@mail.it",
						'descrizione' => "descrizione",
						'Note1' => "note",
					),
					'timeStamp' => (string) $timeStamp,
					'mac' => $mac
					],
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