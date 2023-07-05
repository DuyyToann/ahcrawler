<?php
/**
 * ____________________________________________________________________________
 *          __    ______                    __             
 *   ____ _/ /_  / ____/________ __      __/ /__  _____    
 *  / __ `/ __ \/ /   / ___/ __ `/ | /| / / / _ \/ ___/    
 * / /_/ / / / / /___/ /  / /_/ /| |/ |/ / /  __/ /        
 * \__,_/_/ /_/\____/_/   \__,_/ |__/|__/_/\___/_/         
 * ____________________________________________________________________________ 
 * Free software and OpenSource * GNU GPL 3
 * DOCS https://www.axel-hahn.de/docs/ahcrawler/
 * 
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * 
 * ----------------------------------------------------------------------------
 * 
 * ANALYZER HTML
 * 
 * Analyzer for html documents. 
 * It fetches metainfos, css, scripts, images, links.
 * 
 * dependencies:
 * - fetchUrl function requires rollingcurl/RollingCurl.php
 * 
 * 
 * @example
 * - analyze an url:
 * <pre>require_once './analyzer.html.class.php';<br>
 * $oHtml=new analyzerHtml();<br>
 * $oHtml->fetchUrl("https://www.axel-hahn.de/kiste/");<br>
 * print_r($oHtml->getReport());</pre>
 * 
 * @example
 * - analyze already fetched html content with its url:
 * <pre>$sHtml=(...);<br>
 * $sUrl=(...);<br>
 * $oHtml=new analyzerHtml($sHtml, $sUrl);<br>
 * print_r($oHtml->getReport());</pre>
 * 
 * */
class analyzerHtml {

    private $_sHtml = false;
    private $_oDom = false;
    private $_sUrl = false;
    private $_sBaseHref = false;
    private $_sScheme = false;
    private $_sDomain = false;
    private $_aHttpResponseHeader = [];

    // ----------------------------------------------------------------------

    /**
     * new instance
     * @param string  $sHtmlcode  html document
     * @param string  $sUrl       url
     * @return type
     */
    public function __construct($sHtmlcode = false, $sUrl = false) {
        return $this->setHtml($sHtmlcode, $sUrl);
    }

    // ----------------------------------------------------------------------
    // 
    // GET DOCUMENT
    // 
    // ----------------------------------------------------------------------

    /**
     * get base url from url of the document or base href
     * @return boolean
     */
    private function _getBaseHref(){
        $this->_sBaseHref = false;
        $partsBaseHref=[];
        if (!$this->_oDom){
            return false;
        }
        $anchors=$this->_getNodesByTagAndAttribute('base', 'href');
        /*
        if (!count(($anchors))){
            return false;
        }
         * 
         */
        if (count(($anchors))){
            foreach ($anchors as $element) {
                $sBaseHref = $element->getAttribute('_href');
                break;
            }
            $partsBaseHref = parse_url($sBaseHref);
        }
        $partsUrl = parse_url($this->_sUrl);
        $this->_sBaseHref = ''
                
                // start with scheme
                .(isset($partsBaseHref['scheme']) ? $partsBaseHref['scheme'] : $partsUrl['scheme'])
                .'://'
                
                // add user + password ... if they exist "[user]:[pass]@"
                .(isset($partsBaseHref['user']) 
                    ? $partsBaseHref['user'].':'.$partsBaseHref['pass'].'@' 
                    : (isset($partsUrl['user'])
                        ? $partsUrl['user'].':'.$partsUrl['pass'].'@'
                        : ''
                    )
                 )
                 // 
                .(isset($partsBaseHref['host']) ? $partsBaseHref['host']     : (isset($partsUrl['host']) ? $partsUrl['host']     : ''))
                .(isset($partsBaseHref['port']) ? ':'.$partsBaseHref['port'] : (isset($partsUrl['port']) ? ':'.$partsUrl['port'] : ''))
                .(isset($partsBaseHref['path']) ? $partsBaseHref['path']     : (isset($partsUrl['path']) ? $partsUrl['path']     : ''))
            
            ;
        return $this->_sBaseHref;
    }
    
