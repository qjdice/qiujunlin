<?php 
    set_time_limit(0);
    header("Content-type: text/html; charset=utf-8"); 
    include './db.class.php';
 
    
    function curl_get($url){
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,0);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
        $result=curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    class getdata{
        public $mainurl;
        public $category;
        public function __construct($mainurl)
        {
            $this->mainurl = $mainurl;
            $base_url = parse_url($mainurl)['path'];
            $base_url = explode('/',$base_url)[1];
            $this->category = $base_url;
            $this->dsn = "mysql:host=120.77.178.16;port=3306;dbname=img_source";
        }

        // 获取页面资源
        public function getdata()
        {
            $data = curl_get($this->mainurl);
            return $data;
        }
        // 获取分页url
        public function getlisturl()
        {
            $arr_url = $this->getdata();
            $preg = '/<[a|A].*?href=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';
            preg_match_all($preg, $arr_url, $matches);
            foreach($matches[1] as $k=>$v){
                if(strpos($v,'ist') == 1){
                    $list_url[] = $this->mainurl . '/' .$v;
                }
            }
            return $list_url;
        }

        // 获取相册标题和URL
        public function getphoto()
        {
            $arr_url = $this->getdata();
            $preg = '/(<a.*?>).*?(<\/a>)/is';
            // 拿到所有A标签
            preg_match_all($preg, $arr_url, $matches);
            foreach($matches[0] as $key => $val){
                if(strpos($val,'title')){
                    $res[] = $val;
                }
            }
           
            // 匹配到A标签里的href和title
            $preg2 = '/href=[\'\"]{0,1}(\s?.*)title=[\'\"]{0,1}([^>\'\"\ ]*)/';
            foreach($res as $key => $val){
                preg_match($preg2, $val, $m);
                $r[$key]['href'] = str_replace('"',' ',$m[1]);
                $r[$key]['title'] =  iconv('GBK', 'UTF-8', $m[2]);
            }
            $r = $this->array_unique_fb($r);
            return $r;
        }
       // 数组去重并保留键名
       function array_unique_fb($array2D) 
        { 
            foreach ($array2D as $k=>$v) 
            { 
                $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                $temp[$k] = str_replace(' ', '', $v); 
            } 
            $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
            foreach ($temp as $k => $v) 
            { 
                $array=explode(",",$v); //再将拆开的数组重新组装 
                $temp2[$k]["href"] =$array[0]; 
                $temp2[$k]["title"] =$array[1]; 
            } 
            return $temp2; 
        } 
        // 保存分页url到数据库
        public function savelisturl()
        {
            $db = new DB($this->dsn,'listurl');
            $listurl = $this->getlisturl();
            $listurl = array_unique($listurl);
            foreach($listurl as $key => $val){
               $inserid = $db->add(array('listurl'=>$val,'category'=>$this->category));
            }
            if($inserid){
                return true;
            }else{
                return false;
            }
        }
        // 保存相册url到数据库
        public function savephotourl()
        {
            $photourl = $this->getphoto();
            
            if(!is_array($photourl)){
                return false;
            }
            $db = new DB($this->dsn,'imgconurl');
            
            foreach($photourl as $key => $val){
                $data['conurl'] = $val['href'];
                $data['title'] = $val['title'];
                $data['category'] = $this->category;
                $insid = $db->add($data);
                if($insid){
                    echo $insid . '->' . $val['title'] . ':' . $val['href'] . '<br>';
                }else{
                    echo '失败啦' . '<br>';
                }
            }
        }
    }
    $url = 'http://www.ycgkja.com/xinggan/list_9_';
    $wei = '.html';
    
    for($i = 1; $i <= 9;$i++){
        $pobj = new getdata($url . $i . $wei);
        $a = $pobj->savephotourl();
    }
