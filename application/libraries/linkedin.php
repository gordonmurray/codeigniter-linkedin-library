<?php

require_once("OAuth.php");

class linkedin
{

    var $consumer;
    var $token;
    var $method;
    var $http_status;
    var $last_api_call;
    var $callback;

    function linkedin($data)
    {
        $this->method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($data['consumer_key'], $data['consumer_secret']);
        $this->callback = $data['callback_url'];

        if (!empty($data['oauth_token']) && !empty($data['oauth_token_secret']) && !empty($data['callback_url']))
        {
            $this->token = new OAuthConsumer($data['oauth_token'], $data['oauth_token_secret']);
        }
        else
        {
            $this->token = NULL;
        }
    }

    function get_request_token()
    {
        $args = array('scope' => 'rw_nus');

        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, 'GET', "https://api.linkedin.com/uas/oauth/requestToken", $args);
        $request->set_parameter("oauth_callback", $this->callback);
        $request->sign_request($this->method, $this->consumer, $this->token);
        $request = $this->http($request->to_url());

        parse_str($request, $token);

        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret'], $this->callback);

        return $token;
    }

    function get_access_token($oauth_verifier)
    {
        $args = array();

        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, 'GET', "https://api.linkedin.com/uas/oauth/accessToken", $args);
        $request->set_parameter("oauth_verifier", $oauth_verifier);
        $request->sign_request($this->method, $this->consumer, $this->token);
        $request = $this->http($request->to_url());

        parse_str($request, $token);

        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret'], 1);

        return $token;
    }

    function get_authorize_URL($token)
    {
        if (is_array($token))
        {
            $token = $token['oauth_token'];
        }
        return "https://api.linkedin.com/uas/oauth/authorize?oauth_token=" . $token;
    }

    function http($url, $post_data = null)
    {
        $ch = curl_init();

        if (defined("CURL_CA_BUNDLE_PATH"))
            curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (isset($post_data))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);

        return $response;
    }

    /**
     * Post to Linkedin
     * @param type $comment
     * @param type $title
     * @param type $url
     * @param type $image_url
     * @param type $access_token
     * @return type
     */
    function share($comment, $title, $url, $image_url, $access_token)
    {
        $shareUrl = "http://api.linkedin.com/v1/people/~/shares";

        $xml = "<share>
              <comment>$comment</comment>
              <content>
                 <title>$title</title>
                     <description>asdfsadf</description>
                 <submitted-url>$url</submitted-url>
                 <submitted-image-url>$image_url</submitted-image-url>
              </content>
              <visibility>
                <code>anyone</code>
              </visibility>
            </share>";


        $request = OAuthRequest::from_consumer_and_token($this->consumer, $access_token, "POST", $shareUrl);
        $request->sign_request($this->method, $this->consumer, $access_token);
        $auth_header = $request->to_header("https://api.linkedin.com");

        $response = $this->httpRequest($shareUrl, $auth_header, "POST", $xml);

        return $response;
    }

    /**
     * Send a http request using Curl
     * @param type $url
     * @param type $auth_header
     * @param string $method
     * @param type $body
     * @return type
     */
    function httpRequest($url, $auth_header, $method, $body = NULL)
    {

        if (!$method)
        {
            $method = "GET";
        };

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header)); // Set the headers.

        if ($body)
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header, "Content-Type: text/xml;charset=utf-8"));
        }

        $data = curl_exec($curl);
        echo curl_getinfo($curl, CURLINFO_HTTP_CODE);

        echo $data . "\n";

        curl_close($curl);

        return $data;
    }

}

?>