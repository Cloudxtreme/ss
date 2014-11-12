#include <efnet.h>

int strip_vlan(char *pkg, int len)
{
    if(IF_VLAN(pkg))
    {
        /*
        int i = 12;
        int *p = (int *)(pkg + 12);
        while((len - i) > (sizeof(int) * 2))
        {
            *p = *(p + 1);
            p++;
            i += sizeof(int);
        }
        pkg = (char *)p;
        while(len - i > 1)
        {
            *pkg = *(pkg + 1);
            pkg++;
            i++;
        }
        */
        memmove(pkg + 12, pkg + 16, len - 16);
        return 1;
    }
    return 0;
}

int merge_vlan(char *pkg, int len, int vlan)
{
    if(!IF_VLAN(pkg))
    {
        memmove(pkg + 16, pkg + 12, len - 12);
        ETHTYPE(pkg) = PKT_TYPE_VL;
		VLAN(pkg) = htons(vlan);
		return 1;
    }
    return 0;
}
