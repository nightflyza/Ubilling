<?php

class RosKomNadzor {
  // Приватные переменные:
  private $_soap;
  private $_getLastDumpDate;
  private $_getLastDumpDateEx;
  private $_sendRequest;
  private $_getResult;
  private $_requestPK;
  private $_resultPK;
  // Ключи UB-хранилища:
  const STORAGE_OPERATOR_KEY = 'RBS_OPERATOR';
  const STORAGE_PASSWORD_KEY = 'RBS_PASSWORD';
  const STORAGE_LASTSYNC_KEY = 'RBS_LASTSYNC';
  // WSDL:
  const WSDL = 'http://vigruzki.rkn.gov.ru/services/OperatorRequestTest/?wsdl';
  const REQUEST_FILE     = 'request.xml';
  const REQUEST_FILE_SIG = 'request.xml.sig';
  const REQUEST_DUMP_FORMAT_VERSION = '2.0';
  // Ключ POST-массива с данными об операторе:
  const FORM_OPERATOR = 'rbs_operator';
  // Пути к файлам
  const FILE_DUMP_ZIP         = './content/roskomnadzor/dump.zip';
  const FILE_OPENSSL_PEM      = './content/roskomnadzor/openssl.pem';
  const FILE_REQUEST_XML      = './content/roskomnadzor/request.xml';
  const FILE_REQUEST_XML_SIG  = './content/roskomnadzor/request.xml.sig';
  // Путь к OpenSSL с поддержкой ГОСТ:
  const PATH_OPENSSL = '/usr/local/bin/openssl';
  
  public function __construct() {
    $this->_soap = new SoapClient(self::WSDL);
    if ( !is_dir(DATA_PATH . 'roskomnadzor') )
      mkdir(DATA_PATH . 'roskomnadzor', 0777);
  }
    
  public function getLastDumpDate($var = null) {
    if ( !is_object($this->_getLastDumpDate))
      $this->_getLastDumpDate = $this->_soap->getLastDumpDate();
    return empty($var) ? $this->_getLastDumpDate : @$this->_getLastDumpDate->$var;
  }

  public function getLastDumpDateEx($var = null) {
    if ( !is_object($this->_getLastDumpDateEx))
      $this->_getLastDumpDateEx = $this->_soap->getLastDumpDateEx();
    return empty($var) ? $this->_getLastDumpDateEx : @$this->_getLastDumpDateEx->$var;
  }
    
  public function generateRequestXml() {
    // Извлекаем данные об операторе:
    $data = zb_StorageGet(self::STORAGE_OPERATOR_KEY);
    $data = base64_decode($data);
    $data = unserialize($data);
    if ( !empty($data) ) {
      // Если есть информация об операторе начинаем генерирование
      // XML-документа версии 1.0 в кодировке windows-1251 с дальнейшим
      // форматированием.
      $dom = new DOMDocument;
      $dom->version = '1.0';
      $dom->encoding = 'windows-1251';
      $dom->formatOutput = true;
      // Генерируем дерево xml-документа
      $root  = $dom->createElement('request');
      $child = $dom->createElement('requestTime', date('c'));
      $root->appendChild($child);
      foreach ( $data as $key => $value ) {
        $child = $dom->createElement($key, $value);
        $root->appendChild($child);
      }
      $dom->appendChild($root);
      // Записываем xml-документ в файл
      return file_put_contents(self::FILE_REQUEST_XML, $dom->saveXML());
    }
  }
    
  private function signRequestXml() {
    // Формируем отсоединенную ЭП файла запроса в формате PKCS#7:
    $command = self::PATH_OPENSSL . ' smime -sign -inkey ' . self::FILE_OPENSSL_PEM . ' -signer ' . self::FILE_OPENSSL_PEM . ' -outform pem -nodetach -in ' . self::FILE_REQUEST_XML . ' -out ' . self::FILE_REQUEST_XML_SIG;
    exec($command);
  }
    
