<?php
/**
 *时时彩  北京赛车接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 12:09
 */
namespace Api\Controller;
use Think\Controller;
use QL\QueryList;
class IndexController extends Controller
{
    /**
     * 获取时时彩 北京赛车数据
     */
    public function get_lottery()
    {
        $type = empty($_GET['type'])?$_POST['type']:$_GET['type'];
        $data = array();
        $flag = 0;
        $lottery_type =1;
        if ($type==5){
            $lottery_type =2;
            $type =1;
            $flag = 1;
        }
        //获取本期数据
        $EveryData = D('award');
        $map['award_number']  = array('neq','');
        $map['status']  = array('neq','');
        $map['type']  = array('eq',$type);
        $map['lottery_type']  = array('eq',$lottery_type);

        $new_info = $EveryData->field('award_number,period')->where($map)->order('id desc')->limit(1)->find();
        if($new_info){
            $data['award_number'] = array();
            if($new_info['award_number']){
                $data['award_number'] = explode(' ',$new_info['award_number']);
            }
            $data['period'] =$new_info['period'];
            if( $flag==0){
                $data['period'] = substr($new_info['period'], -3);
            }
        }
        echo json_encode($data);
    }
    /**
     * 获取时时彩 北京赛车数据
     */
    public function get_lottery_plan()
    {
        $type = empty($_GET['type'])?$_POST['type']:$_GET['type'];
        $data = array();
        $lottery_type =1;
        if($type==1)
        {
            $lottery_name = '个位计划';
        }elseif ($type==2){

            $lottery_name = '后二直选';
        }elseif ($type==3){

            $lottery_name = '后三直选';
        }elseif ($type==4){
            $lottery_name = '后三组六';
        }elseif ($type==5){
            $lottery_type =2;
            $type =1;
            $lottery_name = '冠军';
        }
        $EveryData = D('award');
        $map['type']  = array('eq',$type);
        $map['lottery_type']  = array('eq',$lottery_type);
        $period = $EveryData->field()->where($map)->order('id desc')->getField('period');
        $next_date = substr($period, -3);
        if($type!=5){
            if($next_date=='120'){
                $next_date = '001';
            }else{
                $next_date = $next_date+1;
            }
            $next_date = sprintf ( "%03d",$next_date);
        }else{
            $next_date = $next_date+1;
            $next_date = substr($next_date, -3);
            $next_date = sprintf ( "%03d",$next_date);
        }
        //获取下一期数据
        $EveryData = D('award_plans');
        $next_plan = '';
        $nap['type']  = array('eq',$type);
        $nap['lottery_type']  = array('eq',$lottery_type);
        $next_info = $EveryData->field('serial,analysis')->where($nap)->order('id desc')->limit(1)->find();
        dump($next_info);die;
        if($next_info){
            $next_plan = $next_info['serial'].'期 '.$lottery_name.'【'. $next_info['analysis'].'】'.$next_date.'期 等开';
        }
        $data['next_plan'] = $next_plan;
        //获取每天计划表
        $today_fir = strtotime(date('Y-m-d 00:00:00'));
        $today_las = strtotime(date('Y-m-d 23:59:59'));
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['type']  = array('eq',$type);
        $plan['lottery_type']  = array('eq',$lottery_type);
        $plan['add_time'] =  array(array('egt',$today_fir),array('elt',$today_las),'and');

        $list = $EveryData->field('award_plan')->where($plan)->order('id desc')->select();
        //下一期分析计划表
        $data['awd_plan'] = $list;
        echo json_encode($data);
    }
}