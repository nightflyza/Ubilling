<?php
/**
 * Print card management API
 */

/**
 * Returns print card lister with some controls
 *
 * @return string
 */
const IMG_CARD = 'content/documents/card_print/card_print.jpg';
const IMG_CARD_TEMPLATE = 'content/documents/card_print/card_print_template.jpg';
const PRINT_TEMPLATE = 'config/printablecards.tpl';

function web_PrintCardLister($ids) {
    $cards = zb_GetCardByIds($ids);

    $cells = wf_TableCell(__('Serial number'));
    $cells.= wf_TableCell(__('Price'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($cards)) {
        foreach ($cards as $row) {
            $cells = wf_TableCell($row['serial']);
            $cells.= wf_TableCell($row['cash']);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', 0);
    $idsQuery = http_build_query(array('id' => $ids));
    if (file_exists(IMG_CARD)) {
        $printcheck = wf_tag('a', false, '', 'href="#" onClick="window.open(\'?module=printcards&action=page&'.$idsQuery.'\',\'checkwindow\',\'scrollbars=yes,width=800,height=600\')"');
        $printcheck.= wf_img_sized('skins/icon_edit.gif', __('Page'), 24, 24);
        $printcheck.= wf_tag('a', true);
        $printcheck.= wf_tag('a', false, '', 'href="#" onClick="window.open(\'?module=printcards&action=print&'.$idsQuery.'\',\'checkwindow\',\'scrollbars=yes,width=800,height=600\')"');
        $printcheck.= wf_img_sized('skins/icon_documents.gif', __('Print'), 24, 24);
        $printcheck.= wf_tag('a', true);
    } else {
        $printcheck = wf_Link("?module=printcards&action=setting", wf_img_sized('skins/icon_edit.gif', __('Page'), 24, 24));
        $printcheck.= wf_Link("?module=printcards&action=setting", wf_img_sized('skins/icon_documents.gif', __('Page'), 24, 24));
    }

    $printcheck.= wf_SubmitClassed(true, 'back', 'back', __('Back'));

    $result = wf_Form('?module=cards', 'POST', $result.$printcheck, 'glamour');

    return ($result);
}

/**
 * Returns print card creation form
 *
 * @return string
 */
function web_PrintCardCreateForm() {
    $messages = new UbillingMessageHelper();
    if (!file_exists(IMG_CARD)) {
        return web_UploadFileForm();
    }

    $image = IMG_CARD;
    if (file_exists(IMG_CARD_TEMPLATE)) {
        $image = IMG_CARD_TEMPLATE;
    }

    $sup = wf_tag('sup').'*'.wf_tag('sup', true);
    $form = wf_img($image).'<br/>';
    $form.= $messages->getStyledMessage(__('Available macroses').__(': <b>{number} {serial} {sum}</b>'), 'info').wf_tag('br/', false);

    $printCardData = zb_SelectAllPrintCardData();

    $cells = wf_TableCell(__('Parameter'));
    $cells.= wf_TableCell(__('Color'));
    $cells.= wf_TableCell(__('Font size'));
    $cells.= wf_TableCell(__('Top'));
    $cells.= wf_TableCell(__('Left'));
    $cells.= wf_TableCell(__('Text'));
    $rows = wf_TableRow($cells, 'row1');

    foreach ($printCardData as $row) {
        $cells = wf_TableCell(__($row['title']));
        $cells.= wf_TableCell(wf_TextInput('print_card['.$row['field'].'][color]', $sup, $row['color'], false, 12));
        $cells.= wf_TableCell(wf_TextInput('print_card['.$row['field'].'][font_size]', $sup, $row['font_size'], false, 3));
        $cells.= wf_TableCell(wf_TextInput('print_card['.$row['field'].'][top]', $sup, $row['top'], false, 3));
        $cells.= wf_TableCell(wf_TextInput('print_card['.$row['field'].'][left]', $sup, $row['left'], false, 3));
        $cells.= wf_TableCell(wf_TextInput('print_card['.$row['field'].'][text]', $sup, $row['text'], true));
        $rows.= wf_TableRow($cells, 'row3');
    }

    $iputs = wf_SubmitClassed(true, 'save', 'save', __('Save'));
    $iputs.= wf_SubmitClassed(true, 'delete', 'delete', __('Delete'));
    $iputs.= wf_SubmitClassed(true, 'back', 'back', __('Back'));
    $rows.= wf_TableRow(wf_TableCell($iputs));

    $result = wf_TableBody($rows, '100%', 0);

    $form.= wf_Form('', 'POST', $result, '');

    return ($form);
}

function web_UploadFileForm() {
    $uploadInputs = wf_HiddenInput('upload', 'true');
    $uploadInputs.= __('File').' <input id="fileselector" type="file" name="filename" size="10" /><br>';
    $uploadInputs.= wf_Submit('Upload');

    $uploadForm = '<form action="" method="POST" class="glamour" enctype="multipart/form-data">
        '.$uploadInputs.'
        </form>
        <div style="clear:both;"></div>
    ';

    return ($uploadForm);
}

function web_UploadFileCopy($tmpName) {
    move_uploaded_file($tmpName, IMG_CARD);
}

function web_CreateTemplateCardPrint() {
    $printCardData = zb_SelectAllPrintCardData();
    $generateCard = new GenerateCard(IMG_CARD);
    $generateCard
        ->createStringForImage($printCardData)
        ->saveImage(IMG_CARD_TEMPLATE);
}

function web_DeleteImege() {
    @unlink(IMG_CARD);
    @unlink(IMG_CARD_TEMPLATE);
}

function web_PageCard($ids) {
    $cardList = web_GenerateImages($ids);
    return (web_ParsePrintable($cardList));
}

function web_GenerateImages($ids) {
    $filePathList = array();
    web_ClearDirForGenerate();
    $cards = zb_GetCardByIds($ids);
    $printCardDataFormat = web_PrintCardDataFormatForGenerate(zb_SelectAllPrintCardData());

    foreach ($cards as $card) {
        $printCardData = $printCardDataFormat;
        $printCardData['number']['text'] = str_replace('{number}', $card['serial'], $printCardData['number']['text']);
        $printCardData['serial']['text'] = str_replace('{serial}', $card['part'], $printCardData['serial']['text']);
        $printCardData['rating']['text'] = str_replace('{sum}', $card['cash'], $printCardData['rating']['text']);

        $filePath = sprintf('content/documents/card_print/tmp/%s.jpg', $card['serial']);
        $generateCard = new GenerateCard(IMG_CARD);
            $generateCard
                ->createStringForImage($printCardData)
                ->saveImage($filePath);
        array_push($filePathList, $filePath);
    }

    return ($filePathList);
}

function web_PrintCardDataFormatForGenerate($printCards) {
    $rc = array();
    foreach ($printCards as $printCard) {
        if (count(array_filter($printCard)) !== count($printCard)) {
            continue;
        }
        $rc[$printCard['field']] = $printCard;
    }

    return ($rc);
}

function web_ClearDirForGenerate() {
    $dir = 'content/documents/card_print/tmp';
    removeRmdir($dir);
    mkdir($dir);
}

function removeRmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") {
                    removeRmdir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * @return array|string
 */
function zb_SelectAllPrintCardData() {
    $query = "SELECT * FROM `print_card` ORDER BY `id` ASC";
    $allData = simple_queryall($query);
    $allData = !empty($allData) ? $allData : array();

    return ($allData);
}

/**
 * @param $printCardData
 */
function zb_SaveCardPrint($printCardData) {
    foreach ($printCardData as $key => $row) {
        $field = mysql_real_escape_string($key);
        $text = mysql_real_escape_string($row['text']);
        $fontSize = vf($row['font_size'], 3);
        $top = vf($row['top'], 3);
        $left = vf($row['left'], 3);

        $color = mysql_real_escape_string($row['color']);
        if (count(explode('.', $color)) !== 3) {
            $color = '0.0.0';
        }

        $query = sprintf(
            "UPDATE `print_card` SET `text` = '%s', `color` = '%s', `font_size` = '%u', `top` = '%u', `left` = '%u' WHERE `field` = '%s'; ",
            $text, $color, $fontSize, $top, $left, $field
        );
        nr_query($query);
        log_register(sprintf('UPDATE PrintCard [%s] `%s` `%s` `%u` `%u` `%u`',  $field, $text, $color, $fontSize, $top, $left));
    }
}

function web_ParsePrintable($cardList, $title = '') {
    $data = '';
    foreach ($cardList as $card) {
        $data.= wf_img($card);
    }

    if (file_exists(PRINT_TEMPLATE)) {
        $template = file_get_contents(PRINT_TEMPLATE);
        $template = str_replace('{PAGE_TITLE}', $title, $template);
        $result = $template . $data;
    } else {
        $result = $data;

        $result.= wf_tag('body', true);
        $result.= wf_tag('html', true);
    }

   return ($result);
}

function web_CreatePdf($cardList) {
    $indentWidth = 15;
    $indentHeight = 3;
    $paperWidth = 210;
    $paperHeight = 297;
    $correctiveToMM = 0.265;
    $geImageInfo = getimagesize($cardList[0]);
    $width = ($geImageInfo[0] + $indentWidth) * $correctiveToMM;
    $height = ($geImageInfo[1] + $indentHeight) * $correctiveToMM;

    //ob_clean();
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $wPos = $indentWidth;
    $hPos = $indentHeight;
    foreach ($cardList as $card) {
        if ($paperHeight < ($hPos + $height)) {
            $hPos = $indentHeight;
            $wPos+= $width;
        }
        if ($paperWidth < ($wPos + $width)) {
            $pdf->AddPage();
            $wPos = $indentWidth;
            $hPos = $indentHeight;
        }
        $pdf->Image($card, $wPos, $hPos);
        $hPos += $height;
    }
    $pdf->Output();
}
?>
