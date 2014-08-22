crawler
=======

### 使用前
* php, 並確定有安裝 php5-curl
* git, cvs, svn, mercurial 
* 匯入DB (注意resource中的DB file 為資料表，須先建立`nsc`資料庫)

### 主程式
`php crawler.php <project-id>`

* project-id DB中專案之ID

在使用前要先設定DB、路徑 (程式碼)
會去對該專案的網頁及repo做分析並寫入資料庫

### 網頁爬蟲測試
`php testCrawler.php <url>`

* url 該專案之首頁，不用care是哪個網站，程式會自動處理

### VCS爬蟲測試 
`php testStat.php <url>`

* url 專案之首頁，會透過webcrawler解析repo url 

proxycheck
======

php proxyCheck.php > /dev/null &

啟動後會依序檢查Proxy Server狀態、專案排程表以及執行中的專案狀態php proxyCheck.php > /dev/null &

