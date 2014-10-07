<?php

class RosKomNadzor {
  // Приватные переменные:
  private $_soap;
  private $_getLastDumpDate;
  private $_getLastDumpDateEx;
  private $_sendRequest;
  private $_getResult;
  private $_isUrgent;
  // Ключи записай логов:
  private $_requestPK;
  private $_resultPK;
  // Ключ POST-массива:
  const FORM_NAME = 'rbs_operator';
  // Ключи UB-хранилища:
  const STORAGE_OPERATOR_KEY = 'RBS_OPERATOR';
  const STORAGE_LASTSYNC_KEY = 'RBS_LASTSYNC';
  // WSDL:
  const WSDL = 'http://vigruzki.rkn.gov.ru/services/OperatorRequest/?wsdl';
  // Пути к файлам
  const FILE_DUMP_ZIP     = './content/roskomnadzor/dump.zip';
  const FILE_OPENSSL_PEM  = './content/roskomnadzor/openssl.pem';
  const FILE_REQUEST_XML  = './content/roskomnadzor/request.xml';
  const FILE_REQUEST_SIG  = './content/roskomnadzor/request.xml.sig';
  // Путь к OpenSSL с поддержкой ГОСТ:
  const PATH_OPENSSL = '/usr/local/bin/openssl';

  public function __construct() {
    $this->_soap = new SoapClient(self::WSDL);
    if ( !is_dir(DATA_PATH . 'roskomnadzor') )
      mkdir(DATA_PATH . 'roskomnadzor', 0777);
  }
    
  private function getLastDumpDate($var = null) {
    if ( !is_object($this->_getLastDumpDate))
      $this->_getLastDumpDate = $this->_soap->getLastDumpDate();
    return empty($var) ? $this->_getLastDumpDate : @$this->_getLastDumpDate->$var;
  }

  private function getLastDumpDateEx($var = null) {
    if ( !is_object($this->_getLastDumpDateEx))
      $this->_getLastDumpDateEx = $this->_soap->getLastDumpDateEx();
    return empty($var) ? $this->_getLastDumpDateEx : @$this->_getLastDumpDateEx->$var;
  }
  
  private function sendRequest($var = null) {
    if ( !is_object($this->_sendRequest) ) {
      $query = "
        SELECT
          `rbs_requests`.`id`,
          `rbs_requests`.`code`,
          `rbs_requests`.`requestStatus`,
          `rbs_requests`.`requestComment`
        FROM  `rbs_requests` 
        JOIN  `rbs_results` ON `rbs_results`.`requestID` = `rbs_requests`.`id`
        WHERE `rbs_results`.`resultStatusCode` = '0'
      ";
      $result = simple_query($query);
      if ( !empty($result) ) {
        // Если в БД есть запрос со статусом ответа = '0' вносим в
        // переменную запроса `code`, `result` и `resultComment` этого запроса
        $this->_sendRequest = (object) array(
          'code'          => $result['code'],
          'result'        => $result['requestStatus'],
          'resultComment' => $result['requestComment']
        );
        // Запоминаем индекс для дальнейшей связи с результатом
        $this->_requestPK = $result['id'];
      } else {
        // Иначе, генерируем новый xml-файл запроса, подписываем его и делаем
        // запрос в Роскомнадзор на получение новой выгрузки
        $request = $this->generateRequestXml();
        $this->_sendRequest = $this->_soap->sendRequest(array(
          'requestFile'   => new SoapVar($request['xml'], XSD_BASE64BINARY, 'xsd:base64Binary'),
          'signatureFile' => new SoapVar($request['sig'], XSD_BASE64BINARY, 'xsd:base64Binary'),
          'dumpFormatVersion' => '2.0'
        ));
        // Пишем результат в лог и запоминаем индекс для дальнейшей связи с
        // результатом
        $this->_requestPK = $this->logRequest();
      }
    }
    // Если не запрошена конкретная переменная результата запроса - возвращаем
    // все, иначе возвращаем конкретную переменную.
    return is_null($var) ? $this->_sendRequest : @$this->_sendRequest->$var;
  }
  
  private function getResult($var = null) {
    if ( !is_object($this->_getResult) ) {
      $code = $this->sendRequest('code');
      $this->_getResult = $this->_soap->getResult(array(
        'code' => new SoapVar($code, XSD_STRING, 'xsd:string')
      ));
      // Пишем результат в логи запоминаем индекс
      $this->_resultPK = $this->logResult();
    }
    // Если не запрошена конкретная переменная результата запроса - возвращаем
    // все, иначе возвращаем конкретную переменную.
    return is_null($var) ? $this->_getResult : @$this->_getResult->$var;
  }
  