    /**
     * set a new html document with its sourcecode and url;
     * If you don't have the html source then use the method fetchUrl($sUrl)
     * 
     * @see fetchUrl($sUrl)
     * @param string  $sHtmlcode  html body
     * @param string  $sUrl       optional: url (used to generate full urls of internal links)
     * @return boolean
     */
    public function setHtml($sHtmlcode = false, $sUrl = false) {

        $this->_sHtml = $sHtmlcode;
        $this->_sUrl = $sUrl;
        $this->_sBaseHref = false;
        $this->_sScheme = false;
        $this->_sDomain = false;
        $this->_oDom = false;
        if ($sUrl) {
            $parts = parse_url($this->_sUrl);
            $this->_sScheme = $parts['scheme'];
            $this->_sDomain = $parts['host'];
        }

        if(!$sHtmlcode){
            $this->_oDom = false;
            return false;
        }
        // echo __METHOD__. " ... $sUrl". PHP_EOL ."html size: " . strlen($sHtmlcode) . ' byte'. PHP_EOL;
        $this->_oDom = new DOMDocument('1.0');
        @$this->_oDom->loadHTML($sHtmlcode);

        if (!$this->_oDom) {
            echo "WARNING: this is no valid DOM $sUrl \n<br>";
            return false;
        } else if ($sHtmlcode){
            $this->_getBaseHref();
        }
        return true;
    }

    /**
     * fetch a given url as html document
     * If you have the html source already then use the method setHtml(($sHtmlcode, $sUrl)
     * 
     * @see setHtml($sHtmlcode = false, $sUrl=false)
     * @param string  $sUrl  url
     * @return boolean¨
     */
    public function fetchUrl($sUrl) {
        require_once __DIR__ . './../vendor/rolling-curl/src/RollingCurl/RollingCurl.php';
        require_once __DIR__ . './../vendor/rolling-curl/src/RollingCurl/Request.php';
        $this->_aHttpResponseHeader = [];

        $rollingCurl = new \RollingCurl\RollingCurl();
        $self = $this;
        $rollingCurl->setOptions([
                    CURLOPT_FOLLOWLOCATION => true,
                    // CURLOPT_HEADER => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36',
                    CURLOPT_VERBOSE => false,
                    // TODO: this is unsafe .. better: let the user configure it
                    CURLOPT_SSL_VERIFYPEER => 0,
                ])
                ->get($sUrl)
                ->setCallback(function(\RollingCurl\Request $request) use ($self) {
                    $self->processResponse($request);
                })
                ->execute()
        ;
        /*
          $request = new \RollingCurl\Request($sUrl);
          $oRc = \RollingCurl\RollingCurl(array($this, 'processResponse'));
          $oRc->add($request);
          $oRc->execute();
         * 
         */
        return true;
    }

    /**
     * callback function for rollingCurl
     * @param object  $response  content
     * @param array   $info      http reposnse header
     */
    public function processResponse($response) {
        $url = $response->getUrl();
        $this->_aHttpResponseHeader = $response->getResponseInfo();
        $this->setHtml($response->getResponseText(), $url);        
    }

    // ---------------------------------------------------------------------- 
    // 
    // PARSE AND ANALYSE
    //   
    // ----------------------------------------------------------------------    

    /**
     * helper function: get html header from html document as string
     * 
     * @param string   $sItem   header element to extract
     * @return boolean|array
     */
    public function getHtmlHead() {
        preg_match("@<head[^>]*>(.*?)<\/head>@si", $this->_sHtml, $regs);
        if (!is_array($regs) || count($regs) < 2) {
            return false;
        }
        return $regs[1];
    }
    
    /**
     * helper function: get a value from html header
     * 
     * @param string   $sItem   header element to extract
     * @return boolean|array
     */
    private function _getMetaHead($sItem) {
        /*
        preg_match("@<head[^>]*>(.*?)<\/head>@si", $this->_sHtml, $regs);
        if (!is_array($regs) || count($regs) < 2) {
            return false;
        }
        $headdata = $regs[1];
         */
        $headdata = $this->getHtmlHead();

        $res = [];
        if ($headdata != "") {
            if (preg_match("@<" . $sItem . " *>(.*?)<\/" . $sItem . "*>@si", $this->_sHtml, $regs)) {
                return trim($regs[1]);
            }
            preg_match("/<meta +name *=[\"']?" . $sItem . "[\"']? *content=[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if (isset($res) && count($res) > 1) {
                return $res[1];
            }
        }
        return false;
    }
    
