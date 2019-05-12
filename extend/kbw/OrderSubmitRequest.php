<?php
namespace kbw;

class OrderSubmitRequest
{
	/** 
	 * 物流公司类型 可选值。4:圆通
	 **/
	public $logiType;

	public $orders;
	
	private $apiParas = array();
	
	public function setLogiType($logiType)
	{
		$this->logiType = $logiType;
		$this->apiParas["logiType"] = $logiType;
	}

	public function getLogiType()
	{
		return $this->logiType;
	}

   
    public function setOrders($orders)
	{
		require 'Utils.php';
		$this->orders = $orders;
		$this->apiParas["orders"] =encode_json ($orders, JSON_UNESCAPED_UNICODE) ;
	}

	public function getOrders()
	{
		return $this->orders;
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}
}
class Order{
	public $Platform;
	public $SendContact;
	public $SendOfficePhone;
	public $SendCellPhone;
	public $UserId;
	public $CellPhone;
	public $SendState;
	public $SendCity;
	public $SendDistrict;
	public $SendAddress;
	public $ProductTitle;
	public $Weight;
	public $Raddress;
}

class rOrder{
	public $OrderNo;
	public $Contact;
	public $OfficePhone;
	public $CellPhone;
	public $State;
	public $City;
	public $District;
	public $Address;
}

class delOrder{
	public $trackingno;
}

class Getprices{
	public $Platform;
	public $Adminikey;
}

class GetAdminiAmount{
	public $Adminikey;
}


