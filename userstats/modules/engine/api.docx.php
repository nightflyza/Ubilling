<?php

/**
 * DOCXTemplate class 0.1.10 by sergey.shuchkin@gmail.com
 * Replace {var} in MS Word 2007+ documents (*.docx)

  [test.tpl.docx]

  INVOICE {NUM}               Invoice date                    {COMPANY}
  {DATE}

  <?php // test.php

  include('docxtemplate.class.php');

  $docx = new DOCXTemplate('test.tpl.docx');
  $docx->set('NUM', 123456 );
  $docx->set('DATE', date('m.d.Y'));
  $docx->set('COMPANY', 'SIBVISION.RU
  Russian Federation, Omsk
  phone: +73812590554');

  $docx->saveAs('test.docx'); // or $docx->downloadAs('test.docx');

 */
class DOCXTemplate {

    private $data;
    private $package;
    private $error;

    public function __construct($template_filename, $is_data = false, $debug = false) {
        $this->data = array();
        $this->error = false;
        $this->debug = $debug;
        $this->_unzip($template_filename, $is_data);
    }

    public function set($var, $value = NULL) {

        if (is_array($var) || is_object($var)) {
            foreach ($var as $k => $v)
                $this->data[$k] = $v;
        } else {
            $this->data[$var] = $value;
        }
    }

    public function error($set = false) {
        if ($set) {
            $this->error = $set;
            if ($this->debug)
                trigger_error(__CLASS__ . ': ' . $set, E_USER_WARNING);
        } else {
            return $this->error;
        }
    }

    public function success() {
        return !$this->error;
    }

    private function _parse() {
        if ($doc = $this->getEntryData('word/document.xml')) {
            if (preg_match_all('/\{[^}]+\}/', $doc, $m)) {
                foreach ($m[0] as $v) {
                    $var = preg_replace('/[\s\{\}]/', '', strip_tags($v));
                    if (isset($this->data[$var])) {
                        if (is_scalar($this->data[$var]))
                            $doc = str_replace($v, $this->_esc($this->data[$var]), $doc);
                    } else {
                        $doc = str_replace($v, '{!404_' . __CLASS__ . '_' . $var . '}', $doc);
                    }
                }
            }
            $this->setEntryData('word/document.xml', $doc);
            return true;
        } else
            return false;
    }

