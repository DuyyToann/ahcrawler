## Last changes

### 2022

#### 2022-12-xx: v0.156

**Important note:**

The folder structure was changed! The files of the software were moved to public_html subfolder.
If you installed the software with git then you need to change the webserver config.

* 🟢 ADDED: docker development environment 
* 🟢 ADDED: docs folder with markdown help
* 🟢 ADDED: Textareas with placeholders: on double click the default value is editable
* ↗️ UPDATE: **software was moved to public_html subfolder**
* ↗️ UPDATE: search index - hide newest and oldest data it delta is below 1d
* ↗️ UPDATE: unify display: search index url is linked to details; showing url has same button like in resources
* ↗️ UPDATE: search index - detail page contains http response header
* ↗️ UPDATE: search index - word list on detail page is a toggled content element now
* ↗️ UPDATE: search result - contains html elememts for preview with marks
* ↗️ UPDATE: search result - full content data were removed: added a preview snippet
* ↗️ UPDATE: show clear message if a cookie file exists but is not readable (no permissions)
* ↗️ UPDATE: public search 
  * highlight searchterms in title, url, description, keywords, preview ...
  * added variable for hits per term or which term was not found
  * added meta information including timers and request data
  * added {{TOTALTIME}} (time in ms for search) and {{HITCOUNT}} (number of search results) in head template
  * added {{COUNTER}} in search result template for number of search result item
  *  output template: added html placeholders to show data with and without marked searchterm hits
    eg. {{TITLE}} and {{HTML_TITLE}}
  * placeholders from head can be used in search result template too
* ↗️ UPDATE: internal search - show times to prepare, database search, sorting results and total time

#### 2022-10-23: v0.155

* 💣 FIX: php error in setup on missing defaultUrls
* ↗️ UPDATE: deselect OK status buttons on linked resources only
* ↗️ UPDATE: backend search additionally can search in html response

#### 2022-10-18: v0.154

* 💣 FIX: http header of a failed page in detail page
* ↗️ UPDATE: css of default theme: move all colors into variables to simplify custom skins
* ↗️ UPDATE: link details show switch from secure https to unsecure http
* ↗️ UPDATE: resource details disable http ok links


#### 2022-09-06: v0.153

* 💣 FIX: add support of git repo outside approot
* 💣 FIX: php error on if a project was not crawled
* 💣 FIX: relative redirect urls
* ↗️ UPDATE: use session_write_close
* ↗️ UPDATE: skips by extension
* ↗️ UPDATE: reduce memory usage while crawling
* ↗️ UPDATE: log viewer shows filtered view as default
* ↗️ UPDATE: jquery 3.6.0 --> jquery 3.6.1
* ↗️ UPDATE: pure 2.0.6 --> pure 2.1.0
* ↗️ UPDATE: chartjs 3.6.0 --> chartjs 3.9.1

#### 2022-03-17: v0.152

* 💣 FIX: repeat search on page search terms - top N
* 💣 FIX: do not abort if creation of database index failes
* 🟢 ADDED: update detects a git instance and starts a git pull or download+unzip
