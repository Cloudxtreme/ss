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
				tip="�û�����֤ʧ��!";
			}else if(tip.equals("Parameter Error!"))
			{
				tip="��������!";
			}else if(tip.equals("Verify Fail!"))
			{
				tip="����Ա��֤ʧ��!";
			}else if(tip.equals("Purge Type Fail"))
			{
				tip="������֤ʧ��!";
			}else if(tip.equals("Failed!"))
			{
				tip="���ݿ�ִ��ʧ��!";
			}
			else if(tip.equals("None!"))
			{
				tip="û������ִ���е�����!";
			}
			else if (tip.equals("Owner or Id Error!"))
			{
				tip="ȱ�ٱ�Ҫ����!";
			}
		}
		else
		{
			tip="�������û���";
		}
		return SUCCESS;
	}
}
