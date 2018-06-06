<?php

/**
 * @todo Write file documentation.
 */

namespace Finlet\flexmail\FlexmailAPI;

use Finlet\flexmail\Config\ConfigInterface;

class FlexmailAPI implements FlexmailAPIInterface
{

    protected $config = null;

    private $soapClient = null;

    /**
     *
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Reove header/error codes from the response
     *
     * @param stdClass $response The response from the API
     *
     * @return stdClass The same stdClass without the header information
     */
    public static function stripHeader($response, $debug_mode = false)
    {
        if (!$debug_mode):
            $valuesToStrip = ["header", "errorCode", "errorMessage"];

            foreach ($valuesToStrip as $value):
                if (property_exists($response, $value)):
                    unset ($response->$value);
                endif;
            endforeach;
        endif;

        return $response;
    }

    /**
     * Get the request Service Instance
     *
     * @param String $service Requested service name
     *
     * @return Object An instance of the requested service
     */
    public function service($service)
    {
        $classname = "\Finlet\\flexmail\FlexmailAPI\Service\FlexmailAPI_{$service}";

        return new $classname($this->config);
    }

    /**
     * Convert two-(or-more)-dimensional arrays to an stdClass object
     *
     * @param array $arr The array to convert
     * @param stdClass $parent The object to convert it to
     *
     * @return stdClass The converted array
     */
    protected function parseArray(array $arr, \stdClass $parent = null)
    {
        if ($parent === null):
            $parent = $this;
        endif;

        foreach ($arr as $key => $val):
            if (is_array($val) AND $key == "groups"):
                $parent->$key = $val;
            elseif (is_array($val) AND $key != "custom" AND substr($key,
                -3) != "Ids"):
                $parent->$key = $this->parseArray($val, new \stdClass);
            else:
                $parent->$key = $val;
            endif;
        endforeach;

        return $parent;
    }

    /**
     * Execute the requested call
     *
     * @param string $service The name of the service to execute
     * @param array $parameters All parameter in an assiociative array
     *
     * @return type
     *
     * @throws Exception
     */
    protected function execute($service, $parameters)
    {
        // make sure a SOAP client exists
        if (is_null($this->soapClient)):
            $this->createSoapClient();
        endif;

        // create a request object (an stdClass) from the parameters array
        $request = (object)$parameters;

        // add authentication to the request object
        $request->header = $this->getRequestHeader();

        // execute the call
        $response = $this->soapClient->__soapCall($service, [$request]);

        // return the response
        return $response;
    }

    /**
     * Create a new SOAP Client
     *
     * @returns void
     */
    private function createSoapClient()
    {
        // create a new SoapClient instance
        $this->soapClient = new \SoapClient(
          $this->config->get('wsdl'),
          [
            "location" => $this->config->get('service'),
            "uri" => $this->config->get('service'),
            "trace" => 1,
          ]
        );
    }

    /**
     * Function to create the user's personal request header
     *
     * @return stdClass The user's personal header
     */
    private function getRequestHeader()
    {
        //check of module aanwezig is, geef waarschuwing indien niet.
        $header = new \stdClass();

        $header->userId = $this->config->get('user_id');
        $header->userToken = $this->config->get('user_token');

        return $header;
    }
}
