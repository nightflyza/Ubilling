<?php

/**
 * Minimal XLSX writer without external dependencies
 */
class XLSX {

    /**
     * XLSX MIME type
     */
    const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Worksheet name
     *
     * @var string
     */
    protected $sheetName = 'Sheet1';

    /**
     * Header cell values
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Data rows as list of cell value arrays
     *
     * @var array
     */
    protected $rows = array();

    /**
     * Zero-based column indexes treated as numbers
     *
     * @var array
     */
    protected $numberColumns = array();

    /**
     * Sets worksheet name
     *
     * @param string $sheetName
     *
     * @return void
     */
    public function setSheetName($sheetName) {
        $sheetName = trim($sheetName);
        if (!empty($sheetName)) {
            $this->sheetName = $sheetName;
        }
    }

    /**
     * Sets header row values
     *
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders($headers) {
        $this->headers = array();
        if (!empty($headers)) {
            foreach ($headers as $io => $value) {
                $this->headers[] = $value;
            }
        }
    }

    /**
     * Sets zero-based column indexes that should be stored as numbers
     *
     * @param array $columnIndexes
     *
     * @return void
     */
    public function setNumberColumns($columnIndexes) {
        $this->numberColumns = array();
        if (!empty($columnIndexes)) {
            foreach ($columnIndexes as $io => $index) {
                $this->numberColumns[intval($index)] = 1;
            }
        }
    }

    /**
     * Appends one data row
     *
     * @param array $cells
     *
     * @return void
     */
    public function addRow($cells) {
        $row = array();
        if (!empty($cells)) {
            foreach ($cells as $io => $value) {
                $row[] = $value;
            }
        }
        $this->rows[] = $row;
    }

    /**
     * Replaces all data rows
     *
     * @param array $rows
     *
     * @return void
     */
    public function setRows($rows) {
        $this->rows = array();
        if (!empty($rows)) {
            foreach ($rows as $io => $cells) {
                $this->addRow($cells);
            }
        }
    }

    /**
     * Escapes value for XML inline string cell
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeXmlValue($value) {
        $result = htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
        return ($result);
    }

    /**
     * Converts zero-based column index to Excel column letters
     *
     * @param int $index
     *
     * @return string
     */
    protected function columnLetter($index) {
        $result = '';
        $index = intval($index);
        do {
            $result = chr(65 + ($index % 26)) . $result;
            $index = floor($index / 26) - 1;
        } while ($index >= 0);
        return ($result);
    }

    /**
     * Builds one XLSX cell XML
     *
     * @param string $col
     * @param int $rowNum
     * @param mixed $value
     * @param bool $asNumber
     *
     * @return string
     */
    protected function buildCell($col, $rowNum, $value, $asNumber = false) {
        $result = '';
        $ref = $col . $rowNum;
        if ($asNumber and ($value !== '') and ($value !== null) and is_numeric($value)) {
            $result = '<c r="' . $ref . '"><v>' . $value . '</v></c>';
        } else {
            if (($value === '') or ($value === null)) {
                $result = '<c r="' . $ref . '"/>';
            } else {
                $result = '<c r="' . $ref . '" t="inlineStr"><is><t>' . $this->escapeXmlValue($value) . '</t></is></c>';
            }
        }
        return ($result);
    }

    /**
     * Builds one XLSX row XML from cell values
     *
     * @param array $cells
     * @param int $rowNum
     *
     * @return string
     */
    protected function buildRow($cells, $rowNum) {
        $xml = '';
        if (!empty($cells)) {
            foreach ($cells as $colIndex => $value) {
                $asNumber = false;
                if (isset($this->numberColumns[$colIndex])) {
                    $asNumber = true;
                }
                $xml .= $this->buildCell($this->columnLetter($colIndex), $rowNum, $value, $asNumber);
            }
        }
        $result = '<row r="' . $rowNum . '">' . $xml . '</row>';
        return ($result);
    }

    /**
     * Builds worksheet sheetData XML
     *
     * @return string
     */
    protected function buildSheetData() {
        $result = '';
        $rowNum = 1;
        if (!empty($this->headers)) {
            $result .= $this->buildRow($this->headers, $rowNum);
            $rowNum++;
        }
        if (!empty($this->rows)) {
            foreach ($this->rows as $io => $cells) {
                $result .= $this->buildRow($cells, $rowNum);
                $rowNum++;
            }
        }
        return ($result);
    }

    /**
     * Saves workbook as XLSX file
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function save($filePath) {
        $result = false;
        $sheetName = $this->escapeXmlValue($this->sheetName);

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<sheetData>' . $this->buildSheetData() . '</sheetData>';
        $sheetXml .= '</worksheet>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $workbookXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $workbookXml .= '<sheets><sheet name="' . $sheetName . '" sheetId="1" r:id="rId1"/></sheets>';
        $workbookXml .= '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $workbookRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $workbookRels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $workbookRels .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $workbookRels .= '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $rootRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rootRels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rootRels .= '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $contentTypes .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $contentTypes .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $contentTypes .= '<Default Extension="xml" ContentType="application/xml"/>';
        $contentTypes .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $contentTypes .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $contentTypes .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $contentTypes .= '</Types>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $stylesXml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $stylesXml .= '<fonts count="1"><font/></fonts>';
        $stylesXml .= '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>';
        $stylesXml .= '<borders count="1"><border/></borders>';
        $stylesXml .= '<cellStyleXfs count="1"><xf/></cellStyleXfs>';
        $stylesXml .= '<cellXfs count="1"><xf xfId="0"/></cellXfs>';
        $stylesXml .= '</styleSheet>';

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE) === true) {
            $zip->addFromString('[Content_Types].xml', $contentTypes);
            $zip->addFromString('_rels/.rels', $rootRels);
            $zip->addFromString('xl/workbook.xml', $workbookXml);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
            $zip->addFromString('xl/styles.xml', $stylesXml);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->close();
            if (file_exists($filePath)) {
                $result = true;
            }
        }
        return ($result);
    }

}