  public function sendRequest($var = null) {
    if ( !is_object($this->_sendRequest) ) {
      $query = "
        SELECT
          `rbs_requests`.`id`,
          `rbs_requests`.`code`,
          `rbs_requests`.`requestStatus`,
          `rbs_requests`.`requestComment`
        FROM  `rbs_requests` 
        JOIN  `rbs_results` ON `rbs_results`.`requestID` = `rbs_requests`.`id`
        WHERE `rbs_results`.`resultStatusCode` = 0
      ";
      $result = simple_query($query);
      if ( !empty($result) ) {
        // Добавляем индекс для дальнейшей связи с результатом
        $this->_requestPK = $result['id'];
        // Если в БД есть запрос со статусом ответа = 0 вносим в
        // переменную запроса `code`, `result` и `resultComment` этого запроса
        $this->_sendRequest = (object) array(
          'code'          => $result['code'],
          'result'        => $result['requestStatus'],
          'resultComment' => $result['requestComment']
        );
      } else {
        // Иначе, генерируем новый xml-файл запроса, подписываем его и делаем
        // запрос в Роскомнадзор на получение новой выгрузки
        $this->generateRequestXml();
        $this->signRequestXml();
        $this->_sendRequest = $this->_soap->sendRequest(array(
          'requestFile'   => new SoapVar(file_get_contents(self::FILE_REQUEST_XML),     XSD_BASE64BINARY, 'xsd:base64Binary'),
          'signatureFile' => new SoapVar(file_get_contents(self::FILE_REQUEST_XML_SIG), XSD_BASE64BINARY, 'xsd:base64Binary'),
          'dumpFormatVersion' => '2.0'
        ));
        $this->_requestPK = $this->logRequest();
      }
    }
    // Если не запрошена конкретная переменная результата запроса - возвращаем
    // все, иначе возвращаем конкретную переменную.
    return is_null($var) ? $this->_sendRequest : @$this->_sendRequest->$var;
  }

  private function logRequest() {
    $code           = $this->sendRequest('code');
    $requestStatus  = $this->sendRequest('result');
    $requestComment = $this->sendRequest('resultComment');
    $query = "
      INSERT INTO `rbs_requests` (`code`, `requestStatus`, `requestComment`, `requestTime`)
      VALUES ('$code', '$requestStatus', '$requestComment', NOW())
    ";
    nr_query($query);
    return simple_get_lastid('rbs_requests');
  }
  
