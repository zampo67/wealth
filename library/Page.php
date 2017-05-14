<?php

class Page {
    // 分页栏每页显示的页数
    public $rollPage = 10;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 默认列表每页显示行数
    public $listRows = 20;
    // 起始行数
    public $firstRow	;
    // 分页总页面数
    protected $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage    ;
    // 分页的栏的总页数
    protected $coolPages   ;
    // 分页显示定制
    protected $config  =	array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' %first% %upPage% %prePage% %linkPage% %nextPage% %downPage% %end% %search%' );
    // 默认分页变量名
    protected $varPage;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     +----------------------------------------------------------
     */
    public function __construct($totalRows,$listRows='',$parameter='') {
        $this->totalRows = $totalRows;
        $this->parameter = $parameter;
        $this->varPage = 'start' ;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = !empty($_GET[$this->varPage])?intval($_GET[$this->varPage]):1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    /**
     +----------------------------------------------------------
     * 分页显示输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show() {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?");
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<li><a href='".$url."&".$p."=$upRow{$this->parameter}'>".$this->config['prev']."</a></li>";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<li><a href='".$url."&".$p."=$downRow{$this->parameter}'>".$this->config['next']."</a></li>";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            //$prePage = "<a href='".$url."&".$p."=$preRow' >上".$this->rollPage."页</a>";
            $prePage = "<li><a href='".$url."&".$p."=$preRow{$this->parameter}' >...</a>";
            $theFirst = "<li><a href='".$url."&".$p."=1{$this->parameter}' >".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            //$nextPage = "<a href='".$url."&".$p."=$nextRow' >下".$this->rollPage."页</a>";
            $nextPage = "<li><a href='".$url."&".$p."=$nextRow{$this->parameter}' >...</a></li>";
            $theEnd = "<li><a href='".$url."&".$p."=$theEndRow{$this->parameter}' >".$this->config['last']."(".$this->totalPages.")"."</a></li>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "<li><a href='".$url."&".$p."=$page{$this->parameter}'>".$page."</a></li>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "<li class='disabled'><a href='".$url."&".$p."=$page{$this->parameter}'>".$page."</a></li>";
                }
            }
        }

        if($this->totalPages>10){
            $search = '<input type="text" data-url="'.$url.'&'.$p.'=" class="span2 z-page" placeholder="跳转页">';
        }else{
            $search = '';
        }
        
        $pageStr	 =	 str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%search%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$search),$this->config['theme']);
        return $pageStr;
    }

    public static function showArray($total_page, $current_page){
        $res = array();
        $limit = 10;

        $prev = array(
            'page' => ($current_page==1) ? 1 : $current_page-1,
            'text' => '«',
            'type' => ($current_page==1) ? 0 : 2,
        );
        $next = array(
            'page' => ($current_page == $total_page) ? $total_page : $current_page+1,
            'text' => '»',
            'type' => ($current_page == $total_page) ? 0 : 2,
        );

        $res[] = $prev;

        if($total_page <= $limit){
            for ($i=1; $i<=$total_page; $i++){
                $res[] = array(
                    'page' => $i,
                    'text' => $i,
                    'type' => ($i==$current_page) ? 1 : 2,
                );
            }
        }else{
            $min = floor(($current_page-1)/$limit) * $limit + 1;
            $max = ceil($current_page/$limit) * $limit;
            if($min > $limit){
                $res[] = array(
                    'page' => $current_page-$limit,
                    'text' => '...',
                    'type' => 2,
                );
            }
            if($max > $total_page){
                $max = $total_page;
            }
            for ($i=$min; $i<=$max; $i++){
                $res[] = array(
                    'page' => $i,
                    'text' => $i,
                    'type' => ($i==$current_page) ? 1 : 2,
                );
            }
            if($max < $total_page){
                $next_limit_page = $current_page+$limit;
                if($next_limit_page > $total_page){
                    $next_limit_page = $total_page;
                }
                $res[] = array(
                    'page' => $next_limit_page,
                    'text' => '...',
                    'type' => 2,
                );
            }
        }

        $res[] = $next;

        return $res;
    }

}