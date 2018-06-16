// /*****个位计划*****/
$(function () {
    function postall() {
        $.ajax({
            url:"http://157.119.71.98/api/Index/get_lottery?type=1",
            type:"post",
            cache:false,
            async:true,
            dataType:"json",
            success:function (data) {
                if(data!=null) {
                    var period = data.period,
                        analysis = data.award_number;
                    $('#qishu').text(period);
                    $('.cqssc-nums').empty();
                    for (var i = 0; i < analysis.length; i++) {
                        $('.cqssc-nums').append('<span>' + analysis[i] + '</span>');
                    }
                }
            }
        });
    }
    postall();
    setInterval(postall,2000);
});