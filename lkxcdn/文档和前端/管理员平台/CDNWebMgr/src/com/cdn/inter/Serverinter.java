package com.cdn.inter;

import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionInvocation;
import com.opensymphony.xwork2.interceptor.AbstractInterceptor;

public class Serverinter extends AbstractInterceptor {

	@Override
	public String intercept(ActionInvocation invocation) throws Exception {
		// TODO Auto-generated method stub
		ActionContext ex=ActionContext.getContext();
		String role="";
		String result="";
		if(ex.getSession().get("role")!=null)
		{
			role=ex.getSession().get("role").toString();
		}
		role=role.trim();
		if(role.equals("系统管理员")||role.equals("运维人员"))
		{
			result=invocation.invoke();	
		}
		else
		{
			result="qx";
		}
		return result;
	}

}
