crawler
=======

### 主程式
`php main.php <project-id>`

* project-id DB中專案之ID

會去對該專案的網頁及repo做分析並寫入資料庫

沒事不要測 很恐怖(誤

### 網頁爬蟲測試
`php testCrawler.php <url> [option]`

* url 該專案之首頁，不用care是哪個網站，程式會自動處理
* option 不輸入則分析所有指標，可以輸入IRDW分別代表Issue、Rank、Wiki、Download。
ex. `php testCrawler.php <url> I` 就是只分析issue

### VCS爬蟲測試
`php main.php <type> <id> <url>`

* type repo之型態、包含Git SVN HG CVS (注意大小寫)
* id 在repo資料夾下的目錄 (對CVS沒用)，若該資料夾已存在，
會進行update，否則進行clone
* url repo URL

CVS G_G中

### TODO
Important
* GoogleCodeCrawler.php => getRepoUrl
* SourceForgeCrawler.php => getRepoUrl
* CVSStat.php => cmd String

In Code
* main.php://TODO stat factory
* webcrawler/githubcrawler/GitHubIssue.php:            //TODO : getHTMLContent need to be able for redirection
* webcrawler/SourceForgeCrawler.php:        //TODO  : modify to WebUtility
* webcrawler/SourceForgeCrawler.php:        //TODO : get repo url
* webcrawler/GoogleCodeCrawler.php:            //TODO update times
* /webcrawler/GoogleCodeCrawler.php:        //TODO:  get repo url
* repostat/CVSStat.php:        //TODO : gen cmd string
* repostat/CVSStat.php:        //TODO
* repostat/CVSStat.php:        //TODO deleted files

ERROR control
* DB error
* cannot get/resolve file

Other Issue
* WebUtiltiy.php => persistent connetction?  other issue
