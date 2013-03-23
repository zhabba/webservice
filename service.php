<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhabba
 * Date: 21.03.13
 * Time: 23:59
 * To change this template use File | Settings | File Templates.
 */

/**
 * Organize Zend Classes Autoload
 * If new classes added run classmap_generator.php to
 * regenerate autoload_classmap.php
 */

require_once __DIR__ . '/Zend/Loader/ClassMapAutoloader.php';
$loader = new Zend\Loader\ClassMapAutoloader();
$loader->registerAutoloadMap(__DIR__ . '/autoload_classmap.php');
$loader->register();


/**
 * Serves incoming requests
 */
if(isset($_GET['wsdl'])) {
    $autodiscover = new Zend\Soap\AutoDiscover();
    $autodiscover->setUri('http://' .$_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'])
                 ->setClass('Zhabba\Calculator')
                 ->setServiceName('Calculator');
    $wsdl = $autodiscover->generate();
    echo $wsdl->toXML();
    file_exists('calc.wsdl') ? null : $wsdl->dump(__DIR__ . '/calc.wsdl');
} else {
    // Register our SoapFault Exceptions we throw in our service class
    $exceptions = array('Zend\Math\BigInteger\Exception\DivisionByZeroException', 'Zend\Soap\Exception\InvalidArgumentException');

    $soap = new Zend\Soap\Server(__DIR__ . '/calc.wsdl');
    $soap->setClass('Zhabba\Calculator')
         ->registerFaultException($exceptions)
         ->handle();
}