var i=0;
function blink(){
	document.getElementById("ftcolor").className="changecolor"+i%2;
	i++;
}
var Ajaxurl='http://www.tp.com/index.php/api/Index/';