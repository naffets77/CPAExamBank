
var last_result='';
var saved_result='';
var last_input='';
var last_printed=''
var modifying_history=false;
var history_index=1;

var regex_HexDigit='(?:[0-9a-fA-F])';
var regex_Digit='(?:[0-9])';
var regex_OctalDigit='(?:[0-7])';
var regex_NonZeroDigit='(?:[1-9])';
var regex_Sign='(?:[\+]|[\-])';
var regex_DecimalNum='(?:(?:[0]|'+regex_NonZeroDigit+regex_Digit+'*))';
var regex_OctalNum='(?:[0]'+regex_OctalDigit+'*)';
var regex_HexNum='(?:[0](?:[x]|[X])'+regex_HexDigit+'+)';
var regex_SignedInt='(?:'+regex_Sign+'?'+regex_Digit+'+)';
var regex_Expo='(?:[e]|[E])';
var regex_ExponentPart='(?:'+regex_Expo+regex_SignedInt+')';
var regex_Float1='(?:'+regex_Digit+'+[\.]'+regex_Digit+'*'+regex_ExponentPart+'?)';
var regex_Float2='(?:[\.]'+regex_Digit+'+'+regex_ExponentPart+'?)';
var regex_Float3='(?:'+regex_Digit+'+'+regex_ExponentPart+')';
var regex_Float4='(?:'+regex_Digit+'+)';
var regex_Float='(?:'+regex_Float1+'|'+regex_Float2+'|'+regex_Float3+'|'+regex_Float4+')';
var regex_ZeroFloat1='(?:[0]+[\.][0]*'+regex_ExponentPart+'?)';
var regex_ZeroFloat2='(?:[\.][0]+'+regex_ExponentPart+'?)';
var regex_ZeroFloat3='(?:[0]+'+regex_ExponentPart+')';
var regex_ZeroFloat4='(?:[0]+)';
var regex_ZeroFloat='(?:'+regex_ZeroFloat1+'|'+regex_ZeroFloat2+'|'+regex_ZeroFloat3+'|'+regex_ZeroFloat4+')';
var regex_Space='(?:[\n\ \t])';
var regex_Operands='(?:[\(\)\+\-\/\*\|\&\,\~\^]|\<\<|\>\>|\>\>\>|\%)'
var regex_MathStuff='(?:E|LN10|LN2|LOG10E|LOG2E|PI|SQRT1_2|SQRT2|abs|acos|asin|atan2|atan|ceil|cos|exp|floor|log|max|min|pow|random|round|sin|sqrt|tan)'
var regex_Functions='(?:(?:Math[\.]'+regex_MathStuff+')|'+regex_MathStuff+')';
var regex_allowable=new RegExp(
	regex_HexNum+'|'+regex_OctalNum+'|'+regex_Float+'|'+regex_DecimalNum+'|'+
	regex_ZeroFloat+'|'+regex_Space+'|'+regex_Operands+'|'+regex_Functions+'|ans','g'
);

var E=Math.E;
var LN10=Math.LN10;
var LN2=Math.LN2;
var LOG10E=Math.LOG10E;
var LOG2E=Math.LOG2E;
var PI=Math.PI;
var SQRT1_2=Math.SQRT1_2;
var SQRT2=Math.SQRT2;
function abs(x){return Math.abs(x);}
function acos(x){return Math.acos(x);}
function asin(x){return Math.asin(x);}
function atan(x){return Math.atan(x);}
function atan2(x,y){return Math.atan2(x,y);}
function ceil(x){return Math.ceil(x);}
function cos(x){return Math.cos(x);}
function exp(x){return Math.exp(x);}
function floor(x){return Math.floor(x);}
function log(x){return Math.log(x);}
function max(x,y){return Math.max(x,y);}
function min(x,y){return Math.min(x,y);}
function pow(x,y){return Math.pow(x,y);}
function random(){return Math.random();}
function round(x){return Math.round(x);}
function sin(x){return Math.sin(x);}
function sqrt(x){return Math.sqrt(x);}
function tan(x){return Math.tan(x);}

function replace_binary(s){
	var r=new RegExp("^((?:[a]|[^a])*)0[bB]([01]{1,32})((?:[a]|[^a])*)$");
	while(r.exec(s)){
		s=RegExp.$1+" "+from_bin(RegExp.$2)+" "+RegExp.$3;
	}
	return s;
}

function replace_ans(s){
	var r=new RegExp("^((?:[a]|[^a])*)ans((?:[a]|[^a])*)$");
	while(r.exec(s)){
		s=RegExp.$1+" "+saved_result+" "+RegExp.$2;
	}
	return s;
}

