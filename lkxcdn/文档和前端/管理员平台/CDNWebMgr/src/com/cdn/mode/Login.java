package com.cdn.mode;
import java.util.List;
import com.cdn.ado.Admin;
import com.cdn.util.*;
import org.hibernate.Session;
import org.hibernate.Transaction;
import java.sql.*;
public class Login {
public String loginck(Admin ad)
{
	String bl="";
	try
	{
		if(ad!=null&&ad.getUser()!=""&&ad.getPass()!="")
		{ 
		 DBCon db=new DBCon();
         Connection con=db.getcon();
         String sql="select role as a from admin where user=? and pass=?";
         PreparedStatement ps=con.prepareStatement(sql);
         ps.setString(1, ad.getUser());
         ps.setString(2,MD5Str.EncoderByMd5(ad.getPass()));
         ResultSet rs= ps.executeQuery();
         if(rs.next())
         {
         bl=rs.getString(1);
         }
         rs.close();
         ps.close();
         con.close();
		}
	}
	catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
}