  private function generateRequestXml() {
    // Извлекаем данные об операторе:
    $data = zb_StorageGet(self::STORAGE_OPERATOR_KEY);
    $data = base64_decode($data);
    $data = unserialize($data);
    if ( !empty($data) ) {
      // Если есть информация об операторе начинаем генерирование
      // XML-документа версии 1.0 в кодировке windows-1251 с дальнейшим
      // форматированием.
      $dom = new DOMDocument();
      $dom->version = '1.0';
      $dom->encoding = 'windows-1251';
      $dom->formatOutput = true;
      // Генерируем дерево xml-документа
      $root  = $dom->createElement('request');
      $child = $dom->createElement('requestTime', date('c'));
      $root->appendChild($child);
      foreach ( $data as $key => $value ) {
        if ( empty($value) ) 
          throw new Exception ("Невозможно сформировать <i>request.xml</i>: поле `" . __($key) . "` не заполнено!");
        $child = $dom->createElement($key, $value);
        $root->appendChild($child);
      }
      $dom->appendChild($root);
      // Если удачно записываем xml-документ в файл - формируем отсоединенную ЭП
      // файла запроса в формате PKCS#7
      if ( file_put_contents(self::FILE_REQUEST_XML, $dom->saveXML()) !== false ) {
        if ( !file_exists(self::FILE_OPENSSL_PEM) )
          throw new Exception("Невозможно подписать <i>request.xml</i>: отсутствует сертификат и приватный ключ!");
        exec(self::PATH_OPENSSL . ' smime -sign -inkey ' . self::FILE_OPENSSL_PEM . ' -signer ' . self::FILE_OPENSSL_PEM . ' -outform pem -nodetach -in ' . self::FILE_REQUEST_XML . ' -out ' . self::FILE_REQUEST_SIG);
      } else throw new Exception("Невозможно записать файл <i>request.xml</i>!");
      // Возвращаем содержимое файлов
      return array(
        'xml' => file_get_contents(self::FILE_REQUEST_XML),
        'sig' => file_get_contents(self::FILE_REQUEST_SIG)
      );
    } else throw new Exception("Невозможно сформировать <i>request.xml</i>: информация об операторе не заполнена!");
  }
  
  private function logRequest() {
    $code           = $this->sendRequest('code');
    $requestStatus  = $this->sendRequest('result');
    $requestComment = $this->sendRequest('resultComment');
    $query = "
      INSERT INTO `rbs_requests` (`code`, `requestStatus`, `requestComment`, `requestTime`, `isUrgent`)
      VALUES ('$code', '$requestStatus', '$requestComment', NOW(), '$this->_isUrgent')
    ";
    nr_query($query);
    return simple_get_lastid('rbs_requests');
  }
    
  private function logResult() {
    $resultStatus     = $this->getResult('result');
    $resultStatusCode = $this->getResult('resultCode');
    $resultComment    = $this->getResult('resultComment');
    switch ( $resultStatusCode ) {
      case 1:   $resultComment = 'запрос обработан успешно';          break;
      case 0:   $resultComment = 'запрос обрабатывается';             break;
      case -1:  $resultComment = 'неверный алгоритм ЭП';              break;
      case -2:  $resultComment = 'неверный формат ЭП';                break;
      case -3:  $resultComment = 'недействительный сертификат ЭП';    break;
      case -4:  $resultComment = 'некорректное значение ЭП';          break;
      case -5:  $resultComment = 'ошибка проверки сертификата ЭП';    break;
      case -6:  $resultComment = 'у заявителя отсутствует лицензия';  break;
      case -7:  $resultComment = 'отсутствует идентификатор запроса'; break;
      case -8:  $resultComment = 'неверный формат ID запроса';        break;
      case -9:  $resultComment = 'не найден запрос по указанному ID'; break;
      case -10: $resultComment = 'повторите запрос позднее';          break;
    }
    $query = "
      INSERT INTO `rbs_results` (`requestID`, `resultStatus`, `resultStatusCode`, `resultComment`, `resultTime`)
      VALUES('$this->_requestPK', '$resultStatus', '$resultStatusCode', '$resultComment', NOW())
      ON DUPLICATE KEY UPDATE `resultStatus` = '$resultStatus', `resultStatusCode` = '$resultStatusCode', `resultComment` = '$resultComment', `resultTime` = NOW()
    ";
    nr_query($query);
    return simple_get_lastid('rbs_results');
  }
  
