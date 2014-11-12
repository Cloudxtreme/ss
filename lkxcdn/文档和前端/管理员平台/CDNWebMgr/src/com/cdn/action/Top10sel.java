package com.cdn.action;

import java.io.ByteArrayInputStream;
import java.io.InputStream;
import java.text.DecimalFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import com.cdn.mode.Selflow;
import com.opensymphony.xwork2.ActionSupport;
import com.cdn.ado.Flow;
import com.cdn.ado.Flowsum;
public class Top10sel extends ActionSupport {
	private String type;
	private String ym;
	private String fl;
	private String title;
	private String tip;
	private String dw;
	private String stdate;
	private String endate;
	private InputStream inputStream;
   
	public InputStream getInputStream() {
		return inputStream;
	}

	public void setInputStream(InputStream inputStream) {
		this.inputStream = inputStream;
	}

	public String getStdate() {
		return stdate;
	}

	public void setStdate(String stdate) {
		this.stdate = stdate;
	}

	public String getEndate() {
		return endate;
	}

	public void setEndate(String endate) {
		this.endate = endate;
	}

	public String getDw() {
		return dw;
	}

	public void setDw(String dw) {
		this.dw = dw;
	}

	public String getTip() {
		return tip;
	}

	public void setTip(String tip) {
		this.tip = tip;
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public String getType() {
		return type;
	}

	public void setType(String type) {
		this.type = type;
	}

	public String getYm() {
		return ym;
	}

	public void setYm(String ym) {
		this.ym = ym;
	}

	public String getFl() {
		return fl;
	}

	public void setFl(String fl) {
		this.fl = fl;
	}

	public String execute() throws Exception
	{   
		ym="'";
		fl="";
		dw="mbits/s";
		if(type.equals("out"))
		{
			title="网站流量TOP10";
			tip="流量";
		}
		else
		{
			title="网站流量TOP10";
			tip="流量";
		}
		Selflow f=new Selflow ();
		List<Flow> list=f.selwebtop10(type);
		if(list.size()>0)
		{
		for(int i=0;i<list.size();i++)
		{
			ym=ym+list.get(i).getYm()+"','";
			fl=fl+list.get(i).getFl()+",";
		}
		int a=ym.lastIndexOf(",'");
		int b=fl.lastIndexOf(",");
		ym=ym.substring(0, a);
		fl=fl.substring(0, b);
		}
		return SUCCESS;
	}
	
	public String file() throws Exception
	{   
		ym="'";
		fl="";
		title="文件流量TOP10";
		tip="流量";
		dw="mbps";
		Selflow f=new Selflow ();
		List<Flow> list=f.selfiletop10();
		if(list.size()>0)
		{
		for(int i=0;i<list.size();i++)
		{
			ym=ym+list.get(i).getYm()+"','";
			fl=fl+list.get(i).getFl()+",";
		}
		int a=ym.lastIndexOf(",'");
		int b=fl.lastIndexOf(",");
		ym=ym.substring(0, a);
		fl=fl.substring(0, b);
		}
		return SUCCESS;
	}
	public String selsumview()
	{  
		return "sumview";
	}
/*	public String selsum()
	{  
		Selflow f=new Selflow ();
		String str="false";
		if(type.equals("all")==false)
		{
		List<Flowsum> lt=f.selwebsumflow(stdate, endate, type);
	    int len=lt.size();
	    String date="[\"";
	    String num="[";
	    for(int i=len-1;i>=0;i--)
	    {
	    	Flowsum fl=lt.get(i);
	    	date=date+fl.getTime()+"\",\"";
	    	num=num+fl.getNum()+",";
	    }
        if(len>0)
        {
        	date=date.substring(0,date.lastIndexOf(",\""));
        	num=num.substring(0,num.lastIndexOf(","));
        	str=date+"]"+"****"+num+"]";
        }
		}
		else
		{
			List<Flowsum> lt=f.selwebsumflow(stdate, endate,"web");
			List<Flowsum> lt2=f.selwebsumflow(stdate, endate,"file");
			int len=lt.size();
			int c=lt2.size();
			if(len>c)
			{
				len=c;
			}
			 DecimalFormat df=new DecimalFormat(".##");
			for(int i=0;i<len;i++)
			{		
				double a1=Double.parseDouble(lt.get(i).getNum());
				double a2=Double.parseDouble(lt2.get(i).getNum());	
				double a3=a1+a2;
				lt.get(i).setNum(df.format(a3));
			}
		    String date="[\"";
		    String num="[";
		    for(int i=len-1;i>=0;i--)
		    {  
		    	Flowsum fl=lt.get(i);
		    	date=date+fl.getTime()+"\",\"";
		    	num=num+fl.getNum()+",";
		    }
	        if(len>0)
	        {    
	        	date=date.substring(0,date.lastIndexOf(",\""));
	        	num=num.substring(0,num.lastIndexOf(","));
	        	str=date+"]"+"****"+num+"]";
	        }
		}
		byte[] bytes = str.getBytes();
		inputStream=new ByteArrayInputStream(bytes);
		return "ini";
	} */
	public String selsum1() throws Exception
	{  
		Selflow f=new Selflow ();
		SimpleDateFormat fm = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
		 DecimalFormat df=new DecimalFormat(".##");
		String str="false";
		if(type.equals("all")==false)
		{
		List<Flowsum> lt=f.selwebsumflow(stdate, endate, type);
	    int len=lt.size();
	    String st="[";
	    double max=0;
	    String maxt="";
	    for(int i=len-1;i>=0;i--)
	    {
	    	Flowsum fl=lt.get(i);
	    	Date da=null;
			try {
				da = fm.parse(fl.getTime());
				da.setTime(	da.getTime()+8*1000*60*60);
			} catch (ParseException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
	    	st=st+"["+da.getTime()+","+fl.getNum()+"],";
	    	double a=Double.parseDouble(fl.getNum());
	    	if(max<a)
	    	{
	    		max=a;
	    		maxt=fl.getTime();
	    	}
	    }
        if(len>0)
        {

        	st=st.substring(0,st.lastIndexOf(","))+"]";
        	str=st+"****"+df.format(max)+"****"+maxt;
        }
		}
		else
		{
			List<Flowsum> lt=f.selwebsumflow(stdate, endate,"web");
			List<Flowsum> lt2=f.selwebsumflow(stdate, endate,"file");
			int len=lt.size();
			int c=lt2.size();
			if(len>c)
			{
				len=c;
			}
			double max=0;
			String maxt="";
			for(int i=0;i<len;i++)
			{		
				double a1=Double.parseDouble(lt.get(i).getNum());
				double a2=Double.parseDouble(lt2.get(i).getNum());	
				double a3=a1+a2;
				if(max<a3)
				{
					max=a3;
					maxt=lt.get(i).getTime();
				}
				lt.get(i).setNum(df.format(a3));
				
			}
			String st="[";
		    for(int i=len-1;i>=0;i--)
		    {
		    	Flowsum fl=lt.get(i);
		    	Date da=null;
				try {
					da = fm.parse(fl.getTime());
					da.setTime(	da.getTime()+8*1000*60*60);
				} catch (ParseException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				}
		    	st=st+"["+da.getTime()+","+fl.getNum()+"],";
		    }
	        if(len>0)
	        {

	        	st=st.substring(0,st.lastIndexOf(","))+"]";
	        	str=st+"****"+df.format(max)+"****"+maxt;
	        }
		}
		byte[] bytes = str.getBytes();
		inputStream=new ByteArrayInputStream(bytes);
		return "ini";
	}
	
	public String zhdview() throws Exception
	{  
		return "zhdview";
	}
	public String zhdcx() throws Exception
	{   
		Selflow fl=new Selflow();
		List<Flow> list=fl.zhdshjtop10(type, stdate, endate);
		String str="false";
		if(list.size()>0)
		{    int lt=list.size();
			 String ymm="['";
		     String fll="[";
			for(int i=0;i<lt;i++)
			{
				ymm=ymm+list.get(i).getYm()+"','";
				fll=fll+list.get(i).getFl()+",";
			}
			ymm=ymm.substring(0, ymm.lastIndexOf(",'"))+"]";
			fll=fll.substring(0, fll.lastIndexOf(","))+"]";
			str=ymm+"****"+fll;
		}
		byte[] bytes = str.getBytes();
		inputStream=new ByteArrayInputStream(bytes);
		return "ini";
	}
}
