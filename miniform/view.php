<?php
/**
 *
 * @category        modules
 * @package         miniform
 * @author          Ruud Eisinga / Dev4me
 * @link			http://www.dev4me.nl/modules-snippets/opensource/miniform/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.6 and higher
 * @version         0.15.2
 * @lastmodified    Januari 29, 2021
 *
 */

 /*

 */
if(defined('WB_PATH') == false) { exit("Cannot access this file directly"); }
global $MF;
if(!file_exists(WB_PATH.'/modules/miniform/languages/'.LANGUAGE.'.php')) {
	require(WB_PATH.'/modules/miniform/languages/EN.php');
} else {
	require(WB_PATH.'/modules/miniform/languages/'.LANGUAGE.'.php');
}
require_once (dirname(__FILE__).'/functions.php');

$miniformScalar = static function (array $source, $key, $default = '') {
	if (!array_key_exists($key, $source)) return $default;
	$value = $source[$key];
	return is_scalar($value) || $value === null ? (string) $value : $default;
};
$miniformStep = static function ($value) {
	$value = (string) $value;
	return preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $value) ? $value : '';
};

if(isset($_SESSION['lastform'])) {
	unset($_SESSION['lastform']);
	unset($_SESSION['form']);
	$_SESSION['no'.$section_id] = true;
} 
if(!isset($_SESSION["REF"])) $_SESSION["REF"] = defined('ORG_REFERER') ? ORG_REFERER : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

mb_internal_encoding(DEFAULT_CHARSET);

if(isAjaxRequest() && isset($_POST['miniform'])) {
	$sid = (int)$_POST['miniform'];
	$section_id = $sid;
}

$mf = new mform($section_id);

$get_settings = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_miniform WHERE section_id = '$section_id'");
$settings = $get_settings ? $get_settings->fetchRow() : false;
if (!is_array($settings)) {
	return;
}
$mf->upload_limit_mb = isset($settings['upload_limit_mb']) ? max(1, min(512, (int) $settings['upload_limit_mb'])) : 512;
$var = array();
$value = array();
$fields = array();
$form = $settings['template'];
$email = $settings['email'];
$emailfrom = $settings['emailfrom'];
$subject = $settings['subject'];
$emailmessage = $mf->get_template('email');
$emailmessage = $mf->add_template($emailmessage, "{SUBJECT}", $subject);

$confirm_user = $settings['confirm_user'];
$confirm_subject = $settings['confirm_subject'];
$confirm_message = $mf->get_template('confirm-email');
if(!$confirm_message) $confirm_message = $mf->get_template('email');
$confirm_message = $mf->add_template($confirm_message, "{SUBJECT}", $confirm_subject);

$email_field_val = $mf->get_template('email_field_value');
$email_field_data = '';
$replyto = '';
$successpage = $settings['successpage'];

if(!isset($_SESSION['no'.$section_id]) && !isset($_SESSION['form']) && $wb->get_user_id() > 0) {  // try to get last data
	$mf->load_history($section_id, $wb->get_user_id(), null );
	$_SESSION['no'.$section_id] = true;
} 
if(isset($_GET['guid'])) {
	$guid = strtoupper($miniformScalar($_GET, 'guid'));
	$mf->load_history($section_id, 0, $guid );
	$mf->fieldGetSeen = preg_match('/^[A-F0-9]{32}$/D', $guid) === 1;
}

$prevdata = '';
$dbdata = '';
if(isset($_POST['__next'])) {
	$mf->next = $miniformStep($miniformScalar($_POST, '__next'));
	$next_template = $mf->next === '' ? '' : $mf->get_template('form_'.$mf->next);
}	
if(isset($_POST['__previous'])) {
	$mf->next = $miniformStep($miniformScalar($_POST, '__previous'));
	$template = $mf->next === '' ? '' : $mf->get_template('form_'.$mf->next);
	$next_template = $template;
} elseif(isset($_POST['__current'])) {
	$currentTemplate = $miniformStep($miniformScalar($_POST, '__current'));
	$template = $currentTemplate === '' ? '' : $mf->get_template('form_'.$currentTemplate);
} else {	
	$template = $mf->get_template('form_'.$form);
}
$message = "";
$message_class = "hidden";
$form_class = "";
$use_captcha = ( strpos($template,"{CAPTCHA}")==false ) ? false : true;

