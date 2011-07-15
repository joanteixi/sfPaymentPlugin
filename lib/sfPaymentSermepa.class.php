<?php

class sfPaymentSermepa extends sfPaymentGatewayInterface {

  protected $responses = array(
      101     => "Tarjeta caducada",
      102     => "Tarjeta en excepción transitoria o bajo sospecha de fraude",
      104     => "Operación no permitida para esa tarjeta o terminal",
      9104    => "Operación no permitida para esa tarjeta o terminal",
      116     => "Disponible insuficiente",
      118     => "Tarjeta no registrada",
      129     => "Código de seguridad (CVV2/CVC2) incorrecto",
      180     => "Tarjeta ajena al servicio",
      184     => "Error en la autenticación del titular",
      190     => "Denegación sin especificar Motivo",
      191     => "Fecha de caducidad errónea",
      202     => "Tarjeta en excepción transitoria o bajo sospecha de fraude con retirada de tarjeta",
      0       => "Transacción denegada"
  );

  public function __construct() {
    parent::__construct();
    $this->gatewayUrl = "https://sis.sermepa.es/sis/realizarPago";
    //fields
    $this->addFieldTranslation('Importe', 'Ds_Merchant_Amount');
    $this->addFieldTranslation('Moneda', 'Ds_Merchant_Currency');
    $this->addFieldTranslation('NbPedido', 'Ds_Merchant_Order');
    $this->addFieldTranslation('Descripcion', 'Ds_Merchant_ProductDescription');
    $this->addFieldTranslation('Titular', 'Ds_Merchant_Titular');
    $this->addFieldTranslation('CodigoComercio', 'Ds_Merchant_MerchantCode');
    $this->addFieldTranslation('UrlPost', 'Ds_Merchant_MerchantURL');
    $this->addFieldTranslation('UrlOk', 'Ds_Merchant_UrlOK');
    $this->addFieldTranslation('UrlKo', 'Ds_Merchant_UrlKO');
    $this->addFieldTranslation('NombreComercio', 'Ds_Merchant_MerchantName');
    $this->addFieldTranslation('Idioma', 'Ds_Merchant_ConsumerLanguage');
    $this->addFieldTranslation('Firma', 'Ds_Merchant_MerchantSignature');
    $this->addFieldTranslation('Terminal', 'Ds_Merchant_Terminal');
    $this->addFieldTranslation('TipoTransaccion', 'Ds_Merchant_TransactionType');
    $this->addFieldTranslation('Response', 'Ds_Response');

    //default values
    $this->setMoneda('978'); //euros
    $this->setUrlPost(url_for('donacio/resultatPagament', array('absolute' => true)));
    $this->setUrlOk(url_for('donacio/graciesTarja', array('abslute' => true)));
    $this->setUrlKo(url_for('donacio/pagamentDenegat', array('abslute' => true)));
    $this->setCodigoComercio(sfConfig::get('app_sermepa_fuc'));
    $this->setNombreComercio(sfConfig::get('app_sermepa_nombre_comercio'));
    $this->setTerminal(sfConfig::get('app_sermepa_terminal'));
    $this->setTipoTransaccion(0); //transacción normal de autorización
    $this->setIdioma('003'); //català
    $this->setDescripcion('Donació al Servei Solidari');
  }

  /**
   * Configuration for test mode
   */
  public function enableTestMode() {
    $this->testMode = true;
    $this->setCodigoComercio('xxxxTestCode');
    $this->setTerminal('TERMINAL_NUMBER);
    $this->gatewayUrl = ' https://sis-t.sermepa.es:25443/sis/realizarPago';
  }

  public function prepareSubmit() {
    $this->setNbPedido(substr(time() . $this->getTitular(), 0, 11));
    $this->setFirma($this->calcularHash());
  }

  public function submitPayment() {
    //convertir campos en inputs...
  }

  public function collectPostVariables($post) {

    $this->setImporte($post['Ds_Amount']);
    $this->setNbPedido($post['Ds_Order']);
    $this->setCodigoComercio($post['Ds_MerchantCode']);
    $this->setMoneda($post['Ds_Currency']);
    $this->setResponse($post['Ds_Response']);
    $this->setFirma($post['Ds_Signature']);
    return $this->validateIpn();
  }

  /**
   * Valida la resposta
   */
  protected function validateIpn() {
    $sha = $this->getImporte() .
            $this->getNbPedido() .
            $this->getCodigoComercio() .
            $this->getMoneda() .
            $this->getResponse();

    if ($this->testMode) {
      $sha = sha1($sha . sfConfig::get('app_sermepa_psw_test'));
    } else {
      $sha = sha1($sha . sfConfig::get('app_sermepa_psw'));
    }

    if ($sha == strtolower($this->getFirma())) {
      return true;
    } else {
      return false;
    }
  }

  protected function calcularHash() {
    $sha =
            $this->getImporte() .
            $this->getNbPedido() .
            $this->getCodigoComercio() .
            $this->getMoneda() .
            $this->getTipoTransaccion() .
            $this->getUrlPost();

    if ($this->testMode) {
      $sha = sha1($sha . sfConfig::get('app_sermepa_psw_test'));
    } else {
      $sha = sha1($sha . sfConfig::get('app_sermepa_psw'));
    }

    return $sha;
  }

}

