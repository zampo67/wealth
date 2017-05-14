<?php

class Log {
    private static $logpath = LOG_DIR;
    
    public static function debug($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('debug', $strType, $strMSG, $strExtra, $line);
    }

    public static function script($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('script', $strType, $strMSG, $strExtra, $line);
    }

    public static function weixin($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('weixin', $strType, $strMSG, $strExtra, $line);
    }

    public static function sql($strType = "SQL", $strMSG = "", $strExtra = "", $line = ""){
        self::out('sql', $strType, $strMSG, $strExtra, $line);
    }

    public static function sqlError($strType = "SQL", $strMSG = "", $strExtra = "", $line = ""){
        self::out('sqlError', $strType, $strMSG, $strExtra, $line);
    }

    public static function common($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('common', $strType, $strMSG, $strExtra, $line);
    }

    public static function request($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('request', $strType, $strMSG, $strExtra, $line);
    }

    public static function hardware($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('hardware', $strType, $strMSG, $strExtra, $line);
    }

    public static function accessDoor($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('accessDoor', $strType, $strMSG, $strExtra, $line);
    }

    public static function printer($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('printer', $strType, $strMSG, $strExtra, $line);
    }

    public static function mail($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('mail', $strType, $strMSG, $strExtra, $line);
    }

    public static function mobile($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('mobile', $strType, $strMSG, $strExtra, $line);
    }
    
    public static function aliPay($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('aliPay', $strType, $strMSG, $strExtra, $line);
    }

    public static function wxPay($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('wxPay', $strType, $strMSG, $strExtra, $line);
    }

    public static function file($strType = "I", $strMSG = "", $strExtra = "", $line = ""){
        self::out('file', $strType, $strMSG, $strExtra, $line);
    }

    /**
     * 写入日志
     *
     * @param string $strFileDir
     * @param string $strType
     * @param string $strMSG
     * @param string $strExtra
     * @param string $line
     */
    public static function out($strFileDir = "", $strType = "I", $strMSG = "", $strExtra = "", $line = "") {
        if ($strType == "")
            $strType = "I";

        $log_path = self::$logpath.$strFileDir;
        if (!file_exists($log_path)) {
            if (!mkdir($log_path, 0755, true)) {
                if (DEBUG_MODE) {
                    die(Tools::displayError("Make " . $log_path . " error"));
                } else {
                    die("error");
                }
            }
        }
        if (!is_dir($log_path)) {
            if (DEBUG_MODE) {
                die(Tools::displayError($log_path . " is already token by a file"));
            } else {
                die("error");
            }
        }

        if (!is_writable($log_path)) {
            @chmod($log_path, 0755);
        }
        $logfile = rtrim($log_path, '/') . '/' . date("ymd") . '.log';
        if (file_exists($logfile) && !is_writable($logfile)) {
            @chmod($logfile, 0644);
        }

        $handle = @fopen($logfile, "a+");
        if ($handle) {
            $strMSG = preg_replace("/\s(?=\s)/", "\\1", str_replace(PHP_EOL, '', $strMSG));
            if (Tools::isCli()) {
                $strContent = "[" . Common::microtimeFormat('Y-m-d  H:i:s:x',Common::microtimeFloat()) . "] [" . strtoupper($strType) . "] [" . $strMSG . "] [CLI]" . "\n";
            } else {
                $strContent = "[" . Common::microtimeFormat('Y-m-d  H:i:s:x',Common::microtimeFloat()) . "] [" . strtoupper($strType) . "] [" . $strMSG . "] [" . Tools::getRemoteAddr() . "]" . "\n";
            }

            if (!fwrite($handle, $strContent)) {
                @fclose($handle);
                die("Write permission deny");
            }
            @fclose($handle);
        }
    }

//	/**
//	 * 将$strMSG写入$strFileName文件，覆盖原来内容
//	 *
//	 * @param $strFileName
//	 * @param $strMSG
//	 */
//	public static function simplewrite($strFileName, $strMSG) {
//		if (!file_exists(self::$logpath))
//		{
//			if (!mkdir(self::$logpath, '0777'))
//			{
//				if (DEBUG_MODE)
//				{
//					die(Tools::displayError("Make " . self::$logpath . " error"));
//				}
//				else
//				{
//					die("error");
//				}
//			}
//		}
//		elseif (!is_dir(self::$logpath))
//		{
//			if (DEBUG_MODE)
//			{
//				die(Tools::displayError(self::$logpath . " is already token by a file"));
//			}
//			else
//			{
//				die("error");
//			}
//		}
//		else
//		{
//			if (!is_writable(self::$logpath))
//			{
//				@chmod(self::$logpath, 0777);
//			}
//			$logfile = rtrim(self::$logpath, '/') . '/' . $strFileName . '.log';
//			if (file_exists($logfile) && !is_writable($logfile))
//			{
//				@chmod($logfile, 0644);
//			}
//			$handle = @fopen($logfile, "w");
//			if ($handle)
//			{
//				$strContent = $strMSG . "\n";
//				if (!fwrite($handle, $strContent))
//				{
//					@fclose($handle);
//					die("Write permission deny");
//				}
//				@fclose($handle);
//			}
//		}
//	}

//	/**
//	 * 写入文件，追加方式
//	 *
//	 * @param $strFileName
//	 * @param $strMSG
//	 */
//	public static function simpleappend($strFileName, $strMSG) {
//		if (!file_exists(self::$logpath))
//		{
//			if (!mkdir(self::$logpath, '0777'))
//			{
//				if (DEBUG_MODE)
//				{
//					die(Tools::displayError("Make " . self::$logpath . " error"));
//				}
//				else
//				{
//					die("error");
//				}
//			}
//		}
//		elseif (!is_dir(self::$logpath))
//		{
//			if (DEBUG_MODE)
//			{
//				die(Tools::displayError(self::$logpath . " is already token by a file"));
//			}
//			else
//			{
//				die("error");
//			}
//		}
//		else
//		{
//			if (!is_writable(self::$logpath))
//			{
//				@chmod(self::$logpath, 0777);
//			}
//			$logfile = rtrim(self::$logpath, '/') . '/' . $strFileName . '.log';
//			if (file_exists($logfile) && !is_writable($logfile))
//			{
//				@chmod($logfile, 0644);
//			}
//			$handle = @fopen($logfile, "a");
//			if ($handle)
//			{
//				$strContent = $strMSG . "\n";
//				if (!fwrite($handle, $strContent))
//				{
//					@fclose($handle);
//					die("Write permission deny");
//				}
//				@fclose($handle);
//			}
//		}
//	}

//	/**
//	 * 读文件内容
//	 *
//	 * @param $strFileName
//	 *
//	 * @return bool|string
//	 */
//	public static function simpleread($strFileName) {
//		$logfile = trim(self::$logpath, '/') . '/' . $strFileName . '.log';
//		if (file_exists($logfile) && is_readable($logfile))
//		{
//			$strContent = '';
//			$handler = @fopen($logfile, 'r');
//			if ($handler)
//			{
//				while (!feof($handler))
//				{
//					$strContent .= fgets($handler);
//				}
//				@fclose($handler);
//			}
//
//			return $strContent;
//		}
//
//		return false;
//	}
}
