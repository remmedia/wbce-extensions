<?php
defined('WB_PATH') or die('No direct access');
if(isset($database)&&is_object($database)){Settings::set('mailer',json_encode(['enabled'=>'1','engine'=>'symfony','provider'=>'smtp','from_email'=>'','from_name'=>'']));}
