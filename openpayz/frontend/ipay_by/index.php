<?php

//error_reporting(0);

/*
 * �������� ��� ��������� ����������� �� IPAY.BY � ���� POST XML
 */

// ���������� API OpenPayz
include ("../../libs/api.openpayz.php");

// ��������� ��� ������� ��������
global $salt;
$salt = addslashes('f5d6d6wgs45d52c4<GRf>245');

//��� ���� ����������
$site=$_SERVER['HTTP_HOST'];

//������� ������������ ������� �� �������
function answer_to_ipay($template,$parameters=array()){
global $salt;
// ��������� ����� �� �������
$answer = file_get_contents($template);

//���� � ������� ���� ���������, �������� �� �� ��������
if (!empty($parameters)) {
$answer=str_replace(array_keys($parameters),array_values($parameters),$answer);
$answer=str_replace("\r",'',$answer);
}
$answer = preg_replace('/^.*\<\?xml/sim', '<?xml', $answer);
$answer = preg_replace('/\<\/ServiceProvider_Response\>.*/sim', '</ServiceProvider_Response>', $answer);

// ��������� �� � ���������� �� � ����� � iPay
$md5 = md5($salt . $answer);
//log_register('Template: '.$template."\n Salt: ".$salt."\n Answer: ".iconv("WINDOWS-1251", "UTF-8", $answer)."\n md5: ".$md5);
header('Content-Type: text/html; charset=windows-1251');
header("ServiceProvider-Signature: SALT+MD5: $md5");
print $answer;
exit(0);
}

// ����� ����� � ���������� � ���� POST XML
if (!empty($_POST['XML'])) {
// ������� ������ ������� �� ������ xml-������� � ����� xml-�������
$_POST['XML'] = preg_replace('/^.*\<\?xml/sim', '<?xml', $_POST['XML']);
$_POST['XML'] = preg_replace('/\<\/ServiceProvider_Request\>.*/sim', '</ServiceProvider_Request>', $_POST['XML']);

// ����������� �� �������������
$_POST['XML'] = stripslashes($_POST['XML']);

// �������� ������� �� iPay
$signature = '';
if (preg_match('/SALT\+MD5\:\s(.*)/', $_SERVER['HTTP_SERVICEPROVIDER_SIGNATURE'], $matches)){
$signature = $matches[1];
}

//log_register('POST XML: '.iconv("WINDOWS-1251", "UTF-8",$_POST['XML']).' Signature: '.$signature);
// ��������� ������� iPay
if (strcasecmp(md5($salt.$_POST['XML']), $signature)){
// ��������� ����� � ������� �������� ��
//���������������� ��� ������������
answer_to_ipay('error_sign.txt');
}

//////////////////������ �� ��������� �������//////////////////////////
if(strpos($_POST['XML'],'ServiceInfo')!==false) {
preg_match("/<PersonalAccount>(.*)<\/PersonalAccount>/imU", $_POST['XML'],$order);
$orderid=$order['1'];
//fnshop specific
//$order_array=fn_shop_get_orderdata($orderid);
$order_array=op_CustomersGetAll();

//���� ��� ������ � ����
if (!isset($order_array[$orderid])) {
answer_to_ipay('error_no_order.txt',array('%ORDER_ID%'=>$orderid,'%SITE%'=>$site));
} else {
	//�������, ��� ���� �������
	answer_to_ipay('awaiting_payment.txt',array('%ORDER_ID%'=>$orderid));
}

/*
�������� ��� �������� ��� � ������ �� ����� ������ ��������?
=====
//��������� ����� ������
$total=0;
//��� �� ��� ��������? �������.
$items=unserialize($order_array['items']);
foreach($items as $id=>$count)	{
$itemdata=fn_cat_get_itemdata($id);
$summ=$itemdata['price']*$count;
$total=$total+$summ;
}
answer_to_ipay('answer.txt',array('%ORDER_ID%'=>$orderid,'%AMOUNT%'=>$total));
}

*/

////////////////////////������ ����������////////////////////////
if(strpos($_POST['XML'],'TransactionStart')!==false)	{
preg_match("/<PersonalAccount>(.*)<\/PersonalAccount>/imU", $_POST['XML'],$order);
$order_array=op_CustomersGetAll();
$orderid=$order['1'];
if (!isset($order_array[$orderid])) {
answer_to_ipay('error_no_order.txt',array('%ORDER_ID%'=>$orderid,'%SITE%'=>$site));
}

    preg_match("/<RequestId>(.*)<\/RequestId>/imU", $_POST['XML'],$hash_arr);
	$hash=$hash_arr['1'];
    $paysys='IPAYBY';
    $note=iconv("WINDOWS-1251", "UTF-8", $_POST['XML']);
    //������������ ����� ����������
    op_TransactionAdd($hash, $total, $orderid, $paysys, $note);
	answer_to_ipay('trans_start.txt',array('%TRX_ID%'=>$orderid));
}

//////////////////////��������� ����������/////////////////////////
if(strpos($_POST['XML'],'TransactionResult')!==false){
preg_match("/<PersonalAccount>(.*)<\/PersonalAccount>/imU", $_POST['XML'],$prog);
$orderid=$prog['1'];
if(strpos($_POST['XML'],'ErrorText')!==false){
//��������! ����� ���������!
	answer_to_ipay('error_time_out.txt',array('%TRX_ID%'=>$orderid,'%SITE%'=>$site));
}
   //�������� ����������� �������������� ����������
    op_ProcessHandlers();
	answer_to_ipay('success.txt',array('%TRX_ID%'=>$orderid,'%SITE%'=>$site));
}
} }

die(0);
?>
