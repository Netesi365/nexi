<?php

namespace Netesi365\Nexi\Client;

use Netesi365\Nexi\Nexirequest;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\BadResponseException;
use Composer\CaBundle\CaBundle;

class Pay extends Nexirequest
{
	protected $serviceType = array('paga_1click', 'paga_oc3d');
	
	public function action(string $codTrans = '', string $importo = '', string $divisa = '', string $returnUrl = '', string $backUrl = '', string $contractNumber = '', string $group = '', string $serviceType = '', string $requestType = '') : string {
		try {
			if (empty($codTrans) || empty($importo) || empty($divisa) || empty($returnUrl) || empty($backUrl) || empty($contractNumber) || empty($serviceType)) {
				throw new \Exception('Missing a parameter',002);
			}
			if (!in_array($serviceType, $this->serviceType)) {
				throw new \Exception('Wrong Service Type',003);
			}
			$timeStamp = (time()) * 1000;
			if ($serviceType == 'paga_oc3d') {
				if (empty($requestType)) {
					throw new \Exception('Missing a parameter',002);
				}
				$mac = sha1('codTrans=' . $codTrans . 'divisa=' . $divisa . 'importo=' . $importo . $this->secret);
				$params = array(
					'alias' => $this->apiKey,
					'importo' => $importo,
					'divisa' => $divisa,
					'codTrans' => $codTrans,
					'url' => $returnUrl,
					'url_back' => $backUrl,
					'mac' => $mac,
					'num_contratto' => $contractNumber,
					'tipo_servizio' => $serviceType,
					'tipo_richiesta' => $requestType,
					'request_url' => $this->url .'/ecomm/ecomm/DispatcherServlet'
				);
			}
			else if ($serviceType == 'paga_1click') {
				if (empty($group)) {
					throw new \Exception('Missing a parameter', 002);
				}
				$mac = sha1('codTrans=' . $codTrans . 'divisa=' . $divisa . 'importo=' . $importo . 'gruppo=' . $group . 'num_contratto=' . $contractNumber . $this->secret);
				$params = array(
					'alias' => $this->apiKey,
					'importo' => $importo,
					'divisa' => $divisa,
					'codTrans' => $codTrans,
					'url' => $returnUrl,
					'url_back' => $backUrl,
					'mac' => $mac,
					'num_contratto' => $contractNumber,
					'tipo_servizio' => $serviceType,
				);
			}
			$result = [
				'success' => 1,
				'error' => 0,
				'code' => '0',
				'msg' => 'OK',
				'data' => $params
			];

		}
		catch (\Exception $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => $e->getCode(),
					'msg' => $e->getMessage(),
					'data' => NULL
			];
		}
		catch (BadResponseException $e) {
			$result = [
					'success' => 0,
					'error' => 1,
					'code' => 0,
					'msg' => $e->getResponse()->getBody()->getContents(),
					'data' => NULL
			];
		}
		$response = json_encode($result);
		return $response;
	}
}