//删除左右两端的空格
function trim(str){ 
	return str.replace(/(^\s*)|(\s*$)/g, "");
}
//删除左边的空格
function ltrim(str){ 
	return str.replace(/(^\s*)/g,"");
}
//删除右边的空格
function rtrim(str){ 
	return str.replace(/(\s*$)/g,"");
}

//获取地址栏参数
String.prototype.getQuery = function (name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]); return null;
}

/***
* 含有中英文时，计算字串的字节数
*/
String.prototype.len = function () {
    return this.replace(/[^\x00-\xff]/g, "rr").length;
}
//返回指定长度的字符串
function getStr(str, len) {
    var strLen = str.len();
    if (strLen <= len) return str;
    var rgx = /[^\x00-\xff]/,
		resultStr = '',
		k = 0,
		m = 0;
    while (m < len) {
        var tempStr = str.charAt(k);
        rgx.test(tempStr) ? m += 2 : m++;
        k++;
        resultStr += tempStr;
    }
    return resultStr + '...';
}

//转码
function encodeStr(prev, str) {
    var tempStr = "";
    for (var i = 0; i < str.length; i++) {
        var hexStr = str.charCodeAt(i).toString(16);
        if (hexStr.length <= 2) {//按UTF-16进行编码		
            hexStr = "00" + hexStr;
        }
        tempStr += hexStr;
    }
    tempStr = "0x" + tempStr;
    return prev + "X-CUSTOM" + tempStr;
}

function HtmlEncode(sStr) {
    sStr = sStr.replace(/&/g, "&amp;");
    sStr = sStr.replace(/>/g, "&gt;");
    sStr = sStr.replace(/</g, "&lt;");
    sStr = sStr.replace(/"/g, "&quot;");
    sStr = sStr.replace(/'/g, "&#39;");
    return sStr;
}

function HtmlUnEncode(sStr) {
    sStr = sStr.replace(/&amp;/g, "&");
    sStr = sStr.replace(/&gt;/g, ">");
    sStr = sStr.replace(/&lt;/g, "<");
    sStr = sStr.replace(/&quot;/g, '"');
    sStr = sStr.replace(/&#39;/g, "'");
    return sStr;
}