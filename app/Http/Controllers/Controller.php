<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    // 加载excel文档
    include base_path() . '/app/Library/PHPExcel.php';
}
