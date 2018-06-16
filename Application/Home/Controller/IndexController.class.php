<?php
namespace Home\Controller;
use Think\Controller;
use QL\QueryList;
class IndexController extends Controller
{
    public function index()
    {

       $this->display();
    }
    public function lottery_se()
    {

       $this->display('lottery_se');
    }
    public function lottery_th()
    {

       $this->display('lottery_th');
    }
    public function lottery_fo()
    {

       $this->display('lottery_fo');
    }
     public function lottery_fi()
    {

       $this->display('lottery_fi');
    }

    /**
     * 获取时时彩 北京赛车数据
     */
    private function get_lottery_data()
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
    private function common_lottery()
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
            return json_encode(array());
        }
        $today_fir = strtotime(date('Y-m-d 00:00:00'));
        $today_las = strtotime(date('Y-m-d 23:59:59'));
        $data['new_kai']  = $new_kai;
        $data['time']  = $time;
        $data['today_fir']  = $today_fir;
        $data['today_las']  = $today_las;
        return $data;

    }
    /**
     * 个位计划
     * @param $new_kai  数据
     * @param $time     时间
     */
    private function every_shicai()
    {
     
        //抓取每天时时彩的开奖结果
       $common_data = $this->common_lottery();
       $new_kai = $common_data['new_kai'];
       $time = $common_data['time'];
       $today_fir = $common_data['today_fir'];
       $today_las = $common_data['today_las'];


        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);

        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //分析推荐
        //(1)各位计划
        //查询是否有本期数据1.如果本期为空的话 插入（第一次进入） 2.如果有的话更新
        $EveryData = M('award_plan');
        $map = array('period' => $new_kai['date'], 'type' =>1);
        $new_info = $EveryData->where($map)->limit(1)->select();

        $new_data = array();
        if(empty($new_info)){
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
            $new_data['analysis_data'] = $next_str_num;//分析数据
            $new_data['period'] = $new_kai['date'];//期数
            $new_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $new_data['type'] = 1;//
            $new_data['add_time'] = $time;
            $new_data['serial_number'] = $serial_number;
            $new_data['award_number'] = $award_number;//开奖号码
            if(in_array($last_num,$every_num)){
                $new_data['status'] =1;//中
            }else{
                $new_data['status'] =2;//错
            }
            //插入数组
            $EveryData->add($new_data);
        }else{
            if(empty($new_info['award_number'])&&empty($new_info['status']))
            {
                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;//获取开奖时间
                $EveryData = M('award_plan');
                $map = array('period' => $new_kai['date'], 'type' =>1,'lottery_type'=>1);
                $every_str_num = $EveryData->where($map)->limit(1)->getField('analysis_data');
                $pos = strpos($every_str_num,$last_num);
                if ($pos === false) {
                    $new_data['status'] =2;//错
                } else {
                    $new_data['status'] =1;//中
                }
                //更新数据
                $EveryData = M('award_plan');
                $up_kai = array('period' => $new_kai['date'], 'type' =>1,'lottery_type'=>1);
                $EveryData->where($up_kai)->save($new_data);

            }
        }
        //查询是否有下一期数据1.如果有的话 不变2.如果没有 插入数据 并输出计划表
        $every_data = array();
        if($new_date=='120'){
            $next_date = '001';
        }else{
            $next_date = $new_date+1;
        }
        $next_date = sprintf ( "%03d",$next_date);
        $time = date('Ymd');
        $str = substr($time,-6);
        $period = $str.$next_date;
        $EveryData = M('award_plan');
        $map = array('period' => $period, 'type' =>1);
        $next_info = $EveryData->where($map)->limit(1)->find();

        if(empty($next_info)){
//            $old_serial_number = $EveryData->where('type=1')->order('id desc')->limit(1)->getField('serial_number');
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            $every_num = $this->getCode(5);
            $next_str_num = implode($every_num,'');
            $every_data['analysis_data'] = $next_str_num;//分析数据
            $every_data['period'] = $period;//期数
            $every_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $every_data['type'] = 1;//
            $every_data['serial_number'] = $serial_number;
            $EveryData->add($every_data);
            $data['analysis_data'] = $next_str_num;
            $data['next_plan'] = $serial_number.'期 个位【'.$next_str_num.'】'.$next_date.'期 等开';

        }else{
            $data['analysis_data'] = $next_info['analysis_data'];
            $data['next_plan'] = $next_info['serial_number'].'期 个位【'.$next_info['analysis_data'].'】'.$next_date.'期 等开';
        }
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['type']  = array('eq',1);
        $plan['lottery_type']  = array('eq',1);
        $plan['add_time']  = array('egt',$today_fir);
        $plan['add_time']  = array('elt',$today_las);
        $list = $EveryData->where($plan)->order('id desc')->select();

        foreach ($list as $key=>$value){
            if($value['status']==1){
                $value['status'] ='中';
            }else{
                $value['status'] ='错';
            }
            $awd_plan[$key] = $value['serial_number'].'个位'.'['.$value['analysis_data'].']'.$value['period'].'期'.$value['award_number'].$value['status'];
        }

        //下一期分析计划表
        $data['awd_plan'] = $awd_plan;
        echo json_encode($data);
    }
    /**
     * 后二直选
     * @param $new_kai  数据
     * @param $time     时间
     */
    private function since_two_shicai()
    {
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $today_fir = $common_data['today_fir'];
        $today_las = $common_data['today_las'];

        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //分析推荐
        //(1)后二直选
        //查询是否有本期数据1.如果本期为空的话 插入（第一次进入） 2.如果有的话更新
        $EveryData = M('award_plan');
        $map = array('period' => $new_kai['date'], 'type' =>2);
        $new_info = $EveryData->where($map)->limit(1)->select();
        $new_data = array();
        if(empty($new_info)){

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
            $new_data['analysis_data'] = $next_one_num.'-'.$next_two_num;//分析数据
            $new_data['period'] = $new_kai['date'];//期数
            $new_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $new_data['type'] = 2;//
            $new_data['add_time'] = $time;
            $new_data['serial_number'] = $serial_number;
            $new_data['award_number'] = $award_number;//开奖号码
            if(in_array($second_num,$every_one_num)&&in_array($last_num,$every_two_num)){
                $new_data['status'] =1;//中
            }else{
                $new_data['status'] =2;//错
            }

            //插入数组
            $EveryData->add($new_data);
        }else{

            if(empty($new_info['award_number'])&&empty($new_info['status']))
            {

                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $EveryData = M('award_plan');
                $map = array('period' => $new_kai['date'],'type' =>2);
                $every_str_num = $EveryData->where($map)->limit(1)->getField('analysis_data');
                $every = explode('-',$every_str_num);

                if($every){
                    $fir_number = strpos($every[0],$second_num);
                    $sec_number = strpos($every[1],$last_num);
                    if ($fir_number === false&&$sec_number===false) {
                        $new_data['status'] =2;//错
                    } else {
                        $new_data['status'] =1;//中
                    }
                    //更新数据
                    $EveryData = M('award_plan');
                    $up_kai = array('period' => $new_kai['date'], 'type' =>1);
                    $EveryData->where($up_kai)->save($new_data);
                }
            }
        }
        //查询是否有下一期数据1.如果有的话 不变2.如果没有 插入数据 并输出计划表
        $every_data = array();
        if($new_date=='120'){
            $next_date = '001';
        }else{
            $next_date = $new_date+1;
        }
        $next_date = sprintf ( "%03d",$next_date);

        $time = date('Ymd');
        $str = substr($time,-6);
        $period = $str.$next_date;
        $EveryData = M('award_plan');
        $map = array('period' => $period, 'type' =>2);
        $next_info = $EveryData->where($map)->limit(1)->find();

        if(empty($next_info)){
//            $old_serial_number = $EveryData->where('type=1')->order('id desc')->limit(1)->getField('serial_number');
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $every_data['analysis_data'] = $next_one_num.'-'.$next_two_num;//分析数据
            $every_data['period'] = $period;//期数
            $every_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $every_data['type'] = 2;//
            $every_data['serial_number'] = $serial_number;
            $EveryData->add($every_data);
            $data['analysis_data'] = $every_data['analysis_data'];
            $data['next_plan'] = $serial_number.'期 后二直选【'. $every_data['analysis_data'].'】'.$next_date.'期 等开';

        }else{
            $data['analysis_data'] = $next_info['analysis_data'];
            $data['next_plan'] = $next_info['serial_number'].'期 后二直选【'.$next_info['analysis_data'].'】'.$next_date.'期 等开';
        }
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['type']  = array('eq',2);
        $plan['lottery_type']  = array('eq',1);
        $plan['add_time']  = array('egt',$today_fir);
        $plan['add_time']  = array('elt',$today_las);
        $list = $EveryData->where($plan)->order('id desc')->select();

        foreach ($list as $key=>$value){
            if($value['status']==1){
                $value['status'] ='中';
            }else{
                $value['status'] ='错';
            }
            $awd_plan[$key] = $value['serial_number'].'后二直选'.'['.$value['analysis_data'].']'.$value['period'].'期'.$value['award_number'].$value['status'];
        }

        //下一期分析计划表
        $data['awd_plan'] = $awd_plan;
        echo json_encode($data);
    }

    /**
     * 后三直选
     * @param $new_kai  数据
     * @param $time     时间
     */
    private function since_three_shicai()
    {
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $today_fir = $common_data['today_fir'];
        $today_las = $common_data['today_las'];

        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $three_num = substr($award_number, -5,1);//获取开奖号码倒数第三位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //分析推荐
        //(1)后三直选
        //查询是否有本期数据1.如果本期为空的话 插入（第一次进入） 2.如果有的话更新
        $EveryData = M('award_plan');
        $map = array('period' => $new_kai['date'], 'type' =>3);
        $new_info = $EveryData->where($map)->limit(1)->select();
        $new_data = array();
        if(empty($new_info)){

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
            $new_data['analysis_data'] = $next_one_num.'-'.$next_two_num.'-'.$next_three_num;//分析数据
            $new_data['period'] = $new_kai['date'];//期数
            $new_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $new_data['type'] = 3;//
            $new_data['add_time'] = $time;
            $new_data['serial_number'] = $serial_number;
            $new_data['award_number'] = $award_number;//开奖号码
            if(in_array($three_num,$every_one_num)&&in_array($second_num,$every_two_num)&&in_array($last_num,$every_three_num)){
                $new_data['status'] =1;//中
            }else{
                $new_data['status'] =2;//错
            }
            //插入数组
            $EveryData->add($new_data);
        }else{

            if(empty($new_info['award_number'])&&empty($new_info['status']))
            {

                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $EveryData = M('award_plan');
                $map = array('period' => $new_kai['date'],'type' =>3);
                $every_str_num = $EveryData->where($map)->limit(1)->getField('analysis_data');
                $every = explode('-',$every_str_num);
                if($every){
                    $fir_number = strpos($every[0],$three_num);
                    $sec_number = strpos($every[1],$second_num);
                    $thr_number = strpos($every[2],$last_num);
                    if ($fir_number === false&&$sec_number===false&&$thr_number===false) {
                        $new_data['status'] =2;//错
                    } else {
                        $new_data['status'] =1;//中
                    }
                    //更新数据
                    $EveryData = M('award_plan');
                    $up_kai = array('period' => $new_kai['date'], 'type' =>3);
                    $EveryData->where($up_kai)->save($new_data);
                }
            }
        }
        //查询是否有下一期数据1.如果有的话 不变2.如果没有 插入数据 并输出计划表
        $every_data = array();
        if($new_date=='120'){
            $next_date = '001';
        }else{
            $next_date = $new_date+1;
        }
        $next_date = sprintf ( "%03d",$next_date);

        $time = date('Ymd');
        $str = substr($time,-6);
        $period = $str.$next_date;
        $EveryData = M('award_plan');
        $map = array('period' => $period, 'type' =>3);
        $next_info = $EveryData->where($map)->limit(1)->find();

        if(empty($next_info)){
//            $old_serial_number = $EveryData->where('type=1')->order('id desc')->limit(1)->getField('serial_number');
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            $every_one_num = $this->getCode(7);
            $every_two_num = $this->getCode(7);
            $every_three_num = $this->getCode(7);
            $next_one_num = implode($every_one_num,'');
            $next_two_num = implode($every_two_num,'');
            $next_three_num = implode($every_three_num,'');
            $every_data['analysis_data'] = $next_one_num.'-'.$next_two_num.'-'.$next_three_num;//分析数据
            $every_data['period'] = $period;//期数
            $every_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $every_data['type'] = 3;//
            $every_data['serial_number'] = $serial_number;
            $EveryData->add($every_data);
            $data['analysis_data'] = $every_data['analysis_data'];
            $data['next_plan'] = $serial_number.'期 后三直选【'. $every_data['analysis_data'].'】'.$next_date.'期 等开';

        }else{
            $data['analysis_data'] = $next_info['analysis_data'];
            $data['next_plan'] = $next_info['serial_number'].'期 后三直选【'.$next_info['analysis_data'].'】'.$next_date.'期 等开';
        }
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['type']  = array('eq',3);
        $plan['lottery_type']  = array('eq',1);
        $plan['add_time']  = array('egt',$today_fir);
        $plan['add_time']  = array('elt',$today_las);
        $list = $EveryData->where($plan)->order('id desc')->select();
        foreach ($list as $key=>$value){
            if($value['status']==1){
                $value['status'] ='中';
            }else{
                $value['status'] ='错';
            }
            $awd_plan[$key] = $value['serial_number'].' 后三直选 '.'['.$value['analysis_data'].']'.$value['period'].'期 '.$value['award_number'].$value['status'];
        }
        //下一期分析计划表
        $data['awd_plan'] = $awd_plan;
        echo json_encode($data);

    }

    /**
     *后三组六
     * @param $new_kai 数据
     * @param $time    时间
     */
    private function since_six_shicai()
    {
        $common_data = $this->common_lottery();
        $new_kai = $common_data['new_kai'];
        $time = $common_data['time'];
        $today_fir = $common_data['today_fir'];
        $today_las = $common_data['today_las'];

        $award_number = htmlspecialchars($new_kai['num']);;//开奖号码
        $data['award_number'] = explode(' ',$award_number);
        $last_num = substr($award_number, -1);//获取开奖号码最后一位
        $second_num = substr($award_number, -3,1);//获取开奖号码倒数第二位
        $three_num = substr($award_number, -5,1);//获取开奖号码倒数第三位
        $new_date = substr($new_kai['date'], -3);
        //网页表头
        $data['period'] = $new_date;//期数
        //分析推荐
        //(1)后三组六
        //查询是否有本期数据1.如果本期为空的话 插入（第一次进入） 2.如果有的话更新
        $EveryData = M('award_plan');
        $map = array('period' => $new_kai['date'], 'type' =>4);
        $new_info = $EveryData->where($map)->limit(1)->select();
        $new_data = array();
        if(empty($new_info)){

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
            $new_data['analysis_data'] = $next_num;//分析数据
            $new_data['period'] = $new_kai['date'];//期数
            $new_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $new_data['type'] = 4;//
            $new_data['add_time'] = $time;
            $new_data['serial_number'] = $serial_number;
            $new_data['award_number'] = $award_number;//开奖号码
            if(in_array($three_num,$every_num)&&in_array($second_num,$every_num)&&in_array($last_num,$every_num)){
                $new_data['status'] =1;//中
            }else{
                $new_data['status'] =2;//错
            }
            //插入数组
            $EveryData->add($new_data);
        }else{

            if(empty($new_info['award_number'])&&empty($new_info['status']))
            {

                $new_data['award_number'] = $award_number;//开奖号码
                $new_data['add_time'] = $time;
                $EveryData = M('award_plan');
                $map = array('period' => $new_kai['date'],'type' =>4);
                $every_str_num = $EveryData->where($map)->limit(1)->getField('analysis_data');
                if($every_str_num)
                {
                    $fir_number = strpos($every_str_num,$three_num);
                    $sec_number = strpos($every_str_num,$second_num);
                    $thr_number = strpos($every_str_num,$last_num);
                    if ($fir_number === false&&$sec_number===false&&$thr_number===false) {
                        $new_data['status'] =2;//错
                    } else {
                        $new_data['status'] =1;//中
                    }
                    //更新数据
                    $EveryData = M('award_plan');
                    $up_kai = array('period' => $new_kai['date'], 'type' =>4);
                    $EveryData->where($up_kai)->save($new_data);
                }
            }
        }
        //查询是否有下一期数据1.如果有的话 不变2.如果没有 插入数据 并输出计划表
        $every_data = array();
        if($new_date=='120'){
            $next_date = '001';
        }else{
            $next_date = $new_date+1;
        }
        $next_date = sprintf ( "%03d",$next_date);

        $time = date('Ymd');
        $str = substr($time,-6);
        $period = $str.$next_date;
        $EveryData = M('award_plan');
        $map = array('period' => $period, 'type' =>4);
        $next_info = $EveryData->where($map)->limit(1)->find();

        if(empty($next_info)){
//            $old_serial_number = $EveryData->where('type=1')->order('id desc')->limit(1)->getField('serial_number');
            if($next_date>118){
                $last_serial_number = $next_date+2-120;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }else{
                $last_serial_number = $next_date+2;
                $last_serial_number = sprintf ( "%03d",$last_serial_number);
                $serial_number = $next_date.'-'.$last_serial_number;
            }
            $every_num = $this->getCode(9);
            $next_one_num = implode($every_num,'');
            $every_data['analysis_data'] = $next_one_num;//分析数据
            $every_data['period'] = $period;//期数
            $every_data['lottery_type'] = 1;//；类型1.时时彩2.汽车
            $every_data['type'] = 4;//
            $every_data['serial_number'] = $serial_number;
            $EveryData->add($every_data);
            $data['analysis_data'] = $every_data['analysis_data'];
            $data['next_plan'] = $serial_number.'期 后三组六【'. $every_data['analysis_data'].'】'.$next_date.'期 等开';

        }else{
            $data['analysis_data'] = $next_info['analysis_data'];
            $data['next_plan'] = $next_info['serial_number'].'期 后三组六【'.$next_info['analysis_data'].'】'.$next_date.'期 等开';
        }
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['type']  = array('eq',4);
        $plan['lottery_type']  = array('eq',1);
        $plan['add_time']  = array('egt',$today_fir);
        $plan['add_time']  = array('elt',$today_las);
        $list = $EveryData->where($plan)->order('id desc')->select();
        foreach ($list as $key=>$value){
            if($value['status']==1){
                $value['status'] ='中';
            }else{
                $value['status'] ='错';
            }
            $awd_plan[$key] = $value['serial_number'].'后三组六'.'['.$value['analysis_data'].']'.$value['period'].'期'.$value['award_number'].$value['status'];
        }
        //下一期分析计划表
        $data['awd_plan'] = $awd_plan;
        echo json_encode($data);

    }

    /**
     * 北京赛车pk10
     */
    private function racing()
    {
        $time = time();
        //抓取每天北京赛车开奖信息
        $rules = array(
            'text' => array('.nums','text'),
            'date' => array('.even td:eq(0)','text'),
        );
        $today_fir = strtotime(date('Y-m-d 00:00:00'));
        $today_las = strtotime(date('Y-m-d 23:59:59'));

        $url = 'http://www.beijingsaiche.com/pk10/';
        $win = QueryList::Query($url,$rules)->data;
        $new_date = substr($win[0]['date'], -3);
        //网页表头
        $data['period'] = $win[0]['date'];//期数
        $win_first_str = preg_replace("/[\s]{2,}/"," ",$win[0]['text']);
        $win = explode(' ',$win_first_str);
        if($win){
            foreach ($win as $k=>&$v){
                $v = sprintf ( "%02d",$v);
            }
            unset($v);
        }
        $win_second_str = implode(' ',$win);
        $data['award_number'] = $win;
        //分析推荐
        //查询是否有本期数据1.如果本期为空的话 插入（第一次进入） 2.如果有的话更新
        $EveryData = M('award_plan');
        $map = array('period' => $data['period'], 'lottery_type' =>2);
        $new_info = $EveryData->where($map)->limit(1)->select();
        $new_data = array();
        if(empty($new_info))
        {
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
            $new_data['analysis_data'] = $race_str_fir_num;//分析数据
            $new_data['period'] = $data['period'];//期数
            $new_data['lottery_type'] = 2;//；类型1.时时彩2.汽车
            $new_data['type'] = 1;//
            $new_data['add_time'] = $time;
            $new_data['serial_number'] = $serial_number;
            $new_data['award_number'] = $win_second_str;//开奖号码
            if ($race_fir_num == array_intersect($race_fir_num, $win)) {
                $new_data['status'] =1;//中
            }else {
                $new_data['status'] =2;//挂';
            }
            //插入数组
            $EveryData->add($new_data);
        }else{
            if(empty($new_info['award_number'])&&empty($new_info['status']))
            {
                $new_data['award_number'] = $win_second_str;//开奖号码
                $new_data['add_time'] = $time;
                $EveryData = M('award_plan');
                $map = array('period' => $data['period'],'lottery_type' =>2);
                $every_str_num = $EveryData->where($map)->limit(1)->getField('analysis_data');
                if($every_str_num){
                    $every = implode(" ",$every_str_num);
                    if ($every == array_intersect($every, $win)) {
                        $new_data['status'] =1;//中
                    }else {
                        $new_data['status'] =2;//错
                    }
                    //更新数据
                    $EveryData = M('award_plan');
                    $up_kai = array('period' => $data['period'], 'lottery_type' =>2);
                    $EveryData->where($up_kai)->save($new_data);

                }
            }
        }
        //查询是否有下一期数据1.如果有的话 不变2.如果没有 插入数据 并输出计划表
        $every_data = array();
        $next_date = $new_date+1;
        $next_date = sprintf ( "%03d",$next_date);

        $period = $data['period']+1;

        $EveryData = M('award_plan');
        $map = array('period' => $period, 'lottery_type' =>2);
        $next_info = $EveryData->where($map)->limit(1)->find();

        if(empty($next_info)){
//            $old_serial_number = $EveryData->where('type=1')->order('id desc')->limit(1)->getField('serial_number');
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
            $every_data['analysis_data'] = $race_str_fir_num;//分析数据
            $every_data['period'] = $period;//期数
            $every_data['lottery_type'] = 2;//；类型1.时时彩2.汽车
            $every_data['type'] = 1;//
            $every_data['serial_number'] = $serial_number;

            $EveryData->add($every_data);
            $data['analysis_data'] = $every_data['analysis_data'];
            $data['next_plan'] = $serial_number.'期 冠军【'. $every_data['analysis_data'].'】'.$next_date.'期 等开';

        }else{
            $data['analysis_data'] = $next_info['analysis_data'];
            $data['next_plan'] = $next_info['serial_number'].'期 冠军【'.$next_info['analysis_data'].'】'.$next_date.'期 等开';
        }
        $awd_plan = array();
        $plan['award_number']  = array('neq','');
        $plan['lottery_type']  = array('eq',2);
        $plan['add_time']  = array('egt',$today_fir);
        $plan['add_time']  = array('elt',$today_las);
        $list = $EveryData->where($plan)->order('id desc')->select();

        foreach ($list as $key=>$value){
            if($value['status']==1){
                $value['status'] ='中';
            }else{
                $value['status'] ='挂';
            }
            $awd_plan[$key] = $value['serial_number'].'冠军'.'['.$value['analysis_data'].']'.$value['period'].'期'.$value['award_number'].$value['status'];
        }
        //下一期分析计划表
        $data['awd_plan'] = $awd_plan;
        echo json_encode($data);

    }
    /**
     * 自动生成随机数字
     * @param $length  长度
     * @param int $min 最小值
     * @param int $max 最大值
     * @return array|bool
     */
    private function getCode($length,$min=0,$max=9)
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