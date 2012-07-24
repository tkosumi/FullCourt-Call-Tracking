<?php
require_once 'HTTP/Request2.php';


class FullcourtError extends Exception { }


function validate_signature($uri, $post_params=array(), $signature, $auth_token) {
    ksort($post_params);
    foreach($post_params as $key => $value) {
        $uri .= "$key$value";
    }
    $generated_signature = base64_encode(hash_hmac("sha1",$uri, $auth_token, true));
    return $generated_signature == $signature;
}


class RestAPI {
    private $api;

    private $auth_id;

    private $auth_token;

    function __construct($auth_id, $auth_token, $url="https://api.fullcourt.co", $version="v0.1") {
        if ((!isset($auth_id)) || (!$auth_token)) {
            throw new FullcourtError("no auth_id");
        }
        if ((!isset($auth_token)) || (!$auth_token)) {
            throw new FullcourtError("no auth_token");
        }
        $this->version = $version;
        $this->api = $url."/".$this->version."/Account/".$auth_id;
        $this->auth_id = $auth_id;
        $this->auth_token = $auth_token;
    }

    private function request($method, $path, $params=array()) {
        $url = $this->api.rtrim($path, '/').'/';
        if (!strcmp($method, "POST")) {
            $req = new HTTP_Request2($url, HTTP_Request2::METHOD_POST);
            $req->setHeader('Content-type: application/json');
            if ($params) {
                $req->setBody(json_encode($params));
            }
        } else if (!strcmp($method, "GET")) {
            $req = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
            $url = $req->getUrl();
            $url->setQueryVariables($params);
        } else if (!strcmp($method, "DELETE")) {
            $req = new HTTP_Request2($url, HTTP_Request2::METHOD_DELETE);
            $url = $req->getUrl();
            $url->setQueryVariables($params);
        }
        $req->setAdapter('curl');
        $req->setConfig(array('timeout' => 30));
        $req->setAuth($this->auth_id, $this->auth_token, HTTP_Request2::AUTH_BASIC);
        $req->setHeader(array(
            'Connection' => 'close',
            'User-Agent' => 'PHPfullcourt',
        ));
        $r = $req->send();
        $status = $r->getStatus();
        $body = $r->getbody();
        $response = json_decode($body, true);
        return array("status" => $status, "response" => $response);
    }

    private function pop($params, $key) {
        $val = $params[$key];
        if (!$val) {
            throw new FullcourtError($key." parameter not found");
        }
        unset($params[$key]);
        return $val;
    }

    ## Calls ##
    public function make_call($params=array()) {
        return $this->request('POST', '/Call/', $params);
    }

}


/* XML */

class Element {
    protected $nestables = array();

    protected $valid_attributes = array();

    protected $attributes = array();

    protected $name;

    protected $body = NULL;

    protected $childs = array();

