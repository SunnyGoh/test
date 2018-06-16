var i=0;
function blink(){
    document.getElementById("ftcolor").className="changecolor"+i%2;
    i++;
}
$(function () {
    /*****PK10*****/
    function postallpk10() {
        let c=console;
        $.ajax({
            url:"http://157.119.71.98/index.php/api/Index/get_lottery_plan?type=5",
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null){
                    var next_plan=data.next_plan,
                        awd_plan=data.awd_plan;
                    $('#sendpk10').text(next_plan);//期数
                    $('#plandtpk10').html('');//清空内容
                    for(var k=0;k<awd_plan.length;k++){//计划表
                        $('#plandtpk10').append('<span style="font-size:18px;line-height:30px;">'+awd_plan[k]['award_plan']+'</span><br>');
                    }
                }else{
                    c.log("读取数据失败!");
                }
            },error:function () {
                c.log("读取数据失败!");
            }
        });
    }
    postallpk10();
    setInterval(postallpk10,2000);
});