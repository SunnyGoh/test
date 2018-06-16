$(function () {
    /*****后三组六*****/
    function postallh3z6() {
        let c=console;
        $.ajax({
            url:Ajaxurl+'get_lottery_plan?type=4',
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null){
                    var next_plan=data.next_plan,
                        awd_plan=data.awd_plan;
                    $('#sendh3z6').text(next_plan);//期数
                    $('#plandth3z6').html('');//清空内容
                    for(var k=0;k<awd_plan.length;k++){//计划表
                        $('#plandth3z6').append('<span style="font-size:18px;line-height:30px;">'+awd_plan[k]['award_plan']+'</span><br>');
                    }
                }else{
                    c.log("读取数据失败!");
                }
            },error:function () {
                c.log("读取数据失败!");
            }
        });
    }
    postallh3z6();
    setInterval(postallh3z6,2000);
});