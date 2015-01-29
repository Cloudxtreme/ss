/*
Files list:
/js/index.js
/js/dmsearch_index.js
*/

// ===========================
//js/index.js
jq(function(){
    if(typeof(window.startBanner)=='function')
        startBanner();

    //合作伙伴循环
    jq("#partners_section").als({
        visible_items: 8,
        scrolling_items: 7,
        viewport_width: 1120,
        wrapper_width:1120,
        circular:'yes',
        autoscroll:'no'
    });
});

// ===========================
//js/dmsearch_index.js
jq(function() {
	//背景色渐变效果
	jq(".boxes .indexpro_block .item").mouseover(function(){
		jq(this).children("span").addClass("hovercss");
		jq(this).css("color","#FFFFFF");
		jq(this).children("div .item-content").children("ul").hide();
		jq(this).children("div .item-content").children(".info-box").show();
	}); 
	jq(".boxes .indexpro_block .item").mouseout(function(){
		jq(this).children("span").removeClass("hovercss");
		jq(this).css("color","#333333");
		jq(this).children("div .item-content").children("ul").show();
		jq(this).children("div .item-content").children(".info-box").hide();
	}); 
	var objs = jq(".boxes  .indexpro_block .item");
	for(var i = 0, len = objs.length; i < len; i++){
		new ColorFade(objs[i], {StartColor: "#FFFFFF", EndColor: "#046b9f", Background: true, Speed: 20});
	}
	
	
	//输入框效果
	var valText;
	jq(".domainSearch").focus(function(){
	  valText = jq(".domainSearch").val();
	  jq(".domainSearch").val("");
	});
	jq(".domainSearch").blur(function(){
	  if (jq(".domainSearch").val() == "") {
		jq(".domainSearch").val(valText);
	  }
	});
	
})

function cg_hidtlds(type){
 if(type=='zh'){
	var htmlstr='<input type="hidden" name="enc" value="G">';
	jq("#zhongwen_tlds li").each(function(){
	    var selvalue= jq(this).children("label").html();
		var selid= jq(this).children("label").html().replace(/\./gi,'');
		htmlstr+='<input type="checkbox" name="suffix[]" id="'+selid+'" value="'+selvalue+'">';
	});
	jq("#hidtlds").html(htmlstr);
	jq("#zhongwen_tlds .on").each(function(){
		var selvalue= jq(this).children("label").html().replace(/\./gi,'');
		jq("#"+selvalue).prop("checked",true);
	})
 }else{
	var htmlstr='<input type="hidden" name="enc" value="E">';
	jq("#yingwen_tlds li").each(function(){
	    var selvalue= jq(this).children("label").html();
		var selid= jq(this).children("label").html().replace(/\./gi,'');
		htmlstr+='<input type="checkbox" name="suffix[]" id="'+selid+'" value="'+selvalue+'">';
	});
	jq("#hidtlds").html(htmlstr);
	jq("#yingwen_tlds .on").each(function(){
		var selvalue= jq(this).children("label").html().replace(/\./gi,'');
		jq("#"+selvalue).prop("checked",true);
	})
 }
}

function show_alltlds(tlds_en,tlds_ch){
	tlds_en_arr=tlds_en.split(';');
	var i=0;
	jq("#yingwen_tlds li").each(function(){
		var temparr= tlds_en_arr[i].split('_');
	    var selvalue= jq(this).children("label").html(temparr[0]);
		if(temparr.length>1&&temparr[1]=='checked'){
			jq(this).addClass("on");
			jq(this).children("span").addClass("glyphicon glyphicon-ok");
		}
		i++;
	});
	tlds_ch_arr=tlds_ch.split(';');
	var i=0;
	jq("#zhongwen_tlds li").each(function(){
		var temparr= tlds_ch_arr[i].split('_');
	    var selvalue= jq(this).children("label").html(temparr[0]);
		if(temparr.length>1&&temparr[1]=='checked'){
			jq(this).addClass("on");
			jq(this).children("span").addClass("glyphicon glyphicon-ok");
		}
		i++;
	});
	cg_hidtlds();
}

