<?php

namespace Netesi365\Nexi;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Composer\CaBundle\CaBundle;

class NexiRequest
{
	protected $env;
	public $url;
	public $alias;
	public $keysecret;
	public $sandbox = 'https://int-ecommerce.nexi.it';
    public $production = 'https://ecommerce.nexi.it';
	
	public function __construct(array $args)
    {
		$this->env = (($args['env'] == 'sandbox') ? 'sandbox' : 'production');
        $this->url = (($args['env'] == 'sandbox') ? $this->sandbox : $this->production);
		$this->alias = (!empty($args['alias']) ? $args['alias'] : '');
		$this->keysecret = (!empty($args['keysecret']) ? $args['keysecret'] : '');
    }
}