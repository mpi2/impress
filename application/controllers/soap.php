<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 *
 * Copyright 2014 Medical Research Council Harwell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */


/**
* To load the wsdl you go to http://localhost/impress/soap/server?wsdl
* The SOAP methods are defined in the ImpressSoap class and the wsdl
* is dynamically generated with Zend_Soap_AutoDiscover using the Zend
* Framework SOAP library which is very clean for a soap library
*/

class Soap extends CI_Controller
{
    private $_controller = null;
    private $_cachingEnabled = true;

    public function __construct()
    {
        parent::__construct();
        $this->_controller = $this->router->class;
        ini_set('soap.wsdl_cache_enabled', 'Off');
        set_include_path(get_include_path() . PATH_SEPARATOR . APPPATH . 'third_party/');
        require_once 'Zend/Loader.php';
    }

    public function server()
    {
        if(isset($_GET['wsdl']) || isset($_GET['WSDL']))
        {
            ob_start(null, 9999999);
            Zend_Loader::loadClass('Zend_Soap_AutoDiscover');
            Zend_Loader::loadClass('Zend_Soap_Wsdl_Strategy_DefaultComplexType');
            Zend_Loader::loadClass('Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence');
            $ad = new Zend_Soap_AutoDiscover('Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence');
            //if(stristr($this->config->item('base_url'), 'mousephenotype'))
            //    $ad->setUri('http://www.mousephenotype.org/impress/soap/server');
            $this->load->helper('httpsify_url');
            $ad->setUri(dehttpsify_url(current_url()));
            $ad->setOperationBodyStyle(array('use'=>'literal'));
            $ad->setBindingStyle(array('style'=>'document'));
            $ad->setClass('ImpressSoap');
            $ad->handle();
            ob_end_flush();
        }
        else
        {
            Zend_Loader::loadClass('Zend_Soap_Server');
            $server = new Zend_Soap_Server(base_url() . $this->_controller . '/server?wsdl', array('encoding'=>'UTF-8', 'soap_version'=>SOAP_1_2, 'cache_wsdl'=>$this->_cachingEnabled)); //'http://localhost/impress/zendsoap/server?wsdl'
            $proxy = new Service_Soap('ImpressSoap', null, array('wrappedParts' => TRUE));
            $server->setObject($proxy);
            $server->registerFaultException('Exception');
            $server->setReturnResponse(FALSE);
            $server->handle();
        }
    }

	// public function client()
    // {
        // exit;
        // Zend_Loader::loadClass('Zend_Soap_Client');
        // $client = new Zend_Soap_Client(base_url() . $this->_controller . '/server?wsdl');
        // try {
            // $client->getProcedureWeek(array('procedureKey'=>'ESLIM_001_001'));
            // header('Content-Type: text/xml');
            // //print $client->getLastRequest();
            // print $client->getLastResponse();
        // }
        // catch(SoapFault $e){
            // die('Error: [' . $e->faultcode . '] ' . $e->faultstring);
        // }
        // catch(Exception $e){
            // die($e->getMessage());
        // }
    // }

    // public function ccclient()
    // {
        // Zend_Loader::loadClass('Zend_Soap_Client');
        // $client = new Zend_Soap_Client('http://empress.har.mrc.ac.uk/empress.wsdl');
        // try {
            // print
            // var_dump(
                // $client->getAge('ESLIM_001_001')
            // )
            // ;
            // header('Content-Type: text/xml');
            //print $client->getLastRequest();
        // }
        // catch(SoapFault $e){
            // die('Error: [' . $e->faultcode . '] ' . $e->faultstring);
        // }
        // catch(Exception $e){
            // die($e->getMessage());
        // }
    // }

    // public function getWeek()
    // {
        // $c = new ImpressSoap();
        // print $c->getProcedureWeek('ESLIM_001_001');
    // }

}

/**
 * Service_Soap proxy creates an intermediate (proxy) class between the SOAP server
 * and the actual handling class, allowing pre-processing of function arguments
 * and return values.
 * This class fixes the Zend_Soap_AutoDiscover classes' faulty implementation of
 * SOAP in the 'document/literal' binding.
 * @author Fabien Crespel
 * @see http://framework.zend.com/issues/browse/ZF-6351
 */
class Service_Soap
{
    protected $_className = null;
    protected $_classInstance = null;
    protected $_wrappedParts = false;

    /**
     * @param string $className name of the handling class to proxy.
     * @param array $classArgs arguments used to instantiate the handling class.
     * @param array $options proxy options.
     */
    public function __construct($className, $classArgs = array(), $options = array())
    {
        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();
        if($constructor === null){
            $this->_classInstance = $class->newInstance();
        }else{
            $this->_classInstance = $class->newInstanceArgs($classArgs);
        }
        $this->_className = $className;
        $this->_setOptions($options);
    }

    protected function _setOptions($options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'wrappedParts':
                    $this->_wrappedParts = (bool)$value;
                    break;
                default:
                    break;
            }
        }
    }

    protected function _getOptions()
    {
        $options['wrappedParts'] = $this->_wrappedParts;
        return $options;
    }

    protected function _preProcessArguments($name, $arguments)
    {
        if($this->_wrappedParts && count($arguments) == 1 && is_object($arguments[0])){
            return get_object_vars($arguments[0]);
        }else{
            return $arguments;
        }
    }

    protected function _preProcessResult($name, $result)
    {
        if($this->_wrappedParts)
            $result = array($name.'Result' => $result);
        return $result;
    }

    public function __call($name, $arguments)
    {
        $result = call_user_func_array(array($this->_classInstance, $name), $this->_preProcessArguments($name, $arguments));
        return $this->_preProcessResult($name, $result);
    }
}
