<?php
namespace Cron\Controller;
use Think\Controller;
use QL\QueryList;
class IndexController extends Controller
{
    /**
     * 获取时时彩 北京赛车数据
     */
    public function get_lottery_data()
    {

        $type = empty($_GET['type'])?$_POST['type']:$_GET['type'];

        if($type==1)
        {
            $this->every_shicai();//个位计划
        }elseif ($type==2){
            $this->since_two_shicai();//后二直选
        }elseif ($type==3){
            $this->since_three_shicai();//后三直选
        }elseif ($type==4){
            $this->since_six_shicai();//后三组六
        }
    }
    public function common_lottery()
    {


        $date = date('Ymd',time());
        $time = time();
        $rules = array(
            'text' => array('#chartsTable','html'),
        );
        $url = 'http://trend.caipiao.163.com/cqssc/';
        $win = QueryList::Query($url,$rules)->data;
        $win =$win[0]['text'];
        preg_match_all ('/<tr.*?data-period="(.*?)".*?data-award="(.*?)"/is', $win, $matches);
        //dump($matches);die;
        $kai=array();
        for ($i=0; $i<count($matches[0]); $i++) {
            if($matches[1][$i]){
                $pai = (int)substr($matches[1][$i], -3);
                $kai[$pai]['num']=$matches[2][$i];
                $kai[$pai]['date']=$matches[1][$i];
            }
        }
        array_multisort(array_column($kai, 'date'),SORT_NUMERIC, SORT_ASC, $kai);
        $new_kai = array_pop($kai);//获取最新一期开奖号码以及日期

        if(empty($new_kai)){
            return false;
        }
        if($new_kai['num']==']'&&$new_kai['date']=='[')
        {
            return false;
        }
        $data['new_kai']  = $new_kai;
        $data['time']  = $time;
        return $data;
    }
    /**
     * 个位计划
     * @param $new_kai  数据
     * @param $time     时间
     */
    public function every_shicai()
    {

        //抓取每天时时彩的开奖结果
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //类型
        $type = 1;
        $lottery_type = 1;
        //分析推荐
        //(1)个位计划
        $AveryData = M('award');
        $amap = array('lottery_type' => 1, 'type' =>1,'period'=>$new_kai['date']);
        $award_info = $AveryData->where($amap)->limit(1)->find();
        //如果有本期中奖号码的话 返回
        if($award_info){
            return false;
        }
        //本期开奖号码 数据库没有的话  进行插入
        if(empty($award_info)){
            $award_data['period'] = $new_kai['date'];//期数
            $award_data['award_number'] = $award_number;//开奖号码
            $award_data['type'] = $type;
            $award_data['lottery_type'] = $lottery_type;
            $AveryData = M('award');
            $AveryData->add($award_data);
        }
        //查询是否有本期数据1.查询最新的一期计划 2.没有数据的话从新建立
        $EveryData = M('award_plans');
        $map = array('lottery_type' => $lottery_type, 'type' =>$type);
        $new_info = $EveryData->where($map)->order('id  desc')->limit(1)->find();
        $flag = 0;
        if($new_info){
            $serial_number = $new_info['serial'];
            $every_str_num = $new_info['analysis'];
            $number  = $new_info['number'];
            $pos = strpos($every_str_num,$last_num);
            if ($pos === false) {
                $new_data['status'] =2;//错
                $status_name = '错';
                //如果为错的话 查看number数量 如果等于2更新 并插入下一期
                //如果为1的话更新number数量
                if($number==2) {
                    $new_data['period'] = $new_date;//期数
                    $new_data['award_number'] = $award_number;//开奖号码
                    $new_data['add_time'] = $time;
                    $new_data['number'] = 3;
                    $new_data['award_plan'] = $serial_number.' 个位计划 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    //更新number
                    $EveryData->where($up_kai)->save($new_data);
                    $flag = 1;
                    //插入下一期
                }else{
                    //更新number
                    if($number<2){
                        $number = $number+1;
                    }
                    $new_data['number'] =$number;//错
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    $EveryData->where($up_kai)->save($new_data);
                }
            } else {
                if($number<3){
                    $number = $number+1;
                }
                $new_data['status'] =1;//中
                $status_name = '中';
                //下一期计划表  更新 下一期
                $new_data['number'] =$number;
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['award_plan'] = $serial_number.' 个位计划 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                $EveryData = M('award_plans');
                $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                $EveryData->where($up_kai)->save($new_data);
                $flag = 1;
            }
        }else{
            //开始进来没有数据的时候
            if($new_date>118){
                $last_serial_number = $new_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $new_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }
            //获取最新的序列号
            $every_num = $this->getCode(5);
            $next_str_num = implode($every_num,'');
            $new_data['analysis'] = $next_str_num;//分析数据
            $new_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $new_data['type'] = $type;
            $new_data['serial'] = $serial_number;
            $new_data['number'] = 1;//三个为一期
            if(in_array($last_num,$every_num)){
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['status'] =1;//中
                $status_name = '中';
                $flag=1;
                $new_data['award_plan'] = $serial_number.' 个位计划 '.'['.$next_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
            }
            $EveryData = M('award_plans');
            //插入数组
            $EveryData->add($new_data);
        }
        //如果 三期都没有中的话 计划下一期  如果中的话 直接进入下一期（flag=1）
        if($flag==1){
            //下一期
            $every_data = array();
            if($new_date=='120'){
                $next_date = '001';
            }else{
                $next_date = $new_date+1;
            }
            $next_date = sprintf ( "%03d",$next_date);
            //下一期序列号
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            //分析数据
            $every_num = $this->getCode(5);
            $next_str_num = implode($every_num,'');
            $every_data['analysis'] = $next_str_num;//分析数据
            $every_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $every_data['type'] = $type;
            $every_data['number'] = 0;
            $every_data['serial'] = $serial_number;
            //添加下一期计划数据
            $EveryData = M('award_plans');
            $EveryData->add($every_data);
        }
    }
    /**
     * 后二直选
     * @param $new_kai  数据
     * @param $time     时间
     */
    public function since_two_shicai()
    {
        //抓取每天时时彩的开奖结果
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //类型
        $type = 2;
        $lottery_type = 1;
        //分析推荐
        //(1)后二直选
        $AveryData = M('award');
        $amap = array('lottery_type' => $lottery_type, 'type' =>$type,'period'=>$new_kai['date']);
        $award_info = $AveryData->where($amap)->limit(1)->find();
        //如果有本期中奖号码的话 返回
        if($award_info){
            return false;
        }
        //本期开奖号码 数据库没有的话  进行插入
        if(empty($award_info)){
            $award_data['period'] = $new_kai['date'];//期数
            $award_data['award_number'] = $award_number;//开奖号码
            $award_data['type'] = $type;
            $award_data['lottery_type'] = $lottery_type;
            $AveryData = M('award');
            $AveryData->add($award_data);
        }
        //查询是否有本期数据1.查询最新的一期计划 2.没有数据的话从新建立
        $EveryData = M('award_plans');
        $map = array('lottery_type' => $lottery_type, 'type' =>$type);
        $new_info = $EveryData->where($map)->order('id  desc')->limit(1)->find();
        $flag = 0;
        if($new_info){
            $serial_number = $new_info['serial'];
            $every_str_num = $new_info['analysis'];
            $number  = $new_info['number'];
            $every = explode('-',$every_str_num);
            $fir_number = strpos($every[0],$second_num);
            $sec_number = strpos($every[1],$last_num);
            if ($fir_number === false||$sec_number===false) {
                $new_data['status'] =2;//错
                $status_name = '错';
                //如果为错的话 查看number数量 如果等于2更新 并插入下一期
                //如果为1的话更新number数量
                if($number==2) {
                    $new_data['period'] = $new_date;//期数
                    $new_data['award_number'] = $award_number;//开奖号码
                    $new_data['add_time'] = $time;
                    $new_data['award_plan'] = $serial_number.' 后二直选 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    //更新number
                    $EveryData->where($up_kai)->save($new_data);
                    $flag = 1;
                    //插入下一期
                }else{
                    //更新number
                    if($number<2){
                        $number = $number+1;
                    }
                    $new_data['number'] =$number;//错
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    $EveryData->where($up_kai)->save($new_data);
                }
            } else {
                if($number<3){
                    $number = $number+1;
                }
                $new_data['status'] =1;//中
                $status_name = '中';
                //下一期计划表  更新 下一期
                $new_data['number'] =$number;
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['award_plan'] = $serial_number.'后二直选 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                $EveryData = M('award_plans');
                $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                $EveryData->where($up_kai)->save($new_data);
                $flag = 1;
            }
        }else{
            //开始进来没有数据的时候
            if($new_date>118){
                $last_serial_number = $new_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $new_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }
            //获取最新的序列号
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $new_data['analysis'] = $next_one_num.'-'.$next_two_num;//分析数据
            $new_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $new_data['type'] = $type;
            $new_data['serial'] = $serial_number;
            $new_data['number'] = 1;//三个为一期
            if(in_array($second_num,$every_one_num)&&in_array($last_num,$every_two_num)){
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['status'] =1;//中
                $status_name = '中';
                $flag=1;
                $new_data['award_plan'] = $serial_number.' 后二直选 '.'['.$new_data['analysis'].'] '.$new_date.'期 '.$award_number.' '.$status_name;
            }
            $EveryData = M('award_plans');
            //插入数组
            $EveryData->add($new_data);
        }
        //如果 三期都没有中的话 计划下一期  如果中的话 直接进入下一期（flag=1）
        if($flag==1){
            //下一期
            $every_data = array();
            if($new_date=='120'){
                $next_date = '001';
            }else{
                $next_date = $new_date+1;
            }
            $next_date = sprintf ( "%03d",$next_date);
            //下一期序列号
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            //分析数据
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $every_data['analysis'] = $next_one_num.'-'.$next_two_num;//分析数据
            $every_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $every_data['type'] = $type;
            $every_data['number'] = 0;
            $every_data['serial'] = $serial_number;
            //添加下一期计划数据
            $EveryData = M('award_plans');
            $EveryData->add($every_data);
        }
    }