function do_calculation(){
	var current_calc=document.calculator.line.value;
	var mod_calc=replace_ans(current_calc);
	mod_calc=replace_binary(mod_calc);
	if(mod_calc!=last_printed&&mod_calc!=last_input&&!modifying_history){
		var not_allowed=mod_calc.split(regex_allowable);
		var num_badTokens=0;
		for(var k=0;k<not_allowed.length;k++){
			if(not_allowed[k].length!=0){
				num_badTokens++;
			}
		}
		if(num_badTokens==0){
			try{
				var calc_result=''+eval(mod_calc);
				if(calc_result!=undefined){
					last_result=calc_result;
					saved_result=calc_result;
					last_input='';
					display_result();
					add_toHistory(current_calc);
					save_calc();
				}
			}catch(ex){
				alert('Error type: '+ex.name+'\n'+'Error message: '+ex.message);
				last_input=document.calculator.line.value;
			}
		}else{
			alert(num_badTokens+' unknown tokens:\n'+not_allowed);
			last_input=document.calculator.line.value;
		}
		document.calculator.line.focus();
	}
}

function line_change(){
	if(last_printed!=document.calculator.line.value){
		last_result='';
	}
}

function display_result(){
	if(last_result!=''){
		var should_display=document.calculator.display.selectedIndex;
		var int_val=parseInt(last_result);
		var float_val=parseFloat(last_result);
		var to_print='';
		if(''+float_val!='NaN'&&should_display==1){
			to_print=to_sci(last_result,false);
		}else if(''+float_val!='NaN'&&should_display==2){
			to_print=to_sci(last_result,true);
		}else if(''+int_val!='NaN'&&should_display==3){
			to_print=to_hex(int_val);
		}else if(''+int_val!='NaN'&&should_display==4){
			to_print=to_octal(int_val);
		}else if(''+int_val!='NaN'&&should_display==5){
			to_print=to_bin(int_val);
		}else{
			to_print=round_extra_sf(float_val);
		}
		last_printed=to_print;
		document.calculator.line.value=to_print;
		history_index=1;
		document.calculator.line.style.backgroundColor='#aacc99';
	}else{
		document.calculator.line.style.backgroundColor='#cccc99';
	}
}

function round_extra_sf(f){
	var s=f.toPrecision(14);
	s=s.replace(/^([\+\-0-9\\.]*[1-9\.])0+((?:e[0-9\+\-]+)?)$/g,'$1$2');
	s=s.replace(/\.((?:e[0-9\+\-]+)?)$/g,'$1');
	return s;
}

function to_sci(s,eng){
	var the_exp=0;
	var is_negative=false;
	if(s.length>0&&s.charAt(0)=='-'){
		is_negative=true;
		s=s.substring(1,s.length);
	}
	var regex_splitter=s.split(new RegExp('[eE]'));
	if(regex_splitter.length>1){
		the_exp=parseInt(regex_splitter[1]);
		s=regex_splitter[0];
	}
	regex_splitter=s.split(/[\.]/);
	if(regex_splitter.length>1){
		s=regex_splitter[0]+regex_splitter[1];
		the_exp+=regex_splitter[0].length-1;
	}else{
		the_exp+=s.length-1;
	}
	var leading_zeros=0;
	for(leading_zeros=0;leading_zeros<s.length&&s.charAt(leading_zeros)=='0';leading_zeros++){
		the_exp=the_exp-1;
	}
	s=s.substring(leading_zeros,s.length);
	var move_dec;
	if(eng){
		if(the_exp>=0){
			move_dec=(the_exp%3)+1;
		}else{
			move_dec=4-((-the_exp)%3);
			if(move_dec==4){
				move_dec=1;
			}
		}
		the_exp-=(move_dec-1);
	}else{
		move_dec=1;
	}
	var trailing_zeros='';
	for(var i=s.length;i<move_dec;i++){
		trailing_zeros+='0';
	}
	return(
		(is_negative?'-':'')+
		((s.length==0)?'0':s.substring(0,move_dec))+
		((s.length<=move_dec)?trailing_zeros:('.'+s.substring(move_dec,s.length)))+
		((s.length==0||the_exp==0)?'':('e'+the_exp))
	);
}

var digit_array = new Array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

function to_hex(n){
	var hex_result=''
	var the_start=true;
	for(var i=32;i>0;){
		i-=4;
		var one_digit=(n>>i)&0xf;
		if(!the_start||one_digit!=0){
			the_start=false;
			hex_result+=digit_array[one_digit];
		}
	}
	return '0x'+(hex_result==''?'0':hex_result);
}

function to_octal(n){
	var oct_result=''
	var the_start=true;
	for(var i=33;i>0;){
		i-=3;
		var one_digit=(n>>i)&0x7;
		if(!the_start||one_digit!=0){
			the_start=false;
			oct_result+=digit_array[one_digit];
		}
	}
	return '0'+(oct_result==''?'0':oct_result);
}

