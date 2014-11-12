package com.cdn.action;

import com.cdn.mode.Htp;
import com.opensymphony.xwork2.ActionSupport;

public class TaskAction extends ActionSupport {
	private String tip;
	private String owner;
	
	public String getTip() {
		return tip;
	}
	public void setTip(String tip) {
		this.tip = tip;
	}
	public String getOwner() {
		return owner;
	}
	public void setOwner(String owner) {
		this.owner = owner;
	}
	public String execute() throws Exception
	{   
		
		return SUCCESS;
	}
	public String tj() throws Exception
	{   
		if(owner!=null&&owner.equals("")==false)
		{
			Htp ht=new Htp();
			tip=ht.get(owner);
			if(tip.equals("Owner Error!"))
			{
				tip="用户名验证失败!";
			}else if(tip.equals("Parameter Error!"))
			{
				tip="参数错误!";
			}else if(tip.equals("Verify Fail!"))
			{
				tip="管理员验证失败!";
			}else if(tip.equals("Purge Type Fail"))
			{
				tip="类型验证失败!";
			}else if(tip.equals("Failed!"))
			{
				tip="数据库执行失败!";
			}
			else if(tip.equals("None!"))
			{
				tip="没有正在执行中的任务!";
			}
			else if (tip.equals("Owner or Id Error!"))
			{
				tip="缺少必要参数!";
			}
		}
		else
		{
			tip="请输入用户名";
		}
		return SUCCESS;
	}
}
