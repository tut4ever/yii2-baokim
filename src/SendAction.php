<?php

namespace quangthinh\yii2\baokim;

use yii\base\Action;

use quangthinh\yii2\baokim;
use yii\exception\BadRequestException;

/**
 * this is action baokim
 */
class SendAction extends Action
{
	/**
	 * bao kim options
	 */
	public $baokim;
	public $view;

	public function run() {
		// return $this->controller->render($this->view);
	}
}