    /**
     * 后三直选
     * @param $new_kai  数据
     * @param $time     时间
     */
    public function since_three_shicai()
    {


        //抓取每天时时彩的开奖结果
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $three_num = substr($award_number, -5,1);//获取开奖号码倒数第三位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //类型
        $type = 3;
        $lottery_type = 1;
        //分析推荐
        //(1)后三直选
        $AveryData = M('award');
        $amap = array('lottery_type' => $lottery_type, 'type' =>$type,'period'=>$new_kai['date']);
        $award_info = $AveryData->where($amap)->limit(1)->find();
        //如果有本期中奖号码的话 返回
        if($award_info){
            return false;
        }
        //本期开奖号码 数据库没有的话  进行插入
        if(empty($award_info)){
            $award_data['period'] = $new_kai['date'];//期数
            $award_data['award_number'] = $award_number;//开奖号码
            $award_data['type'] = $type;
            $award_data['lottery_type'] = $lottery_type;
            $AveryData = M('award');
            $AveryData->add($award_data);
        }
        //查询是否有本期数据1.查询最新的一期计划 2.没有数据的话从新建立
        $EveryData = M('award_plans');
        $map = array('lottery_type' => $lottery_type, 'type' =>$type);
        $new_info = $EveryData->where($map)->order('id  desc')->limit(1)->find();
        $flag = 0;
        if($new_info){
            $serial_number = $new_info['serial'];
            $every_str_num = $new_info['analysis'];
            $number  = $new_info['number'];
            $every = explode('-',$every_str_num);
            $fir_number = strpos($every[0],$three_num);
            $sec_number = strpos($every[1],$second_num);
            $thr_number = strpos($every[2],$last_num);
            if ($fir_number === false||$sec_number===false||$thr_number===false) {
                $new_data['status'] =2;//错
                $status_name = '错';
                //如果为错的话 查看number数量 如果等于2更新 并插入下一期
                //如果为1的话更新number数量
                if($number==2) {
                    $new_data['period'] = $new_date;//期数
                    $new_data['award_number'] = $award_number;//开奖号码
                    $new_data['add_time'] = $time;
                    $new_data['award_plan'] = $serial_number.' 后三直选 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    //更新number
                    $EveryData->where($up_kai)->save($new_data);
                    $flag = 1;
                    //插入下一期
                }else{
                    //更新number
                    if($number<2){
                        $number = $number+1;
                    }
                    $new_data['number'] =$number;//错
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    $EveryData->where($up_kai)->save($new_data);
                }
            } else {
                if($number<3){
                    $number = $number+1;
                }
                $new_data['status'] =1;//中
                $status_name = '中';
                //下一期计划表  更新 下一期
                $new_data['number'] =$number;
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['award_plan'] = $serial_number.' 后三直选 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                $EveryData = M('award_plans');
                $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                $EveryData->where($up_kai)->save($new_data);
                $flag = 1;
            }
        }else{
            //开始进来没有数据的时候
            if($new_date>118){
                $last_serial_number = $new_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $new_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }
            //获取最新的序列号
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $every_three_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $next_three_num = implode($every_three_num,'');
            $new_data['analysis'] = $next_one_num.'-'.$next_two_num.'-'.$next_three_num;//分析数据
            $new_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $new_data['type'] = $type;
            $new_data['serial'] = $serial_number;
            $new_data['number'] = 1;//三个为一期
            if(in_array($three_num,$every_one_num)&&in_array($second_num,$every_two_num)&&in_array($last_num,$every_three_num)){
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['status'] =1;//中
                $status_name = '中';
                $flag=1;
                $new_data['award_plan'] = $serial_number.' 后三直选 '.'['.$new_data['analysis'].'] '.$new_date.'期 '.$award_number.' '.$status_name;
            }
            $EveryData = M('award_plans');
            //插入数组
            $EveryData->add($new_data);
        }
        //如果 三期都没有中的话 计划下一期  如果中的话 直接进入下一期（flag=1）
        if($flag==1){
            //下一期
            $every_data = array();
            if($new_date=='120'){
                $next_date = '001';
            }else{
                $next_date = $new_date+1;
            }
            $next_date = sprintf ( "%03d",$next_date);
            //下一期序列号
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            //分析数据
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $every_three_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $next_three_num = implode($every_three_num,'');
            $every_data['analysis'] = $next_one_num.'-'.$next_two_num.'-'.$next_three_num;//分析数据
            $every_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $every_data['type'] = $type;
            $every_data['number'] = 0;
            $every_data['serial'] = $serial_number;
            //添加下一期计划数据
            $EveryData = M('award_plans');
            $EveryData->add($every_data);
        }
    }