var Class = {
  create: function() {
    return function() {
      this.initialize.apply(this, arguments);
    }
  }
}
Object.extend = function(destination, source) {
    for (var property in source) {
        destination[property] = source[property];
    }
    return destination;
}
function addEventHandler(oTarget, sEventType, fnHandler) {
    if (oTarget.addEventListener) {
        oTarget.addEventListener(sEventType, fnHandler, false);
    } else if (oTarget.attachEvent) {
        oTarget.attachEvent("on" + sEventType, fnHandler);
    } else {
        oTarget["on" + sEventType] = fnHandler;
    }
};
var ColorFade = Class.create();
ColorFade.prototype = {
  initialize: function(Obj, options) {
    this._obj = "string" == typeof Obj ? jq(Obj) : Obj;
    this._timer = null;
    this.SetOptions(options);
    this.Step = Math.abs(this.options.Step);
    this.Speed = Math.abs(this.options.Speed);
    this.StartColor = this._color = this.GetColors(this.options.StartColor);
    this.EndColor = this.GetColors(this.options.EndColor);
    this._arrStep = [this.GetStep(this.StartColor[0], this.EndColor[0]), this.GetStep(this.StartColor[1], this.EndColor[1]), this.GetStep(this.StartColor[2], this.EndColor[2])];
    this._set = !this.options.Background ? function(color){ this._obj.style.color = color; } : function(color){ this._obj.style.backgroundColor = color; };
    this._set(this.options.StartColor);
    var oThis = this;
    addEventHandler(this._obj, "mouseover", function(){ oThis.Fade(oThis.EndColor); });
    addEventHandler(this._obj, "mouseout", function(){ oThis.Fade(oThis.StartColor); });
  },
  //默认属性
  SetOptions: function(options) {
    this.options = {
  StartColor:    "#000",
  EndColor:        "#DDC",
  Background:    false,
    Step:            20,
  Speed:        10
    };
    Object.extend(this.options, options || {});
  },
  //颜色    
  GetColors: function(sColor) {
    sColor = sColor.replace("#", "");
    var r, g, b;
    if (sColor.length > 3) {
        r = Mid(sColor, 0, 2); g = Mid(sColor, 2, 2); b = Mid(sColor, 4, 2);
    } else {
        r = Mid(sColor, 0, 1); g = Mid(sColor, 1, 1); b = Mid(sColor, 2, 1); r += r; g += g; b += b;
    }
    return [parseInt(r, 16), parseInt(g, 16), parseInt(b, 16)];
  },
  //渐变颜色
  GetColor: function(c, ec, iStep) {
    if (c == ec) { return c; }
    if (c < ec) { c += iStep; return (c > ec ? ec : c); }
    else { c -= iStep; return (c < ec ? ec : c); }
  },
  //渐变级数
  GetStep: function(start, end) {
    var iStep = Math.abs((end - start) / this.Step);
    if(iStep > 0 && iStep < 1) iStep = 1;
    return parseInt(iStep);
  },
  //颜色渐变
  Fade: function(rColor) {
    clearTimeout(this._timer);
    var er = rColor[0], eg = rColor[1], eb = rColor[2], r = this.GetColor(this._color[0], er, this._arrStep[0]), g = this.GetColor(this._color[1], eg, this._arrStep[1]), b = this.GetColor(this._color[2], eb, this._arrStep[2]);
    this._set("#" + Hex(r) + Hex(g) + Hex(b));
    this._color = [r, g, b];
    if(r != er || g != eg || b != eb){ var oThis = this; this._timer = setTimeout(function(){ oThis.Fade(rColor); }, this.Speed); }
  }
};
//返回16进制数
function Hex(i) {
    if (i < 0) return "00";
    else if (i > 255) return "ff";
    else { var str = "0" + i.toString(16); return str.substring(str.length - 2); }
}
//仿asp的mid 截字
function Mid(string, start, length) {
    if (length) return string.substring(start, start + length);
    else return string.substring(start);
}
// ===========================