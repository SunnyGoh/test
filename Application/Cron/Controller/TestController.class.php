<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 12:09
 */
namespace Test\Controller;
use Think\Controller;

class DrawController extends Controller
{
    public function test(){
        ECHO 111;
        DIE;
    }

}