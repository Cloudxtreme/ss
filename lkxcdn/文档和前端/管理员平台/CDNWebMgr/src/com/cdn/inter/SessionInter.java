package com.cdn.inter;

import javax.servlet.http.HttpServletRequest;

import org.apache.struts2.ServletActionContext;

import com.opensymphony.xwork2.Action;
import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionInvocation;
import com.opensymphony.xwork2.interceptor.AbstractInterceptor;

public class SessionInter extends AbstractInterceptor {
	
    private String str;
    
	public void setStr(String str) {
		this.str = str;
	}

	@Override
	public String intercept(ActionInvocation invocation) throws Exception {
		// TODO Auto-generated method stub
		ActionContext ex=ActionContext.getContext();	
		HttpServletRequest request = ServletActionContext.getRequest();
		String strip=request.getRemoteAddr();
		String ip="";
		String user="";
		String result="";
		if(ex.getSession().get("ip")!=null)
		{
			ip=ex.getSession().get("ip").toString();
		}
		if(ex.getSession().get("user")!=null)
		{
			user=ex.getSession().get("user").toString();
		}
		if(user.equals("")==false&&strip.equals(ip))
		{
			result=invocation.invoke();	
		}
		if(str==null||str=="")
		{
			ex.getSession().put("fl", "true");
		}
		return Action.LOGIN;
	}

}
