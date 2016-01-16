<?

class DPDIntegration
{
  public $arMSG = array();

  private $IS_ACTIVE = 1;
  private $IS_TEST;
  private $SOAP_CLIENT;
  private $MY_NUMBER = 'YOUR_NUMBER';
  private $MY_KEY = 'YOUR_KEY';
  private $arDPD_HOST = array(0 => 'ws.dpd.ru/services/', 1 => 'wstest.dpd.ru/services/',);

  private $arSERVICE = array(
    'getServiceCostByParcels' => 'calculator2',
    'getServiceCost' => 'calculator2',
    'getTerminalsSelfDelivery' => 'geography',
    'getCitiesCashPay' => 'geography',
    'createOrder' => 'order2',
    'cancelOrder' => 'order2',
    'getOrderStatus' => 'order2',
    'createLabelFile' => 'label-print',
    'getStatesByDPDOrder' => 'tracing',);

  function __construct($is_test_env = FALSE) {
    $this->IS_TEST = $is_test_env ? 1 : 0;
  }

  public function createOrder($arData) {
    $obj = $this->_getDpdData('createOrder', $arData, 'orders');
    $res = $this->_parceObj2Arr($obj->return, 0);
    return $res;
  }

  public function cancelOrder($arData) {
    $obj = $this->_getDpdData('cancelOrder', $arData, 'orders');
    $res = $this->_parceObj2Arr($obj->return, 0);
    return $res;
  }

  public function getCityList() {
    $obj = $this->_getDpdData('getCitiesCashPay');
    $res = $this->_parceObj2Arr($obj->return);
    return $res;
  }

  public function getTerminalsSelfDelivery($arData) {
    $obj = $this->_getDpdData('getTerminalsSelfDelivery');
    $res = $this->_parceObj2Arr($obj->return, 0);
    return $res;
  }

  public function getServiceCostByParcels($arData) {
    $obj = $this->_getDpdData('getServiceCostByParcels');
    return $obj;
  }

  public function getServiceCost($arData) {
    $obj = $this->_getDpdData('getServiceCost', $arData, 'request');
    $res = $this->_parceObj2Arr($obj->return);

    return $res;
  }

  public function createLabelFile($arData) {
    $obj = $this->_getDpdData('createLabelFile', $arData, 'getLabelFile');
    return $obj;
  }

  public function getOrderStatus($arData) {
    $obj = $this->_getDpdData('getOrderStatus', $arData, 'orderStatus');
    return $obj;
  }

  public function getStatesByDPDOrder($arData) {
    $obj = $this->_getDpdData('getStatesByDPDOrder', $arData, 'tracing');
    $res = $this->_parceObj2Arr($obj->return);
    return $res;
  }

  private function _connect2Dpd($method_name) {
    if (!$this->IS_ACTIVE)
      return false;

    if (!$service = $this->arSERVICE[$method_name]) {
      $this->arMSG['str'] = 'В свойствах класса нет сервиса "' . $method_name . '"';
      if ($this->IS_TEST)
        print $this->arMSG['str'];
      return false;
    }
    $host = $this->arDPD_HOST[$this->IS_TEST] . $service . '?WSDL';

    try {
      // Soap-подключение к сервису
      $this->SOAP_CLIENT = new SoapClient('http://' . $host);
      if (!$this->SOAP_CLIENT)
        throw new Exception('Error');
    } catch (Exception $ex) {
      $this->arMSG['str'] = 'Не удалось подключиться к сервисам DPD ' . $service;
      if ($this->IS_TEST)
        print $this->arMSG['str'];
      return false;
    }

    return true;
  }

  private function _getDpdData($method_name, $arData = array(), $is_request = 0) {
    if (!$this->_connect2Dpd($method_name))
      return false;

    $arData['auth'] = array('clientNumber' => $this->MY_NUMBER, 'clientKey' => $this->MY_KEY,);

    if ($is_request)
      $arRequest[$is_request] = $arData; else $arRequest = $arData;

    try {
      $obj = $this->SOAP_CLIENT->$method_name($arRequest);
      if (!$obj)
        throw new Exception('Error');

    } catch (Exception $ex) {
      $this->arMSG['str'] = 'Не удалось вызвать метод ' . $method_name . ' / ' . $ex;
      if ($this->IS_TEST)
        print $this->arMSG['str'];
    }

    return $obj ? $obj : false;
  }

  private function _parceObj2Arr($obj, $isUTF = 1, $arr = array()) {
    $isUTF = $isUTF ? 1 : 0;

    if (is_object($obj) || is_array($obj)) {
      $arr = array();
      for (reset($obj); list($k, $v) = each($obj);) {
        if ($k === "GLOBALS")
          continue;
        $arr[$k] = $this->_parceObj2Arr($v, $isUTF, $arr);
      }
      return $arr;
    } elseif (gettype($obj) == 'boolean') {
      return $obj ? 'true' : 'false';
    } else {
      if ($isUTF && gettype($obj) == 'string')
        $obj = iconv('utf-8', 'windows-1251', $obj);
      return $obj;
    }
  }
}
