<?php

namespace lib;


class ScreenSteps
{

    /**
     * @var string - server url
     */
    private $serverUrl = "https://%s.screenstepslive.com/api/v2/";
    /**
     * @var int - seconds
     */
    private $curlTimeout = 30;

    private $login;
    private $token;
    private $account;
    private $returnResult;

    CONST JSON_RESULT = "json";
    CONST OBJECT_RESULT = "object";


    /**
     * ScreenSteps constructor.
     *
     * @param $login
     * @param $token
     * @param $account
     * @param string $returnResult - object / json
     */
    public function __construct($login, $token, $account, $returnResult = "object")
    {
        $this->login        = $login;
        $this->token        = $token;
        $this->account      = $account;
        $this->returnResult = $returnResult;
    }


    /**
     * @param string $serverUrl
     * Example: "https://%s.screenstepslive.com/api/v2/";
     */
    public function setServerUrl( $serverUrl )
    {
        $this->serverUrl = $serverUrl;
    }

    /**
     * @param int $curlTimeout - seconds
     */
    public function setCurlTimeout( $curlTimeout )
    {
        $this->curlTimeout = $curlTimeout;
    }

    /**
     * List Sites
     *
     * @return Object || JSON
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     */
    public function getSites()
    {
        return $this->callApi("sites");
    }

    /**
     *
     * Show Site
     *
     * @param $siteId
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     *
     */
    public function showSite($siteId)
    {
        return $this->callApi("sites/" . $siteId);
    }

    /**
     * Search Site
     *
     * TODO: Not completed
     * @param $siteId
     * @param array $args
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     */
    public function search($siteId, $args)
    {
        $urlParam = "";

        if(!is_array($args)) {
            $this->formatError(400, "args does not contain array");
        }

        if(isset($args['text']) || isset($args['title'])) {

            $key = 'text';
            if(isset($args['title'])) {
                $key = 'title';
            }
            $urlParam = $key . "=" . urlencode($args[$key]);

        } else if(isset($args['tags']) || isset($args['manual_ids'])) {

            $key = 'tags';
            if(isset($args['manual_ids'])) {
                $key = 'manual_ids';
            }

            if(count($args[$key]) == 1) {
                $urlParam = $key . "=" . urlencode($args[$key][0]);
            } else if(count($args[$key]) > 1) {
                for($i = 0; $i < count($args[$key]); $i++) {
                    if($i > 0) {
                        $urlParam.="&";
                    }
                    $urlParam.= $key . "[]=" . $args[$key][$i];
                }
            }
        }

        if(!empty($urlParam) && isset($args['page'])) {
            $urlParam = $urlParam . "&page=" . $args['page'];
        }

        echo $urlParam;

        return $this->callApi("sites/" . $siteId . "/search?" . $urlParam);

    }

    /**
     *
     * Show Manual
     *
     * @param $siteId
     * @param $manualId
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     */
    public function showManual($siteId, $manualId)
    {
        return $this->callApi("sites/" . $siteId . "/manuals/" . $manualId);
    }

    /**
     *
     * Show Chapter
     *
     * @param $siteId
     * @param $chapterId
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     */
    public function showChapter($siteId, $chapterId)
    {
        return $this->callApi("sites/" . $siteId . "/chapters/" . $chapterId);
    }

    /**
     *
     * Show Article
     *
     * @param $siteId
     * @param $articleId
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     */
    public function showArticle($siteId, $articleId)
    {
        return $this->callApi("sites/" . $siteId . "/articles/" . $articleId);
    }

    /**
     *
     * Show File for Article
     *
     * @param $siteId
     * @param $articleId
     * @param $fileId
     *
     * DOC: http://help.screensteps.com/m/screensteps-api/l/623202-example-requests-and-responses-v2-json-api
     *
     * @return Object || JSON
     */
    public function showFileForArticle($siteId, $articleId, $fileId)
    {
        return $this->callApi("sites/" . $siteId . "/articles/" . $articleId . "/files/" . $fileId);
    }

    /**
     * @return string
     */
    private function formatServerUrl()
    {
        return sprintf($this->serverUrl, $this->account);
    }

    /**
     * @return string
     */
    private function formatUserPwd()
    {
        return $this->login . ":" . $this->token;
    }

    /**
     * @param $args
     *
     * @return Object || JSON
     */
    private function callApi($args)
    {

        $serverUrl = $this->formatServerUrl() . $args;

        $ch = curl_init( $serverUrl );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt( $ch, CURLOPT_USERPWD, $this->formatUserPwd());

        $result = curl_exec( $ch );

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        if ($result !== false && $statusCode == 200) {
            $json = json_decode($result);
            if($json !== null) {

                if($this->returnResult == self::OBJECT_RESULT) {
                    return $json;
                } else {
                    return $result;
                }

            }
        }
        return $this->formatError($statusCode, $result);

    }

    /**
     * @param $statusCode
     * @param $message
     *
     * @return \stdClass
     */
    private function formatError($statusCode, $message)
    {
        $error = new \stdClass();
        $error->code = $statusCode;
        $error->message = $message;

        return $error;
    }



}