  public function getResult($var = null) {
    if ( !is_object($this->_getResult) ) {
      $code = $this->sendRequest('code');
      $this->_getResult = $this->_soap->getResult(array(
        'code' => new SoapVar($code, XSD_STRING, 'xsd:string')
      ));
      // Пишем результат в лог
      $this->_resultPK = $this->logResult();
    }
    // Если не запрошена конкретная переменная результата запроса - возвращаем
    // все, иначе возвращаем конкретную переменную.
    return is_null($var) ? $this->_getResult : @$this->_getResult->$var;
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
    
  public function showOperatorForm() {
    // Извлекаем уже сохраннённые данные:
    $data = zb_StorageGet(self::STORAGE_OPERATOR_KEY);
    $data = base64_decode($data);
    $data = unserialize($data);
    // Создаём форму:
    $operator = new InputForm(null, 'POST', __('Save'), null, null, 'multipart/form-data', self::FORM_OPERATOR, null);
    // Раздел информации об операторе связи:
    $operator->addmessage(__('Necessary information about operator'));
    // Полное наименование оператора связи:
    $contents = $operator->text_box(self::FORM_OPERATOR . '[operatorName]', @$data['operatorName'], 60, 0, false, null);
    $operator->addrow(__('operatorName'), $contents);
    // ИНН оператора связи (10 цифр для юридических лиц, 12 цифр для ИП):
    $contents = $operator->text_box(self::FORM_OPERATOR . '[inn]',   @$data['inn'],   30, 12, false, null);
    $operator->addrow(__('inn'), $contents);
    // ОГРН оператора связи (13 цифр для юридических лиц, 15 цифр для ИП):
    $contents = $operator->text_box(self::FORM_OPERATOR . '[ogrn]',  @$data['ogrn'],  30, 15, false, null);
    $operator->addrow(__('ogrn'), $contents);
    // Электронный адрес технического специалиста:
    $contents = $operator->text_box(self::FORM_OPERATOR . '[email]', @$data['email'], 30,  0, false, null);
    $operator->addrow(__('email'), $contents);
    // Раздел добавления сертификата:
    $operator->addmessage(__('Adding of new *.pem certificate'));
    // PEM-сертификат:
    $contents = $operator->file(self::FORM_OPERATOR . '[pem]');
    $operator->addrow(__('*.pem certificate'), $contents, 'middle', 'left');
    // Возвращаем готовую форму:
    return $operator->show(true);
  }
    
  public function submitOperatorForm($data) {
    $errors = array();
    foreach ( $data as $key => $value ) {
      if ( empty($value) )
        $errors[] = $key;
    }
    if ( empty($errors) ) {
      // Сохраняем данные об операторе:
      $data = serialize($data);
      $data = base64_encode($data);
      zb_StorageSet(self::STORAGE_OPERATOR_KEY, $data);
      // Сохраняем pem-сертификат:
      $error = $_FILES[self::FORM_OPERATOR]['error']['pem'];
      if ( $error === UPLOAD_ERR_OK ) {
        if ( !move_uploaded_file($_FILES[self::FORM_OPERATOR]['tmp_name']['pem'], self::FILE_OPENSSL_PEM) )
          show_window(__('Error'), __('Error while moving file from tmp-directory'));
      } elseif ( $error !== UPLOAD_ERR_NO_FILE) {
        show_window(__('Error'), __('Error while loading file to server. Code:') . ' ' . intval($_FILES[self::FORM_OPERATOR]['error']['pem']));
      }
    } else {
      $dom = new DOMDocument;
      $dom->formatOutput = true;
      // Генерируем дерево xml-документа
      $ul = $dom->createElement('ul');
      foreach ( $errors as $value ) {
        $li = $dom->createElement('li', __($value));
        $ul->appendChild($li);
      }
      $dom->appendChild($ul);
      show_window('<span style="color:red">' . __('Next fields were filled in incorrectly:') . '</span>', $dom->saveHTML());
    }
  }

  public function showLog() {
    // Заголовок таблицы
    $cells  = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('code'));
    $cells .= wf_TableCell(__('requestStatus'), '', '', 'colspan="2"');
    $cells .= wf_TableCell(__('requestTime'), 125);
    $cells .= wf_TableCell(__('resultStatus'), '', '', 'colspan="2"');
    $cells .= wf_TableCell(__('resultTime'), 125);
    $rows   = wf_TableRow($cells, 'row1');
    $query = "
      SELECT
        `rbs_requests`.`id`               AS `id`,
        `rbs_requests`.`code`             AS `code`,
        `rbs_requests`.`requestStatus`    AS `requestStatus`,
        `rbs_requests`.`requestComment`   AS `requestComment`,
        `rbs_requests`.`requestTime`      AS `requestTime`,
        
        `rbs_results`.`resultStatus`      AS `resultStatus`,
        `rbs_results`.`resultStatusCode`  AS `resultStatusCode`,
        `rbs_results`.`resultComment`     AS `resultComment`,
        `rbs_results`.`resultTime`        AS `resultTime` 
      FROM `rbs_requests`
 LEFT JOIN `rbs_results` ON `rbs_results`.`requestID` = `rbs_requests`.`id`
  ORDER BY `rbs_requests`.`id` DESC
      LIMIT 25
    ";
    $results = simple_queryall($query);
    if ( !empty($results) ) {
      foreach ( $results as $result ) {
        $cells  = wf_TableCell($result['id']);
        $cells .= wf_TableCell($result['code'], 250);
        
        $cells .= wf_TableCell(web_bool_led($result['requestStatus']), 16);
        $cells .= wf_TableCell('<i>' . $result['requestComment'] . '</i>');
        $cells .= wf_TableCell($result['requestTime'], 125);
        
        $cells .= wf_TableCell(web_bool_led($result['resultStatus']), 16);
        $cells .= wf_TableCell('<i>' . $result['resultComment'] . '</i>');
        $cells .= wf_TableCell($result['resultTime'], 125);
        $rows  .= wf_TableRow($cells, 'row3');
      }
    }
    // Возвращаем готовую таблицу
    return wf_TableBody($rows, '100%', '0', 'sortable');
  }
    