function to_bin(n){
	var bin_result=''
	var the_start=true;
	for(var i=32;i>0;){
		i-=1;
		var one_digit=(n>>i)&0x1;
		if(!the_start||one_digit!=0){
			the_start=false;
			bin_result+=digit_array[one_digit];
		}
	}
	return '0b'+(bin_result==''?'0':bin_result);
}

function from_bin(s){
	var bin_result=0;
	var the_place=0;
	var i=s.length-1;
	while(i>=0&&the_place<32){
		if(s.charAt(i)=='1'){
			bin_result|=1<<the_place;
		}
		the_place++;
		i-=1;
	}
	return bin_result;
}

function set_calc(s){
	if(!modifying_history&&s!=''){
		last_result='';
		last_input=s;
		document.calculator.line.value=s;
		document.calculator.line.focus();
		last_input='';
		last_printed='';
		history_index=1;
		document.calculator.line.style.backgroundColor='#cccc99';
		document.calculator.line.focus();
	}
}

function append_calc(s,replaceLast){
	if(!modifying_history&&s!=''){
		last_result='';
		var new_contents
		if(replaceLast==0&&document.calculator.line.value==last_printed){
			new_contents=s;
		}else if(replaceLast==1&&document.calculator.line.value==last_printed){
			new_contents='ans '+s;
		}else{
			new_contents=document.calculator.line.value+s;
		}
		last_input=new_contents;
		document.calculator.line.value=new_contents;
		document.calculator.line.focus();
		last_input='';
		last_printed='';
		history_index=1;
		document.calculator.line.style.backgroundColor='#cccc99';
		document.calculator.line.focus();
	}
}

function clear_calc(){
	document.calculator.line.value='';
	history_index=1;
	document.calculator.line.style.backgroundColor='#cccc99';
	document.calculator.line.focus();
}

function add_toHistory(s){
	modifying_history=true;
	var is_found=false;
	var the_last=s;
	var next_history;
	var history_elements=document.calculator.history.options;
	for(var i=1;i<history_elements.length&&!is_found;i++){
		next_history=history_elements[i].text;
		history_elements[i].text=the_last;
		if(next_history==s){
			is_found=true;
		}
		the_last=next_history;
	}
	document.calculator.history.selectedIndex=0;
	modifying_history=false;
}

function load_calc(){
	modifying_history=true;
	var history_elements=document.calculator.history.options;
	var calc_cookie=get_cookie('calculatorState');
	if(calc_cookie!=null&&calc_cookie.length>1){
		var history_part=calc_cookie.substring(1,calc_cookie.length);
		if(history_part!=null){
			var history_split=history_part.split('\n');
			for(var i=1;i<history_elements.length&&i<history_split.length+1;i++){
				history_elements[i].text=history_split[i-1];
			}
		}
		document.calculator.display.selectedIndex=parseInt(calc_cookie.charAt(0));
	}
	modifying_history=false;
}

function save_calc(){
	var history_elements=document.calculator.history.options;
	var calc_cookie=document.calculator.display.selectedIndex;
	for(var i=1;i<history_elements.length;i++){
		calc_cookie+=history_elements[i].text+'\n';
	}
	var expires_date=new Date();
	// cookie expires in one year
	expires_date.setTime(expires_date.getTime()+365*24*60*60*1000);
	document.calc_cookie=(
		'calculatorState'+'='+
		escape(calc_cookie)+
		';expires='+expires_date.toGMTString()
	);
}

function get_cookie(name){
	var cookie_prefix=name+"=";
	var cookie_begin=document.cookie.indexOf(";"+cookie_prefix);
	if(cookie_begin==-1){
		cookie_begin=document.cookie.indexOf(cookie_prefix);
		if(cookie_begin!=0)return null;
	}else{
		cookie_begin+=2;
	}
	var cookie_end=document.cookie.indexOf(";",cookie_begin);
	if(cookie_end==-1)cookie_end=document.cookie.length;
	return unescape(document.cookie.substring(cookie_begin+cookie_prefix.length,cookie_end));
}

function display_nextHistory(){
	var history_elements=document.calculator.history.options;
	var next_history="";
	if(history_index>=history_elements.length||history_elements[history_index].text==""){
		history_index=1;
	}
	if(history_elements[history_index].text!=""){
		var temp_history=history_index;
		set_calc(history_elements[history_index].text);
		history_index=temp_history;
		document.calculator.history.selectedIndex=history_index;
		history_index++;
	}
	document.calculator.line.focus();
}

function next_display_method(){
	var d=document.calculator.display;
	var s=d.selectedIndex;
	s++;
	if(s>=d.options.length)s=0;
	display_method(s)
}

function display_method(index){
	document.calculator.display.selectedIndex=index;
	display_result();
	save_calc();
	document.calculator.line.focus();
}
