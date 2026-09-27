<?php
defined('WB_PATH') or die('No direct access'); foreach(array('sitekey','secret','version','theme','score','domain','installed') as $key) Settings::Del('captcha_recaptcha_'.$key);
