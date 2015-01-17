<?php

/**
 * EZohoCrm extension for Yii framework.
 *
 * API Reference Zoho CRM
 * @link https://www.zoho.com/crm/help/api/api-methods.html
 *
 * @author: Emile Bons <emile@emilebons.nl>
 * @link http://www.malvee.com
 * @link http://www.emilebons.nl
 * @copyright Copyright &copy; Emile Bons 2013
 * @license The MIT License
 * @category Yii 1.1
 * @package ext\EZohoCrm
 *
 * Extension was improved by
 * @author: Dmitry Kulikov <kulikovdn@gmail.com>
 *
 * TODO need to improve error handling, checkResponseOnMultipleRecordsRequest and
 * TODO fixOrderInResponseOnMultipleRecordsRequest must be executed automatically
 *
 * TODO documentation
 *
 * TODO use Yii::t for translation
 *
 * TODO unit tests
 *
 * TODO check for compatibility with Yii 2 and modify if needed
 *
 * TODO rewrite as descendant of CApplicationComponent
 *
 * TODO validate data before sending of insert / update requests to Zoho CRM API,
 * TODO because API just ignore incorrect values: it is needed to validate length, is date etc.
 */

namespace ext\EZohoCrm;

/**
 * EZohoCrm is main class of extension. It is good idea to create in your project class ZohoCrm extending this class.
 */
class EZohoCrm
{
    /**
     * Auth token request url constant.
     */
    const AUTH_TOKEN_REQUEST_URL = 'https://accounts.zoho.com/apiauthtoken/nb/create';

    /**
     * Base url constant.
     */
    const BASE_URL = 'https://crm.zoho.com/crm/private/json/';

    /**
     * Maximum number of records which can be retrieved in one getRecords API call.
     */
    const MAX_RECORDS_GET_RECORDS = 200;

    /**
     * Maximum number of records which can be created / updated in one API call.
     */
    const MAX_RECORDS_INSERT_UPDATE = 100;

    /**
     * Module constants.
     */
    const MODULE_ACCOUNTS = 'Accounts';
    const MODULE_CALLS = 'Calls';
    const MODULE_CAMPAIGNS = 'Campaigns';
    const MODULE_CASES = 'Cases';
    const MODULE_COMPETITORS = 'Competitors';
    const MODULE_CONTACTS = 'Contacts';
    const MODULE_DASHBOARDS = 'Dashboards';
    const MODULE_EMAILS = 'Emails';
    const MODULE_EVENTS = 'Events';
    const MODULE_FORECASTS = 'Forecasts';
    const MODULE_INFO = 'Info';
    const MODULE_INTEGRATIONS = 'Integrations';
    const MODULE_INVOICES = 'Invoices';
    const MODULE_LEADS = 'Leads';
    const MODULE_POTENTIALS = 'Potentials';
    const MODULE_PRICE_BOOKS = 'PriceBooks';
    const MODULE_PRODUCTS = 'Products';
    const MODULE_PURCHASE_ORDERS = 'PurchaseOrders';
    const MODULE_QUOTES = 'Quotes';
    const MODULE_REPORTS = 'Reports';
    const MODULE_SALES_ORDERS = 'SalesOrders';
    const MODULE_SOLUTIONS = 'Solutions';
    const MODULE_TASKS = 'Tasks';
    const MODULE_USERS = 'Users';
    const MODULE_VENDORS = 'Vendors';

    /**
     * Parameter constants.
     * These constants are used when calling methods in the API.
     */
    const ALL_COLUMNS = 'All';
    const SORT_ORDER_ASC = 'asc';
    const SORT_ORDER_DESC = 'desc';

    /**
     * Scope constants.
     */
    const SCOPE = 'crmapi';
    const SCOPE_AUTH_TOKEN_REQUEST = 'ZohoCRM/crmapi';
    const VERSION = 2;

    /**
     * User type constants.
     */
    const USER_TYPE_ACTIVE_CONFIRMED_ADMINS = 'ActiveConfirmedAdmins';
    const USER_TYPE_ACTIVE_USERS = 'ActiveUsers';
    const USER_TYPE_ADMIN_USERS = 'AdminUsers';
    const USER_TYPE_ALL_USERS = 'AllUsers';
    const USER_TYPE_DEACTIVE_USERS = 'DeactiveUsers';

