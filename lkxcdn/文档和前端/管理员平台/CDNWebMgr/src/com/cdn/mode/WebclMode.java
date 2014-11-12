package com.cdn.mode;
import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;
import com.cdn.util.DBCon;
import com.cdn.ado.Webcl;
public class WebclMode {
public List<Webcl> sel()
{
	List<Webcl> list=new ArrayList<Webcl>();
	try
	{
		DBCon db=new DBCon();
		Connection con=db.getcdninfoweb();
		Statement st=con.createStatement();
		String sql="select distinct a.user,a.`desc`,a.mydesc,b.domainname from user as a  LEFT JOIN user_hostname as b on a.user=b.owner where a.status='true'";
		ResultSet rs=st.executeQuery(sql);
		String str="";
		int i=-1;
		while(rs.next())
		{
			if(str.equals(rs.getString("user")))
			{
				list.get(i).getHostname().add(rs.getString("domainname"));
	

				
			}
			else
			{
				Webcl cl=new Webcl();
				cl.setOwner(rs.getString("user"));
	
				cl.setHostname(new ArrayList<String>());
				cl.getHostname().add(rs.getString("domainname"));
				cl.setClname(rs.getString("desc"));
				cl.setBz(rs.getString("mydesc"));
				list.add(cl);
				str=rs.getString("user");
				i++;
			}
		}
		rs.close();
		st.close();
		con.close();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return list;
}
}
