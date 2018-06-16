$(function () {
    /*****后三直选*****/
    function postallh3() {
        let c=console;
        $.ajax({
            url:Ajaxurl+'get_lottery_plan?type=3',
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null){
                    var next_plan=data.next_plan,
                        awd_plan=data.awd_plan;
                    $('#sendh3').text(next_plan);//期数
                    $('#plandth3').html('');//清空内容
                    for(var k=0;k<awd_plan.length;k++){//计划表
                        $('#plandth3').append('<span style="font-size:18px;line-height:30px;">'+awd_plan[k]['award_plan']+'</span><br>');
                    }
                }else{
                    c.log("读取数据失败!");
                }
            },error:function () {
                c.log("读取数据失败!");
            }
        });
    }
    postallh3();
    setInterval(postallh3,2000);
});