    /**
     * get url of canonical link from html -> head -> <link rel="canonical" ...>
     * @return string|boolean
     */
    public function getCanonicalUrl(){
        if (preg_match("/<link +rel *=[\"']?canonical[\"'].*\>?/i", $this->getHtmlHead(), $aFoundLinks)){
            preg_match("/<link.*href=[\"']?([^<>'\"]+)[\"']?/i", $aFoundLinks[0], $res);
            if (isset($res[1])){
                $aUrl= parse_url($res[1]);
                return ''
                    . $aUrl['scheme'].'://'
                    . (isset($aUrl['user']) ? $aUrl['user'].':'.$aUrl['passwort'].'@' : '' )
                    . $aUrl['host']
                    . (isset($aUrl['port']) ? ':'.$aUrl['port'] : '' )
                    . (isset($aUrl['path']) ? $aUrl['path'] : '/' )
                    . (isset($aUrl['query']) ? '?'.$aUrl['query'] : '' )
                    ;
            }
        }
        return false;
    }

    private function url_remove_dot_segments($path) {
        // multi-byte character explode
        $inSegs = preg_split('!/!u', $path);
        $outSegs = [];
        foreach ($inSegs as $seg) {
            if ($seg == '' || $seg == '.')
                continue;
            if ($seg == '..')
                array_pop($outSegs);
            else
                array_push($outSegs, $seg);
        }
        $outPath = implode('/', $outSegs);
        if ($path[0] == '/')
            $outPath = '/' . $outPath;
        // compare last multi-byte character against '/'
        if ($outPath != '/' 
            && (mb_strlen($path) - 1) == mb_strrpos($path, '/', 0, 'UTF-8'))
        {
            $outPath .= '/';
        }
        return $outPath;
    }

    /**
     * get an absolute url with fqdn from any relative or absolute url; 
     * it requres that the url of the document is known
     * @param string   $sRelUrl   url
     * @param string   $sDocUrl   optional: url of the current document to override
     * @return string
     */
    public function getFullUrl($sRelUrl, $sDocUrl=false) {

        $aPartsRelUrl = parse_url($sRelUrl);

        // if it has a scheme then it must be a full url
        if (isset($aPartsRelUrl['scheme'])) {
            return $sRelUrl;
        }
        if(!$sDocUrl){
            // $sDocUrl=$this->_sUrl;
            $sDocUrl=$this->_sBaseHref;
        }

        if (!$sDocUrl) {
            return false;
        }
        $aPartsDoc = parse_url($sDocUrl);

        // --- handle relative path values
        
        $sPath = isset($aPartsRelUrl['path']) ? $aPartsRelUrl['path'] : '';
        if ($sPath){
            if (strpos($sPath, '../') === 0) {
                $base = (($aPartsDoc['path'][strlen($aPartsDoc['path']) - 1]) !== '/') ? dirname($aPartsDoc['path']) . '/' : $aPartsDoc['path'];
                $sPath = $this->url_remove_dot_segments($base . $sPath);
            } else {
                $sPath = isset($aPartsRelUrl['path']) ? $aPartsRelUrl['path'] : $aPartsDoc['path'];
                if (strlen($sPath) && $sPath[0] != '/') {
                    $base = isset($aPartsDoc['path']) ? mb_strrchr($aPartsDoc['path'], '/', TRUE, 'UTF-8') : '';
                    $sPath = $base . '/' . $aPartsRelUrl['path'];
                }
                $sPath = str_replace('./', '', $sPath);
            }
            $aPartsRelUrl['path']=$sPath;
        }

        // --- create parts for target url
        
        $aPartsReturn = $aPartsRelUrl;
        foreach (['scheme', /* 'user', 'pass', */ 'host', 'port', /*'path' , 'query', 'fragment' */] as $sKey){
            if(!isset($aPartsReturn[$sKey]) && isset($aPartsDoc[$sKey])){
                $aPartsReturn[$sKey]=$aPartsDoc[$sKey];
            }
        }
        
        // no host an no path $sRelUrl --> then it is a relative path and we add it from current document
        if(!isset($aPartsRelUrl['host']) && !isset($aPartsRelUrl['path'])){
            $aPartsReturn['path']=$aPartsDoc['path'];
        }
        
        // take user + password ... only if scheme + host match
        if (
                $aPartsReturn['scheme']===$aPartsDoc['scheme']
                && $aPartsReturn['host']===$aPartsDoc['host']
                ){
            foreach (['user', 'pass'] as $sKey){
                if(!isset($aPartsReturn[$sKey]) && isset($aPartsDoc[$sKey])){
                    $aPartsReturn[$sKey]=$aPartsDoc[$sKey];
                }
            }
        }
        
        // --- create url from target target parts
        $sUrl = $aPartsReturn['scheme'] . '://'
                . (isset($aPartsReturn['user'])     ? $aPartsReturn['user'].':' : '')
                . (isset($aPartsReturn['pass'])     ? $aPartsReturn['pass'].'' : '')
                . ((isset($aPartsReturn['user']) || isset($aPartsReturn['pass'])) ? '@' : '')
                . $aPartsReturn['host']
                . (isset($aPartsReturn['port'])     ? ':'.$aPartsReturn['port'] : '')
                . (isset($aPartsReturn['path'])     ? $aPartsReturn['path'] : '')
                . (isset($aPartsReturn['query'])    ? '?'.$aPartsReturn['query'] : '')
                . (isset($aPartsReturn['fragment']) ? '#'.$aPartsReturn['fragment'] : '')
                ;
        // echo "DEBUG: " . $sRelUrl . " -- " . print_r($aPartsRelUrl, 1) ." NEW \n". print_r($aPartsReturn, 1) . "\n--> $sUrl\n\n";
        return $sUrl;
    }