    private function _unzip($filename, $is_data = false) {

        // Clear current file
        $this->datasec = array();

        if ($is_data) {

            $this->package['filename'] = 'default.xlsx';
            $this->package['mtime'] = time();
            $this->package['size'] = strlen($filename);

            $vZ = $filename;
        } else {

            if (!is_readable($filename)) {
                $this->error('File not found');
                return false;
            }

            // Package information
            $this->package['filename'] = $filename;
            $this->package['mtime'] = filemtime($filename);
            $this->package['size'] = filesize($filename);

            // Read file
            $oF = fopen($filename, 'rb');
            $vZ = fread($oF, $this->package['size']);
            fclose($oF);
        }
        // Cut end of central directory
        /* 		$aE = explode("\x50\x4b\x05\x06", $vZ);

          if (count($aE) == 1) {
          $this->error('Unknown format');
          return false;
          }
         */
        if (($pcd = strrpos($vZ, "\x50\x4b\x05\x06")) === false) {
            $this->error('Unknown format');
            return false;
        }
        $aE = array(
            0 => substr($vZ, 0, $pcd),
            1 => substr($vZ, $pcd + 3)
        );

        // Normal way
        $aP = unpack('x16/v1CL', $aE[1]);
        $this->package['comment'] = substr($aE[1], 18, $aP['CL']);

        // Translates end of line from other operating systems
        $this->package['comment'] = strtr($this->package['comment'], array("\r\n" => "\n", "\r" => "\n"));

        // Cut the entries from the central directory
        $aE = explode("\x50\x4b\x01\x02", $vZ);
        // Explode to each part
        $aE = explode("\x50\x4b\x03\x04", $aE[0]);
        // Shift out spanning signature or empty entry
        array_shift($aE);

        // Loop through the entries
        foreach ($aE as $vZ) {
            $aI = array();
            $aI['E'] = 0;
            $aI['EM'] = '';
            // Retrieving local file header information
//			$aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL', $vZ);
            $aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL/v1EFL', $vZ);

            // Check if data is encrypted
//			$bE = ($aP['GPF'] && 0x0001) ? TRUE : FALSE;
            $bE = false;
            $nF = $aP['FNL'];
            $mF = $aP['EFL'];

            // Special case : value block after the compressed data
            if ($aP['GPF'] & 0x0008) {
                $aP1 = unpack('V1CRC/V1CS/V1UCS', substr($vZ, -12));

                $aP['CRC'] = $aP1['CRC'];
                $aP['CS'] = $aP1['CS'];
                $aP['UCS'] = $aP1['UCS'];
                // 2013-08-10
                $vZ = substr($vZ, 0, -12);
                if (substr($vZ, -4) === "\x50\x4b\x07\x08")
                    $vZ = substr($vZ, 0, -4);
            }

            // Getting stored filename
            $aI['N'] = substr($vZ, 26, $nF);

            if (substr($aI['N'], -1) == '/') {
                // is a directory entry - will be skipped
                continue;
            }

            // Truncate full filename in path and filename
            $aI['P'] = dirname($aI['N']);
            $aI['P'] = $aI['P'] == '.' ? '' : $aI['P'];
            $aI['N'] = basename($aI['N']);

            $vZ = substr($vZ, 26 + $nF + $mF);

            if (strlen($vZ) != $aP['CS']) { // check only if availabled
                $aI['E'] = 1;
                $aI['EM'] = 'Compressed size is not equal with the value in header information.';
            } else {
                if ($bE) {
                    $aI['E'] = 5;
                    $aI['EM'] = 'File is encrypted, which is not supported from this class.';
                } else {
                    switch ($aP['CM']) {
                        case 0: // Stored
                            // Here is nothing to do, the file ist flat.
                            break;
                        case 8: // Deflated
                            $vZ = gzinflate($vZ);
                            break;
                        case 12: // BZIP2
                            if (!extension_loaded('bz2')) {
                                if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                                    @dl('php_bz2.dll');
                                } else {
                                    @dl('bz2.so');
                                }
                            }
                            if (extension_loaded('bz2')) {
                                $vZ = bzdecompress($vZ);
                            } else {
                                $aI['E'] = 7;
                                $aI['EM'] = "PHP BZIP2 extension not available.";
                            }
                            break;
                        default:
                            $aI['E'] = 6;
                            $aI['EM'] = "De-/Compression method {$aP['CM']} is not supported.";
                    }
                    if (!$aI['E']) {
                        if ($vZ === FALSE) {
                            $aI['E'] = 2;
                            $aI['EM'] = 'Decompression of data failed.';
                        } else {
                            if (strlen($vZ) != $aP['UCS']) {
                                $aI['E'] = 3;
                                $aI['EM'] = 'Uncompressed size is not equal with the value in header information.';
                            } else {
                                if (crc32($vZ) != $aP['CRC']) {
                                    $aI['E'] = 4;
                                    $aI['EM'] = 'CRC32 checksum is not equal with the value in header information.';
                                }
                            }
                        }
                    }
                }
            }

            $aI['D'] = $vZ;

            // DOS to UNIX timestamp
            $aI['T'] = mktime(($aP['FT'] & 0xf800) >> 11, ($aP['FT'] & 0x07e0) >> 5, ($aP['FT'] & 0x001f) << 1, ($aP['FD'] & 0x01e0) >> 5, ($aP['FD'] & 0x001f), (($aP['FD'] & 0xfe00) >> 9) + 1980);

            //$this->Entries[] = &new SimpleUnzipEntry($aI);
            $this->package['entries'][] = array(
                'data' => $aI['D'],
                'error' => $aI['E'],
                'error_msg' => $aI['EM'],
                'name' => $aI['N'],
                'path' => $aI['P'],
                'time' => $aI['T']
            );
        } // end for each entries
    }

    public function getPackage() {
        return $this->package;
    }

    public function entryExists($name) { // 0.6.6
        $dir = dirname($name);
        $name = basename($name);
        foreach ($this->package['entries'] as $entry)
            if ($entry['path'] == $dir && $entry['name'] == $name)
                return true;
        return false;
    }

    public function getEntryData($name) {
        $dir = dirname($name);
        $name = basename($name);
        foreach ($this->package['entries'] as $entry)
            if ($entry['path'] == $dir && $entry['name'] == $name)
                return $entry['data'];
        $this->error('Unknown format');
        return false;
    }

    public function setEntryData($name, $data) {
        $dir = dirname($name);
        $name = basename($name);
        foreach ($this->package['entries'] as $k => $entry)
            if ($entry['path'] == $dir && $entry['name'] == $name) {
                $this->package['entries'][$k]['data'] = $data;
                return;
            }
        return false;
    }

    private function _esc($s) {
        $s = str_replace(array('&', '"', "'", "<", ">"), array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'), $s);
        $s = str_replace(array("\r\n", "\r"), "\n", $s);
        $s = str_replace("\n", '</w:t><w:br/><w:t>', $s);
        return $s;
    }

    private function _zip($fh) {

        $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
        $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
        $dirSignatureE = "\x50\x4b\x05\x06"; // end of central dir signature

        $zipComments = 'Generated by ' . __CLASS__ . ' PHP class, thanks Sergey Shuchkin';

//		$fh = fopen( $filename, 'wb' );

        if (!$fh)
            return false;

        $cdrec = '';

        foreach ($this->package['entries'] as $e) {

            $cfilename = ($e['path']) ? $e['path'] . '/' . $e['name'] : $e['name'];


            $e['uncsize'] = strlen($e['data']);

            // if data to compress is too small, just store it
            if ($e['uncsize'] < 256) {
                $e['comsize'] = $e['uncsize'];
                $e['vneeded'] = 10;
                $e['cmethod'] = 0;
                $zdata = $e['data'];
            } else { // otherwise, compress it
                $zdata = gzcompress($e['data']);
                $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug (thanks to Eric Mueller)
                $e['comsize'] = strlen($zdata);
                $e['vneeded'] = 10;
                $e['cmethod'] = 8;
            }

            $e['bitflag'] = 0;
            $e['crc_32'] = crc32($e['data']);

            // Convert date and time to DOS Format, and set then
            $lastmod_timeS = str_pad(decbin(date('s') >= 32 ? date('s') - 32 : date('s')), 5, '0', STR_PAD_LEFT);
            $lastmod_timeM = str_pad(decbin(date('i')), 6, '0', STR_PAD_LEFT);
            $lastmod_timeH = str_pad(decbin(date('H')), 5, '0', STR_PAD_LEFT);
            $lastmod_dateD = str_pad(decbin(date('d')), 5, '0', STR_PAD_LEFT);
            $lastmod_dateM = str_pad(decbin(date('m')), 4, '0', STR_PAD_LEFT);
            $lastmod_dateY = str_pad(decbin(date('Y') - 1980), 7, '0', STR_PAD_LEFT);

            # echo "ModTime: $lastmod_timeS-$lastmod_timeM-$lastmod_timeH (".date("s H H").")\n";
            # echo "ModDate: $lastmod_dateD-$lastmod_dateM-$lastmod_dateY (".date("d m Y").")\n";
            $e['modtime'] = bindec("$lastmod_timeH$lastmod_timeM$lastmod_timeS");
            $e['moddate'] = bindec("$lastmod_dateY$lastmod_dateM$lastmod_dateD");

            $e['offset'] = ftell($fh);

            fwrite($fh, $zipSignature);
            fwrite($fh, pack('s', $e['vneeded'])); // version_needed
            fwrite($fh, pack('s', $e['bitflag'])); // general_bit_flag
            fwrite($fh, pack('s', $e['cmethod'])); // compression_method
            fwrite($fh, pack('s', $e['modtime'])); // lastmod_time
            fwrite($fh, pack('s', $e['moddate'])); // lastmod_date
            fwrite($fh, pack('V', $e['crc_32']));  // crc-32
            fwrite($fh, pack('I', $e['comsize'])); // compressed_size
            fwrite($fh, pack('I', $e['uncsize'])); // uncompressed_size
            fwrite($fh, pack('s', strlen($cfilename)));   // file_name_length
            fwrite($fh, pack('s', 0));  // extra_field_length
            fwrite($fh, $cfilename);    // file_name
            // ignoring extra_field
            fwrite($fh, $zdata);

            // Append it to central dir
            $e['external_attributes'] = (substr($cfilename, -1) == '/' && !$zdata) ? 16 : 32; // Directory or file name
            $e['comments'] = '';

            $cdrec .= $dirSignature;
            $cdrec .= "\x0\x0";                  // version made by
            $cdrec .= pack('v', $e['vneeded']); // version needed to extract
            $cdrec .= "\x0\x0";                  // general bit flag
            $cdrec .= pack('v', $e['cmethod']); // compression method
            $cdrec .= pack('v', $e['modtime']); // lastmod time
            $cdrec .= pack('v', $e['moddate']); // lastmod date
            $cdrec .= pack('V', $e['crc_32']);  // crc32
            $cdrec .= pack('V', $e['comsize']); // compressed filesize
            $cdrec .= pack('V', $e['uncsize']); // uncompressed filesize
            $cdrec .= pack('v', strlen($cfilename)); // file name length
            $cdrec .= pack('v', 0);                // extra field length
            $cdrec .= pack('v', strlen($e['comments'])); // file comment length
            $cdrec .= pack('v', 0); // disk number start
            $cdrec .= pack('v', 0); // internal file attributes
            $cdrec .= pack('V', $e['external_attributes']); // internal file attributes
            $cdrec .= pack('V', $e['offset']); // relative offset of local header
            $cdrec .= $cfilename;
            $cdrec .= $e['comments'];
        }
        $before_cd = ftell($fh);
        fwrite($fh, $cdrec);

        // end of central dir
        fwrite($fh, $dirSignatureE);
        fwrite($fh, pack('v', 0)); // number of this disk
        fwrite($fh, pack('v', 0)); // number of the disk with the start of the central directory
        fwrite($fh, pack('v', count($this->package['entries']))); // total # of entries "on this disk" 
        fwrite($fh, pack('v', count($this->package['entries']))); // total # of entries overall 
        fwrite($fh, pack('V', strlen($cdrec)));     // size of central dir 
        fwrite($fh, pack('V', $before_cd));         // offset to start of central dir
        fwrite($fh, pack('v', strlen($zipComments))); // .zip file comment length
        fwrite($fh, $zipComments);

        return true;
    }

    function saveAs($filename) {
        if ( !$this->_parse() ) return false;
        $fh = fopen($filename, 'wb');
        if ( !$fh ) return false;
        if (!$this->_zip($fh)) {
            fclose($fh);
            return false;
        }
        fclose($fh);
        return true;
    }

    function downloadAs( $filename, $exit = true ) {
        if ( !$this->_parse() ) return false;
        //php://stdin
        $fh = tmpfile();
        if ( !$fh ) return false;
        if ( !$this->_zip($fh) ) {
            fclose($fh);
            return false;
        }
        $size = ftell($fh);
        $filename = ($filename) ? $filename : gmdate('Ymdhi');
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
        header('Content-Length: ' . $size);
        fseek($fh, 0);
        echo fread($fh, $size);
        fclose($fh);
        if ( $exit ) exit();
        return true;
    }

}

?>