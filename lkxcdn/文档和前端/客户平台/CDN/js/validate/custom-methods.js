jQuery.validator.addMethod("letterswithbasicpunc", function(value, element) {
	return this.optional(element) || /^[a-z-.,()'\"\s]+$/i.test(value);
}, jQuery.validator.messages.letterswithbasicpunc);

jQuery.validator.addMethod("letteronly", function(value, element) {
	return this.optional(element) || /^[a-z]+$/i.test(value);
}, jQuery.validator.messages.letteronly );

jQuery.validator.addMethod("letterdigit", function(value, element) {
	return this.optional(element) || /^[a-z0-9]+$/i.test(value)|| /^[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+(;[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+)*$/.test(value);
}, jQuery.validator.messages.letterdigit );

jQuery.validator.addMethod("letterdigitdash", function(value, element) {
	return this.optional(element) || /^[a-z0-9_-]+$/i.test(value)|| /^[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+(;[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+)*$/.test(value);
}, jQuery.validator.messages.letterdigit );

jQuery.validator.addMethod("nowhitespace", function(value, element) {
	return this.optional(element) || /^\S+$/i.test(value);
}, jQuery.validator.messages.nospace);


jQuery.validator.addMethod("time24", function(value, element) {
	return this.optional(element) || /^([01]\d|2[0-3])(:[0-5]\d){0,2}$/.test(value);
}, jQuery.validator.messages.time24);

jQuery.validator.addMethod("time12", function(value, element) {
	return this.optional(element) || /^((0?[1-9]|1[012])(:[0-5]\d){0,2}(\ [AP]M))$/i.test(value);
}, jQuery.validator.messages.time12);

jQuery.validator.addMethod("ipv4", function(value, element) {
    return this.optional(element) || /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/i.test(value);
},jQuery.validator.messages.ipv4);

jQuery.validator.addMethod("ipv6", function(value, element) {
    return this.optional(element) || /^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i.test(value);
}, jQuery.validator.messages.ipv6);

jQuery.validator.addMethod("qq", function(value, element) {
	return this.optional(element) || /^[1-9]\d{4,}$/.test(value);
}, jQuery.validator.messages.qq);

jQuery.validator.addMethod("phoneCN", function(value, element) {
	return this.optional(element) || /^((\+86)|(86))?(\d{4}|\d{3})-(\d{7,8})$/.test(value);
}, jQuery.validator.messages.phoneCN);

jQuery.validator.addMethod("mobileCN", function(value, element) {
	return this.optional(element) ||  /^((\+86)|(86))?(1[358]{1})\d{9}$/.test(value);
}, jQuery.validator.messages.mobileCN);

jQuery.validator.addMethod("strongPwd", function(value, element) { 
	  var i = 0;
	  var flag = false;
	  if (! /\s+/.test(value) && ! /[^\x00-\xff]+/.test(value))
	  {
		  // 密码不能包含空白或双字节符号(中文)
		  
		  if (/\d+/.test(value)) { // 包含数字
			  i++;
		  }
		  if (/[a-z]+/.test(value)) { // 包含小写字母
			  i++;
		  }
		  if (/[A-Z]+/.test(value)) { // 包含大写字母
			  i++;
		  }
		  if (/[\`\~\!\@\#\$\%\^&\*\(\)\-\_\=\+\{\}\[\]\|\\\;\:\'\"\,\.\<\>\/\?]+/.test(value)) {
			  // 包含特殊符号
			  i++;
		  }
		  if (i >= 3) {
			  // 密码自少包含上面四者中的三个
			  flag = true;
		  }
	  }
	  return this.optional(element) || flag; 
}, jQuery.validator.messages.strongPwd);

jQuery.validator.addMethod("moreEmail", function(value, element) { 
	  return this.optional(element) || /^[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+(;[a-zA-Z0-9_\.-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+)*$/.test(value); 
}, jQuery.validator.messages.moreEmail);
