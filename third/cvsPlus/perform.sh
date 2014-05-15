#Auther Pei-Chen Tsai AKA Hammer or Nostrum
#please call this script for all Mercurial addtional information

echo start mining data...
if [ "$1" == "" ] || [ "$2" == "" ];then
tFolder="mx4j"
CMD="cvs -z3 -d:pserver:anonymous@mx4j.cvs.sourceforge.net:/cvsroot/mx4j co -P mx4j"
tSrv="pserver:anonymous@mx4j.cvs.sourceforge.net:/cvsroot/mx4j"
else
CMD="cvs -z3 -d:"$1" co -P "$2""
tFolder=$2
tSrv=$1
fi

mkdir "working"
cd working
$CMD | python ../cvsPlus.py $tSrv 
cd ..
rm -Rf working
echo end mining data...
