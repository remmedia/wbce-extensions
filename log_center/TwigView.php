<?php
defined('WB_PATH') or die('No direct access');
final class WbceLogCenterTwigView{public static function display($content){$file=__DIR__.'/templates/tool.twig';if(class_exists('Twig\\Environment')&&class_exists('Twig\\Loader\\FilesystemLoader')){$twig=new Twig\Environment(new Twig\Loader\FilesystemLoader(__DIR__.'/templates'),array('autoescape'=>'html'));echo $twig->render('tool.twig',array('content'=>$content));return;}echo $content;}}
