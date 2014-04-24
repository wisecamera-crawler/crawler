# Author Pei-Chen Tsai aka Hammer
# Ok, the line break position is impossible to 100% accurate currently, so just tune global parameter for your own purpose

from cStringIO import StringIO
from lxml import etree
from pprint import pprint
import urllib2
import sys

global DB_FLT
global TYPE_P
global TYPE_H
# Author Pei-Chen Tsai aka Hammer
# Ok, the line break position is impossible to 100% accurate currently, so just tune global parameter for your own purpose

from cStringIO import StringIO
from lxml import etree
from pprint import pprint
import urllib2
import sys

global DB_FLT
global TYPE_P
global TYPE_H
global TYPE_LI
global BREAK_CNT_P
global BREAK_CNT_H
global BREAK_CNT_LI

DB_FLT = 0
TYPE_P = 0
TYPE_H = 1
TYPE_LI = 2
BREAK_CNT_P = 124
BREAK_CNT_H = 96
BREAK_CNT_LI = 120

def DB(level,msg):
   if int(level) == int(DB_FLT) :
      print msg

def contentStrip(iList,iType):
   DB(1,'stripping all tag inside')
   stack = []   
   BREAK_CNT = BREAK_CNT_P #default policy
   
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
   DB(1, 'There are'+str(ret)+' <p> found')
   ret = len(contentStrip(iList,TYPE_P))
   DB(1, 'LEAVE li handler')
   return int(ret)

def handler_h(iList):   
   DB(1,'ENTER h handler')
   ret = len(iList)
   DB(1, 'There are'+str(ret)+' <h> found')
   ret = len(contentStrip(iList,TYPE_H))
   DB(1, 'LEAVE li handler')
   return int(ret)

def handler_li(iList):
   DB(1,'ENTER li handler')
   ret = len(iList)
   DB(1, 'There are'+str(ret)+' <li> found')
   ret = len(contentStrip(iList,TYPE_LI))
   DB(1, 'LEAVE li handler')
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
   DB(2, result)

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
   print "%d" %(lineSum)

def main():
   tPage = sys.argv[1]
   DB(1,'target is:'+tPage)
   htmlParser(tPage)

def verify():
   if len(sys.argv) < 2 or len(sys.argv) > 3 :
      print "command format is: "
      print sys.argv[0]+" <x/y> <DB>"
      print "--"
      print "you need to input x/y where is inside 'https://github.com/x/y/wiki'"
      print "DB is option"
      exit()
   if len(sys.argv) == 3 :
      global DB_FLT
      DB_FLT = int(sys.argv[2])

if __name__ == '__main__':
   verify()
   main()
