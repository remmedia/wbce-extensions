<?php
defined('WB_PATH') or die('No direct access'); foreach(array('sitekey','apikey','max_score','theme','minimal','installed') as $key) Settings::Del('captcha_trustcaptcha_'.$key);
