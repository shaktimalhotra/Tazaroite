<?php
namespace common\libraries;


class PageRank
{
    public function googlePagerank($url)
    {
        $query = 'http://toolbarqueries.google.com/tbr?client=navclient-auto&ch='.self::checkHash(self::hashUrl($url)).'&features=Rank&q=info:'.$url.'&num=100&filter=0';
        $data = file_get_contents($query);
        $pos = strpos($data, "Rank_");
        if($pos === false) {
            $pagerank = "0";
            return $pagerank;
        } 
        else {
            $pagerank = substr($data, $pos + 9);
            return $pagerank;
        }
    }
    public function indexedPagesGoogle($url)
    {
        $query = 'http://www.google.com/search?hl=en&q=site%3A' . $url;
        $data = file_get_contents($query);
        if (preg_match('#about ([0-9\,]+) results#i', $data, $p)) {
            $hit = (int) str_replace(',', '', $p[1]);
            return $hit;
        }
        return 0;
    }
    public function indexedPagesBing($url)
    {
        $query = 'http://www.bing.com/search?mkt=en-US&q=site%3A' . $url;
        $data = file_get_contents($query);
        if (preg_match('#([0-9\,]+) results#i', $data, $p)) {
            return (int) str_replace(',', '', $p[1]);
        }
        return 0;
    }
    public function indexedPagesFacebook($url)
    {
        $addr = "http://api.facebook.com/restserver.php?method=links.getStats&urls=".$url;
        $page_source = file_get_contents($addr);
        $page = htmlentities($page_source);
        $like = "<like_count>";
        $like1 = "</like_count>";
        $lik = strpos($page,htmlentities($like));
        $lik1 = strpos($page,htmlentities($like1));
        $fullcount = strlen($page);
        $a = $fullcount - $lik1;
        $aaa = substr($page, $lik+18, -$a);
        $aaa1 = substr($page, 605, 610);
        if($aaa != 0) {
            return $aaa;
        }
        else {
            return 0;
        }
    }

    // TODO: Update twitter API as it is no longer active.
    public function indexedPagesTwitter($url)
    {
        //http://www.htpcbeginner.com/wp-admin/post.php?post=3196&action=edit&message=1#suggestion-4
        $api = @file_get_contents('http://cdn.api.twitter.com/1/urls/count.json?url='.$url);
        $count = json_decode($api);
        if(isset($count->count) && $count->count != '0') {
            return $count->count;
        }
        return 0;
    }
    public function linkedInShares($url)
    {
        $lowurl = strtolower($url);
        $replaceurl = str_replace(' ', '-', $lowurl);
        $urladd = 'http://www.linkedin.com/company/'.$replaceurl.'/index.php';
        $curl = curl_init($urladd);
        //don't fetch the actual page, you only want to check the connection is ok
        curl_setopt($curl, CURLOPT_NOBODY, true);
        //do request
        $result = curl_exec($curl);
        $ret = false;
        //if request did not fail
        if ($result !== false) {
            //if request was ok, check response code
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
            if ($statusCode == 200) $ret = true;   
        }
        curl_close($curl);
   
        return $ret;
    }
    public function googlePlusOnes($url)
    {
        if(function_exists('curl_version')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://clients6.google.com/rpc');
            curl_setopt($curl, CURLOPT_POST, 1 );
            curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            $curl_results = curl_exec( $curl );
            curl_close($curl);
            $json = json_decode($curl_results, true);
            $googlePlusOnesCount = intval($json[0]['result']['metadata']['globalCounts']['count']);
        }
        else {
            $content = file_get_contents('https://plusone.google.com/u/0/_/+1/fastbutton?url='.urlencode($url).'&count=true');
            $doc = new DOMdocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content);
            $doc->saveHTML();
            $num = $doc->getElementById('aggregateCount')->textContent;
            if($num) $googlePlusOnesCount = intval($num);
        }
        return (int)$googlePlusOnesCount;
    }
    public function indexedPagesYahoo($url)
    {
        $url = 'http://siteexplorer.search.yahoo.com/advsearch?p='.$url.'&bwm=p&bwmf=s&bwmo=d'; 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser. 
        curl_setopt($ch, CURLOPT_URL, $url); 
        $data = curl_exec($ch); 
        curl_close($ch); 
        preg_match('/of about \<strong\>(.*?) \<\/strong\>/si',$data,$r); 
        return ($r[1]) ? $r[1] : '0'; 
    }
    public function alexaRank($url)
    {
        $query ='http://data.alexa.com/data?cli=10&url=' . $url;
        $data = file_get_contents($query);
        $xml = @simplexml_load_string($data);
        if($xml->SD->POPULARITY['TEXT']) {
            return (string)$xml->SD->POPULARITY['TEXT'];
        }
        return 0;
    }

    // If $url is in active a warning is thrown    
    public static function checkUrl($url)
    {
        if ($url == '') return false;
        $file_headers = @get_headers($url);

        return ($file_headers[0] != 'HTTP/1.1 404 Not Found'); 
    }
    public static function curlCheckUrl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode >= 200 && $httpCode < 300;
    }
    public function strToNum($str, $check, $magic)
    {
        $int32Unit = 4294967296; // 2^32
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $check *= $magic;
            if ($check >= $int32Unit) {
                $check = ($check - $int32Unit * (int) ($check / $int32Unit));
                $check = ($check < -2147483648) ? ($check + $int32Unit) : $check;
            }
            $check += ord($str{$i});
        }
        return $check;
    }
    public static function hashUrl($string)
    {
        $check1 = self::strToNum($string, 0x1505, 0x21);
        $check2 = self::strToNum($string, 0, 0x1003F);
        $check1 >>= 2;
        $check1 = (($check1 >> 4) & 0x3FFFFC0) | ($check1 & 0x3F);
        $check1 = (($check1 >> 4) & 0x3FFC00) | ($check1 & 0x3FF);
        $check1 = (($check1 >> 4) & 0x3C000) | ($check1 & 0x3FFF);
        $t1 = (((($check1 & 0x3C0) << 4) | ($check1 & 0x3C)) << 2) | ($check2 & 0xF0F);
        $t2 = (((($check1 & 0xFFFFC000) << 4) | ($check1 & 0x3C00)) << 0xA) | ($check2 & 0xF0F0000);
        return ($t1 | $t2);
    }
    public static function checkHash($hashnum)
    {
        $checkByte = 0;
        $flag = 0;
        $hashStr = sprintf('%u', $hashnum);
        $length = strlen($hashStr);
        for ($i = $length - 1; $i >= 0; $i--) {
            $Re = $hashStr{$i};
            if (1 === ($flag % 2)) {
                $Re += $Re;
                $Re = (int)($Re / 10) + ($Re % 10);
            }
            $checkByte += $Re;
            $flag ++;
        }
        $checkByte %= 10;
        if (0 !== $checkByte) {
            $checkByte = 10 - $checkByte;
            if (1 === ($flag % 2)) {
                if (1 === ($checkByte % 2)) $checkByte += 9;
                $checkByte >>= 1;
            }
        }
        return '7'.$checkByte.$hashStr;
    }
}
