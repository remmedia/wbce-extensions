<?php
defined('WB_PATH') or die('No direct access');
if (class_exists('Settings')) Settings::delete('cookie_banner');
