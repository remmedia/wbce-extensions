<?php
defined('WB_PATH') or die('No direct access'); foreach(array('sitekey','secret','theme','mode','start','installed') as $key) Settings::Del('captcha_captchafox_'.$key);
