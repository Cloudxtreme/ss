package com.cdn.inter;

import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionInvocation;
import com.opensymphony.xwork2.interceptor.MethodFilterInterceptor;

public class CDNUserInter extends MethodFilterInterceptor {

	@Override
	protected String doIntercept(ActionInvocation invocation) throws Exception {
		// TODO Auto-generated method stub
		ActionContext ex=ActionContext.getContext();
		String role="";
		String result="";
		if(ex.getSession().get("role")!=null)
		{
			role=ex.getSession().get("role").toString();
		}
		role=role.trim();
		if(role.equals("ϵͳ����Ա")||role.equals("��ά��Ա"))
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