  public function run() {
    // Проверить, обновилась ли выгрузка из реестра. Для этого вызвать метод 
    // getLastDumpDateEx и сравнить полученное значение со значением, полученным на 
    // предыдущей итерации. В случае если значение lastDumpDateUrgently изменилось, то 
    // незамедлительно запросить обновленную выгрузку. В остальных случаях обновлять 
    // выгрузку на усмотрение, но не реже одного раза в сутки.
    $lastSync = zb_StorageGet(self::STORAGE_LASTSYNC_KEY);
    if ( $lastSync < $this->getLastDumpDateEx('lastDumpDateUrgently') ) {
      // В случае, если выгрузка обновилась, направить запрос на получение выгрузки с 
      // использованием метода sendRequest и получить в ответ код запроса
      if ( $this->sendRequest('result') == true ) {
        // Через несколько минут для получения результата обработки запроса вызвать метод
        // getResult с кодом, полученным на этапе 2. Данный метод необходимо опрашивать с 
        // определенным интервалом (1-2 минуты) до тех пор, пока значение resultCode равно нулю. 
        // При получении ненулевого значения запрос результата по данному коду необходимо 
        // прекратить, так как будет либо получена выгрузка, либо код ошибки.
        if ( $this->getResult('result') == true ) {
          // Записываем полученный от Роскомнадзора zip-архив во временный файл
          // и пытаемся открыть его для парсинга выгрузки
          $tmp = tempnam(sys_get_temp_dir(), 'tmp_');
          file_put_contents($tmp, $this->getResult('registerZipArchive'));
          $zip = new ZipArchive;
          if ( $zip->open($tmp) === true ) {
            // Чистим БД от старых записей
            nr_query("TRUNCATE `rbs_banned`");
            nr_query("TRUNCATE `rbs_banned_ips`");
            nr_query("TRUNCATE `rbs_banned_urls`");
            nr_query("TRUNCATE `rbs_banned_domains`");
            nr_query("TRUNCATE `rbs_banned_ipSubnets`");
            nr_query("TRUNCATE `rbs_banned_decisions`");
            // Загружаем DomDocument, открываем извлечённый xml-документ
            // и вносим данные из xml-документа в базу данных
            $dom = new DOMDocument;
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
              foreach ( $content->getElementsByTagName('url') as $url ) {
                nr_query("INSERT INTO `rbs_banned_urls` VALUES(
                  NULL,
                  '" . $content->getAttribute('id') . "',
                  '" . $url->nodeValue . "'
                )");
              }
              // Добавляем доменные имена
              foreach ( $content->getElementsByTagName('domain') as $domain ) {
                nr_query("INSERT INTO `rbs_banned_domains` VALUES(
                  NULL,
                  '" . $content->getAttribute('id') . "',
                  '" . $domain->nodeValue . "'
                )");
              }
              // Добавляем IP-адреса
              foreach ( $content->getElementsByTagName('ip') as $ip ) {
                nr_query("INSERT INTO `rbs_banned_ips` VALUES(
                  NULL,
                  '" . $content->getAttribute('id') . "',
                  '" . $ip->nodeValue . "'
                )");
              }
              // Добавляем IP-подсети
              foreach ( $content->getElementsByTagName('ipSubnet') as $ipSubnet ) {
                nr_query("INSERT INTO `rbs_banned_ipSubnets` VALUES(
                  NULL,
                  '" . $content->getAttribute('id') . "',
                  '" . $ipSubnet->nodeValue . "'
                )");
              }
            }
            $zip->close();
            // Обновляем время последней синхронизации
            zb_StorageSet(self::STORAGE_LASTSYNC_KEY, $this->getLastDumpDateEx('lastDumpDateUrgently'));
            echo 'OK:SYNCHRONIZED';
          } else echo 'ERROR:CANT_OPEN_ZIP_ARCHIVE';
          // Удаляем временный файл
          unlink($tmp);
        } else echo 'INFO:RESULT_IS_NOT_READY_YET';
      } else echo 'ERROR:SEND_REQUEST_ERROR';
    } else echo 'INFO:UNNECESSARY';
  }
}