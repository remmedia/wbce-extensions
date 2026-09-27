<?php
defined('WB_PATH') or die('No direct access'); foreach(array('sitekey','apikey','endpoint','theme','start','installed') as $key) Settings::Del('captcha_friendly_'.$key);
