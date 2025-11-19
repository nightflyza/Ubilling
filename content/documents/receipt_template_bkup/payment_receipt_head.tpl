<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no" />
    <script type="text/javascript" src="../../../modules/jsc/jquery.min.js"></script>
    <script type="text/javascript" src="../../../modules/jsc/qr_gen_inpage/jquery.qrcode.min.js"></script>
    <script type="text/javascript" src="../../../modules/jsc/qr_gen_inpage/qrcode.min.js"></script>
    <title>TITLE</title>
    <style>
        td.brdr-bottom {
            border-bottom: 1px solid #000;
        }

        td.brdr-bottom-right {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }

        td.brdr-bottom-right-top {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            border-top: 1px solid #000;
        }

        td.brdr-bottom-left {
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
        }

        td.brdr-bottom-right-left {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            border-left: 1px solid #000;
        }

        td.brdr-bottom-right-left-top {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            border-left: 1px solid #000;
            border-top: 1px solid #000;
        }

        td.brdr-top {
            border-top: 1px solid #000;
        }

        td.brdr-top-right {
            border-top: 1px solid #000;
            border-right: 1px solid #000;
        }

        td.brdr-top-left {
            border-top: 1px solid #000;
            border-left: 1px solid #000;
        }

        td.brdr-left {
            border-left: 1px solid #000;
        }

        td.brdr-right {
            border-right: 1px solid #000;
        }

        td {
            padding-left: 5px;
            padding-bottom: 1px;
            padding-top: 2px;
        }

        @media print {
            .receipt_container {
                height: 21cm;
                padding-top: 3cm;

            }

            .pagebreak_footer {
                page-break-after: always;
                bottom: 0;
            }

            .footer_dashed_line {
                display: none;
            }
        }
    </style>
</head>
<body>
<script type="text/javascript">
/*
Hack from Hatter Jiang (https://github.com/jht5945)
for jquery.qrcode properly process long UTF-8 strings
*/
var _countBits = function(_c) {
    var cnt = 0;
    while(_c > 0) {
        cnt++;
        _c = _c >>> 1;
    }
    return cnt;
};

function UnicodeToUtf8Bytes2(code) {
        if ((code == null) || (code < 0) ||
        (code > (Math.pow(2, 31) -1))) {
        return ["?".charCodeAt(0)];
    }
    if (code < 0x80) {
        return [code];
    }
    var arr = [];
    while ((code >>> 6) > 0) {
        arr.push(0x80 | (code & 0x3F));
        code = code >>> 6;
    }
    if ((arr.length + 2 + (_countBits(code))) > 8) {
        arr.push(0x80 | code);
        code = 0;
    }
    var pre = 0x80;
    for (var i = 0; i < arr.length; i++) {
      pre |= (0x80 >>> (i + 1));
    }
    arr.push(pre | code);
    return arr.reverse();
}

QR8bitByte.prototype.getLength = function(buffer) {
  var len = 0;
  for (var i = 0; i < this.data.length; i++) {
    var bytes = UnicodeToUtf8Bytes2(this.data.charCodeAt(i));
    len += bytes.length;
  }
  return len;
};

QR8bitByte.prototype.write = function(buffer) {
  for (var i = 0; i < this.data.length; i++) {
    var bytes = UnicodeToUtf8Bytes2(this.data.charCodeAt(i));
    for (var x = 0; x < bytes.length; x++) {
      buffer.put(bytes[x], 8);
    }
  }
};
/* Hack End*/

function genQRs() {
    if ($('#qr_embedded').val() == "1") {
        console.log('QRs are embedded');
        return false;
    }

    // if you plan to save generated page as document -
    // need to prevent the duplication of QRs
    $('[name ^= "qr"] canvas').remove();

    var qrCodesCount = $('#qr_count').val();

    for (i = 1; i <= qrCodesCount; i++) {
        var qrContent = $('#qr_content_' + i).val();
        $('[name = "qr' + i + '"]').qrcode({
            text: qrContent,
            width: 150,
            height: 150,
            correctLevel : QRErrorCorrectLevel.M
        });
    }
}
</script>

<script type="text/javascript" id="QRGen">
$(document).ready(function() {
    genQRs();
});
</script>

<div>
<!--
{QR_EXT_START}
ООО "Рога&Копыта"  ИНН: 456789213  Р/С: 204087899521005  +38(50)125-26-15  г. Аркхэм, ул. Вязов, 51, офис №1
Л/С: {CONTRACT} {REALNAME} {STREET} {BUILD}{APT} моб. тел.: {MOBILE}
К оплате: {SUMMCOINS}. Оплата за период: {PAYFORPERIODSTR}
{QR_EXT_END}
{DATES_FORMAT_START}Y-m-d{DATES_FORMAT_END}
{MONTHYEAR_FORMAT_START}m-Y{MONTHYEAR_FORMAT_END}

-->
    <input id="qr_count" type="hidden" value="{QR_CODES_CNT}" />
    <input id="qr_embedded" type="hidden" value="0" />