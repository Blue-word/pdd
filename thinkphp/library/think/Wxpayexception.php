<?php
namespace think;
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class Wxpayexception extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
