<?php
/**
* Класс исключений класса Requester
* @author Petr Bondarenko
* @see Requester
*/
class RequesterException extends Exception {}

/**
* Класс для работы с библиотекой cURL
* @author Petr Bondarenko
* @version 0.1
* @license http://opensource.org/licenses/bsd-license.php BSD License
*/
class Requester {
    /**
    * URL запроса
    * @var string
    */
    protected $_url;

    /**
    * Ассоциативный массив параметров запроса
    * @var array
    */
    protected $_params;

    /**
    * Метод запроса.
    * @var string
    */
    protected $_method;

    /**
    * DEBUG-режим
    * @var boolean
    */
    protected $_debug = false;

    /**
    * Таймаут соединения. Секунд.
    * @var int
    */
    protected $_cTimeout = 0;

    /**
    * Протокол соединения
    * @var string
    */
    protected $_protocol = CURLPROTO_HTTP;

    /**
    * Порт соединения
    * @var int
    */
    protected $_port = null;

    /**
    * Заголовок User-agent
    * @var string
    */
    protected $_useragent;

    /**
    * Конструктор класса
    * @param string $url Адрес URL на который будет отправлен запрос
    * @param array $params Ассоциативный массив параметров запроса
    * @param string $method Метод запроса
    * @see Requester::setUrl()
    * @see Requester::setParams()
    * @see Requester::setMethod()
    * @throws RequesterException
    */
    public function __construct($url = null, $params = array(), $method = null) {
        $this->_url = $url;
        
        //Если получен массив, то присваиваем значение, иначе выкидываем исключение
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
        //Если массив пустой, то возвращаем пустую строку
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
    * @throws RequesterException
    */
    public function request($header = false) {
        if ($this->_url === null) {
            throw new RequesterException('URL can not be empty');
        }
        if ($this->_method === null) {
            throw new RequesterException('No request method is specified');
        }
                
        //Инициализируем curl
        $curl = curl_init();

        //Получаем закодированную строку запроса
        $query_string = $this->getQueryString($this->_params);

        if (strtolower($this->_method) == 'post') {
            //Устанавливаем URL
            curl_setopt($curl, CURLOPT_URL, $this->_url);
            
            //Указываем метод POST
            curl_setopt($curl, CURLOPT_POST, 1);
            
            //Передаем список параметров запроса
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query_string);
        } else if (strtolower($this->_method) == 'get') {
            //Устанавливаем URL и конкейтим с параметрами запроса
            curl_setopt($curl, CURLOPT_URL, $this->_url . '?' . $query_string);

            //Сбрасываем метод запроса на GET
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
        } else {
            //Устанавливаем URL и конкейти с параметрами запроса
            curl_setopt($curl, CURLOPT_URL, $this->_url . '?' . $query_string);

            //Устанавливаем метод запроса, кроме GET/POST.
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->_method));
        }

        //Режим отображения заголовков в ответе
        curl_setopt($curl, CURLOPT_HEADER, $header);

        //Возвращать результат, а не выводить его
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        //Если от сервера получен код >400, то завершаем передачу и не выводим ответ
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);

        //Слудем заголовкам Location:
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        
        if ($this->_debug) {
            //Включаем PROGRESS
            curl_setopt($curl, CURLOPT_NOPROGRESS, 0);
        }

        //Устанавливаем таймаут для соединения
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->_cTimeout);

        //Устанавливаем протокол
        curl_setopt($curl, CURLOPT_PROTOCOLS, $this->_protocol);

        if ($this->_port !== null) {
            //Устанавливаем порт
            curl_setopt($curl, CURLOPT_PORT, $this->_port);
        }

        //Устанавливаем USER-AGENT
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_useragent);
        
        //Выполняем запрос
        $result = curl_exec($curl);
        
        //Закрываем соединение
        curl_close($curl);

        //Парамтеры запроса
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