$no_store = $settings['no_store'];
$use_ajax = $settings['use_ajax'];
$use_recaptcha = $settings['use_recaptcha'];
$recaptcha_key = $settings['recaptcha_key'];
$recaptcha_secret = $settings['recaptcha_secret'];
if(!$recaptcha_key || !$recaptcha_secret) $use_recaptcha = false;

$use_asp = ( strpos($template,"{ASPFIELDS}")==false ) ? false : true;
$asp = "";
$all_required = true;
$next_required = true;
$aspdetect = false;

if ($use_captcha && $mf->dataPosted && $mf->myPost) {
	if(isset($_POST['captcha'.$section_id])) $_POST['captcha'] = $_POST['captcha'.$section_id];  // Fixing undocumented change in WB2.12.2
	if($use_recaptcha) {
		if($mf->reCaptcha_check($recaptcha_secret) === false) {
			$var[] = "{CAPTCHA_ERROR}"; $value[] = " missing";
			$all_required = false;
		}
	} elseif (!$mf->captcha_check($section_id)) {
		$var[] = "{CAPTCHA_ERROR}"; $value[] = " missing";
		$all_required = false;
	}
}			

if(isset($next_template) && $next_template) {
	$use_captcha = ( strpos($next_template,"{CAPTCHA}")==false ) ? false : true;
}
$captcha_class = $use_captcha ? "":"hidden";
$captcha = "";											 

