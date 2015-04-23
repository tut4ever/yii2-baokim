<?php

namespace quangthinh\yii2\baokim;

use yii\base\Action;

use quangthinh\yii2\baokim;
use yii\exception\BadRequestException;

/**
 * this is action baokim
 */
class ConfirmAction extends Action
{
	/**
	 * bao kim options
	 */
	public $baokim;
	public $view;

	/**
	 * on confirm
	 */
	public function run($order_id) {
		// return $this->controller->render($this->view);
	}
}