    /**
     * An authentication token is required in order to be able to make use of the Zoho CRM API.
     * An authentication token can be obtained by using the generateAuthToken function inside this class
     * or by using the url https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi while being
     * logged in in Zoho CRM. You could hardcode the authToken, obtain it from a config file or from a database.
     * @var string
     */
    public $authToken = null;

    /**
     * Defines the module which you want to use within the application.
     * @var string
     */
    public $module;

    /**
     * Defines whether print or return result of API call, defaults to false.
     * @var boolean
     */
    public $print = false;

    /**
     * Timeout for \EHttpClient requests in seconds, defaults to 30 seconds.
     * @var integer
     */
    public $timeout = 30;

    /**
     * Maximum number of attempts to send request. If this number is greater than 1
     * then request will be automatically repeated in case of connection timeout up to reaching of Max Attempts value.
     * @var integer
     */
    public $maxAttempts = 1;

    /**
     * Number of already performed attempts to send request.
     * @var integer
     */
    public $attemptsCount = 1;

    /**
     * Time in seconds between attempts to send request in case of connection timeout.
     * @var integer
     */
    public $sleepTime = 1;

    /**
     * Enable debug mode, extension logs all requests to Zoho CRM API in debug mode.
     * @var boolean
     */
    public $debug = YII_DEBUG;

    /**
     * Option for a cURL transfer.
     * @var array
     */
    public $curlOptions = array();

    /**
     * Callback function which will be executed before sending of request to Zoho CRM API; callback will receive
     * $path, $method, $getParameters, $postParameters, $postBody, $bodyEncodingType; all arguments passed by reference;
     * result of execution of this function will be passed to $afterApiCall (if this callback specified).
     * @var null|callable
     */
    public $beforeApiCall = null;

    /**
     * Callback function which will be executed after sending of request to Zoho CRM API; callback will receive
     * $response, $beforeApiCallResult; $response passed by reference, $beforeApiCallResult passed by value;
     * if $beforeApiCall callback is not specified then $beforeApiCallResult will be null.
     * @var null|callable
     */
    public $afterApiCall = null;

    /**
     * Constructor.
     * @param null|array $configArray use it to override default values for variables
     */
    public function __construct($configArray = null)
    {
        // default curl options
        $this->curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => true,
            /**
             * Please everyone, stop setting CURLOPT_SSL_VERIFYPEER to false or 0. If your PHP installation does not
             * have an up-to-date CA root certificate bundle, download the one at the curl website
             * and save it on your server: http://curl.haxx.se/docs/caextract.html
             * Then set a path to it in your php.ini file, e.g. on Windows:
             *     curl.cainfo=c:\php\cacert.pem
             * Turning off CURLOPT_SSL_VERIFYPEER allows man in the middle (MITM) attacks, which you don't want!
             */
            CURLOPT_SSL_CIPHER_LIST => 'TLSv1+HIGH:!SSLv2:!aNULL:!eNULL',
        );

