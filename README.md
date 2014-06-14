crawler
=======

### 使用前
* php, 並確定有安裝 php5-curl
* git, cvs, svn, mercurial 
* 安裝 GitStats (參考 http://gitstats.sourceforge.net/ , 請直接從source make, apt-get版本沒跟上)
* 匯入DB (注意resource中的DB file 為資料表，須先建立`nsc`資料庫)

### 主程式
`php main.php <project-id>`

* project-id DB中專案之ID

在使用前要先設定DB、路徑 (程式碼)
會去對該專案的網頁及repo做分析並寫入資料庫

### 網頁爬蟲測試 (目前不能使用)
`php testCrawler.php <url> [option]`

* url 該專案之首頁，不用care是哪個網站，程式會自動處理
* option 不輸入則分析所有指標，可以輸入IRDW分別代表Issue、Rank、Wiki、Download。
ex. `php testCrawler.php <url> I` 就是只分析issue

### VCS爬蟲測試 (目前不能使用)
`php testStat.php <type> <id> <url>`

* type repo之型態、包含Git SVN HG CVS (注意大小寫)
* id 在repo資料夾下的目錄 (對CVS沒用)，若該資料夾已存在，
會進行update，否則進行clone
* url repo URL

### TODO
Important
* CVS 分析 : 目前對CVS repo沒法解析url 無法進行分析

ERROR control
* DB error
* cannot get/resolve file

Other Issue
* WebUtiltiy.php => persistent connetction?  other issue
