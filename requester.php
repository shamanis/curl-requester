<?php
class RequesterException extends Exception {}

/**
* Класс для работы с библиотекой cURL
* @author Petr Bondarenko
* @version 0.1
*/
class Requester {
    protected $_url;
    protected $_params;
    protected $_method;
    protected $_debug = false;
    protected $_cTimeout = 0;
    protected $_protocol = CURLPROTO_HTTP;
    protected $_port = null;
    protected $_useragent;

    /**
    * Конструктор класса
    * @param string $url Адрес URL на который будет отправлен запрос
    * @param array $params Ассоциативный массив параметров запроса
    * @param string $method Метод запроса
    * @see Requester::setUrl()
    * @see Requester::setParams()
    * @see Requester::setMethod()
    */
    public function __construct($url = null, $params = array(), $method = null) {
        $this->_url = $url;
        
        if (is_array($params)) {
            $this->_params = $params;
        } else {
            throw new RequesterException('Constructor $params value must be array.');
        }

        $this->_method = $method;
        $curl_info = curl_version();
        $this->_useragent = 'Curl/' . $curl_info['version'];
    }

    /**
    * Метод устанавливает URL, на который будет отправлен запрос
    * @param string $url Строка URL
    * @return Requester
    */
    public function setUrl($url) {
        $this->_url = $url;
        return $this;
    }

    /**
    * Метод устанавливает тип запроса. GET, POST etc.
    * @param string $method Тип запроса
    * @return Requester
    */
    public function setMethod($method) {
        $this->_method = $method;
        return $this;
    }

    /**
    * Метод принимает параметры запроса
    * @param array Ассоциативный массив параметров запроса
    * @return Requester
    */
    public function setParams($params) {
        if (is_array($params)) {
            $this->_params = $params;
            return $this;
        } else {
            throw new RequesterException('$params must be array');
        }
    }

    /**
    * Метод включает DEBUG-ркжим
    * @param boolean $debug Вкл/выкл DEBUG-режим
    * @return Requester
    */
    public function setDebug($debug) {
        $this->_debug = $debug;
        return $this;
    }

    /**
    * Установить таймаут соединения. Если 0, то соединение будет ожидаться бесконечно.
    * @param int $timeout Таймаут в секундах
    * @return Requester
    */
    public function setConnectTimeout($timeout) {
        $this->_cTimeout = (int) $timeout;
        return $this;
    }

    /**
    * Установить протокол в контексте которого работать
    * @param string $protocol Протокол. Например HTTP, HTTPS.
    * @return Requester
    */
    public function setProtocol($protocol) {
        switch (strtolower($protocol)) {
            case 'http':
                $this->_protocol = CURLPROTO_HTTP;
                break;
            case 'https':
                $this->_protocol = CURLPROTO_HTTPS;
                break;
            case 'ftp':
                $this->_protocol = CURLPROTO_FTP;
                break;
            case 'ftps':
                $this->_protocol = CURLPROTO_FTPS;
                break;
            case 'scp':
                $this->_protocol = CURLPROTO_SCP;
                break;
            case 'sftp':
                $this->_protocol = CURLPROTO_SFTP;
                break;
            case 'telnet':
                $this->_protocol = CURLPROTO_TELNET;
                break;
            case 'ldap':
                $this->_protocol = CURLPROTO_LDAP;
                break;
            case 'ldaps':
                $this->_protocol = CURLPROTO_LDAPS;
                break;
            case 'dict':
                $this->_protocol = CURLPROTO_DICT;
                break;
            case 'file':
                $this->_protocol = CURLPROTO_FILE;
                break;
            case 'tftp':
                $this->_protocol = CURLPROTO_TFTP;
                break;
            case 'all':
                $this->_protocol = CURLPROTO_ALL;
                break;
        }
        return $this;
    }

    /**
    * Установить альтернативный порт для соединения
    * @param int $port Номер порта
    * @return Requester
    */
    public function setPort($port) {
        $this->_port = (int) $port;
        return $this;
    }

    /**
    * Установить версию агента
    * @param string $ua Версия агента
    * @return Requester
    */
    public function setUserAgent($ua) {
        $this->_useragent = (string) $ua;
        return $this;
    }
    
    /**
    * Получить закодированную строку запроса и массива параметров запроса
    * @return string Закодированная строка запроса
    * @access protected
    */
    protected function getQueryString() {
        if (sizeof($this->_params) == 0) {
            return '';
        } else {
            $query_string = '';
            foreach ($this->_params as $key => $value) {
                $query_string .= '&' . $key. '=' . urlencode($value);
            }
            $query_string[0] = '';
            return $query_string;
        }
    }

    /**
    * Выполнить запрос на сервер
    * @param boolean $header Включить заголовки
    * @return array Массив результат запроса
    */
    public function request($header = false) {
        if ($this->_url === null) {
            throw new RequesterException('URL can not be empty');
        }
        if ($this->_method === null) {
            throw new RequesterException('No request method is specified');
        }
                
        $curl = curl_init();

        $query_string = $this->getQueryString($this->_params);

        if (strtolower($this->_method) == 'post') {
            curl_setopt($curl, CURLOPT_URL, $this->_url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query_string);
        } else if (strtolower($this->_method) == 'get') {
            curl_setopt($curl, CURLOPT_URL, $this->_url . '?' . $query_string);
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
        } else {
            curl_setopt($curl, CURLOPT_URL, $this->_url . '?' . $query_string);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->_method));
        }

        curl_setopt($curl, CURLOPT_HEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        
        if ($this->_debug) {
            curl_setopt($curl, CURLOPT_NOPROGRESS, 0);
        }

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->_cTimeout);
        curl_setopt($curl, CURLOPT_PROTOCOLS, $this->_protocol);

        if ($this->_port !== null) {
            curl_setopt($curl, CURLOPT_PORT, $this->_port);
        }

        curl_setopt($curl, CURLOPT_USERAGENT, $this->_useragent);
        
        $result = curl_exec($curl);
        curl_close($curl);

        $request_info = array(
                            'url' => $this->_url,
                            'method' => $this->_method,
                            'query_string' => $query_string,
                            'protocol' => $this->_protocol,
                            'alternate_port' => $this->_port,
                            'user_agent' => $this->_useragent,
                        );
        
        if (!$result) {
            $res = array(
                'result' => 'error',
                'error' => array(
                    'error_text' => trim(curl_error($curl)),
                    'error_code' => trim(curl_errno($curl)),
                ),
                'request' => $request_info,
            );
            return $res;
        } else {
            $res = array(
                'result' => 'success',
                'response' => trim($result),
                'request' => $request_info,
            );
            return $res;
        }
    }
}
?>
