# ------------------------------------------------------------------
# Build the wurblets
# @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a> 
# ------------------------------------------------------------------

WCBUILDDIR=../lib

.PHONY: build_wurblets
%.wrbl.php : %.wrbl ; wurbiler $(WCFLAGS) $< 

WSRCS=\
SimpleBeaner.wrbl\
SimpleDBRead.wrbl\
SimpleDBSave.wrbl\
MySQLCreateTable.wrbl\
HTMLForm.wrbl

PSRCS=\
AbstractAttributes.php\
AbstractDBRead.php\
AbstractModel.php\
AbstractMySQL.php\
AbstractHTMLForm.php\
IncludeAspect.php

WC=wurbiler
WCFLAGS=-d $(WCBUILDDIR)
WURBLETS=${WSRCS:.wrbl=.wrbl.php}

build_wurblets:	${WURBLETS}
	@echo "$(WC) done"
	@for i in $(PSRCS) ; do \
	  cp $$i $(WCBUILDDIR) ;\
	done
