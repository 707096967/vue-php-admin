<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

//require APPPATH . 'libraries/kindeditor/php/JSON.php';

class Uploadimg extends REST_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Api_model');
    }


    public function testapi_get()
    {
        echo "test api ok...";

        echo APPPATH . "\n";
        echo SELF . "\n";
        echo BASEPATH . "\n";
        echo FCPATH . "\n";
        echo SYSDIR . "\n";
        var_dump($this->config->item('rest_language'));
        var_dump($this->config->item('language'));

        var_dump($this->config);
    }

    public function upload_post()
    {
        $uploadDir = FCPATH . 'uploads/';
        $id = 'T' . $this->POST('identify');
        $php_path = dirname(__FILE__) . '/';//dirname($_SERVER['DOCUMENT_ROOT']);  dirname(__FILE__)
//        $php_url = "";//dirname($_SERVER['HTTP_HOST']) . '/';//PHP_SELF
        $php_url = $_SERVER['HTTP_HOST'] . '/'; //PHP_SELF

        $save_path = $uploadDir;
        //文件保存目录URL
        $save_url = $php_url . 'uploads/';
        // nginx服务器端修改绝对路径
        //$save_url = "http://172.30.3.11/static/home/kindeditor/attached/";

        //        var_dump($save_path);
        //        var_dump($save_url);
        // string(46) "D:\Q\code\vue\CodeIgniter-3.1.10\uploads\imgs\"
        // string(28) "www.cirest.com:8889/uploads/"


        //定义允许上传的文件扩展名
        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
        );
        //最大文件大小  10M 默认是1M
        $max_size = 10000000;

        $save_path = realpath($save_path) . '/';
        $save_path = str_replace('\\', '/', $save_path);

