$(function () {
    let c=console;
    /*****后二直选*****/
    function postallh2() {
        $.ajax({
            url:Ajaxurl+'get_lottery_plan?type=2',
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null){
                    var next_plan=data.next_plan,
                        awd_plan=data.awd_plan;
                    $('#sendh2').text(next_plan);//期数
                    $('#plandth2').html('');//清空内容
                    for(var k=0;k<awd_plan.length;k++){//计划表
                        $('#plandth2').append('<span style="font-size:18px;line-height:30px;">'+awd_plan[k]['award_plan']+'</span><br>');
                    }
                }else{
                    c.log("读取数据失败!");
                }
            },error:function () {
                c.log("读取数据失败!");
            }
        });
    }
    postallh2();
    setInterval(postallh2,2000);
});