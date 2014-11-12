package com.cdn.mode;
import java.text.DecimalFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.Statement;
import java.sql.ResultSet;
import com.cdn.ado.Flow;
import com.cdn.util.DBCon;
import com.cdn.ado.Flowsum;
import java.sql.Time;
public class Selflow {
	
	public List<Flow> selwebtop10(String type)
	{
		List<Flow> list= new ArrayList<Flow>();
		List<Flow> lt= new ArrayList<Flow>();
	    HashMap<String,Double> mp=new HashMap<String,Double>();		
		DBCon co=new DBCon();
		String zd="";
		String sql="select `hostname`";
		String table="";
		String end="";
		String start="";
		Date now=new Date();
		SimpleDateFormat fm = new SimpleDateFormat("yyyy-MM-dd");
		SimpleDateFormat xs = new SimpleDateFormat("HH:mm:ss");
		table=fm.format(now);
		end=xs.format(now);
		now.setMinutes(now.getMinutes()-10);
		start=xs.format(now);
		if(type.equals("out"))
		{
			zd="outrate";
			sql=sql+",`outrate`";
		}
		if(type.equals("in"))
		{
			zd="inrate";
			sql=sql+",`inrate`";
		}
		sql=sql+"  from `"+table+"`  where `time`>='"+start+"' and `time`<='"+end+"'";
		try{
		Connection con=co.getwebcon();
	    Statement st=con.createStatement();
	    ResultSet rs=st.executeQuery(sql);	
	     while(rs.next())
	     {
	    	String sh=chk(rs.getString("hostname"));
	   		  
	      	 if(mp.get(sh)==null)
	    	 {
	    		 mp.put(sh, rs.getDouble(zd));
	    	 }
	      	 else
	    	 {
	      		Double it=(Double)mp.get(sh);
	    		 mp.put(sh, new Double(it.doubleValue()+rs.getDouble(zd)));
	    	 }
	     }	
	     rs.close();
	     st.close();
	     con.close();	    
	     Set<String> set=mp.keySet();
		 Iterator<String> iter =set.iterator();
		 int li=0;
		 while(iter.hasNext())
		 {
		    	String si=(String)iter.next();
		    	Flow fl=new Flow();
		    	fl.setYm(si);
		    	fl.setFl(((Double)mp.get(si)).doubleValue());
                lt.add(li,fl);
                li++;
		  }
		 set.clear();
		 mp.clear();
	     lt=sort(lt);
	     DecimalFormat df=new DecimalFormat(".##");
	     for(int x=lt.size()-1;x>=lt.size()-10;x--)
	     {   
	    	 Flow ow=lt.get(x);
	    	 double ft=ow.getFl()/1024/1024*8;
	    	 String ing=df.format(ft);
	    	 Double db=new Double(ing);
	    	 ow.setFl(db.doubleValue());
	    	 list.add(ow);
	     }
	     lt.clear();
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return list;
	}
	public  String chk(String str)
	{   String st="";
		String t=str.substring(0,str.lastIndexOf("."));
		int b=t.lastIndexOf(".");
		if(b<0)
		{
			st=str;
		}
		else
		{
			st=str.substring(b+1);
		}
		return st;
	}
	public List<Flow> sort(List<Flow> lt)
	{  
		for(int i=0;i<lt.size();i++)
		{
			for(int j=0;j<lt.size()-i-1;j++)
			{
				if(lt.get(j).getFl()>lt.get(j+1).getFl())
				{
					Flow f=lt.get(j+1);
					Flow h=lt.get(j);
					lt.set(j, f);
					lt.set(j+1, h);
				}
			}
		}
		return lt;
	}
	
	public List<Flow> selfiletop10()
	{   
		List<Flow> list=new ArrayList<Flow>();
		try
		{    Date now=new Date();
		    SimpleDateFormat fm = new SimpleDateFormat("yyyy-MM-dd");
		    SimpleDateFormat xs = new SimpleDateFormat("HH:mm:ss");
			String table=fm.format(now);
		    String end=xs.format(now);
			now.setMinutes(now.getMinutes()-10);
			String start=xs.format(now);
			String sql="select sum(flow) as fl,user from `"+table+"` where time>='"+start+"' and time <='"+end+"' group by `user` order by sum(`flow`) desc LIMIT 10 OFFSET 0";
			DBCon cn=new DBCon();
			Connection con=cn.getfilecon();
			Statement st=con.createStatement();
			ResultSet rs=st.executeQuery(sql);
			 DecimalFormat df=new DecimalFormat(".##");
			while(rs.next())
			{
				Flow fl=new Flow();
				fl.setYm(rs.getString("user"));
				double ft=rs.getDouble("fl")/1024/1024/300*8;
		    	String ing=df.format(ft);
		    	Double db=new Double(ing);
				fl.setFl(db.doubleValue());
				list.add(fl);
			}
			rs.close();
			st.close();
			con.close();
		}
		catch(Exception e)
		{
			e.printStackTrace();
		}
		return list;
	}
	public List<Flowsum> selwebsumflow(String ststr,String edstr,String type)
	{
		List<Flowsum> list=new ArrayList<Flowsum>();
	   Connection con=null;
       try
       {  
    	   DBCon co=new DBCon();
    		if(type.equals("web"))
    		{
    			con=co.getwebcon();
    		}
    		else
    		{
    			con=co.getfilecon();
    		}
    		SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd");
    	    Date d1=format.parse(ststr);
    	    Date d2=format.parse(edstr);
    	    long len=(d2.getTime()-d1.getTime())/(24*60*60*1000);
    	    long timejg=0;
    	    if(len<=1)
    	    {
    	    	timejg=5;
    	    }
    	    else if(len>=2&&len<=7)
    	    {
    	    	timejg=30;
    	    }
    	    else 
    	    {
    	    	timejg=120;
    	    }
    	    String sql="";
    	    String date="";
    	    for(int i=0;i<=len;i++)
    	    {   
    	    	date=format.format(d2);
    	    	d2.setTime(d2.getTime()-24*60*60*1000);
    	    	list.addAll(selwebsumflowoneda(date,type,con,timejg));
    	    }
    	    con.close();
       }catch(Exception e)
       {
    	   e.printStackTrace();
       }
		return list;
	}
	
	public List<Flowsum> selwebsumflowoneda(String date,String type,Connection con,long timejg)
	{
		 List<Flowsum> list=new ArrayList<Flowsum>();
		 String sql="";
		 if(type.equals("web"))
 		{
 			sql="select sum(outrate)* 8 / 1000 / 1000,time,\""+date+"\" as `date`   from `"+date+"` group by `time`  order by `time` desc";
 		}
 		else
 		{
 			sql="select sum(flow)* 8 / 1000 / 1000/300,time ,\""+date+"\" as `date`  from `"+date+"` group by `time` order by `time` desc";
 		}
		 try
		 {
		 PreparedStatement ps=con.prepareStatement(sql);
		 ResultSet rs=ps.executeQuery();
		 long i=0;
         long c=timejg/5;
         Time t=null;
         SimpleDateFormat mat = new SimpleDateFormat("HH:mm:ss");
         DecimalFormat df=new DecimalFormat(".##");
         double max=0;
         while(rs.next())
         {   
         	if(max<rs.getDouble(1))
         	{
         		max=rs.getDouble(1);
         	}
         	if(i%c==0)
         	{
         	Flowsum fl=new Flowsum();
             t=rs.getTime("time");
             String ti=mat.format(t);
             String da=rs.getString("date");
             fl.setTime(da+" "+ti);
             fl.setNum(df.format(max));
         	list.add(fl);
         	max=0;
         	}
         	i++;
         }
         rs.close();
         ps.close();
		 }catch(Exception e)
		 {
			 e.printStackTrace();
		 }
		 return list;
	}
	public List<Flow> zhdshjtop10(String type,String ststr,String edstr)
	{
		List<Flow> list=new ArrayList<Flow>();
		List<Flow> lt=new ArrayList<Flow>();
		HashMap<String,Double> mp=new HashMap<String,Double>();		
		Connection con=null;
		DBCon db=new DBCon();
		try
		{
			if(type.equals("web"))
			{
			con=db.getwebcon();
			}
			else
			{
			con=db.getfilecon();	
			}
			SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd");
    	    Date d1=format.parse(ststr);
    	    Date d2=format.parse(edstr);
    	    long len=(d2.getTime()-d1.getTime())/(24*60*60*1000);
    	    String date="";
    	    for(int i=0;i<=len;i++)
    	    {   
    	    	date=format.format(d1);
    	    	d1.setTime(d1.getTime()+24*60*60*1000);
    	    	list.addAll(zhdshjtop10oned(type,date,con));
    	    }
    	    con.close();
    	    int ls=list.size();
    	    if(ls>0)
    	    {   String sh="";
    	    	for(int i=0;i<ls;i++)
    	    	{   
    	    		if(type.equals("web"))
    	    		{
    	    		sh=chk(list.get(i).getYm());
    	    		}
    	    		else
    	    		{
    	    			sh=	list.get(i).getYm();
    	    		}
    	    		if(mp.get(sh)==null)
    		    	 {
    		    		 mp.put(sh, list.get(i).getFl());
    		    	 }
    		      	 else
    		    	 {
    		      		Double it=mp.get(sh);
    		    		mp.put(sh, new Double(it.doubleValue()+list.get(i).getFl()));
    		    	 }
    	    	}
    	    }
    	 list.clear();
    	 Set<String> set=mp.keySet();
   		 Iterator<String> iter =set.iterator();
   		 int li=0;
   		 while(iter.hasNext())
   		 {
   		    	String si=(String)iter.next();
   		    	Flow fl=new Flow();
   		    	fl.setYm(si);
   		    	fl.setFl(((Double)mp.get(si)).doubleValue());
                list.add(li,fl);
                li++;
   		  }
   		list=sort(list);
	     DecimalFormat df=new DecimalFormat(".##");
	     for(int x=list.size()-1;x>=list.size()-10;x--)
	     {   
	    	 Flow ow=list.get(x);
	    	 double ft=0;
	    	 if(type.equals("web"))
	    	 {
	    		 ft=ow.getFl()/1024/1024/1024 ;
	    	 }
	    	 else
	    	 {
	    		 ft=ow.getFl()/1024/1024/1024 ; 
	    	 }
	    	 String ing=df.format(ft);
	    	 Double db1=new Double(ing);
	    	 ow.setFl(db1.doubleValue());
	    	 lt.add(ow);
	     }
	     list.clear();
	     set.clear();
	     mp.clear();
		}
		catch(Exception e)
		{
			e.printStackTrace();
		}
		return lt;
	}
	
	public List<Flow> zhdshjtop10oned(String type,String date,Connection con)
	{
		List<Flow> list=new ArrayList<Flow>();
		try
		{   String sql="";
		   if(type.equals("web"))
		   {
			sql="select `hostname`,sum(`inrate`) as fl from `"+date+"` group by `hostname`";
		   }
		   else
		   {
			  sql="select user,sum(flow) as fl from `"+date+"` group by `user`"; 
		   }
			PreparedStatement ps=con.prepareStatement(sql);
			ResultSet rs=ps.executeQuery();
			while(rs.next())
			{
				Flow fl=new Flow();
				fl.setYm(rs.getString(1));
				fl.setFl(rs.getDouble(2));
				list.add(fl);
			}
			rs.close();
			ps.close();
		}
		catch(Exception e)
		{
			e.printStackTrace();
		}
		return list;
	}
	
} 