//PHP上传失败
        if (!empty($_FILES['file']['error'])) {
            switch ($_FILES['file']['error']) {
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '图片只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择图片。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            $this->alert($error);
        }

//有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $file_name = $_FILES['file']['name'];
            //服务器上临时文件名
            $tmp_name = $_FILES['file']['tmp_name'];
            //文件大小
            $file_size = $_FILES['file']['size'];
            //检查文件名
            if (!$file_name) {
                $this->alert("请选择文件。");
            }
            //检查目录
            if (@is_dir($save_path) === false) {
                $this->alert("上传目录不存在。");
            }
            //检查目录写权限
            if (@is_writable($save_path) === false) {
                $this->alert("上传目录没有写权限。");
            }
            //检查是否已上传
            if (@is_uploaded_file($tmp_name) === false) {
                $this->alert("上传失败。");
            }
            //检查文件大小
            if ($file_size > $max_size) {
                $this->alert("上传文件大小超过限制(<10M)。");
            }
            //检查目录名
            $dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
            if (empty($ext_arr[$dir_name])) {
                $this->alert("目录名不正确。");
            }
            //获得文件扩展名
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);

            //检查扩展名
            if (!in_array($file_ext, $ext_arr[$dir_name])) {
                $this->alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");

            }

            /*
             * 以 T+身份证号作为临时目录 ， 以身份证作为正式目录
             */
            $identify = empty($id) ? '' : trim($id);

            if ($identify == '') {
                echo "Invalid session identify.";
                exit;
            }
            //创建文件夹
            if ($dir_name !== '') {
                $save_path .= $dir_name . "/" . $identify . "/";
                $save_url .= $dir_name . "/" . $identify . "/";
                if (!file_exists($save_path)) {
                    mkdir($save_path, 0777, true); // true 允许创建多级目录
                }
            }
            $ymd = date("Ym");
            $save_path .= $ymd . "/";
            $save_url .= $ymd . "/";
            if (!file_exists($save_path)) {
                mkdir($save_path);
            }
            //新文件名
            $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            //移动文件
            $file_path = $save_path . $new_file_name;
            if (move_uploaded_file($tmp_name, $file_path) === false) {
                $this->alert("上传文件失败。");
            }
            @chmod($file_path, 0644);
            $file_url = $save_url . $new_file_name;

            header('Content-type: text/html; charset=UTF-8');
            // Insert file information in the database
            // $insert = $db->query("INSERT INTO files (file_name, uploaded_on) VALUES ('".$fileName."', NOW())");
            $link = "http://" . $file_url;
            // http://www.cirest.com:8889/uploads/image/T410000000000000000/201902/20190228071354_96833.png
            $message = [
                "code" => 20000,
                "error" => 0,
                "message" => "上传成功",
                "link" => $link,
                "filepath" => preg_replace('/^http.*uploads/', '/uploads', $link)
            ];

            echo json_encode($message);
            //            $this->set_response($message, REST_Controller::HTTP_OK);
            // alert里面使用时 exit() 产生的是空，或字符串，使用原生的json_encode返回统一的字符串，在客户端在统一处理成对象
        }
    }

    public function delimg_post()
    {
        $php_path = dirname(__FILE__) . '/';//dirname($_SERVER['DOCUMENT_ROOT']);  dirname(__FILE__)

        //文件保存目录路径
        $save_path = $php_path . '../../../../';

        $save_path = realpath($save_path) . '/';
        $save_path = str_replace('\\', '/', $save_path);
        //   var_dump($save_path);
        //  "D:/Q/code/vue/CodeIgniter-3.1.10/"

        $DelFileName = $this->POST('filename');
        $DelFileType = $this->POST('isdir');

        $FilePath = $save_path . $DelFileName;

        // var_dump($FilePath);return;

        if ($DelFileType == 'F') {
            if (!is_file($FilePath)) {
                $message = [
                    "code" => 20000,
                    "data" => "文件不存在 - " . $DelFileName,
                    "message" => "文件不存在 - " . $DelFileName
                ];

                echo json_encode($message);

            } else {

                if (!unlink($FilePath)) {
                    $message = [
                        "code" => 20000,
                        "data" => "Error deleting " . $FilePath,
                        "message" => "Error deleting " . $FilePath
                    ];
                    echo json_encode($message);

                } else {
                    // 必须返回code 由于前端vue封装的 request 请求，返回数据对code进行判断
                    $message = [
                        "code" => 20000,
                        "data" => '服务器删除成功!',
                        "message" => '服务器删除成功!'
                    ];
                    echo json_encode($message);
                }
            }
            exit;
        }

        if ($DelFileType == 'D') {
            if (!@rmdir($FilePath)) {
                echo "文件夹 " . $FilePath . " 不为空，不能删除！";
            } else {
                echo "succeed";
            }
            exit;
        }
    }

    private function alert($msg)
    {
        header('Content-type: text/html; charset=UTF-8');
        $message = [
            "code" => 20000,
            "error" => 1,
            "message" => $msg
        ];
        echo json_encode($message);
        exit();
        //        var_dump($message);
        //        $this->set_response($message, REST_Controller::HTTP_OK);
        //        die();
    }


    public function onsubmit_post()
    {
        $identify = $this->POST('identify');
        $phone = $this->POST('phone');
        $idinfo = $this->POST('idinfo');
        $bankinfo = $this->POST('bankinfo');
//        $data = [
//            'identify' => $identify,
//            'phone' => $phone,
//            'idinfo' => $idinfo,
//            'check' => '待审核'
//        ];

        // 写入数据库表 身份证号，手机号，证件照，文件路径等
        $where = [
            'identify' => $identify,
            'phone' => $phone,
        ];

        $data = [
            'idinfo' => $idinfo,
            'bankinfo' => $bankinfo,
            'check' => '待审核'
        ];

        $result = $this->Api_model->saveEdit('upload_tbl', $data, $where);

        if ($result) {
            $message = [
                "code" => 20000,
                "message" => '写入数据库表成功,请请待审核通知!',
                "data" => array_merge($where, $data)
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                "code" => 20000,
                "message" => '写入数据库表失败!',
                "data" => array_merge($where, $data)
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
        }
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */