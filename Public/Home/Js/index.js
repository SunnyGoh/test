
/*****个位计划*****/
$(function () {
    let c=console;
    function postallgw() {
        $.ajax({
            url:Ajaxurl+'get_lottery_plan?type=1',
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null){
                    var next_plan=data.next_plan,
                        awd_plan=data.awd_plan;
                    $('#send').text(next_plan);//期数
                    $('#plandt').html('');//清空当前元素
                    for(var k=0;k<awd_plan.length;k++){//计划表
                        $('#plandt').append('<span style="font-size:18px;line-height:30px;">'+awd_plan[k]['award_plan']+'</span><br>');
                    }
                }else{
                    c.log("读取数据失败!");
                }
            },error:function () {
                c.log("读取数据失败!");
            }
        });
    }
    postallgw();
    setInterval(postallgw,2000);
});

