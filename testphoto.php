<?php 
    set_time_limit(0);
    header("Content-type: text/html; charset=UTF-8"); 
    require './db.class.php';
    $j = 0;
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

    $dsn = 'mysql:host=localhost;port=3306;dbname=img_source';
    $db = new DB($dsn,'imgconurl');
    $map['category'] = ['eq','xinggan'];
    $data = $db->where($map)->select();
   
    foreach($data as $key => $val){
        $i = 1;
        $imgurl = getphoto($val['conurl']);
        $title = htmlspecialchars($val['title']);
        if(!strpos($imgurl,'ttp')){
            continue;
        }
        if($imgurl){
            $j++;
            $filepath = $val['category'] . '/'.$val['category'].'_' . $j;
            $dirdb = new DB($dsn,'dirname');
            $dirdata['dirname'] = $filepath;
            if(empty($title) || $title == '<b'){
                $title =  $filepath; 
            }
            $dirdata['title'] = $title;
            $dirdata['category'] = $val['category'];
            $dirdb->add($dirdata); // 存入目录数据库
            putphoto($imgurl,$title,$i,$val['category']); // 写入第一张图片
            $page = getpage($val['conurl']);
            if($page){
                foreach($page as $k => $v){ // 开始循环写入分页的图片
                   $mgurl =  getphoto($v);
                   if($mgurl){
                        $i++;
                       putphoto($mgurl,$title,$i,$val['category']);
                   }
                }
            }
        }else{
            echo '获取图片失败';
        }
    }
    
    function getpage($url)
    {
        $preg = '/<[a|A].*?href=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';
        $arr_url = curl_get($url);
        preg_match_all($preg,$arr_url,$m);
        
        foreach($m[1] as $k => $v){
            $c = str_replace(' ','', $v);
            preg_match_all('/^\d+\w\d.html/',$c,$b);
            if($b[0]){
                $res[] = $b[0][0];
            }
        }
        $res = array_unique($res);
        $base_url = parse_url($url);
        $b_url = explode('/',$base_url['path'])[1];
        $main_url = $base_url['scheme'] . '://' . $base_url['host'] . '/' . $b_url;
        foreach($res as $k => &$v){
            $v = $main_url . '/' . $v;
        }
        return $res;
    }
    
    function getphoto($url)
    {
        $preg = '/<[img|IMG].*?src=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';
        $arr_url = curl_get($url);
        preg_match_all($preg,$arr_url,$m);
        if(empty($m)){
            return false;
        }
        return $m[1][1];
    }
    function putphoto($url,$title,$i,$category)
    {
        global $j;
        // $title = iconv('utf-8', 'gbk', $title);
        
        $filepath = './photo/'.$category. '/' . $category . '_' . $j . '/';
        $img = file_get_contents($url);
        if($img){
            if(!file_exists($filepath)){
                mkdir($filepath,777,true);
                chmod($filepath, 0777);
            }
            $filename = $filepath . $category . '_' . $i . '.jpg';
            $res = file_put_contents($filename,$img);
            if($res){
                $db = new DB('mysql:host=localhost;port=3306;dbname=img_source','phpot');
                $dd['dirname'] = "{$category}/{$category}_" . $j;
                $dd['filename'] =  $category . '_' . $i . '.jpg';
                if(empty($title) || $title == '<b'){
                    $title = $dd['dirname']; 
                }
                $dd['title'] = $title;
                $dd['category'] = $category;
                $db->add($dd);
                echo '写入成功:' . $filename . '<br>';
            }else{
                echo '写入失败';
            }
        }
    }

