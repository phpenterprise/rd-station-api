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

    const RD_URI_LOGIN = 'https://app.rdstation.com.br/sign_in';
    const RD_URI_LEADS = 'https://app.rdstation.com.br/leads';
    const RD_URI_DASH = 'https://app.rdstation.com.br/dashboard/load_dashboard';
    const CURL_USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    public function __construct($mail, $pass, $ses_key = '') {

        // clt session
        session_start();

        // auth params from user 
        $this->mail = $mail;
        $this->pass = $pass;

        // set session key (part of cookie file name)
        if ($ses_key) {
            $this->_cookie = $ses_key;
        }

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
            $this->_cookie = (!$this->_cookie) ? tempnam($this->_cookie_path, 'CURLCOOKIE') : $this->_cookie_path . $this->_cookie;

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

            // read inputs
            $post = (is_object($doc)) ? $doc->getElementsByTagName('script') : array();

            // sniffing code
            foreach ($post as $script) {
                $code = (($b = explode('"', $script->textContent)) && !empty($b[1])) ? $b[1] : null;

                // $value = $input->ge
                if (!$this->_token && preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $code) && mb_strlen($code) > 40 && base64_encode(base64_decode($code, true)) === $code) {
                    $this->_token = $code;
                }
            }

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

    public function getVisitors() {

        // valid session
        if ($this->getCookieFile()) {

            // refresh last connection (start login)
            curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_DASH);
            curl_setopt($this->curl, CURLOPT_USERAGENT, self::CURL_USER_AGENT);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->_cookie);
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            $c = curl_exec($this->curl);

            // valid connection
            if (!($c = (array) (json_decode($c)))) {
                return 'Api: Page is offline or there was an error in authentication user';
            }

            // response (total visitors)
            return (int) (($c['visitors']->current_month_value) ? $c['visitors']->current_month_value : 0);
        } else {
            return 'Api: The user session expired, attemp new login';
        }
    }

    public function exportLeads() {

        // default
        $data = array();

        // valid session
        if ($this->getCookieFile()) {

            // refresh last connection (start login)
            curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_LEADS . '?pagina=1&per_page=500&query=');
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

            preg_match_all("'<a\s+href=\"(\/leads\/[0-9]+)\"[^>]*?>(.*?)</a>'sim", $c, $match);
            preg_match_all("'\<small\s+class=\"word-break-word\">.*?</small\>'sim", $c, $match_origin);

            if (!empty($match_origin[0][0])) {
                $match_origin = array_map(function($a) {
                    return trim(strip_tags($a), " \n\r\t");
                }, $match_origin[0]);
            }

            // extract leads
            if (key_exists(1, $match) && is_array($match[1]) && $match[1]) {

                foreach ($match[1] AS $k => $lead) {

                    $code = str_replace('/leads/', '', $lead);

                    // read data
                    curl_setopt($this->curl, CURLOPT_URL, self::RD_URI_LEADS . '/' . $code);
                    curl_setopt($this->curl, CURLOPT_USERAGENT, self::CURL_USER_AGENT);
                    curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->_cookie);
                    curl_setopt($this->curl, CURLOPT_VERBOSE, true);
                    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
                    $c = curl_exec($this->curl);

                    preg_match("'<a\s+href=\"mailto:[a-zA-Z\-\.0-9\_]+@[a-zA-Z\-\.0-9_]+\"[^>]*?>([\\s\\S]*?)</a>'sim", $c, $match_lead);
                    preg_match("'\([0-9]{2,3}\)\s+[0-9\-]+'sim", $c, $match_phone);
                    preg_match("'<p>[A-Z]{2}</p>'sim", $c, $match_uf);

                    // valid data
                    if (!empty($match[2][$k]) && !empty($match_lead[1]) && filter_var($match_lead[1], FILTER_VALIDATE_EMAIL)) {
                        $data[$code] = array(
                            'name' => $match[2][$k],
                            'mail' => $match_lead[1],
                            'phone' => (isset($match_phone[0])) ? $match_phone[0] : '',
                            'origin' => (isset($match_origin[$k])) ? $match_origin[$k] : 'Cadastro direto',
                            'uf' => (isset($match_uf[0])) ? trim(strip_tags($match_uf[0]), " \n\r\t") : ''
                        );
                    }
                }
            }

            // response (data leads)
            return (array) $data;
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
        if ($latest_filename && !$this->_cookie) {
            $this->_cookie = $latest_filename;
        } else {
            $latest_filename = $this->_cookie;
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

    public static function logout() {
        if (isset($_SESSION['_CURL'])) {
            unset($_SESSION['_CURL']);
        }
    }

}