  public function run() {
    // Проверить, обновилась ли выгрузка из реестра. Для этого вызвать метод 
    // getLastDumpDateEx и сравнить полученное значение со значением, полученным на 
    // предыдущей итерации. В случае если значение lastDumpDateUrgently изменилось, то 
    // незамедлительно запросить обновленную выгрузку. В остальных случаях обновлять 
    // выгрузку на усмотрение, но не реже одного раза в сутки.
    // P.S. Для Ubilling`а "своё усмотрение" - это обновить немедленно!
    $lastSync = zb_StorageGet(self::STORAGE_LASTSYNC_KEY);
    try {
      if ( ( $this->_isUrgent = $lastSync < $this->getLastDumpDateEx('lastDumpDateUrgently') ) || $lastSync < $this->getLastDumpDateEx('lastDumpDate') ) {
        // В случае, если выгрузка обновилась, направить запрос на получение выгрузки с 
        // использованием метода sendRequest и получить в ответ код запроса
        if ( $this->sendRequest('result') == true ) {
          // Через несколько минут для получения результата обработки запроса вызвать метод
          // getResult с кодом, полученным на этапе 2. Данный метод необходимо опрашивать с 
          // определенным интервалом (1-2 минуты) до тех пор, пока значение resultCode равно нулю. 
          // При получении ненулевого значения запрос результата по данному коду необходимо 
          // прекратить, так как будет либо получена выгрузка, либо код ошибки.
          // P.S. Ubilling не ждёт, после отправки запроса он сразу пытается получить ответ...
          if ( $this->getResult('result') == true ) {
            // Записываем полученный от Роскомнадзора zip-архив во временный
            // файл
            $tmp = tempnam(sys_get_temp_dir(), 'rbs_');
            file_put_contents($tmp, $this->getResult('registerZipArchive'));
            try {
              $zip = new ZipArchive();
              $resource = $zip->open($tmp);
              if ( $resource === true ) {
                // Чистим БД от старых записей
                nr_query("TRUNCATE `rbs_banned`");
                nr_query("TRUNCATE `rbs_banned_ips`");
                nr_query("TRUNCATE `rbs_banned_urls`");
                nr_query("TRUNCATE `rbs_banned_domains`");
                nr_query("TRUNCATE `rbs_banned_ipSubnets`");
                nr_query("TRUNCATE `rbs_banned_decisions`");
                // Инициируем DomDocument, открываем извлечённый xml-документ
                // и вносим данные из xml-документа в базу данных
                $dom = new DOMDocument();
                $dom->loadXML( $zip->getFromName('dump.xml') );
                foreach ( $dom->documentElement->getElementsByTagName('content') as $content ) {
                  // Добавляем данные о записях, подлежащих блокировке:
                  //  * Уникальный идентификатор записи в Роскомнадзоре;
                  //  * Момент времени, с которого возникает необходимость
                  //    ограничения доступа;
                  //  * Тип срочности реагирования;
                  //  * Код типа реестра;
                  nr_query("INSERT INTO `rbs_banned` VALUES(
                    '" . $content->getAttribute('id') . "',
                    '" . $content->getAttribute('includeTime') . "',
                    '" . $content->getAttribute('urgencyType') . "',
                    '" . $content->getAttribute('entryType') . "'
                  )");
                  // Вносим реквизиты решения о необходимости ограничения доступа
                  //  * Дата решения;
                  //  * Номер решения;
                  //  * Орган, принявший решение;
                  foreach ( $content->getElementsByTagName('decision') as $decision ) {
                    nr_query("INSERT INTO `rbs_banned_decisions` VALUES(
                      NULL,
                      '" . $content->getAttribute('id') . "',
                      '" . $decision->getAttribute('date') . "',
                      '" . $decision->getAttribute('number') . "',
                      '" . $decision->getAttribute('org')    . "'
                    )");
                  }
                  // Добавляем указатели страниц сайтов
                  foreach ( $content->getElementsByTagName('url') as $url )
                    nr_query("INSERT INTO `rbs_banned_urls` VALUES(NULL, '" . $content->getAttribute('id') . "', '" . $url->nodeValue . "')");
                  // Добавляем доменные имена
                  foreach ( $content->getElementsByTagName('domain') as $domain )
                    nr_query("INSERT INTO `rbs_banned_domains` VALUES(NULL, '" . $content->getAttribute('id') . "', '" . $domain->nodeValue . "')");
                  // Добавляем IP-адреса
                  foreach ( $content->getElementsByTagName('ip') as $ip )
                    nr_query("INSERT INTO `rbs_banned_ips` VALUES(NULL, '" . $content->getAttribute('id') . "', '" . $ip->nodeValue . "' )");
                  // Добавляем IP-подсети
                  foreach ( $content->getElementsByTagName('ipSubnet') as $ipSubnet )
                    nr_query("INSERT INTO `rbs_banned_ipSubnets` VALUES(NULL, '" . $content->getAttribute('id') . "', '" . $ipSubnet->nodeValue . "')");
                }
                // Закрываем zip-архив
                $zip->close();
                // Обновляем время последней синхронизации
                zb_StorageSet(self::STORAGE_LASTSYNC_KEY, time() * 1000);
              } else throw new Exception("Couldn't open zip-archive, code: " . $resource);
            } catch ( Exception $ex ) {
              throw $ex;
            }
            // Удаляем временный файл
            unlink($tmp);
          } else throw new Exception ("Невозможно получить результат: " . $this->getResult('resultComment'));
        } else throw new Exception ("Невозможно отправить запрос: " . $this->sendRequest('resultComment'));
      } else throw new Exception ("Обновление пока не требуется...");
    } catch ( Exception $ex ) {
      print $ex->getMessage();
    }
  }
}