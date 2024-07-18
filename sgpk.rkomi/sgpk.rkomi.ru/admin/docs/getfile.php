<?php

include("../config.php");                      //получаем настройки
include($document_root."admin/security.php");  //проверка окружения скрипта
include($document_root."admin/functions.php"); //подключаем свои функции


// getfile_on.txt - присутствие этого файла определяет выполнение скрипта
$getfile_on=$document_root."admin/docs/getfile_on.txt";
if (!file_exists($getfile_on)){exit;}

//Получаем ид через пост
/*
if(isset($_POST["idfile"]))
{$idfile=$_POST["idfile"];}
else
{$idfile=""; exit;}
*/

//Получаем ид через пост
if(isset($_GET["idfile"]))
{$idfile=$_GET["idfile"];}
else
{$idfile=""; exit;}


// Лезем в базу за именем файла на диске
$file=mysql_query("select * from `docs_files` where `id`=$idfile");
$file=mysql_fetch_row($file);
$filename=$file[2];


//Отрезаем 70 символов с начала имени чтобы не получилось слишком длинное имя файла при сохранении на стороне клиента
if(mb_strlen($filename)>70)
{$filename=mb_substr($filename, 0, 70)."-";}


if ($idfile==11164 || $idfile==15443) // дописываем 16 случайных символов в имя файла (для СПТ)
{

function generatePassword($length = 16){
$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
$numChars = strlen($chars);
$string = '';
for ($i = 0; $i < $length; $i++) {
$string .= substr($chars, rand(1, $numChars) - 1, 1);
}
return $string;
}

$filename=$filename."-".generatePassword(16);

}


$secure=$file[9];

$ref=$_SERVER["HTTP_REFERER"];  //Ссылка на вызывающую страницу
if($secure==1 AND $ref<>$http_host."admin/docs/getfilesecure.php") {die("Доступ запрещен!");}


$ext=$file[3];
$saveas=$filename.".".$ext;
$saveas=addslashes($saveas); // Экранируем кавычки

$filepath=$document_root."admin/docs/upload/".$idfile;


if(file_exists($filepath))
{
	set_time_limit(0);
    ini_set( "memory_limit", "64M" );
	// Если нет функции mime_content_type то в файле functions.php есть её эмулятор
	$mime_type=mime_content_type($filepath);//$saveas
	// Передаем файл
	header ("Content-Type: $mime_type");
	header ("Accept-Ranges: bytes");
	header ("Content-Length: ".filesize($filepath));
	header ("Content-Disposition: attachment; filename=\"".$saveas."\"");
	$f = fopen($filepath, "r+b");
	flock($f, LOCK_EX);
	while (!feof($f))
	{
     if (file_exists($getfile_on)) // getfile_on.txt присутствие файла определяет выполнение цикла
     {echo fread($f, 4096);}
     else
     {exit;}
	}
	flock($f, LOCK_UN);
	fclose($f);

	// Фиксируем закачку в базе
	$downloads=mysql_query("update `docs_files` set `downloads_count`=`downloads_count`+1 where `id`=$idfile limit 1;");
	ini_set( "memory_limit", -1 );
	exit;
}
else
{
	$mess="<html><body><script>alert('Ошибка! Файла не существует!'); window.close();</script></body></html>";
	echo($mess);
	exit;
}

?>