    /**
     * Get a type url based on link value of href or src attribute.
     * Possible return values are: 
     *      local     url starts with a hash
     *      internal  url is on same domain
     *      external  url is http(s) to other domains
     *      mailto    email
     *      script    script (javascript, jscript or vbscript)
     *      other     other protocol than http(s)
     * 
     * @see getValidUrlTypes()
     * @param string  $sHref
     * @return string
     */
    public function getUrlType($sHref) {

        $sType = 'internal';
        $aParse = parse_url($sHref);
        // print_r($aParse);

        $scheme = isset($aParse['scheme']) ? $aParse['scheme'] : false;

        switch ($scheme) {
            case false:
            case 'http':
            case 'https':
                if (!isset($aParse['host'])) {
                    if (isset($aParse['path']) || isset($aParse['query'])) {
                        return 'internal';
                    }
                    if (isset($aParse['fragment'])) {
                        return 'local';
                    }
                }
                $sHost = isset($aParse['host']) ? $aParse['host'] : false;
                if ($sHost) {
                    if ($sHost !== $this->_sDomain) {
                        return 'external';
                    }
                    if ($scheme) {
                        return $scheme === $this->_sScheme ? 'internal' : 'external';
                    }
                }
                return 'internal';
                break;
            case 'mailto':
                return 'mailto';
                break;
            case 'javascript':
            case 'jscript':
            case 'vbscript':
                return 'script';
                break;
            case 'data':
                return 'local';
                break;
            case 'file':
                return 'file';
                break;
            /*
            case 'tel':
                return 'phone';
                break;
            */
            default:
                break;
        }
        return isset($aParse['scheme']) ? 'other::' . $aParse['scheme'] : 'other';
    }

    /**
     * return array of valid url types; by these types are grouped all
     * css-files, script-files, images, links
     * 
     * @see _getUrlType()
     * @return array
     */
    public function getValidUrlTypes() {
        return [
            'local',
            'internal',
            'external',
            'script',
            'mailto',
            'phone',
            'other',
        ];
    }

