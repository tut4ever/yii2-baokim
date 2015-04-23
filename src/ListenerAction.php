<?php

namespace quangthinh\yii2\baokim;

use yii\base\Action;

use quangthinh\yii2\baokim;
use quangthinh\yii2\baokim\Baokim;
use yii\exception\BadRequestException;

/**
 * this is action listener
 */
class ListenerAction extends Action
{
	/**
	 * bao kim options
	 */ 
	public $baokim;
	public $view;

	public $transaction_status_key;

	public function run() {
		$success_transaction_status = [Baokim::BAOKIM_TRANSACTION_STATUS_COMPLETED, Baokim::BAOKIM_TRANSACTION_STATUS_TEMP_HOLDING];
		$baokim_url = $this->baokim->sandbox ? Baokim::BAOKIM_SANDBOX_URL : Baokim::BAOKIM_URL;

		if (empty($_POST)) {
			throw new BadRequestException('Không nhận được dữ liệu trên máy chủ');
		}

		$request = '';
		foreach ($_POST as $k => $v) {
			$v = urlencode(stripslashes($v));
			$request .= '&' . $k . '=' . $v;
		}

		$result = $this->baokim->listen($request);

		if (is_callable($this->listenerCallbackn)) {
			$this->listenerCallback($result, in_array($_POST[$this->transaction_status_key], $success_transaction_status));
		} else {
			throw new InvalidRequestException('invalid request');
		}
	}
}