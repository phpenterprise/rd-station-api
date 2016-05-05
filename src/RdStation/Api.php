<?php

/* The namespace */

namespace RdStation;

/*
 * The RD Station Api (for metrics)
 * @author: Patrick Otto <patrick@phpenterprise.com>
 * @release: 1.0 beta
 * 
 * For experimental use, rights reserved for Resultados Digitais
 */

class Api {

    private $_cookie, $_token;
    protected $curl;

    const RD_URI_LOGIN = 'https://app.rdstation.com.br/login';
    const RD_URI_LEADS = 'https://app.rdstation.com.br/leads';
    const CURL_USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    public function __construct($mail, $pass) {

        // clt session
        session_start();

        // auth params from user 
        $this->mail = $mail;
        $this->pass = $pass;

        // set tmp storage
        $this->_cookie_path = __DIR__ . '/../../tmp/';

        // start login
        return $this->auth();
    }

    public function auth() {

        if (!($valid = $this->getCookieFile())) {

            // create DOM instance        
            $doc = new \DOMDocument;
            $doc->recover = true;
            $doc->strictErrorChecking = false;
            $doc->validateOnParse = false;

            // set the cookie storage
            $this->_cookie = tempnam($this->_cookie_path, 'CURLCOOKIE');

            // call login page
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_LOGIN);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->_cookie);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            $c = curl_exec($this->curl);

            ob_start();
            $st = @$doc->loadHTML($c, false);
            ob_end_clean();

            // valid connection
            if (!$st) {
                return 'Api: the login page contains incorrect parameters';
            }
            
            // get token
            $this->_token = (is_object($doc)) ? $doc->getElementsByTagName('input')->item(1)->getAttribute('value') : false;

            // valid auth token
            if (!$this->_token) {
                return 'Api: The authentication token is not valid, check API version or your mail and password';
            }

            // refresh last connection (start login)
            curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_LOGIN);
            curl_setopt($this->curl, CURLOPT_USERAGENT, self::CURL_USER_AGENT);
            curl_setopt($this->curl, CURLOPT_POST, 1);

            // set params
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, array(
                'utf8' => '✓',
                'authenticity_token' => $this->_token,
                'user[email]' => $this->mail,
                'user[password]' => $this->pass,
                'user[remember_me]' => '1',
                'commit' => 'Entrar',
            ));

            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->_cookie);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);

            // send data
            $a = curl_exec($this->curl);
            $valid = stristr($a, '/logout');

            // clean invalid cookie
            if (!$valid && file_exists($this->_cookie)) {
                unlink($this->_cookie);
            } elseif (empty($_SESSION['_CURL'])) {
                $_SESSION['_CURL'] = $this->curl;
            }
        }

        // valid session and response
        return ($valid) ? 'Api: Congratulations, this user ' . $this->mail . ' is logged' : 'Api: there was an error in establishing a session with the System';
    }

    public function getMetrics() {

        // valid session
        if ($this->getCookieFile()) {

            // refresh last connection (start login)
            curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_LEADS);
            curl_setopt($this->curl, CURLOPT_USERAGENT, self::CURL_USER_AGENT);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->_cookie);
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            $c = curl_exec($this->curl);

            // valid connection
            if (!stristr($c, '/logout')) {
                return 'Api: Page is offline or there was an error in authentication user';
            }

            preg_match("'<span\s+class=\"btn\-height\-sm\">(.*?)</span>'si", $c, $match);

            // response (total leads)
            return (int) (preg_replace('/[^0-9]/', '', current($match)));
        } else {
            return 'Api: The user session expired, attemp new login';
        }
    }

    private function getCookieFile() {

        // set path
        $path = $this->_cookie_path;

        // default
        $latest_ctime = time() - 600;
        $latest_filename = '';

        // read
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            $filepath = "{$path}/{$entry}";
            // could do also other checks than just checking whether the entry is a file
            if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
                $latest_ctime = filectime($filepath);
                $latest_filename = $filepath;
            } elseif (is_file($filepath)) {
                // delete invalid cookies
                unlink($filepath);
            }
        }

        // reload cookie
        if ($latest_filename) {
            $this->_cookie = $latest_filename;
        }

        // force re-login on loggout
        if (!empty($_SESSION['_CURL']) && !$this->curl && $latest_filename) {
            $this->curl = $_SESSION['_CURL'];
        } else {
            unset($_SESSION['_CURL']);
        }

        // return status
        return (bool) (($latest_filename) && ($this->curl));
    }

}
