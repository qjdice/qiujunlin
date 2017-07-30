<?php 
    

 include './db.class.php';
 

 function curl_get($url){
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_HEADER,1);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
    $result=curl_exec($ch);
    curl_close($ch);
    return $result;
}


 $preg = '/(<a.*?>).*?(<\/a>)/is';
 $preg = '/<[a|A].*?href=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';

 $url = "http://www.ycgkja.com/meihuo";
 // $arr_url = curl_get($url);
 
 // preg_match_all($preg, $arr_url, $matches);
 // $baseurl = parse_url($url);
 // $category = trim($baseurl['path'],'/');
 // foreach($matches[1] as $k=>$v){
 //    if(strpos($v,'ist') == 1){
 //        $list_url[] = $url . '/' .$v;
 //    }
 // }
 // echo '<pre>';
 //      print_r($list_url);
 //  echo '</pre>'; 
 // foreach($matches[0] as $key => $val){
 //    if(strpos($val,'title')){
 //       $res[] = $val;
 //    }
 // }

 // $preg2 = '/href=[\'\"]{0,1}(\s?.*)title=[\'\"]{0,1}([^>\'\"\ ]*)/';
 // preg_match($preg2, $res[2], $m);
 // foreach($res as $key => $val){
 //    preg_match($preg2, $val, $m);
 //    $r[$key]['href'] = str_replace('"',' ',$m[1]);
 //    $r[$key]['title'] = $m[2];
 // }


class getdata{
    public $main_url;
    public $dsn;
    public $data = array();
    public $preg = '/<[a|A].*?href=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';
    public $category;
    public function __construct($main_url)
    {
        $this->dsn = 'mysql:host=localhost;dbname=img_source;port=3306';
        $this->main_url = $main_url;
        $arr_url = $this->getdata();
        $u = $this->getlistpage($main_url,$arr_url[1]);
        // $this->savelisturl($u['listurl']);
        // $this->saveimgcon($u['imgurl']);
    }

    public function getdata()
    {
        $data = curl_get($this->main_url);
        preg_match_all($this->preg,$data,$arr_url);
        return $arr_url;
    }

    // 插入相册列表分页url
    public function savelisturl($listurl)
    {
        $listurl = array_unique($listurl);
        $db = new DB($this->dsn,'listurl');
        foreach($listurl as $key=>$val){
            $db->add(array('listurl'=>$val));
        }  
    }
    // 插入相册封面url
    public function saveimgcon($imgurl)
    {
        $imgurl = array_unique($imgurl);
        $db = new DB($this->dsn,'imgconurl');
        foreach($imgurl as $key => $val){
            $db->add(array('conurl'=>$val));
        }
    }

    // 拼接好相册URL 和 分页URL
    function getimgurl($main_url,$base_info)
    {
        foreach ($base_info as $key => $value) {
            if (strpos($value['path'],'ist') == 1) {
               $imgurl['listurl'][] = $main_url . $this->category .'/'. $value['path'];
               continue;
            }
            $imgurl['imgurl'][] = $main_url .  $value['path'];
        }
        return $imgurl; 
    }
    // 取出所有相册URL和列表
    function getlistpage($base_url,$mm)
    {
        $main_url = parse_url($base_url);
        $this->category = $main_url['path'];
        $main_url = $main_url['scheme'] . '://' . $main_url['host'];
        $preg['int'] = '/\d/';
        $preg['str'] = '/^\/zttp/';
        foreach($mm as $key => $val){
            if(preg_match($preg['int'],$val)){
                $urlinfo[] = $val;
            }
        }
        foreach($urlinfo as $k => $val){
            if(preg_match($preg['str'],$val)){
                continue;
            }
            $base_info[] = parse_url($val);
        }
        
        return $this->getimgurl($main_url,$base_info);
    }

}
