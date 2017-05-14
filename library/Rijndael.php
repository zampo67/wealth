<?php

class Rijndael {
    public static $_iv = '2';

    public static function getKey($type){
        switch ($type){
            case 'resume_homepage':
            default:
                $key = 'cb04b7e103a0cd8b';
                break;
            case 'resume_template':
                $key = 'qu04s7etb3a0cp6b';
                break;
            case 'user_notice':
                $key = 'a0clu0ds3b67et2q';
                break;
            case 'company_homepage':
                $key = 'cl273tura0ds3bkg';
                break;
            case 'company_position':
                $key = 'e7qclt6bb3a04s9p';
                break;
            case 'company_job':
                $key = 'cu0tb3ap6b0q7e4s';
                break;
            case 'link_redirect':
                $key = 'jg2NO1v52Az23p02';
                break;
            case 'link_obtain_compilation':
                $key = 'mu0tc3ap6b0q7e4l';
                break;
        }
        return $key;
    }

    public static function getParam($path, $param){
        switch ($path){
            case '/resume/homepage':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id']);
                }
                break;
            case '/resume/export':
            case '/resume/template':
            case '/resume/preview':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'resume_template');
                }
                break;
            case '/company/homepage':
            case '/c/homepage':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'company_homepage');
                }
                break;
            case '/job/details':
            case '/site/positionIndex':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'company_position');
                }
                break;
            case '/job/acceptFromEmail':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'company_job');
                }
                break;
            case '/page/linkRedirect':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'link_redirect');
                }
                break;
            case '/wx/subscriberTips':
                if(!empty($param['id'])){
                    $param['id'] = self::encrypt($param['id'], 'link_obtain_compilation');
                }
                break;
            default:
                break;
        }
        return $param;
    }

	public static function encrypt($plaintext, $type='') {
        $key = self::getKey($type);
		$length = (ini_get('mbstring.func_overload') & 2) ? mb_strlen($plaintext, ini_get('default_charset')) : strlen($plaintext);
		if ($length >= 1048576){
            return false;
        }
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_ECB, self::$_iv)) . sprintf('%06d', $length);
	}

	public static function decrypt($ciphertext, $type='') {
        $key = self::getKey($type);
		if (ini_get('mbstring.func_overload') & 2) {
			$length = intval(mb_substr($ciphertext, -6, 6, ini_get('default_charset')));
			$ciphertext = mb_substr($ciphertext, 0, -6, ini_get('default_charset'));

			return mb_substr(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($ciphertext), MCRYPT_MODE_ECB, self::$_iv), 0, $length, ini_get('default_charset'));
		} else {
			$length = intval(substr($ciphertext, -6));
			$ciphertext = substr($ciphertext, 0, -6);

			return substr(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($ciphertext), MCRYPT_MODE_ECB, self::$_iv), 0, $length);
		}
	}
}
