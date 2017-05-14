<?php

class Common{

    public static function cookie($name='', $value='', $option=null) {
        $global_var = Yaf\Registry::get('global_var');
        // 默认设置
        $config = array(
            'prefix'    =>  isset($option['prefix']) ? $option['prefix'] : '', // cookie 名称前缀
            'expire'    =>  86400 * 30, // cookie 保存时间
            'path'      =>  '/', // cookie 保存路径
            'domain'    =>  isset($global_var['cookie_domain']) ? $global_var['cookie_domain'] : '', // cookie 有效域名
            'httponly'  =>  '', // httponly设置
        );
        // 参数设置(会覆盖黙认设置)
        if (!is_null($option)) {
            if (is_numeric($option))
                $option = array('expire' => $option);
            elseif (is_string($option))
                parse_str($option, $option);
            $config     = array_merge($config, array_change_key_case($option));
        }
        if(!empty($config['httponly'])){
            ini_set("session.cookie_httponly", 1);
        }
        // 清除指定前缀的所有cookie
        if (is_null($name)) {
            if (empty($_COOKIE))
                return null;
            // 要删除的cookie前缀，不指定则删除config设置的指定前缀
            $prefix = empty($value) ? $config['prefix'] : $value;
            if (!empty($prefix)) {// 如果前缀为空字符串将不作处理直接返回
                foreach ($_COOKIE as $key => $val) {
                    if (0 === stripos($key, $prefix)) {
                        setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                        unset($_COOKIE[$key]);
                    }
                }
            }
            return null;
        }elseif('' === $name){
            // 获取全部的cookie
            return $_COOKIE;
        }
        $name = $config['prefix'] . str_replace('.', '_', $name);
        if ('' === $value) {
            if(isset($_COOKIE[$name])){
                $value =    $_COOKIE[$name];
                if(0===strpos($value,'lx:')){
                    $value  =   substr($value,6);
                    return array_map('urldecode',json_decode(MAGIC_QUOTES_GPC?stripslashes($value):$value,true));
                }else{
                    return $value;
                }
            }else{
                return null;
            }
        } else {
            if (is_null($value)) {
                setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
                unset($_COOKIE[$name]); // 删除指定cookie
            } else {
                // 设置cookie
                if(is_array($value)){
                    $value  = 'lx:'.json_encode(array_map('urlencode',$value));
                }
                $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
                setcookie($name, $value, $expire, $config['path'], $config['domain']);
                $_COOKIE[$name] = $value;
            }
        }
        return null;
    }

    public static function sqlInjectReplace($str=''){
        if(!empty($str) && is_string($str)){
            $str = addslashes($str);
        }
        return $str;
    }

    /**
     * 获取成功返回的json数据
     * @param array $data 返回的数组
     * @param string $msg 信息
     * @return string json字符串
     */
    public static function sendSuccessRes($data=array(), $msg=''){
        $response = self::arrToJson(array(
            'status' => 1,
            'code' => CODE_SUCCESS,
            'data' => !empty($data) ? $data : new \stdClass(),
            'msg' => $msg
        ));
        header('Content-type: application/json');
        echo $response;
        Log::request('res|succ', $response);
    }

    /**
     * 获取失败返回的json数据
     * @param int $error_code 错误码
     * @param string $msg    错误信息
     * @param array $data    数据
     * @return string        json字符串
     */
    public static function sendFailRes($error_code=CODE_UNKNOWN_ERROR, $msg='', $data=array()){
        $response = self::arrToJson(array(
            'status' => 0,
            'code' => $error_code,
            'data' => !empty($data) ? $data : new \stdClass(),
            'msg' => (empty($msg) && is_numeric($error_code)) ? I18n::getInstance()->getErrorCode($error_code) : $msg,
        ));
        header('Content-type: application/json');
        echo $response;
        Log::request('res|fail', $response);
        exit;
    }

    /**
     * 将数组转换成json
     * @param array $arr 数组
     * @return string  json字符串
     */
    public static function arrToJson($arr) {
        $json = json_encode ( $arr );
        // $json = preg_replace('/\"(\d+)\"/', '$1', $json);
//        $json = preg_replace('/\"(\d+)\.(\d+)"/', '$1.$2', $json);
        return $json;
    }