if($mf->myPost) {

	//Find all posted data
	foreach ($_POST as $key => $rawpost) { 
		if (is_string($key) && !in_array($key, array('mf_chunk', 'mf_chunk_token'), true)
			&& preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $key)) $fields[$key] = '!';
	}
	//Find all fields in next template
	if(isset($next_template) && $next_template) {
		preg_match_all('/<input [^>]*>|<select [^>]*>|<textarea [^>]*>/',$next_template,$matches);
		foreach($matches[0] as $match){
			if(preg_match('/name="([^"]*)"/i',$match,$name)) {
				$name = str_replace("[]","",$name[1]);
				$fields[$name] = '-';
			}
		}
	}		
	//Find all fields in template
	preg_match_all('/<input [^>]*>|<select [^>]*>|<textarea [^>]*>/',$template,$matches);
	foreach($matches[0] as $match){
		if(preg_match('/name="([^"]*)"/i',$match,$name)) {
			$name = str_replace("[]","",$name[1]);
			$fields[$name] = $name;
		}
	}
	//Read all fields
	foreach($fields as $key => $type) {
		$fullkey = $key;
		$post = $mf->safe_get_post($key);
		//if($type!='-' && substr($key,0,2)!='__' && $post) $prevdata .= '<input type="hidden" name="'.$key.'" value="'.$post.'" />'."\n\t";
		if($type!='-' && substr($key,0,2)!='__' && $post) $prevdata .= '<input type="hidden" name="'.$key.'" value="'.$post.'" />'."\n\t";
		elseif($type!='-' && substr($key,0,3)=='mf_' ) $prevdata .= '<input type="hidden" name="'.$key.'" value="'.$post.'" />'."\n\t";
		if($mf->error) {
			$message .= $mf->error.'<br/>';
			$message_class = "error alert alert-danger";
			$all_required = false;
			$mf->error = '';
		}
		$required = false;
		$emailmessage = $mf->add_template($emailmessage, '{'.mb_strtoupper($key).'}', $post);
		$confirm_message = $mf->add_template($confirm_message, '{'.mb_strtoupper($key).'}', $post);
		if(substr($key,0,3)=="mf_") {
			$key =  substr($key,3);
			if (substr($key,0,2)=="r_") {
				$required = true;
				$key =  substr($key,2);
			}
			if (substr($key,-2)=="__") {
				unset($_SESSION['form'][$fullkey]);
				$key =  substr($key,0,-2);
			}
			$label_post = str_replace(" ","_",$post);
			$var[] = "{".mb_strtoupper($key)."}"; $value[] = $post;
			$var[] = "{".mb_strtoupper($key.'_'.$label_post)."}"; $value[] = " checked='checked' ";
			$var[] = "{".mb_strtoupper($key.'_CHECKED_'.$label_post)."}"; $value[] = " checked='checked' ";
			$var[] = "{".mb_strtoupper($key.'_SELECTED_'.$label_post)."}"; $value[] = " selected='selected' ";
			if($mf->isArray || strpos($post," | ")!==false) {
				$tmppost = explode(' | ',$post);
				foreach($tmppost as $tmpdata) {
					$label_post = str_replace(" ","_",$tmpdata);
					$var[] = "{".mb_strtoupper($key.'_'.$label_post)."}"; $value[] = " checked='checked' ";
					$var[] = "{".mb_strtoupper($key.'_CHECKED_'.$label_post)."}"; $value[] = " checked='checked' ";
					$var[] = "{".mb_strtoupper($key.'_SELECTED_'.$label_post)."}"; $value[] = " selected='selected' ";
				}
			}
			if($required && $mf->dataPosted && trim($post) == '') {
				if( $type != '-' && $type != '!') {
					$var[] = "{".mb_strtoupper($key)."_ERROR}"; $value[] = " missing";
					$all_required = false;
				} else {
					$next_required = false;
				}
			}			
			//try guessing email adresses
			if(in_array($key,array('email','e-mail','mail','email_address','e-mail_address'))) $replyto = $post;
			
			$key = ucwords($key);
			$key = str_replace("_"," ",$key);
			$post = nl2br($post);
			$email_field_data .= $mf->add_template($email_field_val, array('{FIELD}','{VALUE}'), array($key,$post));
		} elseif(substr($key,0,3)=="my_" && $post !== '') {
			$aspdetect = true;
		}
	}

	// If parameters were given through _GET, reload the page without parameters
	if($mf->fieldGetSeen) {
		$_SESSION["REF"] = defined('ORG_REFERER') ? ORG_REFERER : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		$page_link = $mf->page(PAGE_ID);
		if(headers_sent()) {
			echo '<script type="text/javascript">window.location = '.json_encode($page_link, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
		} else {
			die(header('Location: '.$page_link , TRUE , 301));
		}
	}

	// Check form is  filled completely
	if(!$aspdetect) {
		if ($all_required && $mf->dataPosted) {
			if(!isset($next_template) || !$next_template) {
				// add extra data by other modules SESSION['miniform']['mydata'] will be used as {MYDATA} in the email template
				if(isset($_SESSION['miniform'])) { 
					foreach($_SESSION['miniform'] as $_skey => $_svalue) {
						$emailmessage = $mf->add_template($emailmessage, '{'.mb_strtoupper($_skey).'}', $_svalue);
						$confirm_message = $mf->add_template($confirm_message, '{'.mb_strtoupper($_skey).'}', $_svalue);
					}
				}
				// store message in database
				$data['message_id'] = 0;
				$data['section_id'] = $section_id;
				$data['session_data'] = $mf->serialize($_SESSION['form']);
				$data['user_id'] = $wb->get_user_id();
				$data['guid'] = $mf->guid();
				$emailmessage = $mf->add_template($emailmessage, '{GUID}', $data['guid']);
				$emailmessage = $mf->add_template($emailmessage, '{MAILMESSAGE}', $email_field_data);
				$confirm_message = $mf->add_template($confirm_message, '{GUID}', $data['guid']);
				$confirm_message = $mf->add_template($confirm_message, '{MAILMESSAGE}', $email_field_data);
				$emailmessage = preg_replace('#\{(?=\S)(.*?)\}#s', '', $emailmessage); 
				$_SESSION['lastform'] = $emailmessage;
				$data['data'] = $emailmessage; 
				$data['submitted_when'] = time();
				if(!$no_store) $mf->update_record('mod_miniform_data', 'message_id', $data );
				// send message by mail
				if($mf->mail ($emailfrom, $email, $subject, $emailmessage, WBMAILER_DEFAULT_SENDERNAME, $replyto)) {
					if($confirm_user && $wb->get_user_id() > 0) {
						$useremail = $wb->get_email();
						if(!$mf->mail ($emailfrom, $useremail, $confirm_subject, $confirm_message, WBMAILER_DEFAULT_SENDERNAME)) {
							$message .= $MF['SENDERROR']."<br>".$mf->error;
							$message_class = "error alert alert-danger";
						}
					}
					unset($_SESSION['form']);
					if($successpage) {
						$page_link = $mf->page($successpage);
						if(headers_sent() || isAjaxRequest()) {
							sendOutput('<script type="text/javascript">window.location = '.json_encode($page_link, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>');
						} else {
							die(header('Location: '.$page_link , TRUE , 301));
						}
					}
					$message .= $MF['THANKYOU'];
					$message_class = "ok alert alert-success";
					$form_class = "hidden";
				} else {
					$message .= $MF['SENDERROR']."<br>".$mf->error;
					$message_class = "error alert alert-danger";
				}
			} else {
				$template = $next_template;
				$prevdata .= '<input type="hidden" name="__current" value="'.$mf->next.'" />'."\n\t";
			}
		} elseif ($mf->dataPosted)  {
				$message .= $MF['NOTALL'];
				$message_class = "error alert alert-danger";
				$prevdata .= '<input type="hidden" name="__current" value="'.$mf->current.'" />'."\n\t";
		}
	}
}
if ($use_asp) {
	$asp = 	'<div style="top: -2000px; position: absolute; height:0px; overflow: hidden">'."\n\t\t".
			'<label for="ucomp">CompanyName</label><input autocomplete="off" type="text" id="ucomp" name="my_company" /><br/>'."\n\t\t".
			'<label for="uname">Username</label><input autocomplete="off" type="text" id="uname" name="my_name" /><br/>'."\n\t\t".
			'<label for="umail">Email</label><input autocomplete="off" type="text" id="umail" name="my_email" /><br/>'."\n\t\t".
			'</div>';
}
if ($use_captcha) {
	if($use_recaptcha) {
		$captcha = $mf->reCaptcha($recaptcha_key);
	} else {
		$captcha = $mf->captcha($section_id);
	}
}
$var[] = "{EMAILMESSAGE}"; $value[] = $mf->add_template($emailmessage, '{MAILMESSAGE}', $email_field_data);
$var[] = "{PREVIOUS}"; $value[] = $prevdata;
$var[] = "{ASPFIELDS}";	$value[] = $asp;
$var[] = "{CAPTCHA}";	$value[] = $captcha;
$var[] = "{STATUSMESSAGE}";	$value[] = $message;
$var[] = "{CAPTCHA_CLASS}";	$value[] = $captcha_class;
$var[] = "{MESSAGE_CLASS}";	$value[] = $message_class;
$var[] = "{FORM_CLASS}";	$value[] = $form_class;
$var[] = "{PAGE_ID}";	$value[] = $page_id;
$var[] = "{SECTION_ID}";$value[] = $section_id;
$mfNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
if (function_exists('wbce_timezone')) {
	$mfTimezone = wbce_timezone();
	if (!$mfTimezone instanceof DateTimeZone) {
		try { $mfTimezone = new DateTimeZone((string)$mfTimezone); }
		catch (Throwable $exception) { $mfTimezone = new DateTimeZone('UTC'); }
	}
	$mfNow = $mfNow->setTimezone($mfTimezone);
} elseif (defined('TIMEZONE') && is_numeric(TIMEZONE)) {
	$mfNow = $mfNow->modify(((int) TIMEZONE >= 0 ? '+' : '') . (int) TIMEZONE . ' seconds');
}
$var[] = "{DATE}";$value[] = $mfNow->format(DATE_FORMAT);
$var[] = "{TIME}";$value[] = $mfNow->format(TIME_FORMAT);
$template = $mf->add_template($template, $var, $value);
$miniformChunkToken = bin2hex(random_bytes(24));
$_SESSION['miniform_chunk_tokens'][(int) $section_id] = array(
	'token' => $miniformChunkToken,
	'allowed' => $mf->upload_whitelist,
	'created' => time(),
);
$miniformUploadLimit = $mf->get_upload_limit();
$miniformChunkFields = '<input type="hidden" name="mf_chunk_token" value="'.htmlspecialchars($miniformChunkToken, ENT_QUOTES, 'UTF-8').'" />';
$miniformFormAttributes = ' data-mf-upload-limit="'.$miniformUploadLimit.'" data-mf-upload-too-large="'.htmlspecialchars($MF['UPLOAD_TOO_LARGE'], ENT_QUOTES, 'UTF-8').'" data-mf-upload-failed="'.htmlspecialchars($MF['UPLOAD_INCOMPLETE'], ENT_QUOTES, 'UTF-8').'"';
$template = preg_replace('/(<form\b)([^>]*>)/i', '$1'.$miniformFormAttributes.'$2'.$miniformChunkFields, $template, 1);
//clean unused fields in the template
$template = preg_replace('#\{(?=\S)(.*?)\}#s', '', $template); 
unset($var);
unset($value);


$spinner = '';
if(!defined("spinnerloaded")) {
	$spinnerimg = WB_URL.'/modules/miniform/sending.gif';
	$spinner = '<div class="minispinner" style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;background: black url('.$spinnerimg.') center center no-repeat;opacity:.6;"></div>'."\n";
	define ('spinnerloaded' , true );
}
if(!isAjaxRequest() && $use_ajax) $template = $spinner.'<div class="miniform_ajax">'.$template.'</div>';
sendOutput($template);
