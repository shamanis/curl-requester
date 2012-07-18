Класс для работы с библиотекой cURL
===================================

Описание
--------
Класс-обертка для работы с библиотекой cURL. Очень удобен при работе с различными API, особенно если они выдают ответ в формате JSON. Применялся при разработке и реализации REG.APIv2 (интерфейс сайта reg.ru).

Использование
-------------
Пример использования:

    <?php
    require_once 'requester.php';

    $requester = new Requester();
    $result = $requester->setUrl('http://example.com')
                        ->setMethod('POST')
                        ->setParams(array(
                            'name' => 'John',
                            'surname' => 'Doe'
                        ))
                        ->request();
    
    if ($result['result'] == 'success') {
        echo $result['response'];
    } else {
        echo $result['error_detail']['error_text'];
    }
    ?>

Ответ формируется в виде ассоциативного массива.

Формат успешного ответа:

    array(
        'result' => 'success',
        'response' => [Ответ полученный от сервера],
        'request' => array(
            'url' => [URL на который был отправлен запрос],
            'method' => [Метод которым был отправлен запрос, например POST],
            'query_string' => [Строка параметров, переданных в запросе, например name=John&surname=Doe],
            'protocol' => [Используемый протокол, целое число],
            'alternate_port' => [Альтернативный порт, если назначался],
            'user_agent' => [Значение $this->_useragent],
        ),
    )

Формат ошибочного ответа:

    array(
        'result' => 'error',
        'error_detail' => array(
            'error_text' => [Текст ошибки, полученный от curl],
            'error_code' => [Код ошибки, полученный от curl],
        ),
        'request' => array(
            'url' => [URL на который был отправлен запрос],
            'method' => [Метод которым был отправлен запрос, например POST],
            'query_string' => [Строка параметров, переданных в запросе, например name=John&surname=Doe],
            'protocol' => [Используемый протокол, целое число],
            'alternate_port' => [Альтернативный порт, если назначался],
            'user_agent' => [Значение $this->_useragent],         
        ),
    )

Конструктор класса \_\_consruct($url = null, $params = array(), $method = null)
-------------------------------------------------------------------------------
Конструктор может принимать три параметра:

* `$url` - Строка, содержащая адрес URL, на который будет выполнен запрос

* `$params` - Ассоциативный массив параметров запроса

* `$method` - Строка, определяющая метод, которым будет выполнен запрос. Например POST, GET и т.д.

По-умолчанию эти параметры имеют значение `null`.

setUrl($url)
------------
Метод устанавливает значение адреса сервера `$this->_url`, на который будет выполнен запрос.

setMethod($method)
------------------
Метод устанавливает значение `$this->_method` - метод запроса.

setParams($params)
------------------
Метод устанавливает значение `$this->_params`. Ожидает получить ассоциативный массив.

setDebug($debug)
----------------
Метод устанавливает значение `$this->_debug`. Включает DEBUG-режим. При включении этого режима `CURLOPT_NOPROGRESS` переключается в `False`. По-умолчанию DEBUG-режим отключен.

setConnectTimeout($timeout)
---------------------------
Метод устанавливает значение `$this->_cTimeout`. Таймаут соединения в секундах. По-умолчанию равен 0, т.е. ожидание бесконечное.

setProtocol($protocol)
----------------------
Метод устанавливает значение `$this->_protocol`. Ожилает получить строку с названием протокола, например `HTTPS` и для него соответственно установит `CURLOPT_PROTOCOLS` в значение `CURLPROTO_HTTPS`. По-умолчанию `$this->_protocol` равен `CURLPROTO_HTTP`, что соответствует протоколу HTTP.

setPort($port)
--------------
Метод устанавливает значение `$this->_port`. Используется только в том случае, если используется нестандартный порт, например 8080 для протокола HTTP.

setUserAgent($ua)
-----------------
Метод устанавливает значение `$this->_useragent`. Ожидает получить строку. По-умолчанию равен `Curl/[version]`.

request($header = false)
------------------------
Метод выполняет запрос на сервер. Параметр $header включает заголовке в ответе. По-умолчанию заголовки выключенны.
