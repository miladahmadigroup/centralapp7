<?php
session_start();

// تولید کد تصادفی
$code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5);
$_SESSION['captcha'] = $code;

// ایجاد تصویر
$img = imagecreate(120, 40);
$bg = imagecolorallocate($img, 255, 255, 255);
$text_color = imagecolorallocate($img, 50, 50, 50);

// نوشتن متن
imagestring($img, 5, 25, 12, $code, $text_color);

// خروجی
header('Content-Type: image/png');
imagepng($img);
imagedestroy($img);
?>