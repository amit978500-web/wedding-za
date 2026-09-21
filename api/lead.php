<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'POST required']);
    exit;
}
if (!empty($_POST['company_website'] ?? '')) {
    echo json_encode(['ok'=>true,'message'=>'Thanks — we received it.']);
    exit;
}
function clean(string $key, int $max=1200): string {
    $v=trim((string)($_POST[$key]??''));
    $v=preg_replace('/[\r\n\t]+/',' ',$v)??'';
    return function_exists('mb_substr') ? mb_substr($v,0,$max) : substr($v,0,$max);
}
$type=clean('type',60) ?: 'general';
$name=clean('name',120);
$email=clean('email',180);
$phone=clean('phone',80);
$city=clean('city',100);
$message=clean('message',1500);
if (in_array($type,['contact','vendor-enquiry','vendor-registration','wedding-submission'],true) && $name==='') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'Please add your name.']);
    exit;
}
if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'Please enter a valid email address.']);
    exit;
}
$storage=dirname(__DIR__).'/storage';
if(!is_dir($storage)) @mkdir($storage,0775,true);
$file=$storage.'/leads.csv';
$row=[date('c'),$type,$name,$email,$phone,$city,clean('vendor',150),clean('business',150),clean('category',120),clean('event_date',40),clean('topic',120),clean('price',120),clean('portfolio',400),clean('vendors',700),$message,($_SERVER['REMOTE_ADDR']??'')];
$exists=file_exists($file);
$fp=@fopen($file,'ab');
if(!$fp) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'The server could not save this submission. Check storage permissions.']);
    exit;
}
flock($fp,LOCK_EX);
if(!$exists)fputcsv($fp,['created_at','type','name','email','phone','city','vendor','business','category','event_date','topic','price','portfolio','vendors','message','ip']);
fputcsv($fp,$row);
flock($fp,LOCK_UN);
fclose($fp);
echo json_encode(['ok'=>true,'message'=>'Thanks — your details were received.']);
