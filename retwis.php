<?php
require 'Predis/Autoloader.php';
Predis\Autoloader::register();

//It doesn't work on windows
// function getrand() {
//     $fd = fopen("/dev/urandom","r");
//     $data = fread($fd,16);
//     fclose($fd);
//     return md5($data);
// }
function getrand() {
    return md5(rand(),true);
}

function isLoggedIn() {
    global $User, $_COOKIE;

    if (isset($User)) return true;

    if (isset($_COOKIE['auth'])) {
        $r = redisLink();
        $authcookie = $_COOKIE['auth'];
        if ($userid = $r->hget("auths",$authcookie)) {
            if ($r->hget("user:$userid","auth") != $authcookie) return false;
            loadUserInfo($userid);
            return true;
        }
    }
    return false;
}

function loadUserInfo($userid) {
    global $User;

    $r = redisLink();
    $User['id'] = $userid;
    $User['username'] = $r->hget("user:$userid","username");
    return true;
}

function redisLink() {
    static $r = false;

    if ($r) return $r;
    $r = new Predis\Client();
    return $r;
}

# Access to GET/POST/COOKIE parameters the easy way
function g($param) {
    global $_GET, $_POST, $_COOKIE;

    if (isset($_COOKIE[$param])) return $_COOKIE[$param];
    if (isset($_POST[$param])) return $_POST[$param];
    if (isset($_GET[$param])) return $_GET[$param];
    return false;
}

function gt($param) {
    $val = g($param);
    if ($val === false) return false;
    return trim($val);
}

function utf8entities($s) {
    return htmlentities($s,ENT_COMPAT,'UTF-8');
}

function goback($msg) {
    include("header.php");
    echo('<div id ="error">'.utf8entities($msg).'<br>');
    echo('<a href="javascript:history.back()">Please return back and try again</a></div>');
    include("footer.php");
    exit;
}

function strElapsed($t) {
    $d = time()-$t;
    if ($d < 60) return "$d seconds";
    if ($d < 3600) {
        $m = (int)($d/60);
        return "$m minute".($m > 1 ? "s" : "");
    }
    if ($d < 3600*24) {
        $h = (int)($d/3600);
        return "$h hour".($h > 1 ? "s" : "");
    }
    $d = (int)($d/(3600*24));
    return "$d day".($d > 1 ? "s" : "");
}

function showPost($id) {
    $r = redisLink();
    $post = $r->hgetall("post:$id");
    if (empty($post)) return false;

    $userid = $post['user_id'];
    $username = $r->hget("user:$userid","username");
    $elapsed = strElapsed($post['time']);
    $userlink = "<a class=\"username\" href=\"profile.php?u=".urlencode($username)."\">".utf8entities($username)."</a>";

    echo('<div class="post">'.$userlink.' '.utf8entities($post['body'])."<br>");
    echo('<i>posted '.$elapsed.' ago via web</i></div>');
    return true;
}

function showUserPosts($userid,$start,$count,$self=false) {
    $r = redisLink();
    $key=$userid==-1?'timeline':($self?"posts_self:$userid":"posts:$userid");
    $posts = $r->lrange($key,$start,$start+$count);
    $c = 0;
    foreach($posts as $p) {
        if (showPost($p)) $c++;
        if ($c == $count) break;
    }
    return count($posts) == $count+1;
}

function showUserPostsWithPagination($username,$userid,$start,$count,$self=false) {
    global $_SERVER;
    $thispage = $_SERVER['PHP_SELF'];

    $navlink = "";
    $next = $start+10;
    $prev = $start-10;
    $nextlink = $prevlink = false;
    if ($prev < 0) $prev = 0;

    $u = $username ? "&u=".urlencode($username) : "";
    if (showUserPosts($userid,$start,$count,$self))
        $nextlink = "<a href=\"$thispage?start=$next".$u."\">Older posts &raquo;</a>";
    if ($start > 0) {
        $prevlink = "<a href=\"$thispage?start=$prev".$u."\">&laquo; Newer posts</a>".($nextlink ? " | " : "");
    }
    if ($nextlink || $prevlink)
        echo("<div class=\"rightlink\">$prevlink $nextlink</div>");
}

function showLastUsers() {
    $r = redisLink();
    $users = $r->zrevrange("users_by_time",0,9);
    showUsers($users);
}

function showUsers($userid_arr)
{
    $r = redisLink();
    echo("<table>");
    echo("<tr>");
    $i = 0;
    foreach($userid_arr as $userid) {
        if ($i!=0 && $i % 8 == 0) {
            echo("</tr>");
            echo("<tr>");
        }
        $username = $r->hget("user:$userid","username");
        echo("<td><a class=\"username\" href=\"profile.php?u=".urlencode($username)."\">".utf8entities($username)."</a></td>");
        $i ++;
    }
    echo("</tr>");
    echo("</table>");
}
//$arr1 and $arr2 are both descending list
function OrderedListUnion($arr1,$arr2)
{
    $rs = array();
    $len1=count($arr1);
    $len2=count($arr2);
    for($k=$i=$j=0;$k<1000&&$i<$len1&&$j<$len2;$k++)
    {
        if($arr1[$i]>=$arr2[$j])
            $rs[]=$arr1[$i++];
        else
            $rs[]=$arr2[$j++];
        $k++;
    }
    while($k++<1000&&$i<$len1)
        $rs[]=$arr1[$i++];
    while($k++<1000&&$j<$len2)
        $rs[]=$arr2[$j++];
    return $rs;
}
function OrderedListDiff($arr1,$arr2)
{
    $rs=array();
    $len1=count($arr1);
    for($i=0;$i<$len1;$i++)
    {
        if($arr1[$i]==current($arr2))
            next($arr2);
        else
            $rs[]=$arr1[$i];
    }
    return $rs;
}
?>
