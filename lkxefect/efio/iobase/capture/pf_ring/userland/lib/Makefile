#
# Makefile for both static and dynamic PF_RING user libraries
#
#

#
# Installation directory
#
INSTDIR   = ${DESTDIR}//usr/local

#
# Search directories
#
INCLUDE   = -I../../kernel -I../../vPF_RING/guest/kernel -I../libpcap

#
# Zero ToolKit
#
ZERO_OBJS  = 

#
# DNA
#
DNA_OBJS  = 

#
# Virtual PF_RING
#
VIRTUAL_OBJS = 

#
# DAG
#
DAG_OBJS  = 

#
# Object files
#
OBJS      = pfring.o pfring_mod.o pfring_utils.o pfring_mod_usring.o pfring_hw_filtering.o ${ZERO_OBJS} ${DNA_OBJS} ${VIRTUAL_OBJS} ${DAG_OBJS}

#
# C compiler and flags
#
#
# CROSS_COMPILE=arm-mv5sft-linux-gnueabi-
#
CC        = ${CROSS_COMPILE}gcc
CFLAGS    =  -Wall -fPIC ${INCLUDE}     -D ENABLE_BPF   -O2 # -g
LDFLAGS   = -g -shared

SYSLIBS   = -lpthread 

# How to make an object file
%.o: %.c %.h Makefile
#	@echo "=*= making object $@ =*="
	${CC} ${CFLAGS} -c $< -o $@

#
# Main targets
#
STATICLIB  = libpfring.a
DYNAMICLIB = libpfring.so
TARGETS    = ${STATICLIB} ${DYNAMICLIB}
RING_H     = ../../kernel/linux/pf_ring.h

all: ${TARGETS}

${STATICLIB}: Makefile    ${OBJS} pfring.h ${RING_H}
	@echo "=*= making library $@ =*="
	ar rs $@ ${OBJS}
	ranlib $@

${DYNAMICLIB}: ${OBJS}    pfring.h ${RING_H} Makefile
	@echo "=*= making library $@ =*="
	${CC} ${LDFLAGS} ${OBJS} ${SYSLIBS} -o $@

extract_dna_lib:
	ar x 

extract_zero_lib:
	ar x 

extract_virtual_lib:
	ar x 

extract_userspace_ring_lib:
	ar x @USERSPACE_RING_LIB@

install: install-static install-shared

install-includes:
	mkdir -p ${INSTDIR}/include
	cp pfring.h ${INSTDIR}/include/

install-static: ${STATICLIB} install-includes
	mkdir -p ${INSTDIR}/lib
	cp ${STATICLIB} ${INSTDIR}/lib/

install-shared:	${DYNAMICLIB} install-includes
	mkdir -p ${INSTDIR}/lib
	cp ${DYNAMICLIB} ${INSTDIR}/lib/
	@if test "$(USER)" = "root"; then \
		ldconfig; \
	fi

clean:
	@rm -f ${TARGETS} *.o *~