    /**
     *后三组六
     * @param $new_kai 数据
     * @param $time    时间
     */
    public function since_six_shicai()
    {



        //抓取每天时时彩的开奖结果
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $three_num = substr($award_number, -5,1);//获取开奖号码倒数第三位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //类型
        $type = 4;
        $lottery_type = 1;
        //分析推荐
        //(1)个位计划
//        $AveryData = M('award');
//        $amap = array('lottery_type' => $lottery_type, 'type' =>$type,'period'=>$new_kai['date']);
//        $award_info = $AveryData->where($amap)->limit(1)->find();
//        //如果有本期中奖号码的话 返回
//        if($award_info){
//            return false;
//        }
//        //本期开奖号码 数据库没有的话  进行插入
//        if(empty($award_info)){
//            $award_data['period'] = $new_kai['date'];//期数
//            $award_data['award_number'] = $award_number;//开奖号码
//            $award_data['type'] = $type;
//            $award_data['lottery_type'] = $lottery_type;
//            $AveryData = M('award');
//            $AveryData->add($award_data);
//        }
        //查询是否有本期数据1.查询最新的一期计划 2.没有数据的话从新建立
        $EveryData = M('award_plans');
        $map = array('lottery_type' => $lottery_type, 'type' =>$type);
        $new_info = $EveryData->where($map)->order('id  desc')->limit(1)->find();
        //判断后三是否是存在对子或者豹子
        $arr = array($three_num,$second_num,$last_num);
        $tmp = count(array_count_values($arr));
        $tmp_flag = true;
        if($tmp!=3){
            $tmp_flag = false;
        }
        $flag = 0;
        if($new_info){
            $serial_number = $new_info['serial'];
            $every_str_num = $new_info['analysis'];
            $number  = $new_info['number'];
            $fir_number = strpos($every_str_num,$three_num);
            $sec_number = strpos($every_str_num,$second_num);
            $thr_number = strpos($every_str_num,$last_num);
            if ($fir_number === false||$sec_number===false||$thr_number===false||$tmp_flag === false) {
                $new_data['status'] =2;//错
                $status_name = '错';
                //如果为错的话 查看number数量 如果等于2更新 并插入下一期
                //如果为1的话更新number数量
                if($number==2) {
                    $new_data['period'] = $new_date;//期数
                    $new_data['award_number'] = $award_number;//开奖号码
                    $new_data['add_time'] = $time;
                    $new_data['award_plan'] = $serial_number.' 后三组六 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    //更新number
                    $EveryData->where($up_kai)->save($new_data);
                    $flag = 1;
                    //插入下一期
                }else{
                    //更新number
                    if($number<2){
                        $number = $number+1;
                    }
                    $new_data['number'] =$number;//错
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    $EveryData->where($up_kai)->save($new_data);
                }
            } else {
                if($number<3){
                    $number = $number+1;
                }
                $new_data['status'] =1;//中
                $status_name = '中';
                //下一期计划表  更新 下一期
                $new_data['number'] =$number;
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['award_plan'] = $serial_number.' 后三组六 '.'['.$every_str_num.'] '.$new_date.'期 '.$award_number.' '.$status_name;
                $EveryData = M('award_plans');
                $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                $EveryData->where($up_kai)->save($new_data);
                $flag = 1;
            }
        }else{
            //开始进来没有数据的时候
            if($new_date>118){
                $last_serial_number = $new_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $new_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $new_date.'-'.$last_serial_number;
            }
            //获取最新的序列号
            $every_num = $this->getCode(9);
            $next_num = implode($every_num,'');
            $new_data['analysis'] = $next_num;//分析数据
            $new_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $new_data['type'] = $type;
            $new_data['serial'] = $serial_number;
            $new_data['number'] = 1;//三个为一期
            if(in_array($three_num,$every_num)&&in_array($second_num,$every_num)&&in_array($last_num,$every_num)&&$tmp_flag){
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['status'] =1;//中
                $status_name = '中';
                $flag=1;
                $new_data['award_plan'] = $serial_number.' 后三组六 '.'['.$new_data['analysis'].'] '.$new_date.'期 '.$award_number.' '.$status_name;
            }
            $EveryData = M('award_plans');
            //插入数组
            $EveryData->add($new_data);
        }
        //如果 三期都没有中的话 计划下一期  如果中的话 直接进入下一期（flag=1）
        if($flag==1){
            //下一期
            $every_data = array();
            if($new_date=='120'){
                $next_date = '001';
            }else{
                $next_date = $new_date+1;
            }
            $next_date = sprintf ( "%03d",$next_date);
            //下一期序列号
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            //分析数据
            $every_num = $this->getCode(9);
            $next_one_num = implode($every_num,'');
            $every_data['analysis'] = $next_one_num;//分析数据
            $every_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $every_data['type'] = $type;
            $every_data['number'] =0;
            $every_data['serial'] = $serial_number;
            //添加下一期计划数据
            $EveryData = M('award_plans');
            $EveryData->add($every_data);
        }
    }

