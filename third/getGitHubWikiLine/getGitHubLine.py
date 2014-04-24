# Author Pei-Chen Tsai aka Hammer
# Ok, the line break position is impossible to 100% accurate currently, so just tune global parameter for your own purpose

from cStringIO import StringIO
from lxml import etree
from pprint import pprint
import urllib2
import sys
import os

global DB_FLT, DB_NOR, DB_ARG, DB_VER #verbose print
global TYPE_P, TYPE_H, TYPE_LI
global BREAK_CNT_P, BREAK_CNT_H, BREAK_CNT_LI
global ARGUDB #arugment database
global ARGUDB_IDX_T, ARGUDB_IDX_P, ARGUDB_IDX_H, ARGUDB_IDX_LI
global tPage

DB_FLT, DB_NOR, DB_ARG, DB_VER    = range(4)
TYPE_P, TYPE_H, TYPE_LI, TYPE_PRE = range(4)
BREAK_CNT_P   = 124
BREAK_CNT_H   = 96
BREAK_CNT_LI  = 120
ARGUDB        = []
ARGUDB_IDX_T, ARGUDB_IDX_P, ARGUDB_IDX_H, ARGUDB_IDX_LI = range(4)
tPage         = ''

def DB(level,msg):
   if int(level) == int(DB_FLT) :
      print msg

def contentStrip(iList,iType):
   DB(1,'Doing break line...')
   stack = []   
   BREAK_CNT = BREAK_CNT_P #default policy

   if iType == TYPE_PRE:   #too easy
      for e in iList:
         if e.text is not None:            
            DB(1,e.text)
            DB(1,'Now spliting...')
            meList = e.text.split('\n')
            for eachLine in meList: 
               if len(eachLine) is not 0:
                  DB(1,eachLine)
                  stack.append(eachLine)
                  DB(1,str(len(stack)))
   else:      
      if iType == TYPE_P :
         BREAK_CNT = BREAK_CNT_P
      if iType == TYPE_H :
         BREAK_CNT = BREAK_CNT_H
      if iType == TYPE_LI :
         BREAK_CNT = BREAK_CNT_LI

      for e in iList:                  
         if e.text is not None :        
           strLength = len(e.text)        
           mod = strLength % BREAK_CNT
           times = strLength / BREAK_CNT
           index = 0
           sliceStart = 0
           sliceEnd = 0
           for index in range(times+1) :         
              if int(index) == int(times):                          
                 sliceStart = index*BREAK_CNT
                 sliceEnd   = (index)*BREAK_CNT+mod      
                 DB(1, e.text[sliceStart:sliceEnd])
                 stack.append(e.text)
                 DB(1,str(len(stack)))
              else :
                 sliceStart = index*BREAK_CNT
                 sliceEnd   = (index+1)*BREAK_CNT            
                 DB(1, e.text[sliceStart:sliceEnd])
                 stack.append(e.text)      
                 DB(1, str(len(stack)))
   return stack

def handler_p(iList):      
   DB(1,'ENTER p handler')
   ret = len(iList)
   DB(1, 'There are '+str(ret)+' <p> found')
   ret = len(contentStrip(iList,TYPE_P))
   DB(1, 'LEAVE p handler')
   return int(ret)

def handler_h(iList):   
   DB(1,'ENTER h handler')
   ret = len(iList)
   DB(1, 'There are '+str(ret)+' <h> found')
   ret = len(contentStrip(iList,TYPE_H))
   DB(1, 'LEAVE h handler')
   return int(ret)

def handler_li(iList):
   DB(1,'ENTER li handler')
   ret = len(iList)
   DB(1, 'There are '+str(ret)+' <li> found')
   ret = len(contentStrip(iList,TYPE_LI))
   DB(1, 'LEAVE li handler')
   return int(ret)

def handler_pre(iList):      
   DB(1,'ENTER pre handler')
   ret = len(iList)
   DB(1, 'There are '+str(ret)+' <pre> found')
   ret = len(contentStrip(iList,TYPE_PRE))
   DB(1, 'LEAVE pre handler')
   return int(ret)

def htmlParser(tPage):
   resp = urllib2.urlopen(tPage)
   if resp.code == 200 :
      data = resp.read()
      resp.close()
   elif resp.code == 404 :
      print "page do not exist"
      exit()
   else :
      print "can not open page"
      exit()
   parser = etree.HTMLParser()
   tree = etree.parse(StringIO(data), parser)
   etree.strip_tags(tree,'a')
   etree.strip_tags(tree,'strong')
   etree.strip_tags(tree,'img')
   etree.strip_tags(tree,'span')
   etree.strip_tags(tree,'code')
   
   result = etree.tostring(tree.getroot(), pretty_print=True, method="html")
   DB(DB_VER, result)

   targetURL = ""
   lineSum = 0
   myList = tree.xpath("//div[@class='markdown-body']/p")
   lineSum = handler_p(myList)
   myList = tree.xpath("//div[@class='markdown-body']/h3|\
                        //div[@class='markdown-body']/h2|\
                        //div[@class='markdown-body']/h3|\
                        //div[@class='markdown-body']/h4|\
                        //div[@class='markdown-body']/h5") #[]== this is pretty ugly, any better idea?
   lineSum += handler_h(myList)
   myList = tree.xpath("//div[@class='markdown-body']//li")
   lineSum += handler_li(myList)   
   myList = tree.xpath("//div[@class='markdown-body']//pre")
   lineSum += handler_pre(myList)   

   print "\ntotal lines is %d" %(lineSum)

def assignPageAndOverrideArgu():
   DB(DB_ARG,'ENTER overrideArgu')
   global tPage
   tPage = 'https://github.com/'+sys.argv[1];
   #DB(DB_ARG,'target is:'+tPage)
   for entry in ARGUDB:
      entryItemAry = entry.split(',')      
      #for entryItem in entryItemAry:
         #DB(DB_ARG,'item is:'+entryItem)
      #DB(DB_ARG,entryItemAry[0]+':'+tPage)
      if entryItemAry[ARGUDB_IDX_T] == tPage :
         DB(DB_ARG,'found target:'+tPage+' ,now override configuration')
         global BREAK_CNT_P, BREAK_CNT_H, BREAK_CNT_LI
         BREAK_CNT_P  = int(entryItemAry[ARGUDB_IDX_P])
         BREAK_CNT_H  = int(entryItemAry[ARGUDB_IDX_H])
         BREAK_CNT_LI = int(entryItemAry[ARGUDB_IDX_LI])

   DB(DB_ARG,'LEAVE overrideArgu')

def loadArgumentDb():
   DB(DB_ARG,'ENTER loadArgumentDb')
   if os.path.isfile('./argumentDataBase') is True:
      f = open('argumentDataBase','r')
      if f is not None:
         for line in f :
            if line != '\n' and line[0] != '#':
               line = line.rstrip('\n')
               global ARGUDB
               ARGUDB.append(line)
         f.close()
   else:
      DB(DB_ARG,'override file is not exist')
   DB(DB_ARG,'LEAVE loadArgumentDb')

def main():
   htmlParser(tPage)

def verify():
   if len(sys.argv) < 2 or len(sys.argv) > 3 :
      print "command format is: "
      print sys.argv[0]+" <target> <DB>"
      print "--"
      print "you need to input <target> where is inside 'https://github.com/<target>'"
      print "DB is option"      
      exit()
   if len(sys.argv) == 3 :
      global DB_FLT
      DB_FLT = int(sys.argv[2])

if __name__ == '__main__':
   verify()
   loadArgumentDb()
   assignPageAndOverrideArgu()
   main()