    /**
     * Parse mailto link and return items as array. Non existing fields in url
     * also do not exist in the returned array
     * Known fields are
     *     to       direct email receiver
     *     cc       carbon coby 
     *     bcc      blind carbon copy
     *     subject  subject
     *     body     body
     * examples
     *     mailto:axel@example.com
     *     mailto:axel@example.com?subject=23
     *     mailto:axel@example.com?subject=23&to=fred@example.com
     *     mailto:?subject=23&to=axel@example.com
     * 
     * @param string  $sHref
     * @return array
     */
    private function _parseEmail($sHref) {
        // echo "\n" . __FUNCTION__ .  "($sHref)\n\n";
        $aReturn = [];
        $aUrl = parse_url($sHref);
        if (!isset($aUrl['scheme']) || $aUrl['scheme'] != 'mailto') {
            return false;
        }

        // find to address in mailto:[email] or mailto:[email]?...
        if (isset($aUrl['path'])) {
            $aReturn['to'] = $aUrl['path'];
        } else {
            $aReturn['to'] = '';
        }

        // parse all behind '?'
        if (isset($aUrl['query'])) {
            parse_str($aUrl['query'], $aParams);
            foreach (['to', 'cc', 'bcc', 'subject', 'body'] as $sKey) {
                if (isset($aParams[$sKey]) && $aParams[$sKey]) {
                    $aReturn[$sKey] = (isset($aReturn[$sKey]) && $aReturn[$sKey]) ? $aReturn[$sKey] . ',' . $aParams[$sKey] : $aParams[$sKey];
                }
            }
        }
        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // read header information
    // ----------------------------------------------------------------------

    /**
     * get follow links information from html head area
     * it returns true if is allowed to follow the links in the document
     * @return string
     */
    public function getHttpResponseHeader($bAllowRedirect=false) {
        if ($this->_sUrl 
                && count($this->_aHttpResponseHeader) 
                && ($bAllowRedirect ? true : $this->_aHttpResponseHeader['url'] == $this->_sUrl)
        ) {
            return $this->_aHttpResponseHeader;
        }
        return false;
    }

    // ----------------------------------------------------------------------
    // read header information
    // ----------------------------------------------------------------------

    /**
     * helper function - return boolean if an initialized html page exists 
     * including its url - and that page contains a canonical url that 
     * differs to the url of the document.
     * @return boolean
     */
    public function hasOtherCanonicalUrl(){
        if($this->_sUrl && $this->_sHtml 
                && $this->getCanonicalUrl() 
                && $this->getCanonicalUrl()!==$this->_sUrl){
            return true;
        }
        return false;
    }
    /**
     * get follow links information from html head area and X-Robots-Tag
     * it returns true if is allowed to follow the links in the document
     * @return string
     */
    public function canFollowLinks() {
        $aHttpHeader=$this->getHttpResponseHeader();
        $sRobots =(isset($aHttpHeader['X-Robots-Tag']) ? $aHttpHeader['X-Robots-Tag'].',' : '')
         . $this->_getMetaHead('robots');
        if ($sRobots && (
                strpos($sRobots, 'none') === 0 || strpos($sRobots, 'none') > 0
                || strpos($sRobots, 'nofollow') === 0 || strpos($sRobots, 'nofollow') > 0)
        ) {
            return false;
        }
        if($this->hasOtherCanonicalUrl()){
            return false;
        }
        
        return true;
    }

    /**
     * get follow links information from html head area and X-Robots-Tag
     * it returns true if is allowed to follow the links in the document
     * @return string
     */
    public function canIndexContent() {
        $aHttpHeader=$this->getHttpResponseHeader();
        $sRobots =(isset($aHttpHeader['X-Robots-Tag']) ? $aHttpHeader['X-Robots-Tag'].',' : '')
         . $this->_getMetaHead('robots');
        if ($sRobots && (
                strpos($sRobots, 'none') === 0 || strpos($sRobots, 'none') > 0
                || strpos($sRobots, 'noindex') === 0 || strpos($sRobots, 'noindex') > 0)
        ) {
            return false;
        }
        if($this->hasOtherCanonicalUrl()){
            return false;
        }
        return true;
    }
    /**
     * TODO get words of a given string
     * @return string
     */
    public function getWords($sString) {
        $characterMap='À..ÿ'; // chars #192 .. #255
        $aWords=[];
        foreach(str_word_count(
                str_replace("'", '',$sString)
                ,2,$characterMap) as $sWord ){

            $sKey=$sWord;
            if(strlen($sKey)>2){
                if(!isset($aWords[$sKey])){
                    $aWords[$sKey]=1;
                } else {
                    $aWords[$sKey]++;
                }
            }
        }
        arsort($aWords);
        
        return $aWords;
    }

    /**
     * get description from html head area
     * @return string
     */
    public function getMetaDescription() {
        return $this->_getMetaHead('description');
    }

    /**
     * get keywords from html head area
     * @return string
     */
    public function getMetaKeywords() {
        return $this->_getMetaHead('keywords');
    }

    /**
     * get generator  from html head area
     * @return string
     */
    public function getMetaGenerator() {
        return $this->_getMetaHead('generator');
    }

    /**
     * get index content from html head area
     * @return string
     */
    public function getMetaIndex() {
        $sRobots = $this->_getMetaHead('robots');
        if ($sRobots && (strpos($sRobots, 'noindex') === 0 || strpos($sRobots, 'noindex') > 0)) {
            return false;
        }
        return true;
    }

    /**
     * get title from html head area
     * @return string
     */
    public function getMetaTitle() {
        return $this->_getMetaHead('title');
    }

    // ----------------------------------------------------------------------
    // read document
    // ----------------------------------------------------------------------

    /**
     * get all directly linked css files of this document
     * @return array
     */
    public function getCss() {
        $aReturn = [];
        if ($this->_oDom) {

            $anchors = $this->_oDom->getElementsByTagName('link');
            foreach ($anchors as $element) {
                if ($element->getAttribute('rel') == 'stylesheet' 
                    && $element->getAttribute('href')
                ) {
                    $sHref = $element->getAttribute('href');
                    $sType = $this->getUrlType($sHref);
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'css',
                        'href' => $element->getAttribute('href'),
                        '_url' => $this->getFullUrl($element->getAttribute('href')),
                        'media' => $element->getAttribute('media'),
                    ]);
                }
            }
        }
        return $aReturn;
    }

    /**
     * get all images from IMG tags in this document
     * @param string  $sFilterByType  return links of this type only
     * @return array
     */
    public function getImages($sFilterByType = false) {
        $aReturn = [];
        if ($this->_oDom) {

            $anchors = $this->_oDom->getElementsByTagName('img');
            foreach ($anchors as $element) {
                if ($element->getAttribute('src')) {
                    $sHref = $element->getAttribute('src');
                    $sType = $this->getUrlType($sHref);
                    if ($sFilterByType && $sFilterByType != $sType) {
                        continue;
                    }
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'image',
                        'href' => $sHref,
                        'alt' => $element->getAttribute('alt'),
                        'title' => $element->getAttribute('title'),
                        '_url' => $this->getFullUrl($sHref),
                        '_tag' => 'img',
                        '_line' => $element->getLineNo(),
                    ]);
                }
            }

            // thumbs in <link rel="image_src" href="[...]">
            $anchors = $this->_oDom->getElementsByTagName('link');
            foreach ($anchors as $element) {
                if ($element->getAttribute('rel')==='image_src' && $element->getAttribute('href')){
                    $sHref = $element->getAttribute('href');
                    $sType = $this->getUrlType($sHref);
                    if ($sFilterByType && $sFilterByType != $sType) {
                        continue;
                    }
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'image',
                        'href' => $sHref,
                        'alt' => '',
                        'title' => '',
                        '_url' => $this->getFullUrl($sHref),
                        '_tag' => 'link :: rel=image_src',
                        '_line' => $element->getLineNo(),
                    ]);
                }
            }
            
            // thumbs in <meta property="og:image" content="[...]" />
            $anchors = $this->_oDom->getElementsByTagName('meta');
            foreach ($anchors as $element) {
                if ($element->getAttribute('property')==='og:image' && $element->getAttribute('content')){
                    $sHref = $element->getAttribute('content');
                    $sType = $this->getUrlType($sHref);
                    if ($sFilterByType && $sFilterByType != $sType) {
                        continue;
                    }
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'image',
                        'href' => $sHref,
                        'alt' => '',
                        'title' => '',
                        '_url' => $this->getFullUrl($sHref),
                        '_tag' => 'meta :: property=og:image',
                        '_line' => $element->getLineNo(),
                    ]);
                }
            }
            // thumbs in <video poster="[...]" />
            $anchors = $this->_oDom->getElementsByTagName('video');
            foreach ($anchors as $element) {
                if ($element->getAttribute('poster')>''){
                    $sHref = $element->getAttribute('poster');
                    $sType = $this->getUrlType($sHref);
                    if ($sFilterByType && $sFilterByType != $sType) {
                        continue;
                    }
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'image',
                        'href' => $sHref,
                        'alt' => '',
                        'title' => '',
                        '_url' => $this->getFullUrl($sHref),
                        '_tag' => 'video poster',
                        '_line' => $element->getLineNo(),
                    ]);
                }
            }
        }
        return $aReturn;
    }

    /**
     * get all SOURCEs from AUDIO and VIDEO tags in this document
     * @param string  $sFilterByType  return links of this type only
     * @return array
     */
    public function getMedia($sFilterByType = false) {
        $aReturn = [];
        if ($this->_oDom) {

            foreach(['audio', 'video'] as $sMediaTag){
                $allAudio = $this->_oDom->getElementsByTagName($sMediaTag);
                // var_dump($allAudio);
                foreach ($allAudio as $media) {
                    $sources = $media->getElementsByTagName('source');
                    foreach ($sources as $element) {
                        if ($element->getAttribute('src')) {
                            $sHref = $element->getAttribute('src');
                            $sType = $this->getUrlType($sHref);
                            if ($sFilterByType && $sFilterByType != $sType) {
                                continue;
                            }
                            $this->_add2Array($aReturn[$sType], [
                                'ressourcetype' => 'media',
                                'href' => $sHref,
                                'type' => $element->getAttribute('type'),
                                '_url' => $this->getFullUrl($sHref),
                                '_tag' => 'img',
                                '_line' => $element->getLineNo(),
                            ]);
                        }
                    }
                }
            }
        }
        return $aReturn;
    }
    /**
     * get all scripts of this document
     * @param string  $sFilterByType  return links of this type only
     * @return array
     */
    public function getScripts($sFilterByType = false) {
        $aReturn = [];
        if ($this->_oDom) {

            $anchors = $this->_oDom->getElementsByTagName('script');
            foreach ($anchors as $element) {
                if ($element->getAttribute('src')) {
                    $sHref = $element->getAttribute('src');
                    $sType = $this->getUrlType($sHref);
                    if ($sFilterByType && $sFilterByType != $sType) {
                        continue;
                    }
                    $this->_add2Array($aReturn[$sType], [
                        'ressourcetype' => 'script',
                        'href' => $sHref,
                        '_url' => $this->getFullUrl($sHref),
                        '_tag' => 'script',
                        '_line' => $element->getLineNo(),
                    ]);
                }
            }
        }
        return $aReturn;
    }

    /**
     * helper: get links by tag name and attribute from already loaded html document
     * and return as array of dom objects
     * used in getLinks()
     * @see getLinks
     * @return array
     */
    public function _getNodesByTagAndAttribute($sTag, $sAttribute) {
        $aReturn = [];
        if ($this->_oDom) {

            $anchors = $this->_oDom->getElementsByTagName($sTag);
            if ($anchors) {
                foreach ($anchors as $element) {
                    $domAttribute = $this->_oDom->createAttribute('_attribute');
                    $domAttribute->value = $sAttribute;
                    $element->appendChild($domAttribute);

                    $sHref = $element->getAttribute($sAttribute);
                    $domAttribute2 = $this->_oDom->createAttribute('_href');
                    $domAttribute2->value = htmlentities($sHref);
                    $element->appendChild($domAttribute2);

                    $aReturn[] = $element;
                }
            }
        }
        return $aReturn;
    }

    /**
     * Get all links by type of this document. For internal links you get a
     * full url; email links will be parsed and yout get to, cc, subject, ... as
     * separate values.
     * 
     * Possible types are: 
     *      local     url starts with a hash
     *      internal  url is on same domain
     *      external  url is http(s) to other domains
     *      mailto    email
     *      script    script (javascript, jscript or vbscript)
     *      other     other protocol than http(s)
     * 
     * @param string  $sFilterByType  return links of this type only; deault: return all links
     * @param string  $bShowNofollow  skip links with attribute rel="nofollow"? default: return nofollow links
     * @return array
     */
    public function getLinks($sFilterByType = false, $bShowNofollow=true) {
        $aReturn = [];
        if ($this->_oDom) {

            $anchors = array_merge(
                    $this->_getNodesByTagAndAttribute('a', 'href'), 
                    $this->_getNodesByTagAndAttribute('area', 'href'), 
                    $this->_getNodesByTagAndAttribute('link', 'href'), 
                    $this->_getNodesByTagAndAttribute('frame', 'src'), 
                    $this->_getNodesByTagAndAttribute('iframe', 'src')
            );

            foreach ($anchors as $element) {
                $sHref = $element->getAttribute('_href');

                // skip link tag with rel="stylesheet" - these are fetched in getCss
                if ($element->getAttribute('rel') && (
                        $element->getAttribute('rel') === 'stylesheet'
                        || $element->getAttribute('rel') === 'dns-prefetch'
                        || $element->getAttribute('rel') === 'preconnect'
                        || $element->getAttribute('rel') === 'prefetch'
                        || $element->getAttribute('rel') === 'subresource'
                        || $element->getAttribute('rel') === 'pingback'
                        || $element->getAttribute('rel') === 'preload'
                        || (!$bShowNofollow && $element->getAttribute('rel') === 'nofollow')
                        )
                ) {
                    continue;
                }

                $sType = $this->getUrlType($sHref);
                if ($sFilterByType && $sFilterByType != $sType) {
                    continue;
                }

                $sUrl = false;
                if ($sType == "external") {
                    // $sUrl = preg_replace('/\#.*$/', '', $sHref);
                    $sUrl = preg_replace('/\#.*$/', '', $this->getFullUrl($sHref));
                }
                if ($sType == "internal") {
                    $sUrl = preg_replace('/\#.*$/', '', $this->getFullUrl($sHref));
                }
                $aLink = [
                    'ressourcetype' => 'link',
                    'href' => $sHref,
                    // 'element' => $element,
                    'label' => $element->nodeValue,
                    '_url' => $sHref,
                    '_tag' => $element->nodeName,
                    '_attribute' => $element->getAttribute('_attribute'),
                    '_line' => $element->getLineNo(),
                ];
                if ($sType == "mailto") {
                    $aEmail = $this->_parseEmail($sHref);
                    $sUrl = 'mailto:' . $aEmail['to'];

                    $aLink = array_merge($aLink, $aEmail);
                }

                foreach (['rel', 'title', 'class', 'id'] as $sAttr) {
                    if ($element->getAttribute($sAttr)) {
                        $aLink[$sAttr] = $element->getAttribute($sAttr);
                    }
                }
                if ($sUrl) {
                    $aLink['_url'] = $sUrl;
                }

                $this->_add2Array($aReturn[$sType], $aLink);
                // $aReturn[$sType][] = $aLink;
            }
        }
        return $aReturn;
    }

    private function _add2Array(&$aArray, $aNewItem) {
        $aReturn = $aArray;
        $bFound = false;

        if (!isset($aNewItem['_url'])) {
            // echo "missing _url: ". print_r($aNewItem, 1)."\n<br>";
            return false;
        }
        $aAddItem = $aNewItem;
        unset($aAddItem['ressourcetype']);
        unset($aAddItem['_url']);

        if (is_array($aArray) && count($aArray)) {
            for ($i = 0; $i < count($aArray); $i++) {
                if ($aArray[$i]['_url'] === $aNewItem['_url']) {
                    $aArray[$i]['refcount'] ++;
                    $aArray[$i]['items'][] = $aAddItem;
                    $bFound = true;
                    continue;
                }
            }
        }
        if (!$bFound) {
            $aBase = [
                'ressourcetype' => $aNewItem['ressourcetype'],
                '_url' => $aNewItem['_url'],
                'refcount' => 1,
                'items' => array($aAddItem),
            ];
            $aArray[] = $aBase;
        }
        return $aArray;
    }

    // ----------------------------------------------------------------------
    // 
    // REPORT
    // 
    // ----------------------------------------------------------------------

    /**
     * return array with full report
     * @return array
     */
    public function getReport() {
        /*
          $this->_getFullUrl('tel:0049123456');
          $this->_getFullUrl('/absolute/path/test.html');
          $this->_getFullUrl('https://otherhost/path2/test.html');
          $this->_getFullUrl('//myhost/path3/test.html');
          $this->_getFullUrl('rel/reldocument.html');
          $this->_getFullUrl('rel/reldocument.html#testhash');
          $this->_getFullUrl('rel/reldocument.html?a=3');
          $this->_getFullUrl('./rel/reldocument.html');
          $this->_getFullUrl('../../backward/and/forward.html');
          echo "<hr>";
         * 
         */
        return [
            'document' => [
                'url' => $this->_sUrl,
                'basehref' => $this->_sBaseHref,
                'size' => strlen($this->_sHtml),
                'isXml' => is_object($this->_oDom),
            ],
            'meta' => [
                'css' => $this->getCss(),
                'description' => $this->getMetaDescription(),
                'keywords' => $this->getMetaKeywords(),
                'follow' => $this->canFollowLinks(),
                'generator' => $this->getMetaGenerator(),
                'index' => $this->getMetaIndex(),
                'scripts' => $this->getScripts(),
                'title' => $this->getMetaTitle(),
            ],
            'body' => [
                'images' => $this->getImages(),
                'links' => $this->getLinks(),
                'media' => $this->getMedia(),
            ]
        ];
    }

}
