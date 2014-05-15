# Author Pei-Chen Tsai aka Hammer
# email : please use gitHub issue system
# To use:
import os
import sys
from subprocess import Popen
from subprocess import PIPE

global DB_FLT, DB_TAL
global EARY_STATUS, EARY_FILENAME
global STATUS_CLEAN, STATUS_MODIFY, STATUS_UNKNOWN
global FOI #file of interesting
global totalFiles
global SRV_PATH

DB_FLT, DB_TAL = range(2)
EARY_STATUS, EARY_FILENAME = range(2)
STATUS_UPDATE = 'U'
FOI = []
UOI_USR, UOI_MCNT, UOI_ACNT = range(3)
UOI = [] # [USER | MODIFY CNT | ADD CNT]

def DB(level, msg):
   if int(level) == int(DB_FLT):
      print msg

def getTotalFiles():
   DB(DB_TAL, "ENTER getTotalFiles")
   for line in sys.stdin:         
      DB(DB_TAL, line)
      eAry = line.split(' ')
      if eAry[EARY_STATUS] == STATUS_UPDATE:
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
   output = Popen(prepareWcPara(TYPE), stdout=PIPE, stderr=PIPE).communicate()[0]
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

def prepareFileQueryCMD(filePath):
   iOutput = []
   iOutput.append('cvs')
   iOutput.append('-d:'+SRV_PATH)   
   iOutput.append('log')
   iOutput.append(filePath)
   return iOutput

def prepareAllFileQueryCMD():
   iOutput = []
   iOutput.append('cvs')
   iOutput.append('-d:'+SRV_PATH)   
   iOutput.append('log')      
   return iOutput


def uniqueAddUOI_ForModify(USER):
   bFound = 0
   if len(UOI) != 0 :
      for entry in UOI:
         if entry[UOI_USR] == USER:
            entry[UOI_MCNT]+=1
            bFound = 1
            DB(DB_TAL, entry)
            break
      if bFound == 0:
         DB(DB_TAL, entry)
         UOI.append([USER,1,0])
   else :
      DB(DB_TAL, [USER,1,0])
      UOI.append([USER,1,0])

def uniqueAddUOI_ForAdd(USER):
   bFound = 0
   if len(UOI) != 0 :
      for entry in UOI:
         if entry[UOI_USR] == USER:
            entry[UOI_ACNT]+=1
            bFound = 1
            DB(DB_TAL, entry)
            break
      if bFound == 0:
         DB(DB_TAL, entry)
         UOI.append([USER,0,1])
   else :
      DB(DB_TAL, [USER,0,1])
      UOI.append([USER,0,1])


def travelFOI():
   DB(DB_TAL,"ENTER travelAllFiles")   
   #for filePath in FOI:
      #output = Popen(prepareFileQueryCMD(filePath), stdout=PIPE).communicate()[0]
      #print output
   output = Popen(prepareAllFileQueryCMD(), stdout=PIPE, stderr=PIPE).communicate()[0]
   retAry = output.split('\n')
   for line in retAry:      
      line = line.lstrip(' ')
      line = line.rstrip(' ')      
      entryAry = line.split(' ')
      if len(entryAry) > 1:                  
         if entryAry[0] == 'date:':               
            if len(entryAry) > 9 :
               #print entryAry[5] #commiter               
               uniqueAddUOI_ForModify(entryAry[5].rstrip(';'))               
            else:
               uniqueAddUOI_ForAdd(entryAry[5].rstrip(';'))               
         elif entryAry[0] == 'cvs':
            DB(DB_TAL,line) #eat redundant line
            print 'doomed'

   print "%d users found" %(len(UOI))
   print "===== User activity detail data ====="
   for user in UOI:
      totalCreateFileCnt = 0
      totalModifyFileCnt = 0
      print "%s:" %(user[UOI_USR]) 
      print "%d total create File found" %(user[UOI_ACNT])
      print "%d total modify File found" %(user[UOI_MCNT])

   DB(DB_TAL, "LEAVE travelAllFiles")


def verify():
   lState = 0
   if len(sys.argv) < 2:      
      print "You need to input server location at least"
      exit(1)
   global DB_FLT 
   global SRV_PATH
   SRV_PATH = sys.argv[1]
   if len(sys.argv) > 2 :
      DB_FLT = int(sys.argv[2])   

def main():   
   print "cvs -z3 -d:<server location> co -P <modulename> | python cvsPlus.py <server location> <DB>" 
   print "ctrl+d if you don't pipe in any thing"
   verify()
   print "===== OUTPUT START ====="
   getTotalFiles()   
   getTotal('LINE')
   getTotal('SIZE')   
   travelFOI()

if __name__ == '__main__':
   main()