        if (!empty($configArray)) {
            foreach ($configArray as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param $path
     * @param string $method
     * @param null $getParameters
     * @param null $postParameters
     * @param null $postBody
     * @param null $bodyEncodingType
     * @param bool $rawFile
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    protected function zohoCrmApiCall(
        $path,
        $method = \EHttpClient::GET,
        $getParameters = null,
        $postParameters = null,
        $postBody = null,
        $bodyEncodingType = null,
        $rawFile = false
    ) {
        $response = $this->request($path, $method, $getParameters, $postParameters, $postBody, $bodyEncodingType);

        if ($rawFile) {
            return $response;
        }

        $json = $response->getBody();
        $decodedResponse = json_decode($json);
        $jsonLastError = EUtils::getJsonLastError();
        if (isset($jsonLastError)) {
            throw new exceptions\ZohoCrmInvalidJson("Invalid JSON: $jsonLastError.");
        }
        if (!is_object($decodedResponse)) {
            throw new exceptions\ZohoCrmInvalidResponse(
                "Object expected as decoded response, but got\n" . EUtils::printVarDump($decodedResponse, true)
            );
        }
        if (isset($decodedResponse->response->error)) {
            throw new exceptions\ZohoCrmResponseError(
                'Error ' . $decodedResponse->response->error->code . ': ' . $decodedResponse->response->error->message .
                ' Uri was "' . $decodedResponse->response->uri . '".'
            );
        }

        if ($this->print) {
            static::printResponse($json);

            return null;
        } else {

            return $this->preprocessResponse($decodedResponse);
        }
    }

    /**
     * @param $path
     * @param $method
     * @param $getParameters
     * @param $postParameters
     * @param $postBody
     * @param $bodyEncodingType
     * @return mixed
     * @throws \EHttpClientException
     * @throws \Exception
     * @throws exceptions\RetryAttemptsLimit
     */
    protected function request($path, $method, $getParameters, $postParameters, $postBody, $bodyEncodingType)
    {
        $beforeApiCallResult = null;
        if (is_callable($this->beforeApiCall)) {
            $beforeApiCallResult = call_user_func_array(
                $this->beforeApiCall,
                array(&$path, &$method, &$getParameters, &$postParameters, &$postBody, &$bodyEncodingType)
            );
        }

        $client = new \EHttpClient(
            $path,
            array(
                'maxredirects' => 2,
                'timeout' => $this->timeout,
                'adapter' => 'EHttpClientAdapterCurl',
            )
        );

        $client->setMethod($method);

        $this->processGetRequestParameters($client, $getParameters);
        $this->processPostRequestParameters($client, $postParameters, $postBody, $bodyEncodingType);

        $adapter = new \EHttpClientAdapterCurl();
        $client->setAdapter($adapter);
        $adapter->setConfig(array('curloptions' => $this->curlOptions));
        if ($this->debug) {
            \Yii::log(
                "Sending of request to Zoho CRM API:\n" . EUtils::printVarDump($client, true),
                'info',
                'ext.eZohoCrm'
            );
        }

        $this->attemptsCount = 0;

        $response = $this->requestRecursive($client);

        if (is_callable($this->afterApiCall)) {
            call_user_func_array($this->afterApiCall, array(&$response, $beforeApiCallResult));
        }

        return $response;
    }

    /**
     * @param \EHttpClient $client
     * @return mixed
     * @throws \EHttpClientException
     * @throws \Exception
     * @throws exceptions\RetryAttemptsLimit
     */
    protected function requestRecursive($client)
    {
        try {
            $this->attemptsCount++;
            $response = $client->request();
        } catch (\EHttpClientException $e) {
            if ($this->maxAttempts == 1 || strpos(strtolower($e->getMessage()), 'timed out') === false) {
                // repeating of requests disabled or not timed out error
                throw $e;
            }
            \Yii::log(
                "exception 'EHttpClientException' with message '{$e->getMessage()}'" .
                " in {$e->getFile()}:{$e->getLine()}\nStack trace:\n" . $e->getTraceAsString(),
                'error',
                'exception.EHttpClientException'
            );
            if ($this->attemptsCount < $this->maxAttempts) {
                sleep($this->sleepTime);
                $response = $this->requestRecursive($client);
            } else {
                throw new exceptions\RetryAttemptsLimit(
                    "Can't perform request after {$this->attemptsCount} attempts " .
                    "with {$this->sleepTime} second(s) intervals."
                );
            }
        }

        return $response;
    }

    /**
     * @param \EHttpClient $client
     * @param $getParameters
     */
    protected function processGetRequestParameters(&$client, $getParameters)
    {
        $defaultGetParameters = array('scope' => static::SCOPE);

        if (!empty($this->authToken)) {
            $defaultGetParameters['authtoken'] = $this->authToken;
        }

        if (isset($getParameters) && array_key_exists('excludeNull', $getParameters)) {
            $getParameters['newFormat'] = static::getNewFormat($getParameters['excludeNull']);
            unset($getParameters['excludeNull']);
        }
        if (!empty($getParameters)) {
            $getParameters = array_merge($defaultGetParameters, $getParameters);
        } else {
            $getParameters = $defaultGetParameters;
        }
        $client->setParameterGet($getParameters);
    }

    /**
     * @param \EHttpClient $client
     * @param null $postParameters
     * @param null $postBody
     * @param null $bodyEncodingType
     */
    protected function processPostRequestParameters(&$client, $postParameters, $postBody, $bodyEncodingType)
    {
        // POST parameters
        if (!empty($postParameters)) {
            $client->setParameterPost($postParameters);
        }

        // raw POST data
        if (!empty($postBody)) {
            $client->setRawData($postBody, $bodyEncodingType);
        }

        if (!empty($postParameters) && !empty($postBody)) {
            \Yii::log(
                'Attempt to send POST parameters and POST data. ' .
                "Setting raw POST data for a request will override any POST parameters or file uploads.\n" .
                EUtils::printVarDump($client, true),
                'warning',
                'ext.eZohoCrm'
            );
        }
    }

    /**
     * Preprocess response before return to main application. If you want to add your own processing it is good idea
     * to override this method.
     * @param $response
     * @return mixed
     */
    protected function preprocessResponse($response)
    {
        return $this->rowToArray($response);
    }

    /**
     * Get path for Zoho CRM API request.
     * @param string $function name of function
     * @param null|string $module name of module
     * @return string path for Zoho CRM API request.
     */
    protected function getPath($function, $module = null)
    {
        if (!isset($module)) {
            $module = $this->module;
        }

        return static::BASE_URL . $module . '/' . $function;
    }

    /**
     * Returns a string for the given boolean.
     * @param boolean $boolean
     * @return string
     */
    protected static function getBoolean($boolean)
    {
        return $boolean ? 'true' : 'false';
    }

    /**
     * New format is an integer and can be either 1 or 2. 1 means that null values are excluded, 2 means the opposite.
     * @param $excludeNull
     * @return integer
     */
    protected static function getNewFormat($excludeNull)
    {
        return $excludeNull ? 1 : 2;
    }

    /**
     * Returns a string indicating which columns should be returned based on the selectColumns input variable.
     * @param array $selectColumns array of columns which should be returned
     * @return string string of columns which should be returned.
     */
    protected function getSelectColumns($selectColumns)
    {
        if ($selectColumns === array()) {
            return static::ALL_COLUMNS;
        } else {
            return $this->module . '(' . implode(',', $selectColumns) . ')';
        }
    }

    /**
     * You can use this method to convert lead to potential, account and contact.
     * @link https://www.zoho.com/crm/help/api/convertlead.html
     * @param $leadId
     * @param $createPotential
     * @param $assignTo
     * @param $notifyLeadOwner
     * @param $notifyNewEntityOwner
     * @param null $potentialName
     * @param null $closingDate
     * @param null $potentialStage
     * @param null $contactRole
     * @param null $amount
     * @param null $probability
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function convertLead(
        $leadId,
        $createPotential,
        $assignTo,
        $notifyLeadOwner,
        $notifyNewEntityOwner,
        $potentialName = null,
        $closingDate = null,
        $potentialStage = null,
        $contactRole = null,
        $amount = null,
        $probability = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $moduleBefore = $this->module;
        $this->module = static::MODULE_POTENTIALS;

        $rowNo1 = array(
            'createPotential' => static::getBoolean($createPotential),
            'assignTo' => (string)$assignTo,
            'notifyLeadOwner' => static::getBoolean($notifyLeadOwner),
            'notifyNewEntityOwner' => static::getBoolean($notifyNewEntityOwner),
        );

        if ($closingDate instanceof \DateTime) {
            $closingDate = $closingDate->format('m/d/Y');
        }

        $rowNo2 = array(
            'Potential Name' => (string)$potentialName,
            'Closing Date' => (string)$closingDate,
            'Potential Stage' => (string)$potentialStage,
            'Contact Role' => (string)$contactRole,
            'Amount' => (string)$amount,
            'Probability' => (string)$probability,
        );

        $xmlData = '<' . $this->module . '><row no="1">';

        foreach ($rowNo1 as $key => $value) {
            $xmlData .= '<option val="' . $key . '">' . $value . '</option>';
        }
        $xmlData .= '</row>';
        if ($createPotential) {
            $xmlData .= '<row no="2">';
            foreach ($rowNo2 as $key => $value) {
                $xmlData .= '<FL val="' . $key . '">' . $value . '</FL>';
            }
            $xmlData .= '</row>';
        }
        $xmlData .= '</' . $this->module . '>';

        $path = $this->getPath(__FUNCTION__, $moduleBefore);

        $getParameters = array(
            'leadId' => (string)$leadId,
            'xmlData' => $xmlData,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );

        $this->module = $moduleBefore;

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use this method to delete the selected record (you must specify unique ID
     * of the record) and move to the recycle bin.
     * @link https://www.zoho.com/crm/help/api/deleterecords.html
     * @param $id
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function deleteRecords($id)
    {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array('id' => (string)$id);

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * The Zoho CRM API is available in all editions of Zoho CRM. To use the API, you'll
     * require the Zoho CRM Authentication Token from your CRM account. Please make sure
     * that you have the permission to access the API service. If you do not have
     * permission, please contact your CRM administrator.
     * @link https://www.zoho.com/crm/help/api/using-authentication-token.html
     * @param $usernameOrEmail
     * @param $password
     * @return string
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function generateAuthToken($usernameOrEmail, $password)
    {
        $path = static::AUTH_TOKEN_REQUEST_URL;

        $getParameters = array(
            'SCOPE' => static::SCOPE_AUTH_TOKEN_REQUEST,
            'EMAIL_ID' => $usernameOrEmail,
            'PASSWORD' => $password,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the getCVRecords method to fetch data with respect to the Custom View in Zoho CRM.
     * IMPORTANT: Irrespective of the Zoho CRM Edition, you can send only 250 API requests / day.
     * In each request you can fetch a maximum of 200 records.
     * @link https://www.zoho.com/crm/help/api/getcvrecords.html
     * @param $cvName
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null|string $lastModifiedTime
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     * @deprecated
     */
    public function getCVRecords(
        $cvName,
        $fromIndex = 1,
        $toIndex = 20,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'cvName' => $cvName,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'lastModifiedTime' => $lastModifiedTime,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the getFields method to fetch details of the fields available in a particular module.
     * @link https://www.zoho.com/crm/help/api/getfields.html
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getFields()
    {
        $path = $this->getPath(__FUNCTION__);

        return $this->zohoCrmApiCall($path, \EHttpClient::GET);
    }

    /**
     * You can use the getModules method to get the list of modules in your CRM account.
     * @link https://www.zoho.com/crm/help/api/getmodules.html
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getModules()
    {
        $path = $this->getPath(__FUNCTION__, static::MODULE_INFO);

        return $this->zohoCrmApiCall($path, \EHttpClient::GET);
    }

    /**
     * You can use the getMyRecords method to fetch data by the owner of the
     * Authentication token specified in the API request.
     * @link https://www.zoho.com/crm/help/api/getmyrecords.html
     * @param array $columns
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null $sortColumnString
     * @param string $sortOrderString
     * @param null|string $lastModifiedTime
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     */
    public function getMyRecords(
        $columns = array(),
        $fromIndex = 1,
        $toIndex = 20,
        $sortColumnString = null,
        $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        return $this->getRecords(
            $columns,
            $fromIndex,
            $toIndex,
            $sortColumnString,
            $sortOrderString,
            $lastModifiedTime,
            $excludeNull,
            $version,
            true
        );
    }

    /**
     * You can use this method to retrieve individual records by record ID.
     * @link https://www.zoho.com/crm/help/api/getrecordbyid.html
     * @param $id
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getRecordById($id, $excludeNull = false, $version = self::VERSION)
    {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'id' => (string)$id,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the getRecords method to fetch all users data specified in the API request.
     * @link https://www.zoho.com/crm/help/api/getrecords.html
     * @param array $columns
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null|string $sortColumnString
     * @param string $sortOrderString
     * @param null|string $lastModifiedTime
     * @param boolean $excludeNull
     * @param integer $version
     * @param boolean $myRecords
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getRecords(
        $columns = array(),
        $fromIndex = 1,
        $toIndex = 20,
        $sortColumnString = null,
        $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION,
        $myRecords = false
    ) {
        $path = $this->getPath($myRecords ? 'getMyRecords' : __FUNCTION__);

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($columns),
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'sortColumnString' => $sortColumnString,
            'sortOrderString' => $sortOrderString,
            'lastModifiedTime' => $lastModifiedTime,
            'newFormat' => ($excludeNull ? 1 : 2),
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the getAllRecords method to fetch all users data specified in the API request.
     * getAllRecords unlike getRecords was designed to load all records in module and thus you can't specify
     * paging and sorting parameters for getAllRecords: fromIndex, toIndex, sortColumnString, sortOrderString.
     * @link https://www.zoho.com/crm/help/api/getrecords.html
     * @param array $columns
     * @param null|callable $callback callback function which will be executed after receiving of each page
     * with records; callback will receive $rows and $page arguments; all arguments passed by reference
     * @param boolean $return if this parameter is set to true, function will return array of records otherwise
     * it will return null, if module contains a lot of records it makes sense to process records page by page
     * using callback and do not store thousands of records in array because it may require a lot of memory
     * @param null|string $lastModifiedTime
     * @param boolean $excludeNull
     * @param integer $version
     * @param boolean $myRecords
     * @return mixed
     * @throws \Exception
     */
    public function getAllRecords(
        $columns = array(),
        $callback = null,
        $return = true,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION,
        $myRecords = false
    ) {
        $maxRecordsGetRecords = static::MAX_RECORDS_GET_RECORDS;
        $result = array();
        $count = null;
        $page = 1;
        while (!isset($count) || $count == $maxRecordsGetRecords) {
            $records = $this->getRecords(
                $columns,
                ($page - 1) * $maxRecordsGetRecords + 1,
                $page * $maxRecordsGetRecords,
                'Created Time',
                static::SORT_ORDER_ASC,
                $lastModifiedTime,
                $excludeNull,
                $version,
                $myRecords
            );

            $rows = EUtils::get($records, array('response', 'result', $this->module, 'row'), array());
            unset($records);
            $count = count($rows);

            if (is_callable($callback)) {
                call_user_func_array($callback, array(&$rows, &$page));
            }

            if ($return) {
                $result = array_merge($result, $rows);
            }

            $page++;
        }

        if ($return) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * You can use the getRelatedRecords method to fetch related records.
     * @link https://www.zoho.com/crm/help/api/getrelatedrecords.html
     * @param $parentModule
     * @param $id
     * @param boolean $excludeNull
     * @param integer $fromIndex
     * @param integer $toIndex
     * @return mixed
     * @throws exceptions\ModuleNotSupported
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getRelatedRecords($parentModule, $id, $excludeNull = false, $fromIndex = 1, $toIndex = 20)
    {
        $nonSupportedModules = array(static::MODULE_EMAILS, static::MODULE_COMPETITORS, static::MODULE_INTEGRATIONS);

        if (in_array($this->module, $nonSupportedModules)) {
            throw new exceptions\ModuleNotSupported("Module $this->module not supported for this function.");
        }

        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'parentModule' => (string)$parentModule,
            'id' => (string)$id,
            'excludeNull' => $excludeNull,
            'fromIndex' => (string)$fromIndex,
            'toIndex' => (string)$toIndex,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use this method to search records by expressions of the selected columns.
     * @link https://www.zoho.com/crm/help/api/getsearchrecords.html
     * @param array $selectColumns columns which should be selected, use empty array to select all
     * @param $searchCondition
     * @param boolean $excludeNull
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param integer $version
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     * @deprecated
     */
    public function getSearchRecords(
        $selectColumns,
        $searchCondition,
        $excludeNull = false,
        $fromIndex = 1,
        $toIndex = 20,
        $version = self::VERSION
    ) {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchCondition' => $searchCondition,
            'excludeNull' => $excludeNull,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the searchRecords method to get the list of records that meet your search criteria.
     * @link https://www.zoho.com/crm/help/api/searchrecords.html
     * @param array $selectColumns columns which should be selected, use empty array to select all
     * @param string $criteria
     * @param boolean $excludeNull
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null|string $lastModifiedTime
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function searchRecords(
        $selectColumns,
        $criteria,
        $excludeNull = false,
        $fromIndex = 1,
        $toIndex = 20,
        $lastModifiedTime = null
    ) {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'criteria' => "($criteria)",
            'excludeNull' => $excludeNull,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
        );
        if (isset($lastModifiedTime)) {
            $getParameters['lastModifiedTime'] = $lastModifiedTime;
        }

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use this method to search the values based on predefined columns.
     * @link https://www.zoho.com/crm/help/api/getsearchrecordsbypdc.html
     * @param array $selectColumns columns which should be selected, use empty array to select all
     * @param $searchColumn
     * @param $searchValue
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getSearchRecordsByPDC(
        $selectColumns,
        $searchColumn,
        $searchValue,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchColumn' => (string)$searchColumn,
            'searchValue' => (string)$searchValue,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use the getUsers method to get the list of users in your organization.
     * @link https://www.zoho.com/crm/help/api/getusers.html
     * @param $type
     * @param boolean $excludeNull
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function getUsers($type, $excludeNull = false)
    {
        $path = $this->getPath(__FUNCTION__, static::MODULE_USERS);

        $getParameters = array(
            'type' => (string)$type,
            'excludeNull' => $excludeNull,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * You can use this method to download files from CRM to your system.
     * @link https://www.zoho.com/crm/help/api/downloadfile.html
     * @param $id
     * @return mixed
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function downloadFile($id)
    {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array('id' => (string)$id);

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters, null, null, null, true);
    }

    /**
     * You can use the insertRecords method to insert records into the required Zoho CRM module.
     * @link https://www.zoho.com/crm/help/api/insertrecords.html
     * @param $records
     * @param boolean $wfTrigger
     * @param integer $duplicateCheck
     * @param boolean $isApproval
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ModuleNotSupported
     * @throws exceptions\RecordsInsertUpdateLimit
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function insertRecords(
        $records,
        $wfTrigger = false,
        $duplicateCheck = 1,
        $isApproval = false,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'wfTrigger' => (string)$wfTrigger,
            'isApproval' => (string)$isApproval,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );
        if ($duplicateCheck) {
            $getParameters['duplicateCheck'] = $duplicateCheck;
        }

        $postParameters = array('xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::POST));

        return $this->zohoCrmApiCall($path, \EHttpClient::POST, $getParameters, $postParameters);
    }

    /**
     * You can use the updateRecords method to update or modify the records in Zoho CRM.
     * @link https://www.zoho.com/crm/help/api/updaterecords.html
     * @param $id
     * @param $records
     * @param boolean $wfTrigger
     * @param boolean $excludeNull
     * @param integer $version
     * @return mixed
     * @throws exceptions\ModuleNotSupported
     * @throws exceptions\RecordsInsertUpdateLimit
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function updateRecords($id, $records, $wfTrigger = false, $excludeNull = false, $version = self::VERSION)
    {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'id' => (string)$id,
            'wfTrigger' => (string)$wfTrigger,
            'excludeNull' => $excludeNull,
            'version' => $version,
        );
        $postParameters = array(
            'xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::POST),
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::POST, $getParameters, $postParameters);
    }

    /**
     * You can use the updateRelatedRecords method to update records related to another record.
     * @link https://www.zoho.com/crm/help/api/updaterelatedrecords.html
     * @param $relatedModule
     * @param $id
     * @param $records
     * @return mixed
     * @throws exceptions\ModuleNotSupported
     * @throws exceptions\RecordsInsertUpdateLimit
     * @throws exceptions\ZohoCrmInvalidJson
     * @throws exceptions\ZohoCrmInvalidResponse
     * @throws exceptions\ZohoCrmResponseError
     */
    public function updateRelatedRecords($relatedModule, $id, $records)
    {
        $path = $this->getPath(__FUNCTION__);

        $getParameters = array(
            'relatedModule' => (string)$relatedModule,
            'id' => (string)$id,
            'xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::GET),
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * Transform one or multiple records to XML Data. This function can, for example, be
     * used to format an array of Leads to XML Data in order to make the data ready for
     * the insertRecords function.
     * @param $records
     * @param $method
     * @return string
     * @throws exceptions\ModuleNotSupported
     * @throws exceptions\RecordsInsertUpdateLimit
     * @throws exceptions\UnknownHttpMethod
     */
    public function transformRecordsToXmlData($records, $method)
    {
        $modulesNotSupportedForMultipleInserts = array(
            static::MODULE_QUOTES,
            static::MODULE_SALES_ORDERS,
            static::MODULE_INVOICES,
            static::MODULE_PURCHASE_ORDERS
        );

        if (count($records) > 1 && in_array($this->module, $modulesNotSupportedForMultipleInserts)) {
            throw new exceptions\ModuleNotSupported("Module $this->module does not support multiple inserts.");
        }

        if (count($records) > static::MAX_RECORDS_INSERT_UPDATE) {
            throw new exceptions\RecordsInsertUpdateLimit(
                'Only the first ' . static::MAX_RECORDS_INSERT_UPDATE .
                ' records will be considered when inserting multiple records.'
            );
        }

        $xml = '<' . $this->module . '>';
        $rowNumber = 1;
        foreach ($records as $record) {
            $xml .= '<row no="' . $rowNumber++ . '">';
            foreach ($record as $key => $value) {
                $value = static::getEscapedValue($value, $method);
                $xml .= '<FL val="' . $key . '">' . $value . '</FL>';
            }
            $xml .= '</row>';
        }
        $xml .= '</' . $this->module . '>';

        return $xml;
    }

    /**
     * Returns the escaped value which can be used in the xmlData parameter.
     * @param $value
     * @param $method
     * @return string
     * @throws exceptions\UnknownHttpMethod
     */
    protected static function getEscapedValue($value, $method)
    {
        switch ($method) {
            case \EHttpClient::GET:
                $value = '<![CDATA[' . htmlentities($value) . ']]>';
                break;
            case \EHttpClient::POST:
                $value = '<![CDATA[' . $value . ']]>';
                break;
            default:
                throw new exceptions\UnknownHttpMethod("Unknown HTTP request method $method.");
        }

        return $value;
    }

    /**
     * Print response.
     * @param $response
     */
    protected static function printResponse($response)
    {
        echo '<pre>';
        print_r($response);
        echo '</pre>';
    }

    /**
     * You can use this method to check response on multiple records requests like multiple insertRecords or
     * updateRecords.
     * @param mixed $response response from Zoho CRM API
     * @param array $records array of records which were sent to Zoho CRM API
     * @throws exceptions\ZohoCrmResponseError
     */
    public static function checkResponseOnMultipleRecordsRequest($response, $records)
    {
        $errorMessage = '';
        foreach ($response->response->result->row as $item) {
            if (isset($item->error)) {
                $errorMessage .= "\nError {$item->error->code}: {$item->error->details}";
            }
        }
        if (!empty($errorMessage)) {
            throw new exceptions\ZohoCrmResponseError(
                $errorMessage . "\nUri was \"{$response->response->uri}\".\n" . "Records data:\n" .
                EUtils::printVarDump($records, true)
            );
        }
    }

    /**
     * Order of rows in response of Zoho CRM API may differ from order of rows in request, this method fixes it.
     * @param mixed $response response from Zoho CRM API
     * @return mixed response from Zoho CRM API with reordered rows.
     */
    public static function fixOrderInResponseOnMultipleRecordsRequest($response)
    {
        $newRow = array();
        foreach ($response->response->result->row as $key => $value) {
            $newRow[(int)$value->no - 1] = $value;
            unset($response->response->result->row[$key]);
        }
        ksort($newRow);
        $response->response->result->row = $newRow;

        return $response;
    }

    /**
     * This method needed to make response of Zoho CRM API more consequent: API calls containing "row" return data
     * with different structure depending on number of items, there is difference between response with one item and
     * with many items, this methods makes response unified.
     * @param $response
     * @return mixed
     * @throws \Exception
     */
    protected function rowToArray($response)
    {
        $paths = array(
            array('response', 'result', $this->module, 'row'),
            array('response', 'result', 'row'),
        );
        foreach ($paths as $path) {
            $rows = EUtils::get($response, $path);
            if (isset($rows) && is_object($rows)) {
                EUtils::set($response, $path, array($rows));
            }
        }

        return $response;
    }

    /**
     * Get value of field of row in response on multiple records request.
     * @param \stdClass $row row in response on multiple records request
     * @param string $fieldName field name
     * @return mixed field value
     * @throws \Exception
     */
    public static function getRowFieldValue($row, $fieldName)
    {
        foreach ($row->FL as $field) {
            if ($field->val === $fieldName) {
                return $field->content;
            }
        }

        throw new \Exception("Field with name \"$fieldName\" not found in\n" . EUtils::printVarDump($row, true));
    }

    /**
     * @param null|string $module
     * @return string
     * @throws exceptions\ModuleNotSupported
     */
    public function getSystemIdFieldName($module = null)
    {
        if (!isset($module)) {
            $module = $this->module;
        }
        if (empty($module)) {
            throw new exceptions\ModuleNotSupported("Module name can't be empty.");
        }

        return strtoupper($module) . '_ID';
    }
}
