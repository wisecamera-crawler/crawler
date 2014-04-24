# Author Pei-Chen Tsai aka Hammer
# email : please use gitHub issue system
# To use: please pipe "hg status --all | python HGPlus.py" as start
import os
import sys
from subprocess import Popen
from subprocess import PIPE

global DB_FLT, DB_TAL
global EARY_STATUS, EARY_FILENAME
global STATUS_CLEAN, STATUS_MODIFY, STATUS_UNKNOWN
global FOI #file of interesting
global totalFiles

DB_FLT, DB_TAL = range(2)
EARY_STATUS, EARY_FILENAME = range(2)
STATUS_CLEAN = 'C'
STATUS_MODIFY = 'M'
STATUS_UNKNOWN = '?'
FOI = []

def DB(level, msg):
   if int(level) == int(DB_FLT):
      print msg

def getTotalFiles():
   DB(DB_TAL, "ENTER getTotalFiles")
   for line in sys.stdin:         
      DB(DB_TAL, line)
      eAry = line.split(' ')
      if eAry[EARY_STATUS] == STATUS_CLEAN or eAry[EARY_STATUS] == STATUS_MODIFY:        
         FOI.append(eAry[EARY_FILENAME].rstrip('\n'))
   print "%d files found" % (len(FOI))   
   DB(DB_TAL, "LEAVE getTotalFiles")

def prepareWcPara(TYPE):
   iOutput = []
   iOutput.append('wc')
   if TYPE == 'LINE':
     iOutput.append('-l')   
   elif TYPE == 'SIZE':
     iOutput.append('-c')
   for filename in FOI:
     iOutput.append(filename)
   return iOutput

def getTotal(TYPE):
   DB(DB_TAL, "ENTER getTotal")
   output = Popen(prepareWcPara(TYPE), stdout=PIPE).communicate()[0]
   DB(DB_TAL, output)
   retAry = output.split('\n')
   for line in retAry:      
      line = line.lstrip(' ')
      line = line.rstrip(' ')      
      entryAry = line.split(' ')
      if len(entryAry) > 1:      
         if entryAry[1] == 'total':            
            if TYPE == 'LINE':
              print entryAry[0]+' lines found'
            elif TYPE == 'SIZE':
              print entryAry[0]+' bytes count'
   DB(DB_TAL, "LEAVE getTotal")

def dumpFileName():
   f = open('.FOIdump','w')
   for filename in FOI:
      f.write(filename)
   f.close()

def verify():
   lState = 0
   if os.path.isdir('.hg') == False:   
      print "command format is: "
      print "cd Mercurial versin control folder where .hg exists"   
      exit()
   global DB_FLT 
   if len(sys.argv) > 1 :
      DB_FLT = int(sys.argv[1])

def main():
   print "hg status --all | python HGPlus.py <DB>" 
   print "ctrl+d if you don't pipe in any thing"
   verify()
   print "===== OUTPUT START ====="
   getTotalFiles()   
   getTotal('LINE')
   getTotal('SIZE')   

if __name__ == '__main__':
   main()
