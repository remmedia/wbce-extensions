<?php
defined('WB_PATH') or die('No direct access');
Settings::Del('captcha_cap_endpoint'); Settings::Del('captcha_cap_site_key'); Settings::Del('captcha_cap_secret');
Settings::Del('captcha_cap_installed');
