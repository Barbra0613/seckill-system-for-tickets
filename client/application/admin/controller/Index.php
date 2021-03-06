<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Controller;
use think\cache\driver\Redis;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Index extends Controller
{
    private $redis = null;
    
    public function __construct() {

        parent::__construct();
        $this->redis = new \Redis(); // 实例化
        $this->redis->connect('127.0.0.1','6379');
    }

    public function re_init(){
  
        $datas = db('ticket')->select();

        for ( $i=0; $i<sizeof($datas); $i++ ) {
            $data = $datas[$i];
            $id = $data['id'];
            $place = $data['site_place'];
            $price = $data['price'];
            $store = $data['re_num'];
            $sum = $data['tic_num'];

            $ticket_data = [
                'id' => $id,
                'place' => $place,
                'price' => $price,
                'store' => $store,
                'sum' => $sum,
            ];

            $t_name = 'ticket_data_'.$id;
            $t_s = 'tic_'.$id;
            $this->redis->set($t_name, json_encode($ticket_data));
            for ( $j=0; $j < $store; $j++ ) {
                // 向库存列表推进,模拟商品库存
                $this->redis->lpush($t_s,1);
            }
    
        }
        $this->receive_order();
    }
 
    /**
     * 登入
     */
    public function index()
    {

        $ticket_data = array();

        $ticket_data[0] = json_decode($this->redis->get('ticket_data_1'),true);
        $ticket_data[1] = json_decode($this->redis->get('ticket_data_2'),true);

        $this->assign("info", $ticket_data);
        // return json($ticket_data)
        return $this->fetch('/index/index');
        
    }


     /**
     * 秒杀入口
     */
    public function kill() {
        
        $id = $_POST["place"]; //商品编号
        $name = $_POST["name"];
        $iden_num = $_POST["iden_num"];
        $take = $_POST["take"];


        $t_s = 'tic_'.$id;
        
        if ( empty($id) ) {
            // 记录失败日志
            return $this->writeLog(0,'商品编号不存在');
        }
         
        $count = $this->redis->lpop($t_s);

        if( !$count ){
            $this->writeLog(0,'秒杀失败，无库存');
            $this->error('秒杀失败，无库存', 'index/index');
            return;
        }

        $ordersn = $this->build_order_no(); //生成订单
        $status = 1;

        $data = json_decode($this->redis->get('ticket_data_1'),true);
        $price = $data['price'];
        $insert_data = [
            'name' => $ordersn,
            'user_id' => $name,
            'iden_num' => $iden_num,
            'ticket_id' => $id,
            'price' => $price,
            'status' => $status,
            'take' => $take,
            'time' => date('Y-m-d H:i:s')
        ];
         
        $this->send_order( json_encode($insert_data) );
        $this->success('秒杀成功', 'index/index');
        
    }
     
    /**
    * 生成订单号
    */
    public function build_order_no(){
        return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    public function send_order( $message ) {

        $connection = new AMQPStreamConnection('localhost', 5672, 'mq', 'mq123');
        $channel = $connection->channel();
        $channel->queue_declare('order', false, false, false, false);

        $msg = new AMQPMessage( $message );
        $channel->basic_publish($msg, '', 'order');

        $channel->close();
        $connection->close();
        return;
    }

    public function receive_order() {

        $connection = new AMQPStreamConnection('localhost', 5672, 'mq', 'mq123');
        $channel = $connection->channel();
        $channel->queue_declare('order', false, false, false, false);
 
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            $ticket_data = json_decode(($msg->body),true);
            $result = Db::table('client_order')->insert($ticket_data);
            $res = Db::table('ticket')->where('id',$ticket_data['ticket_id'])->setDec('re_num');
            
            if ($res) {
                $this->writeLog(1,'秒杀成功');
            }else{
                $this->writeLog(0,'秒杀失败');
            }
            
        };
        
        $channel->basic_consume('order', '', false, true, false, false, $callback);
        
        while(count($channel->callbacks)) {
            $channel->wait();
        }
        
        $channel->close();
        $connection->close();
    }
     
    /**
    * 生成日志 1成功 0失败
    */
    public function writeLog($status = 1,$msg){
        $price = $this->redis->get('price');
        $data['count'] = 1;
        $data['sum_price'] = $data['count']*$price;
        $data['ticket_id'] = 1;
        $data['status'] = $status;
        $data['time'] = date('Y-m-d H:i:s');
        $data['message'] = $msg;
        return Db::table('client_log')->insertGetId($data);
    }
 
}
