package com.cdn.mode;
import java.util.List;

import com.cdn.ado.Webcl;
public class Test {

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		// TODO Auto-generated method stub
   WebclMode md=new WebclMode();
   List<Webcl> l=md.sel();
   for(int i=0;i<l.size();i++)
   {
	   Webcl cl=l.get(i);
	   for(int j=0;j<cl.getHostname().size();j++)
	   {
		   System.out.println(cl.getOwner()+" "+cl.getClname()+"  "+cl.getBz()+" "+cl.getHostname().get(j));
	   }
   }
	}

}
