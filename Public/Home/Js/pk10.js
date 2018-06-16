
// /*****PK10*****/
$(function () {
    function postall() {
        $.ajax({
            type:"post",
            url:Ajaxurl+'get_lottery?type=5',
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
var Ajaxurl='http://www.shicai.com/index.php/api/Index/';