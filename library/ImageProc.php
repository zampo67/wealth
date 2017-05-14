<?php 

class ImageProc{
    //图片类型
    var $type;
    //实际宽度
    var $width;
    //实际高度
    var $height;
    //改变后的宽度
    var $resize_width;
    //改变后的高度
    var $resize_height;
    //是否裁图
    var $cut;
    //源的 X 坐标点
    var $src_x=0;
    //源的 Y 坐标点
    var $src_y=0;
    //源图象
    var $srcimg;
    //目标图象地址
    var $dstimg;
    //临时创建的图象
    var $im;
 
    function __construct($img, $dstpath, $wid=0, $hei=0, $c=0, $is_middle=0, $src_x=0, $src_y=0){
        $this->srcimg = $img;
        $this->cut = $c;
        //图片的类型
        $this->type = str_replace('.','',Common::getImageTypeByPath($this->srcimg));
        
        //初始化图象
        $this->initi_img();
        //目标图象地址
        $this->dst_img($dstpath);
        
        $this->width = imagesx($this->im);
        $this->height = imagesy($this->im);
        
        if($wid == 0 && $hei == 0){
//             $wid = $this->width;
//             $hei = $this->height;
        	            $wid = $this->width*0.3;
        	            $hei = $this->height*0.3;
        }elseif($wid != 0 && $hei == 0){
            $hei = $wid * ($this->height/$this->width);
        }elseif($wid == 0 && $hei != 0){            
            $wid = $hei * ($this->width/$this->height);
        }

        if($is_middle==1){
            if($this->width>$this->height){
                $this->src_x = ($this->width-$this->height)/2;
            }else{
                $this->src_y = ($this->height-$this->width)/2;
            }
        }else{
            $this->src_x = $src_x;
            $this->src_y = $src_y;
        }

        $this->resize_width = $wid;
        $this->resize_height = $hei;

        //生成图象
        $this->newimg();
        ImageDestroy ($this->im);
    }
    
    function newimg(){
        //改变后的图象的比例
        $resize_ratio = ($this->resize_width)/($this->resize_height);
        //实际图象的比例
        $ratio = ($this->width)/($this->height);
        if(($this->cut) == "1"){
            //裁图
            if($ratio>=$resize_ratio){
                //高度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                $color=imagecolorallocate($newimg,255,255,255);
                imagecolortransparent($newimg,$color);
                imagefill($newimg,0,0,$color);
                imagecopyresampled($newimg, $this->im, 0, 0, $this->src_x, $this->src_y, $this->resize_width,$this->resize_height, (($this->height)*$resize_ratio), $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
            if($ratio<$resize_ratio){
                //宽度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                $color=imagecolorallocate($newimg,255,255,255);
                imagecolortransparent($newimg,$color);
                imagefill($newimg,0,0,$color);
                imagecopyresampled($newimg, $this->im, 0, 0, $this->src_x, $this->src_y, $this->resize_width, $this->resize_height, $this->width, (($this->width)/$resize_ratio));
                ImageJpeg ($newimg,$this->dstimg);
            }
        }else{
            //不裁图
            if($ratio>=$resize_ratio){
                $newimg = imagecreatetruecolor($this->resize_width,($this->resize_width)/$ratio);
                $color=imagecolorallocate($newimg,255,255,255);
                imagecolortransparent($newimg,$color);
                imagefill($newimg,0,0,$color);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width)/$ratio, $this->width, $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
            if($ratio<$resize_ratio){
                $newimg = imagecreatetruecolor(($this->resize_height)*$ratio,$this->resize_height);
                $color=imagecolorallocate($newimg,255,255,255);
                imagecolortransparent($newimg,$color);
                imagefill($newimg,0,0,$color);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, ($this->resize_height)*$ratio, $this->resize_height, $this->width, $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
        }
    }
    //初始化图象
    function initi_img(){
        if($this->type=="jpg"){
            $this->im = imagecreatefromjpeg($this->srcimg);
        }
        if($this->type=="gif"){
            $this->im = imagecreatefromgif($this->srcimg);
        }
        if($this->type=="png"){
            $this->im = imagecreatefrompng($this->srcimg);
        }
    }
    //图象目标地址
    function dst_img($dstpath){
        $full_length  = strlen($this->srcimg);
        
        $type_length  = strlen($this->type);
        $name_length  = $full_length-$type_length;
        
        $name         = substr($this->srcimg,0,$name_length-1);
        $this->dstimg = $dstpath;
    }
}