    /**
     * 北京赛车pk10
     */
    public function racing()
    {
        $time = time();
        //抓取每天北京赛车开奖信息
        $rules = array(
            'text' => array('.nums','text'),
            'date' => array('.even td:eq(0)','text'),
        );
        $url = 'http://www.beijingsaiche.com/pk10/';
        $win = QueryList::Query($url,$rules)->data;
        $new_date = substr($win[0]['date'], -3);
        //网页表头
        $period = $win[0]['date'];//期数
        $win_first_str = preg_replace("/[\s]{2,}/"," ",$win[0]['text']);
        $win = explode(' ',$win_first_str);
        if($win){
            foreach ($win as $k=>&$v){
                $v = sprintf ( "%02d",$v);
            }
            unset($v);
        }
        $win_second_str = implode(' ',$win);
        $award_number = $win_second_str;
        //类型
        $lottery_type =2;
        $type =1;
        //分析推荐
        $AveryData = M('award');
        $amap = array('lottery_type' => $lottery_type, 'type' =>$type,'period'=>$period);
        $award_info = $AveryData->where($amap)->limit(1)->find();
        //如果有本期中奖号码的话 返回
        if($award_info){
            return false;
        }
        //本期开奖号码 数据库没有的话  进行插入
        if(empty($award_info)){
            $award_data['period'] = $period;//期数
            $award_data['award_number'] = $win_second_str;//开奖号码
            $award_data['type'] = $type;
            $award_data['lottery_type'] = $lottery_type;
            $AveryData = M('award');
            $AveryData->add($award_data);
        }
        $EveryData = M('award_plans');
        $map = array('lottery_type' => $lottery_type, 'type' =>$type);
        $new_info = $EveryData->where($map)->order('id  desc')->limit(1)->find();
        $flag = 0;
        if($new_info){
            $serial_number = $new_info['serial'];
            $every_str_num = $new_info['analysis'];
            $number  = $new_info['number'];
            $every = explode(" ",$every_str_num);
            if(in_array($win[0],$every)){
                if($number<3){
                    $number = $number+1;
                }
                $new_data['status'] =1;//中
                $status_name = '中';
                //下一期计划表  更新 下一期
                $new_data['number'] =$number;
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['award_plan'] = $serial_number.' 冠军 '.'['.$every_str_num.'] '.$new_date.'期 '.$status_name;
                $EveryData = M('award_plans');
                $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                $EveryData->where($up_kai)->save($new_data);
                $flag = 1;
            } else {
                $new_data['status'] =2;//错
                $status_name = '错';
                //如果为错的话 查看number数量 如果等于2更新 并插入下一期
                //如果为1的话更新number数量
                if($number==2) {
                    $new_data['period'] = $new_date;//期数
                    $new_data['award_number'] = $award_number;//开奖号码
                    $new_data['add_time'] = $time;
                    $new_data['award_plan'] = $serial_number.' 冠军 '.'['.$every_str_num.'] '.$new_date.'期 '.$status_name;
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    //更新number
                    $EveryData->where($up_kai)->save($new_data);
                    $flag = 1;
                    //插入下一期
                }else{
                    //更新number
                    if($number<2){
                        $number = $number+1;
                    }
                    $new_data['number'] =$number;//错
                    $EveryData = M('award_plans');
                    $up_kai = array('id' => $new_info['id'], 'type' =>$type,'lottery_type'=>$lottery_type);
                    $EveryData->where($up_kai)->save($new_data);
                }
            }
        }else{
            //开始进来没有数据的时候
            $last_serial_number = $new_date+2;
            $last_serial_number = sprintf ( "%03d",$last_serial_number);
            $serial_number = $new_date.'-'.$last_serial_number;
            //获取最新的序列号
            $race_fir_num = $this->getCode(5,1,10);
            if($race_fir_num){
                foreach ($race_fir_num as $key=>&$value){
                    $value = sprintf ( "%02d",$value);
                }
                unset($value);
            }
            $race_str_fir_num = implode($race_fir_num,' ');
            $new_data['analysis'] = $race_str_fir_num;//分析数据
            $new_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $new_data['type'] = $type;
            $new_data['serial'] = $serial_number;
            $new_data['number'] = 1;//三个为一期
            if(in_array($win[0],$race_fir_num)){
                $new_data['period'] = $new_date;//期数
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $new_data['status'] =1;//中
                $status_name = '中';
                $flag=1;
                $new_data['award_plan'] = $serial_number.' 冠军 '.'['.$race_str_fir_num.'] '.$new_date.'期 '.$status_name;
            }
            $EveryData = M('award_plans');
            //插入数组
            $EveryData->add($new_data);
        }
        //如果 三期都没有中的话 计划下一期  如果中的话 直接进入下一期（flag=1）
        if($flag==1){
            //下一期
            $every_data = array();
            $next_date = $new_date+1;
            $next_date = substr($next_date, -3);
            $next_date = sprintf ( "%03d",$next_date);
            //分析数据
            $last_serial_number = $next_date+2;
            $last_serial_number = sprintf ( "%03d",$last_serial_number);
            $serial_number = $next_date.'-'.$last_serial_number;
            $race_fir_num = $this->getCode(5,1,10);
            if($race_fir_num){
                foreach ($race_fir_num as $key=>&$value){
                    $value = sprintf ( "%02d",$value);
                }
                unset($value);
            }
            $race_str_fir_num = implode($race_fir_num,' ');
            $every_data['analysis'] = $race_str_fir_num;//分析数据
            $every_data['lottery_type'] = $lottery_type;//；类型1.时时彩2.汽车
            $every_data['type'] = $type;
            $every_data['number'] =0;
            $every_data['serial'] = $serial_number;
            //添加下一期计划数据
            $EveryData = M('award_plans');
            $EveryData->add($every_data);
        }
    }
    /**
     * 自动生成随机数字
     * @param $length  长度
     * @param int $min 最小值
     * @param int $max 最大值
     * @return array|bool
     */
    public function getCode($length,$min=0,$max=9)
    {
        if(empty($length)){
            return false;
        }
        $every_arr = array();
        while(count($every_arr) < $length)
        {
            $every_arr[] = rand($min,$max);    // 范围1-10
            $every_arr = array_unique($every_arr); // 防止重复
        }
        sort($every_arr);
        return $every_arr;
    }
}