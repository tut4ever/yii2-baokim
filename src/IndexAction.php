<?php

namespace quangthinh\yii2\baokim;

use yii\base\Action;

use quangthinh\yii2\baokim;
use yii\exception\BadRequestException;

/**
 * this is action baokim
 */
class IndexAction extends Action
{
	/**
	 * bao kim options
	 */
	public $baokim;

	public $error_api = 'call api {error} error';
	public $view;

	public function run() {
		$response = $this->baokim->getSellerInfo($this->baokim->business);

		if (isset($response['error']{3})) {
			throw new BadRequestException(strtr($this->error_api, ['{error}' => $response['error']]));
		}

		$seller = json_decode($response, true);
		if (isset($seller['error'])) {
			throw new BadRequestException(strtr($this->error_api, ['{error}' => $seller['error']]));
		}

		return $this->controller->render($this->view, [
			'seller' => $seller
		]);
	}
}