    function __construct($body='', $attributes=array()) {
        $this->attributes = $attributes;
        if ((!$attributes) || ($attributes === null)) {
            $this->attributes = array();
        }
        $this->name = get_class($this);
        $this->body = $body;
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->valid_attributes)) {
                throw new FullcourtError("invalid attribute ".$key." for ".$this->name);
            }
            $this->attributes[$key] = $this->convert_value($value);
        }
    }

    protected function convert_value($v) {
        if ($v === TRUE) {
            return "true";
        } 
        if ($v === FALSE) {
            return "false";
        } 
        if ($v === NULL) {
            return "none";
        } 
        if ($v === "get") {
            return "GET";
        } 
        if ($v === "post") {
            return "POST";
        } 
        return $v;
    }

    function addSpeak($body=NULL, $attributes=array()) {
        return $this->add(new Speak($body, $attributes));
    }

    function addPlay($body=NULL, $attributes=array()) {
        return $this->add(new Play($body, $attributes));
    }

    function addDial($body=NULL, $attributes=array()) {
        return $this->add(new Dial($body, $attributes));
    }

    function addNumber($body=NULL, $attributes=array()) {
        return $this->add(new Number($body, $attributes));
    }

    function addGetDigits($attributes=array()) {
        return $this->add(new GetDigits($attributes));
    }

    function addRecord($attributes=array()) {
        return $this->add(new Record($attributes));
    }

    function addRedirect($body=NULL, $attributes=array()) {
        return $this->add(new Redirect($body, $attributes));
    }

    public function getName() {
        return $this->name;
    }

    protected function add($element) {
        if (!in_array($element->getName(), $this->nestables)) {
            throw new FullcourtError($element->getName()." not nestable in ".$this->getName());
        }
        $this->childs[] = $element;
        return $element;
    }

    public function setAttributes($xml) {
        foreach ($this->attributes as $key => $value) {
            $xml->addAttribute($key, $value);
        }
    }

    public function asChild($xml) {
        if ($this->body) {
            $child_xml = $xml->addChild($this->getName(), htmlspecialchars($this->body));
        } else {
            $child_xml = $xml->addChild($this->getName());
        }
        $this->setAttributes($child_xml);
        foreach ($this->childs as $child) {
            $child->asChild($child_xml);
        }
    }

    public function toXML($header=FALSE) {
        if (!(isset($xmlstr))) {
            $xmlstr = '';
        }

        if ($this->body) {
            $xmlstr .= "<".$this->getName().">".htmlspecialchars($this->body)."</".$this->getName().">";
        } else {
            $xmlstr .= "<".$this->getName()."></".$this->getName().">";
        }
        if ($header === TRUE) {
            $xmlstr = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>".$xmlstr;
        }
        $xml = new SimpleXMLElement($xmlstr);
        $this->setAttributes($xml);
        foreach ($this->childs as $child) {
            $child->asChild($xml);
        }
        return $xml->asXML();
    }

    public function __toString() {
        return $this->toXML();
    }

}

class Response extends Element {
    protected $nestables = array('Speak', 'Play', 'GetDigits', 'Record',
                                 'Dial', 'Redirect', 'Wait', 'Hangup', 
                                 'PreAnswer', 'Conference');

    function __construct() {
        parent::__construct(NULL);
    }

    public function toXML() {
        $xml = parent::toXML($header=TRUE);
        return $xml;
    }
}


class Speak extends Element {
    protected $nestables = array();

    protected $valid_attributes = array('voice', 'language', 'loop');

    function __construct($body, $attributes=array()) {
        parent::__construct($body, $attributes);
        if (!$body) {
            throw new FullcourtError("No text set for ".$this->getName());
        }
    }
}

class Play extends Element {
    protected $nestables = array();

    protected $valid_attributes = array('loop');

    function __construct($body, $attributes=array()) {
        parent::__construct($body, $attributes);
        if (!$body) {
            throw new FullcourtError("No url set for ".$this->getName());
        }
    }
}

class GetDigits extends Element {
    protected $nestables = array('Speak', 'Play', 'Wait');

    protected $valid_attributes = array('action', 'method', 'timeout', 'finishOnKey',
                                        'numDigits', 'retries', 'invalidDigitsSound',
                                        'validDigits', 'playBeep', 'redirect');

    function __construct($attributes=array()) {
        parent::__construct(NULL, $attributes);
    }
}

class Number extends Element {
    protected $nestables = array();

    protected $valid_attributes = array('sendDigits', 'sendOnPreanswer');

    function __construct($body, $attributes=array()) {
        parent::__construct($body, $attributes);
        if (!$body) {
            throw new FullcourtError("No number set for ".$this->getName());
        }
    }
}

class Dial extends Element {
    protected $nestables = array('Number', 'User');

    protected $valid_attributes = array('action','method','timeout','hangupOnStar',
                                        'timeLimit','callerId', 'callerName', 'confirmSound',
                                        'dialMusic', 'confirmKey', 'redirect',
                                        'callbackUrl', 'callbackMethod', 'digitsMatch',
                                        'sipHeaders');

    function __construct($attributes=array()) {
        parent::__construct(NULL, $attributes);
    }
}

class Record extends Element {
    protected $nestables = array();

    protected $valid_attributes = array('action', 'method', 'timeout','finishOnKey',
                                        'maxLength', 'playBeep', 'recordSession',
                                        'startOnDialAnswer', 'redirect', 'fileFormat');

    function __construct($attributes=array()) {
        parent::__construct(NULL, $attributes);
    }
}

?>
