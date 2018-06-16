<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 12:09
 */
namespace Api\Controller;
use Think\Controller;

class DrawController extends Controller
{
    public function lottery(){
        $prize_arr = array(
            '0' => array('prize'=>'88元香芋粽 ','v'=>10),//弧度：55°-80°范围是：“3折抢购红酒木瓜丰胸靓汤”奖， v=10是中奖率是10%
            '1' => array('prize'=>'28元豆沙粽','v'=>10),
            '2' => array('prize'=>'18元水果粽','v'=>20),
            '4' => array('prize'=>'188元香菇粽','v'=>10),
            '5' => array('prize'=>'3元竹叶粽','v'=>50),
            '7' => array('prize'=>'58元枣子粽','v'=>30),
            '9' => array('prize'=>'8元八宝粽','v'=>40),
            '11' => array('prize'=>'5元蛋黄粽','v'=>40),
        );
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }
        $rid = $this->getRand($arr); //根据概率获取奖项id
        $res = $prize_arr[$rid-1]; //中奖项
        $min = $res['min'];
        $max = $res['max'];
        $result['angle'] = mt_rand($min,$max); //随机生成一个角度
        $result['prize'] = $res['prize'];
        echo json_encode($result);
    }


    private function getRand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return $result;
    }



}