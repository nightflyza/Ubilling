<?php
require_once("content/documents/mpdf/vendor/autoload.php");

/* Start to develop here. Best regards https://php-download.com/ */

use Mpdf\Mpdf;

$html = file_get_contents('content/documents/invoice_min_css.html');

$mpdf = new Mpdf(['tempDir' => 'exports']);
/*$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Acme Trading Co. - Invoice");
$mpdf->SetAuthor("Acme Trading Co.");
$mpdf->SetWatermarkText("Paid");
$mpdf->showWatermarkText = true;
$mpdf->watermark_font = 'DejaVuSansCondensed';
$mpdf->watermarkTextAlpha = 0.1;
$mpdf->SetDisplayMode('fullpage');*/

$mpdf->WriteHTML($html);
$mpdf->Output('exports/file.pdf', 'F');