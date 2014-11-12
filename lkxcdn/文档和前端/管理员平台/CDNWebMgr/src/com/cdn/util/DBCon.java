package com.cdn.util;
import java.sql.*;
public class DBCon {
public  Connection getcon()
{
	Connection con=null;
	try{
		Class.forName("com.mysql.jdbc.Driver");
		con=DriverManager.getConnection("jdbc:mysql://127.0.0.1:3306/cdn?characterEncoding=gbk","root","172537");
	}catch(Exception e)
	{
		e.printStackTrace();
		
	}
	return con;
}

public  Connection getwebcon()
{
	Connection con=null;
	try{
		Class.forName("com.mysql.jdbc.Driver");
		con=DriverManager.getConnection("jdbc:mysql://webstats.cdn.efly.cc:3306/cdn_portrate_stats?characterEncoding=gbk","cdn","cdncdncdn");
	}catch(Exception e)
	{
		e.printStackTrace();
		
	}
	return con;
}

public  Connection getfilecon()
{
	Connection con=null;
	try{
		Class.forName("com.mysql.jdbc.Driver");
		con=DriverManager.getConnection("jdbc:mysql://183.61.80.176:3306/cdn_portrate_stats_new?characterEncoding=gbk","root","rjkj@rjkj");
	}catch(Exception e)
	{
		e.printStackTrace();
		
	}
	return con;
}

public Connection getfile()
{
	Connection con=null;
	try{
		Class.forName("com.mysql.jdbc.Driver");
		con=DriverManager.getConnection("jdbc:mysql://cdninfo.efly.cc:3306/cdn_file?characterEncoding=gbk","root","rjkj@rjkj");
	}catch(Exception e)
	{
		e.printStackTrace();
		
	}
	return con;
}

public Connection getcdninfoweb()
{
	Connection con=null;
	try{
		Class.forName("com.mysql.jdbc.Driver");
		con=DriverManager.getConnection("jdbc:mysql://cdninfo.efly.cc:3306/cdn_web?characterEncoding=gbk","root","rjkj@rjkj");
	}catch(Exception e)
	{
		e.printStackTrace();
		
	}
	return con;
}
}
