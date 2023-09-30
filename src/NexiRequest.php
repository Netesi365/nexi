<?php

namespace Netesi365\Nexi;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Composer\CaBundle\CaBundle;

class NexiRequest
{
	protected $env;
	protected $url;
	protected $apiKey;
	protected $secret;
	protected $sandbox = 'https://int-ecommerce.nexi.it';
    protected $production = 'https://ecommerce.nexi.it';
	
	public function __construct(array $args)
    {
		$this->env = (($args['env'] == 'sandbox') ? 'sandbox' : 'production');
        $this->url = (($args['env'] == 'sandbox') ? $this->sandbox : $this->production);
		$this->apiKey = (!empty($args['alias']) ? $args['alias'] : '');
		$this->secret = (!empty($args['keysecret']) ? $args['keysecret'] : '');
    }
}