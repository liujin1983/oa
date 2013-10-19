<?php

require(DIR_LIB.'xmlrpc/lib/xmlrpc.inc');
// 这里就是解决商品名称中有中文时的乱码问题
//$GLOBALS['xmlrpc_internalencoding']='UTF-8'; 如果从里定义的话，那么 create、write 方法就会出现 500 错误。

class XmlrpcOe{

    public $user = 'admin';
    public $password = 'admin';
    public $user_id = -1;
    public $dbname = 'openerp';
    public $server_url = 'http://127.0.0.1:8069/xmlrpc/';

    public function __construct() {
        $this->sock_common();
        $this->sock_object();
        $this->connect();
    }


    public function sock_common() {
        $this->sock_com = new xmlrpc_client($this->server_url.'common');
    }

    public function sock_object() {
        $this->sock_obj = new xmlrpc_client($this->server_url.'object');
    }

    /**
     * 登录，获取 user_id
     */
    public function connect() {

        /*
        if(isset($_COOKIE["user_id"]) == true)  {
            if($_COOKIE["user_id"]>0) {
                return $_COOKIE["user_id"];
            }
        }
         */

        $msg = new xmlrpcmsg('login');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user, "string"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $resp =  $this->sock_com->send($msg);
        $val = $resp->value();
        $this->user_id = $val->scalarval();

        setcookie("user_id",$this->user_id,time()+3600);
    }


    /**
     * 搜索-单条件
     * $relation: 搜索的对象，如：product.category
     * $attribute: 属性，如：name
     * $operator: 条件，如：>、=、!=、like
     * $keys: 值，如：p
     */
    public function search($relation, $attribute, $operator, $keys) {

        $GLOBALS['xmlrpc_internalencoding']='UTF-8';

        $key = array(new xmlrpcval(array(new xmlrpcval($attribute , "string"),
            new xmlrpcval($operator,"string"),
            new xmlrpcval($keys,"string")),"array"),);

        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user_id, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($relation, "string"));
        $msg->addParam(new xmlrpcval("search", "string"));
        $msg->addParam(new xmlrpcval($key, "array"));

        $resp = $this->sock_obj->send($msg);
        $val = $resp->value();
        print_r($val);
        $ids_rpc_array = $val->scalarval();

        $ids = array();
        foreach($ids_rpc_array as $id_obj) {
            $ids[] = $id_obj->me["int"];
        }

        return $ids_rpc_array;
        //return $ids;
    }

    /**
     * 读数据，通过 id 读取相关数据
     * $relation: 数据对象，如：product.product
     * $ids: id 的数组，如：array(new xmlrpcval(4, "int"));
     * $field_array: 字段数组
     */
    public function read($relation, $ids, $field_array) {

        $GLOBALS['xmlrpc_internalencoding']='UTF-8';
        
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user_id, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($relation, "string"));
        $msg->addParam(new xmlrpcval("read", "string"));
        $msg->addParam(new xmlrpcval($ids, "array"));
        $msg->addParam(new xmlrpcval($field_array, "array"));

        $resp = $this->sock_obj->send($msg);
        $val = $resp->value();
        return $val->scalarval();
    }

    public function create($relation, $partner_data) {

        $GLOBALS['xmlrpc_internalencoding']='UTF-8';

        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user_id, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($relation, "string"));
        $msg->addParam(new xmlrpcval("create", "string"));
        $msg->addParam(new xmlrpcval($partner_data, "struct"));

        $resp = $this->sock_obj->send($msg);

        if ($resp->faultCode())
            echo 'Error: '.$resp->faultString();
        else
            echo 'Partner '.$resp->value()->scalarval().' created !';
    }

    public function write($relation, $data, $ids) {

        $GLOBALS['xmlrpc_internalencoding']='UTF-8';

        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user_id, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($relation, "string"));
        $msg->addParam(new xmlrpcval("write", "string"));
        $msg->addParam(new xmlrpcval($ids, "array"));
        $msg->addParam(new xmlrpcval($data, "struct"));

        $resp = $this->sock_obj->send($msg);
        $val = $resp->value();

        return $val->scalarval();

    }

}
//
//$xmlrpc_oe = new XmlrpcOe();
//
//# 搜索
////echo "search:<br />";
////$ids = $xmlrpc_oe->search("shopilex.product", "id", ">=", "1");
////print_r($ids);
//
//echo "<br /><br />";
//
//
////$id_array = array();
////foreach($ids as $id_obj) {
////    $id_array[] = $id_obj->me["int"];
////}
////print_r($id_array);
//
//echo "<br /><br />---------------------------------------------------------<br />";
////
////
////
//# 读数据
//echo "read:<br />";
////$field_array = array(
////        new xmlrpcval("id", "int"),
////        new xmlrpcval("model", "string"),
////        new xmlrpcval("sku", "string"),
////    );
////$ret_array = $xmlrpc_oe->read("shopilex.qingjd", $ids, null);
////print_r($ret_array);
//echo "<br /><br />";

//$out_array = array();
//foreach($ret_array as $k => $v) {
//	
//	
//    $tmp_id = $v->me['struct']['id']->me["int"];
//    $tmp_name = $v->me['struct']['shiyou']->me["string"];
//    $tmp_price = $v->me['struct']['state']->me["string"];
//    $tmp_shenqr = -1;
//    
//	$shenqr_arr = $v->me['struct']['shenqr'];
//	print_r($shenqr_arr);
//	
//	foreach($shenqr_arr as $shen_k => $shen_v){
//		$tmp_shenqr = $shen_v['array'][0]->me['int'];
//		break;
//	}
//	
//    $out_array[] = array(
//            "id" => $tmp_id,
//            "name" => $tmp_name,
//            "list_price" => $tmp_price,
//    		"tmp_shenqr" => $tmp_shenqr,
//        );
//}

//print_r($out_array);
//
//echo "<br /><br />---------------------------------------------------------<br />";
//
//
//
//# 添加
//echo "create:<br />";


//new xmlrpcval();
//
//$partner_data = array(
//        "model" => new xmlrpcval("53002", "string"),
//        "sku" => new xmlrpcval("22", "string"),
//		"upc" => new xmlrpcval("333", "string"),
//		"location" => new xmlrpcval("中国心", "string"),
//		"status" => new xmlrpcval("1", "string")
//    );
//echo "<br/><br/>";    
//$xmlrpc_oe->create("shopilex.product", $partner_data);
//
//echo "<br /><br />---------------------------------------------------------<br />";



# 修改
//echo "write:<br />";
//$ids = array(
//        new xmlrpcval(1, "int"),
//    );
//
//$data = array(
//        "shiyou" => new xmlrpcval("不想工作", "string"),
//    );
//
//$xmlrpc_oe->write("shopilex.product", $data, $ids);
//$ret_array = $xmlrpc_oe->read("product.product", $ids, $field_array);
//
//$out_array = array();
//foreach($ret_array as $k => $v) {
//    $tmp_id = $v->me['struct']['id']->me["int"];
//    $tmp_name = $v->me['struct']['name']->me["string"];
//    $tmp_price = $v->me['struct']['list_price']->me["double"];
//
//    $out_array[] = array(
//            "id" => $tmp_id,
//            "name" => $tmp_name,
//            "list_price" => $tmp_price,
//        );
//}
//
//print_r($out_array);