    /**
     * 对数组进行排序
     * @param array  $arr   原始数组
     * @param string $keys  根据该值进行排序
     * @param string $type  升序/降序
     * @return array 排序后的数组
     */
    public static function arraySort($arr, $keys, $type = 'asc') {
        $keysvalue = $new_array = array ();
        foreach ( $arr as $k => $v ) {
            $keysvalue [$k] = $v [$keys];
        }
        if ($type == 'asc') {
            asort ( $keysvalue );
        } else {
            arsort ( $keysvalue );
        }
        reset ( $keysvalue );
        foreach ( $keysvalue as $k => $v ) {
            $new_array [] = $arr [$k];
        }
        return $new_array;
    }

    /**
     * 对一维数组排序
     * @param array $arg 原始数组
     * @return array mixed 排序后的数组
     */
    public static function argSort($arg) {
        ksort($arg);
        reset($arg);
        return $arg;
    }

    /**
     * 对数组排序，支持多级排序
     * sortArr($Array,"Key1","SORT_ASC","SORT_RETULAR","Key2"……)
     * @param  array   $ArrayData   the array to sort.
     * @param  string  $KeyName1    the first item to sort by.
     * @param  string  $SortOrder1  the order to sort by("SORT_ASC"|"SORT_DESC")
     * @param  string  $SortType1   the sort type("SORT_REGULAR"|"SORT_NUMERIC"|"SORT_STRING")
     * @return array                sorted array.
     */
    public static function sortArr($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR"){
        if(empty($ArrayData) || !is_array($ArrayData)){
            return $ArrayData;
        }

        // Get args number.
        $ArgCount = func_num_args();
        // Get keys to sort by and put them to SortRule array.
        for($I = 1; $I < $ArgCount; $I ++) {
            $Arg = func_get_arg($I);
            if (!eregi("SORT",$Arg)) {
                $KeyNameList[] = $Arg;
                $SortRule[] = '$'.$Arg;
            }else{
                $SortRule[] = $Arg;
            }
        }
        // Get the values according to the keys and put them to array.
        foreach($ArrayData AS $Key => $Info){
            foreach($KeyNameList AS $KeyName){
                ${$KeyName}[$Key] = strtolower($Info[$KeyName]);
            }
        }

        // Create the eval string and eval it.
        $EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';
        eval ($EvalString);
        return $ArrayData;
    }

    /**
     * 返回以数组中指定的一列的值作为key重置后数组,可将另外一列的值作为value
     * @param array $arr 原始数组
     * @param int|string $key 作为key的列
     * @param int|string $value 作为value的列,为空则返回整个子数组
     * @return array
     */
    public static function arrayColumnToKey($arr, $key, $value=null){
        return array_column($arr, $value, $key);
    }

    public static function curlGetResponse($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $res = array_merge(array('header' => $httpinfo), array('body' => $package));
        return $res;
    }

