<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Library\Curl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReptileController extends BaseController
{   
    /**
     * 采集列表页
     */
    public function index()
    {

    }

    /**
     * 采集地址http://www.alibaba.com/trade/search?fsb=y&IndexArea=product_en&CatId=&SearchText=fff
     * 分页地址http://www.alibaba.com/products/F0/fff/2.html
     */
    public function handle(Request $request)
    {
        $keys = $request->input('keys', '');
        // curl采集
        $curl = new Curl();

        $curl->get('http://www.alibaba.com/trade/search', [
            'fsb' => 'y',
            'IndexArea' => 'product_en',
            'CatId' => '',
            'SearchText' => $keys,
        ]);
        // 获取采集内容
        $content = $curl->response;

        $content = str_replace("\r\n", '', $content); //清除换行符  
        $content = str_replace("\n", '', $content); //清除换行符  
        $content = str_replace("\t", '', $content); //清除制表符  
        $content = preg_replace("/\s+/", " ", $content); //过滤多余回车
        $content = preg_replace("/<[ ]+/si", "<", $content); //过滤<__("<"号后面带空格)
        $content = preg_replace("/<\!–.*?–>/si", "", $content); //注释
        $content = preg_replace("/<(\!.*?)>/si", "", $content); //过滤DOCTYPE
        $content = preg_replace("/<(\/?head.*?)>/si", "", $content); //过滤head标签
        $content = preg_replace("/<(\/?img.*?)>/si", "", $content); //过滤img标签
        $content = preg_replace("/<(\/?div.*?)>/si", "", $content); //过滤img标签
        $content = preg_replace("/<(\/?meta.*?)>/si", "", $content); //过滤meta标签
        $content = preg_replace("/<(\/?link.*?)>/si", "", $content); //过滤link标签
        $content = preg_replace("/<(\/?form.*?)>/si", "", $content); //过滤form标签
        $content = preg_replace("/cookie/si", "COOKIE", $content); //过滤COOKIE标签
        $content = preg_replace("/<(applet.*?)>(.*?)<(\/applet.*?)>/si", "", $content); //过滤applet标签
        $content = preg_replace("/<(\/?applet.*?)>/si", "", $content); //过滤applet标签
        $content = preg_replace("/<(style.*?)>(.*?)<(\/style.*?)>/si", "", $content); //过滤style标签
        $content = preg_replace("/<(\/?style.*?)>/si", "", $content); //过滤style标签
        $content = preg_replace("/<(\/?base.*?)>/si", "", $content); //过滤base标签
        $content = preg_replace("/<(\/?noscript.*?)>/si", "", $content); //过滤noscript标签
        $content = preg_replace("/<(title.*?)>(.*?)<(\/title.*?)>/si", "", $content); //过滤title标签
        $content = preg_replace("/<(\/?title.*?)>/si", "", $content); //过滤title标签
        $content = preg_replace("/<(object.*?)>(.*?)<(\/object.*?)>/si", "", $content); //过滤object标签
        $content = preg_replace("/<(\/?objec.*?)>/si", "", $content); //过滤object标签
        $content = preg_replace("/<(noframes.*?)>(.*?)<(\/noframes.*?)>/si", "", $content); //过滤noframes标签
        $content = preg_replace("/<(\/?noframes.*?)>/si", "", $content); //过滤noframes标签
        $content = preg_replace("/<(i?frame.*?)>(.*?)<(\/i?frame.*?)>/si", "", $content); //过滤frame标签
        $content = preg_replace("/<(\/?i?frame.*?)>/si", "", $content); //过滤frame标签
        $content = preg_replace("/<(script.*?)>(.*?)<(\/script.*?)>/si", "", $content); //过滤script标签
        $content = preg_replace("/<(\/?script.*?)>/si", "", $content); //过滤script标签
        $content = preg_replace("/javascript/si", "Javascript", $content); //过滤script标签
        $content = preg_replace("/vbscript/si", "Vbscript", $content); //过滤script标签
        $content = preg_replace("/on([a-z]+)\s*=/si", "On\\1=", $content); //过滤script标签
        $content = preg_replace("/&#/si", "&＃", $content); //过滤script标签

        // 匹配a标签中的内容    
        preg_match_all('/<h2 class="title"><a href="([^<>]+)" data-hislog="([0-9]+)" data-pid="([0-9]+)" data-domdot="([^<>]+)" target="_blank" .*?>([^.]+)<\/a>\s<\/h2>([^<em>+\/]+)/', $content, $res);

        $data = [];
        foreach ($res[1] as $key => $value) {
            $curl->get($value, []);
            $content = $curl->response;

            $content = str_replace("\r\n", '', $content); // 清除换行符  
            $content = str_replace("\n", '', $content); // 清除换行符  
            $content = str_replace("\t", '', $content); // 清除制表符  

            // 获取关键字
            preg_match('/<meta name="keywords" content="([^<>]+)"\/>/', $content, $keyWords);

            $data[] = [
                'result' => [
                    'title' => $res[5][$key], 
                    'link'  => $res[1][$key],
                    'keyword' => isset($keyWords[1]) ? $keyWords[1] : '',
                    'price' => $res[6][$key],
                ],
            ]; 
        }

        $curl->close();

        return response()->json(['data'=>$data, 'status' => 200]);
    }
}
