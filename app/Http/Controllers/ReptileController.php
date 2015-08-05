<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Library\Curl;
// use App\Library\PHPExcel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Cache;
// use Maatwebsite\Excel\Facades\Excel

class ReptileController extends BaseController
{   
    protected $userIds;

    /**
     * 初始化userIds
     */
    public function __construct()
    {
        $this->setUserIds();
    }

    protected function setUserIds()
    {
        $this->userIds = mt_rand(0, 999999999);
    }

    /**
     * 获取随机ip地址(国内内网IP)
     */
    protected function getIp()
    {
        $ip_long = [
            ['607649792', '608174079'], // 36.56.0.0-36.63.255.255
            ['1038614528', '1039007743'], // 61.232.0.0-61.237.255.255
            ['1783627776', '1784676351'], // 106.80.0.0-106.95.255.255
            ['2035023872', '2035154943'], // 121.76.0.0-121.77.255.255
            ['2078801920', '2079064063'], // 123.232.0.0-123.235.255.255
            ['-1950089216', '-1948778497'], // 139.196.0.0-139.215.255.255
            ['-1425539072', '-1425014785'], // 171.8.0.0-171.15.255.255
            ['-1236271104', '-1235419137'], // 182.80.0.0-182.92.255.255
            ['-770113536', '-768606209'], // 210.25.0.0-210.47.255.255
            ['-569376768', '-564133889'], // 222.16.0.0-222.95.255.255
        ];

        $rand_key = mt_rand(0, 9);

        return long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    }

    /**
     * 过滤html
     */
    protected function htmlFilters($content)
    {
        $content = str_replace("\r\n", '', $content); //清除换行符  
        $content = str_replace("\n", '', $content); //清除换行符  
        $content = str_replace("\t", '', $content); //清除制表符  
        $content = preg_replace("/\s+/", " ", $content); //过滤多余回车
        $content = preg_replace("/<[ ]+/si", "<", $content); //过滤<__("<"号后面带空格)
        $content = preg_replace("/<\!–.*?–>/si", "", $content); //注释
        $content = preg_replace("/<(\!.*?)>/si", "", $content); //过滤DOCTYPE
        $content = preg_replace("/<(\/?head.*?)>/si", "", $content); //过滤head标签
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
        $content = preg_replace("/<(\/?img.*?)>/si", "", $content); //过滤img标签
        $content = preg_replace("/<(\/?div.*?)>/si", "", $content); //过滤img标签
        $content = preg_replace("/<(\/?meta.*?)>/si", "", $content); //过滤meta标签

        return $content;
    }

    /**
     * 采集列表页
     */
    public function index()
    {
        return view('reptile.index');   
    }

    /**
     * 采集处理
     */
    public function handle(Request $request)
    {
        $keys = $request->input('keys', '');
        $page = $request->input('page', '');
        $url = $page ? "http://www.alibaba.com/products/F0/".$keys."/".$page.".html" : "http://www.alibaba.com/trade/search";
        $param = $page ? [] : ['fsb' => 'y', 'IndexArea' => 'product_en', 'CatId' => '', 'SearchText' => $keys,];
        // curl采集
        $curl = new Curl();
        $curl->get($url, $param);
        // 获取采集内容
        $content = $curl->response;
        $content = $this->htmlFilters($content);
        // 匹配a标签中的内容    
        preg_match_all('/<h2 class="title"><a href="([^<>]+)" data-hislog="([0-9]+)" data-pid="([0-9]+)" data-domdot="([^<>]+)" target="_blank" .*?>([^.]+)<\/a>\s<\/h2>([^<em>+\/]+)/', $content, $res);
        
        $data = [];
        foreach ($res[1] as $key => $value) {
            // 伪造IP
            $curl->setIp($this->getIp());
            $curl->get($value, []);
            $content = $curl->response;
            $content = str_replace("\r\n", '', $content); // 清除换行符  
            $content = str_replace("\n", '', $content); // 清除换行符  
            $content = str_replace("\t", '', $content); // 清除制表符  
            // 获取关键字
            preg_match('/<meta name="keywords" content="([^<>]+)"\s*\/>/', $content, $keyWords);

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
        Cache::add('aw:' . $this->userIds, $data, 60);

        return response()->json(['userIds' => $this->userIds, 'data'=>$data, 'status' => 200]);
    }

    /**
     * Excel导出
     */
    public function exportExcel(Request $request)
    {
        // 加载excel文档
        include base_path() . '/app/Library/PHPExcel.php';

        $userIds = $request->input('userIds', '');
        $keys = $request->input('keys', '');

        $excel = new \PHPExcel();
        $letter = ['A','B','C'];
        $tableheader = ['标题', '关键词', '价格'];
        
        $data = [];
        if (Cache::has('aw:' . $userIds)) {
            $data = Cache::get('aw:' . $userIds);
        
            foreach ($tableheader as $key => $value) {
                $excel->getActiveSheet()->setCellValue($letter[$key]."1",$value);
                $excel->getActiveSheet()->getColumnDimension($letter[$key])->setAutoSize(true);
            }

            foreach ($data as $key => $value) {
                $key+=2;
                $excel->getActiveSheet()->setCellValue('A'.$key, strip_tags($value['result']['title']));
                $excel->getActiveSheet()->setCellValue('B'.$key, $value['result']['keyword']);
                $excel->getActiveSheet()->setCellValue('C'.$key, $value['result']['price']);
            }

            $write = new \PHPExcel_Writer_Excel5($excel);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");;
            header('Content-Disposition:attachment;filename="阿里巴巴'.$keys.'关键词'.$userIds.'.xls"');
            header("Content-Transfer-Encoding:binary");
            $write->save('php://output');
        }

        return response()->json(['data'=>[], 'status' => 300, 'message' => '没有数据']);
    }

}
