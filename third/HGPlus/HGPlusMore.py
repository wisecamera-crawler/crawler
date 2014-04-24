# Author Pei-Chen Tsai aka Hammer
#email : please use gitHub issue system
import sys
import os
from subprocess import Popen
from subprocess import PIPE

global COI #user of changeset(commit)
#|changeset|user|
COI = []
global COI_REV, COI_USR
#clean user list no duplicate!
COI_REV, COI_USR = range(2)
USRARY = []

def prepareHgLogStatusPara(rev):
   iOutput = ['hg','status','--change']
   iOutput.append(rev)
   return iOutput

def getUserContributeDetail():
   print "===== User activity detail data ====="
   for user in USRARY:
      totalCreateFileCnt = 0
      totalDeletionFileCnt = 0
      totalModifyFileCnt = 0
      print "%s:" %(user)
      for e in COI:
         if e[COI_USR] == user:            
            cmd = prepareHgLogStatusPara(e[COI_REV])            
            output = Popen(cmd, stdout=PIPE).communicate()[0]         
            retAry = output.split('\n')
            for line in retAry:
               line = line.lstrip(' ')
               line = line.rstrip(' ')
               #print line
               fAry = line.split(' ')
               if fAry[0] == 'M':
                  totalModifyFileCnt+=1
               elif fAry[0] == 'A':
                  totalCreateFileCnt+=1
               elif fAry[0] == 'R':
                  totalDeletionFileCnt+=1
      print "%d total create File found" %(totalCreateFileCnt)
      print "%d total delete File found" %(totalDeletionFileCnt)
      print "%d total modify File found" %(totalModifyFileCnt)

#[]== algorithm could be better
def isUserInsideUSRARY(name):
   for usr in USRARY :
      if name == usr :
         return True
   return False

def prepareCleanUsrAry():
   for entry in COI:
     name = entry[COI_USR]
     if isUserInsideUSRARY(name) == False :        
        USRARY.append(name)
   print "%d users found" %(len(USRARY))

def parsePIPE():
   UOI = []
   state = 0
   for line in sys.stdin:
      line = line.replace(" ","")
      #print line
      lineAry = line.split(":")      
      #print lineAry[0]
      if lineAry[0] == 'changeset' :
         UOI = []
         UOI.append(lineAry[1])
      if lineAry[0] == 'user' :         
         UOI.append(lineAry[1].strip('\n'))
         COI.append(UOI)

   print '%d commit found' %(len(COI))
   #print COI

def main():
   #print "hg log | python HGPlus.py <DB>" 
   #print "ctrl+d if you don't pipe in any thing"
   #verify()
   #print "===== OUTPUT START ====="
   parsePIPE() #output total commits, also.
   prepareCleanUsrAry() #output total users, also.
   getUserContributeDetail()

if __name__ == "__main__":
   main()