    /**
     * 保存文件
     * @param string $filepath 路径
     * @param string $filename 名称
     * @param array $filecontent 内容
     * @return bool
     */
    public static function saveFile($filepath, $filename, $filecontent){
        if(!is_dir($filepath)){
            mkdir($filepath, 0777 ,true);
        }
        $local_file = fopen($filepath.$filename, 'w');
        if (false !== $local_file){
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $param 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    public static function createLinkstring($param) {
        $arg  = "";
        while (list ($key, $val) = each ($param)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        return substr($arg,0,count($arg)-2);
    }

    /**
     * 创建基础URL
     * @param string $path 路径
     * @param array $param 参数
     * @return string URL
     */
    public static function createBaseUrl($path, $param=array()){
        if(substr($path, 0, 1) != '/' && !preg_match('#[http|https]://#', $path)){
            $path = '/'.$path;
        }
        $param = Rijndael::getParam($path, $param);
        $query = empty($param) ? '' : '?'.http_build_query($param);
        return $path.$query;
    }

    /**
     * 创建简历端URL
     * @param string $path 路径
     * @param array $param 参数
     * @param int $is_domain 是否需要域名
     * @return string URL
     */
    public static function createResumeUrl($path, $param=array(), $is_domain=0){
        return ($is_domain==1?RESUME_DOMAIN:'').self::createBaseUrl($path, $param);
    }

    /**
     * 根据时间生成图片名
     * @param string $image_type
     * @return float|string
     */
    public static function getImageType($image_type = "image/jpeg") {
        switch ($image_type){
            case 'image/jpeg': case 'image/pjpeg':
                $res = '.jpg';
                break;
            case 'image/gif':
                $res = '.gif';
                break;
            case 'image/png':
                $res = '.png';
                break;
            default:
                $res = '';
                break;
        }
        return $res;
    }

    public static function getImageTypeByPath($path){
        $type_int = @exif_imagetype($path);

        switch ($type_int){
            case IMAGETYPE_GIF:
                $res = '.gif';
                break;
            case IMAGETYPE_JPEG:
                $res = '.jpg';
                break;
            case IMAGETYPE_PNG:
                $res = '.png';
                break;
            case IMAGETYPE_BMP:
                $res = '.bmp';
                break;
            default :
                $res = '.jpg';
                break;
        }
        return $res;
    }

    /**
     * 处理图片上传变横
     * @param $full_filename
     * @return string
     */

    public static function adjustPicOrientation($full_filename){
        $exif = @exif_read_data($full_filename);
        if($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            if($orientation != 1){
                $img = imagecreatefromjpeg($full_filename);

                $mirror = false;
                $deg    = 0;

                switch ($orientation) {
                    case 2:
                        $mirror = true;
                        break;
                    case 3:
                        $deg = 180;
                        break;
                    case 4:
                        $deg = 180;
                        $mirror = true;
                        break;
                    case 5:
                        $deg = 270;
                        $mirror = true;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 7:
                        $deg = 90;
                        $mirror = true;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                if ($deg) $img = imagerotate($img, $deg, 0);
//                if ($mirror) $img = _mirrorImage($img);
//                $full_filename = str_replace('.jpg', "-O$orientation.jpg",  $full_filename);新文件名
                imagejpeg($img, $full_filename, 95);
            }
        }
        return $full_filename;
    }

    /**
     * 解析XML
     * @param string $xml XML数据
     * @return array|bool
     */
    public static function parseXML($xml){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser, $xml, true)){
            xml_parser_free($xml_parser);
            return false;
        }else{
            xml_parser_free($xml_parser);
            return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
    }

    /**
     * 通过tag_name解析XML
     * @param string $xml XML数据
     * @param string $tagNames 需要解析的字段
     * @return array|string
     */
    public static function parseXMLByTagName($xml, $tagNames){
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($xml);
        $root = $xmlDoc->documentElement;
        if(is_array($tagNames)){
            $results = array();
            foreach ($tagNames as $tagName) {
                $elements = $root->getElementsByTagName($tagName);
                $value = $elements->item(0)->nodeValue;
                $temp = array($tagName => $value);
                $results = array_merge($results, $temp);
            }
        }else{
            $elements = $root->getElementsByTagName($tagNames);
            $results = $elements->item(0)->nodeValue;
        }

        return $results;
    }

    /**
     * 获取固定长度的前面带0的字符串
     * @param int|string $str 原字符串
     * @param int $res_length 返回的长度
     * @return string
     */
    public static function getZeroFillStr($str, $res_length=0){
        if(empty($res_length) || !is_numeric($res_length) || empty($str)){
            return 0;
        }
        $str_length = mb_strlen($str);
        if($res_length <= $str_length){
            return mb_substr($str, 0, $res_length);
        }else{
            $for_num = $res_length - $str_length;
            for ($i=0; $i<$for_num; $i++){
                $str = '0'.$str;
            }
            return $str;
        }
    }

    public static function getMicrotime() {
        list($usec, $sec) = explode(" ", microtime());
        return floor($sec + $usec * 1000000);
    }

    /**
     * 返回当前时间（毫秒级）
     * @return float
     */
    public static function microtimeFloat() {
        list ( $usec, $sec ) = explode ( " ", microtime () );
        return (( float ) $usec + ( float ) $sec);
    }

    /**
     * 获取指定位数的时间戳
     * @param int $len
     * @return int
     */
    public static function microtimeTimestamp($len=3){
        list ( $usec, $sec ) = explode ( " ", microtime () );
        return ( $sec*pow(10, $len) + ceil($usec*pow(10, $len)));
    }

    /**
     * 格式化时间戳，精确到毫秒，x代表毫秒
     * @param string $tag
     * @param float $time
     * @return string
     */
    public static function microtimeFormat($tag, $time) {
        $arr = explode ( ".", $time );
        $usec = isset($arr[0]) ? $arr[0] : 0;
        $sec = isset($arr[1]) ? $arr[1] : 0;
        $date = date ( $tag, $usec );

        $num = strlen($sec);
        for ($i=$num; $i<4; $i++){
            $sec .= '0';
        }

        return str_replace ( 'x', $sec, $date );
    }

    public static function timeTree($time){
        $time_ymd = date('Ymd',$time);
        $now_ymd = date('Ymd');
        $yes_ymd = date('Ymd',strtotime('-1 days'));
        $byes_ymd = date('Ymd',strtotime('-2 days'));
        switch($time_ymd){
            case $now_ymd:
                $str = date('H:i',$time);
                break;
            case $yes_ymd:
                $str = '昨天 '.date('H:i',$time);
                break;
            case $byes_ymd:
                $str = '前天 '.date('H:i',$time);
                break;
            default:
                if(date('Y', $time) == date('Y')){
                    $str = date('m.d H:i',$time);
                }else{
                    $str = date('Y.m.d H:i',$time);
                }
                break;
        }
        return $str;
    }

    public static function tree($data){
        // 制作树状结构
        $res = array();
        foreach($data as $val){
            if($val['pid']==0){
                $res[$val['id']] = $val;
            }else{
                $val['name'] = '├──'.$val['name'];
                $res[$val['pid']]['son'][] = $val;
            }
        }
        return $res;
    }

    public static function arrayInt($arr){
        foreach($arr as $key=>$val){
            $arr[$key] = intval($val);
        }
        return $arr;
    }
    // 获取本月最后一天
    public static function getCurMonthLastDay($time) {
        return date('d', strtotime(date('Y-m-01', $time) . ' +1 month -1 day'));
    }

    // 通过经纬度获取市
    public static function getCity($lat,$lng){
        $url = "http://api.map.baidu.com/geocoder/v2/?ak=Uz35x4gujeTWMCbcyn86dUUP&location=".$lat.",".$lng."&output=json";

        $res = Tools::curl($url);
        $data = json_decode($res,true);
        $city = $data['result']['addressComponent']['city'];
        $last = Tools::substr($city,-1);
        if($last == '市'){
            return Tools::substr($city,0,-1);
        }
        return $city;
    }

    // 判断是不是admin 账户
    public static function isAdmin(){
        $id = AdminUserModel::getId();
        if('1'==$id){
            return true;
        }
        return false;
    }

    public static function getCnRepresentationOfDayOfWeek($en_representation){
        switch ($en_representation){
            case 'Monday':
                $cn_representation = '周一';
                break;
            case 'Tuesday':
                $cn_representation = '周二';
                break;
            case 'Wednesday':
                $cn_representation = '周三';
                break;
            case 'Thursday':
                $cn_representation = '周四';
                break;
            case 'Friday':
                $cn_representation = '周五';
                break;
            case 'Saturday':
                $cn_representation = '周六';
                break;
            case 'Sunday':
                $cn_representation = '周天';
                break;
            default :
                $cn_representation = '';
                break;
        }
        return $cn_representation;
    }

    /**
     * 获取两个时间相差的月数(精确到两位小数)
     * @param string $date1 开始时间
     * @param string $date2 结束时间
     * @param array $diff_date 两个时间相差的数据
     * @return float|mixed
     */
    public static function diffMonths($date1, $date2, $diff_date=array()){
        if(empty($diff_date)){
            $diff_date = self::diffDate($date1, $date2);
        }

        $months = $diff_date['month'];
        if($diff_date['year'] > 0){
            $months += $diff_date['year'] * 12;
        }
        if($diff_date['day'] > 0){
            $months += round($diff_date['day'] / date('t', strtotime($date2)), 2);
        }
        return $months;
    }

    /**
     * 获取两个时间相差的数据(包含年月日)
     * @param string $date1 开始时间
     * @param string $date2 结束时间
     * @return array
     */
    public static function diffDate($date1, $date2){
        if(strtotime($date1)>strtotime($date2)){
            $tmp=$date2;
            $date2=$date1;
            $date1=$tmp;
        }
        list($Y1,$m1,$d1)=explode('-',$date1);
        list($Y2,$m2,$d2)=explode('-',$date2);
        $Y=$Y2-$Y1;
        $m=$m2-$m1;
        $d=$d2-$d1;
        if($d<0){
            $d+=(int)date('t',strtotime("-1 month $date2"));
            $m--;
        }
        if($m<0){
            $m+=12;
            $Y--;
        }
        return array('year'=>$Y,'month'=>$m,'day'=>$d);
    }

    public static function getLetter($num){
        return chr($num+65);
    }

    public static function readXls($resource){
        $objPHPExcel = PHPExcel_IOFactory::load($resource);
        return $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
    }

    public static function exportXls($data, $option, $file_name, $ex='2003'){
        $objExcel = new PHPExcel();
        //设置属性 (这段代码无关紧要，其中的内容可以替换为你需要的)
//        $objExcel->getProperties()->setCreator("andy");
//        $objExcel->getProperties()->setLastModifiedBy("andy");
//        $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
//        $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
//        $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
//        $objExcel->getProperties()->setKeywords("office 2003 openxml php");
//        $objExcel->getProperties()->setCategory("Test result file");
        $objExcel->setActiveSheetIndex(0);

        $i=0;
        foreach ($option as $k=>$v){
            $letter = Common::getLetter($k);
//            $letter = $v['field'];
            //表头
            $objExcel->getActiveSheet()->setCellValueExplicit(strtolower($letter).'1', $v['title']);//表头
            if(!empty($v['width'])){
                // 列的宽度
                $objExcel->getActiveSheet()->getColumnDimension($letter)->setWidth($v['width']);
            }
        }

        foreach($data as $k=>$v) {
            $u1=$i+2;
            /*----------写入内容-------------*/
            foreach ($option as $ok=>$ov){
                $letter = Common::getLetter($ok);
//                $letter = $ov['field'];
                $objExcel->getActiveSheet()->setCellValueExplicit(strtolower($letter).$u1, str_replace(array('='),'',$v[$ov['field']]));
            }
            $i++;
        }

        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

        // 设置页方向和规模
        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);

//        ob_end_clean();
        if($ex == '2007') { //导出excel2007文档
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {  //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }

    public static function getAge($birth_time){
        if($birth_time>0){
            $age = date('Y', time()) - date('Y', $birth_time) - 1;
            if (date('m', time()) == date('m', $birth_time)){

                if (date('d', time()) > date('d', $birth_time)){
                    $age++;
                }
            }elseif (date('m', time()) > date('m', $birth_time)){
                $age++;
            }
            return $age;
        }else{
            return '';
        }
    }

    public static function getConstellation($birth_time){
        $month   = date('m', $birth_time);
        $day = date('d', $birth_time);
        $signs = array(
            array('20'=>'水瓶座'),
            array('19'=>'双鱼座'),
            array('21'=>'白羊座'),
            array('20'=>'金牛座'),
            array('21'=>'双子座'),
            array('22'=>'巨蟹座'),
            array('23'=>'狮子座'),
            array('23'=>'处女座'),
            array('23'=>'天秤座'),
            array('24'=>'天蝎座'),
            array('22'=>'射手座'),
            array('22'=>'摩羯座')
        );
        list($start, $name) = each($signs[$month-1]);
        if ($day < $start)
            list($start, $name) = each($signs[($month-2 < 0) ? 11 : $month-2]);
        return $name;
    }

    public static function getEmailURL($email){
        $arr = explode('@',$email);
        if(!isset($arr[1])){
            return '';
        }
        switch($arr[1]){
            case '163.com':
                $emailURL = 'http://mail.163.com/';
                break;
            case 'qq.com':
                $emailURL = 'http://mail.qq.com/';
                break;
            case 'sina.com':
            case 'sina.cn':
                $emailURL = ' http://mail.sina.com.cn/';
                break;
            case 'gmail.com':
                $emailURL = 'http://www.gmail.com/';
                break;
            case 'sohu.com':
                $emailURL = 'http://mail.sohu.com/';
                break;
            case 'outlook.com':
                $emailURL = 'http://outlook.com/';
                break;
            case '126.com':
                $emailURL = 'http://www.126.com/';
                break;
            case '189.cn':
                $emailURL = 'http://webmail30.189.cn/';
                break;
            case '139.com':
                $emailURL = 'http://mail.10086.cn/';
                break;
            default :
                $emailURL = '';
                break;
        }
        return $emailURL;
    }

    /**
     * 判断是否正确的手机号码
     * @param int $mobilePhone 手机号
     * @return bool
     */
    public static function isMobile($mobilePhone) {
        return preg_match("/^13[0-9]{1}[0-9]{8}$|14[57]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|17[013678]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/", $mobilePhone) ? true : false;
    }

    /**
     * 判断是否正确的邮箱
     * @param string $email 邮箱
     * @return bool
     */
    public static function isEmail($email){
        return preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i", $email) ? true : false;
    }

    /**
     * 判断是否正确的微信号
     * @param string $wechat 微信号
     * @return bool
     */
    public static function isWechat($wechat){
        return preg_match("/[a-zA-Z0-9_-]{6,20}$/", $wechat) ? true : false;
    }

    public static function isPWD($value,$minLen=6,$maxLen=16){
        $match='/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{'.$minLen.','.$maxLen.'}$/';
        $v = trim($value);
        if(empty($v))
            return false;
        return preg_match($match,$v);
    }

    public static function compareMobile($codeData,$mobile,$code){
        if(empty($code) || empty($mobile)) return false;
        foreach($codeData as $val){
            if($val['code']==$code && $val['mobile']==$mobile){
                return true;
            }
        }
        return false;
    }

    /**
     * 将换行符替换为<br/>
     * @param string $str 字符串
     * @return string
     */
    public static function replaceBr($str){
        return str_replace(array(PHP_EOL),array('<br/>'),$str);
    }

    /**
     * 下划线转换成驼峰
     * @param string $str 字符串
     * @return string
     */
    public static function strUnderToHump($str){
        $list = explode('_', $str);
        $str = '';
        foreach ($list as $k=>$item){
            if($k > 0){
                $item = ucfirst($item);
            }
            $str .= $item;
        }
        return $str;
    }
    
    /**
     * 判断字符串长度,中文字符1,其他字符0.5,长度为小数点时往上取整
     * @param string $str 字符串
     * @return bool|int
     */
    public static function strlenHalf($str){
        return is_string($str) ? ceil((strlen($str)+mb_strlen($str))/4) : false;
    }

    /**
     * 判断字符串长度,中文字符2,其他字符1,长度为小数点时往上取整
     * @param string $str 字符串
     * @return bool|int
     */
    public static function strlenFull($str){
        return is_string($str) ? ceil((strlen($str)+mb_strlen($str))/2) : false;
    }

    //php获取中文字符拼音首字母
    public static function getFirstCharter($str){
        if(empty($str)){return '';}
        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
        $s1=iconv('UTF-8','gb2312',$str);
        $s2=iconv('gb2312','UTF-8',$s1);
        $s=$s2==$str?$s1:$str;
        $asc=ord($s{0})*256+ord($s{1})-65536;
        if($asc>=-20319&&$asc<=-20284) return 'A';
        if($asc>=-20283&&$asc<=-19776) return 'B';
        if($asc>=-19775&&$asc<=-19219) return 'C';
        if($asc>=-19218&&$asc<=-18711) return 'D';
        if($asc>=-18710&&$asc<=-18527) return 'E';
        if($asc>=-18526&&$asc<=-18240) return 'F';
        if($asc>=-18239&&$asc<=-17923) return 'G';
        if($asc>=-17922&&$asc<=-17418) return 'H';
        if($asc>=-17417&&$asc<=-16475) return 'J';
        if($asc>=-16474&&$asc<=-16213) return 'K';
        if($asc>=-16212&&$asc<=-15641) return 'L';
        if($asc>=-15640&&$asc<=-15166) return 'M';
        if($asc>=-15165&&$asc<=-14923) return 'N';
        if($asc>=-14922&&$asc<=-14915) return 'O';
        if($asc>=-14914&&$asc<=-14631) return 'P';
        if($asc>=-14630&&$asc<=-14150) return 'Q';
        if($asc>=-14149&&$asc<=-14091) return 'R';
        if($asc>=-14090&&$asc<=-13319) return 'S';
        if($asc>=-13318&&$asc<=-12839) return 'T';
        if($asc>=-12838&&$asc<=-12557) return 'W';
        if($asc>=-12556&&$asc<=-11848) return 'X';
        if($asc>=-11847&&$asc<=-11056) return 'Y';
        if($asc>=-11055&&$asc<=-10247) return 'Z';
        return null;
    }

    /**
     * 时间格式
     * @param $time
     * @param int $type 1为中文时间格式,2为英文时间格式
     * @return bool|string
     */
    public static function timeFormat($time,$type=1){
        if($type == 1){
            $time_ymd = date('Ymd',$time);
            $now_ymd = date('Ymd');
            $yes_ymd = date('Ymd',strtotime('-1 days'));
            $byes_ymd = date('Ymd',strtotime('-2 days'));
            switch($time_ymd){
                case $now_ymd:
                    $str = '今天 '.date('H:i',$time);
                    break;
                case $yes_ymd:
                    $str = '昨天 '.date('H:i',$time);
                    break;
                case $byes_ymd:
                    $str = '前天 '.date('H:i',$time);
                    break;
                default:
                    $str = date('Y/m/d',$time);
                    break;
            }
        }else{
            $changeTime = (time()-$time)/3600;
            if($changeTime<1){
                $str = intval($changeTime*60)<1 ? '1 minute age' : intval($changeTime*60).' minutes age';
            }else{
                if(24<$changeTime && $changeTime<365){
                    $str = intval($changeTime/24)<2 ? '1 day age' : intval($changeTime/24).' days age';
                }elseif ($changeTime>8760){
                    $str = intval($changeTime/8760)<2 ? '1 year age' : intval($changeTime/8760).' years age';
                } else{
                    $str = intval($changeTime)<2 ? '1 hours age' : intval($changeTime).' hours age';
                }
            }
        }
        return $str;
    }

    /**
     * 把图片合并到背景图上
     * @param array $options 参数信息
     * @return bool
     * @throws Exception
     */
    public static function mergeImagesToBgImage($options){
        if(empty($options['bg_path']) || empty($options['bg_name']) || empty($options['des_path']) || empty($options['des_name'])){
            return false;
        }
        if(!file_exists($options['bg_path'].$options['bg_name'])){
            return false;
        }
        //背景图信息
        $bg_type = Common::getImageTypeByPath($options['bg_path'].$options['bg_name']);
        switch ($bg_type){
            case '.jpg':
            default :
                $bg_img = imagecreatefromjpeg($options['bg_path'].$options['bg_name']);
                break;
            case '.png':
                $bg_img = imagecreatefrompng($options['bg_path'].$options['bg_name']);
                break;
            case '.gif':
                $bg_img = imagecreatefromgif($options['bg_path'].$options['bg_name']);
                break;
        }
        $bg_width = imagesx($bg_img);
        $bg_height = imagesy($bg_img);
        //生成跟背景图同样大小的图片
        $create_img = imagecreatetruecolor($bg_width, $bg_height);

        //先渲染背景图
        if(!isset($options['bg_first']) || $options['bg_first'] == 1){
            //将背景图合并到生成的图片上
            imagecopymerge($create_img, $bg_img, 0, 0, 0, 0, $bg_width, $bg_height, 100);
        }

        //将图片合并到生成的图片上
        if(!empty($options['source_list'])){
            foreach ($options['source_list'] as $source){
                if(isset($source['url'])){
                    Common::saveFile($source['temp_path'], $source['temp_name'].$source['temp_type'], Tools::curl($source['url']));
                    $file_path = $source['temp_path'].$source['temp_name'].$source['temp_type'];
                    $file_type = Common::getImageTypeByPath($file_path);
                    if($file_type != $source['temp_type']){
                        rename($source['temp_path'].$source['temp_name'].$source['temp_type'], $source['temp_path'].$source['temp_name'].$file_type);
                        $file_path = $source['temp_path'].$source['temp_name'].$file_type;
                    }
                }else{
                    $file_path = $source['path'];
                    $file_type = Common::getImageTypeByPath($file_path);
                }

                //压缩图片
                if(!isset($source['cut']) || $source['cut'] == 1){
                    new ImageProc($file_path, $file_path, $source['width'], $source['height'], 1, 1);
                }
                //获取压缩后的图片信息
                switch ($file_type){
                    case '.jpg':
                    default :
                        $source['img'] = imagecreatefromjpeg($file_path);
                        break;
                    case '.png':
                        $source['img'] = imagecreatefrompng($file_path);
                        break;
                    case '.gif':
                        $source['img'] = imagecreatefromgif($file_path);
                        break;
                }
                //删除图片
                if(!isset($source['unlink']) || $source['unlink'] == 1){
                    unlink($file_path);
                }
                imagecopymerge($create_img, $source['img'], $source['x'], $source['y'], 0, 0, $source['width'], $source['height'], 100);
            }
        }

        //后渲染背景图
        if(isset($options['bg_first']) && $options['bg_first'] != 1){
            //将背景图合并到生成的图片上
            imagecopymerge($create_img, $bg_img, 0, 0, 0, 0, $bg_width, $bg_height, 100);
        }

        //将文字添加到生成的图片上
        if(!empty($options['text_list'])){
            foreach ($options['text_list'] as $text){
                imagettftext($create_img, $text['fontsize'], 0, $text['x'], $text['y'],
                    imagecolorallocate($create_img, $text['color_red'], $text['color_green'], $text['color_blue']), $text['ttf_path'], $text['text']);
            }
        }
        if(!is_dir($options['des_path'])){
            mkdir($options['des_path'], 0777 ,true);
        }
        return imagejpeg($create_img, $options['des_path'].$options['des_name']);
    }

    public static function getRedisKey($type, $key){
        switch ($type){
            case 'company_email_vefify':
                return 'company_email_vefify_'.$key;
                break;
            case 'resume_email_vefify':
                return 'resume_email_vefify_'.$key;
                break;
            default :
                return '';
                break;
        }
    }

    public static function sendMailVerify($email,$uid){
        //sendVerifyEmail
        $key = Tools::passwdGen(32);
        IRedis::getInstance()->setEx(Common::getRedisKey('resume_email_vefify',$key),$uid,30*86400);
        $url = Common::createResumeUrl('site/verifiEmail',array('key'=>$key));

        $subject = '欢迎加入知页, 请验证邮箱'; //TODO::老版本的邮箱验证链接
        $options = array(
            'logoimg' => '<img src="'.LOGO_URL.'" style="height:36px;"/>',
            'wxqrcodeimg' => '<img src="'.IMAGE_DOMAIN.'/static/images/common/wxqrcode_email.png'.'" style="width:100px;height:100px;"/>',
            'email' => $email,
            'url' => str_replace('https://','http://',DOMAIN).$url,
            'link' => str_replace(array('https://','http://'),array('',''),DOMAIN).$url,
            'dsxQQ' => SERVICE_QQ,
            'domain' => str_replace('https://','http://',DOMAIN),
        );
        $fromname = SendCloud::PARAM_FROMNAME;
        $from = SendCloud::PARAM_FROM;
        $res = SendCloud::sendTemplateMail($email,$subject,SendCloud::TEMPLATE_MAIL_LOGIN_VERIFI,$options,$fromname,$from);
        $res = json_decode($res,true);
        if(!empty($res['message']) && $res['message']=='success'){
            return true;
        }else{
            return false;
        }
    }

}
