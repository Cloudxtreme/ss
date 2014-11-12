package com.cdn.action;

import javax.servlet.http.HttpServletRequest;

import org.apache.struts2.ServletActionContext;

import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionSupport;
import com.cdn.mode.Login;
import com.cdn.ado.Admin;
public class LoginAction extends ActionSupport {
	private Admin ad;
	private String pass;
	private String user;
	private String error;
	public Admin getAd() {
		return ad;
	}

	public void setAd(Admin ad) {
		this.ad = ad;
	}
   
	public String getPass() {
		return pass;
	}

	public void setPass(String pass) {
		this.pass = pass;
	}

	public String getUser() {
		return user;
	}

	public void setUser(String user) {
		this.user = user;
	}

	public String execute() throws Exception
	{   
		ActionContext ex=ActionContext.getContext();
		String user="";
		String pass="";
		if(ex.getSession().get("user")!=null)
		{
			user=ex.getSession().get("user").toString();
		}
		if(user!="")
		{
			return "main";
		}
		return SUCCESS;
	}
	
	public String getError() {
		return error;
	}

	public void setError(String error) {
		this.error = error;
	}

	public String ck() throws Exception
	{   
		String str=SUCCESS;
		Admin aa=new Admin();
		aa.setUser(user);
		aa.setPass(pass);
		Login lg=new Login();
		String role="";
		role=lg.loginck(aa);
		if(role=="")
		{
			setError("µ«¬º ß∞‹”√ªß√˚ªÚ√‹¬Î¥ÌŒÛ£°");
			return str;
		}
		else
		{  
			ActionContext ex=ActionContext.getContext();
			HttpServletRequest request = ServletActionContext.getRequest();
			String strip=request.getRemoteAddr();
			ex.getSession().put("user", user);
			ex.getSession().put("role", role);
			ex.getSession().put("ip", strip);
			if(ex.getSession().get("fl")!=null)
			{
				if(ex.getSession().get("fl").toString().equals("true"))
				{   
					ex.getSession().remove("fl");
					return "center";
				}
			}
		    return "main";
		}
	}
	public String logout() throws Exception
	{
		ActionContext ex=ActionContext.getContext();
		ex.getSession().remove("user");
		ex.getSession().remove("role");
		ex.getSession().remove("fl");
		ex.getSession().remove("ip");
		return "login";
	}
}
