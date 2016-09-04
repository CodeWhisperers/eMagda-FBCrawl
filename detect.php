<?php
die;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$db = new PDO('mysql:host=localhost;dbname=fb;charset=utf8', 'fb', 'emagdafb');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$id = 0;
if(@$_GET['id']) {
    $id = (int)$_GET['id'];
}

// get post id
$stmt = $db->query('select id, fbid, message from fb_comments where length(message) > 50 AND message like "%?%" AND message like "%minut%" AND id > '.$id.' ORDER BY id ASC  LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$words = [];

if(is_array($row) && !empty($row)) {
    $ch = curl_init();

    $sent = $row['message'];
    curl_setopt($ch, CURLOPT_URL, "http://nlptools.info.uaic.ro/WebNpChunkerRo/NpChunkerRoServlet");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "sent=" . $sent);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);

    curl_close($ch);
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($server_output, 'HTML-ENTITIES', 'UTF-8'));
    $finder = new DomXPath($doc);
    $classname="NP";
    $nodes = $finder->query("//div[@class=\"S\"]/span[@class=\"NP\"]");


    foreach ($nodes as $node) {
        $res = strip_tags($node->c14n());
        $words[] = trim(preg_replace('/\s+/', ' ', $res));
    }

    foreach($words as $w => $word) {
        if(count(explode(' ', $word)) < 2) unset($words[$w]);
    }


        if (!empty($words)) {
            foreach($words as $wo) {
                try {
                    $stmt = $db->prepare("INSERT INTO fb_phrase(fbid,phrase) VALUES(:fbid,:phrase)");
                    $stmt->execute(array(':fbid' => $row['fbid'], ':phrase' => $wo));
                    echo "inserted ... ".$wo."\n<br />";
                } catch(Exception $e) {}
            }
        }

    echo '<html><meta http-equiv="refresh" content="1; url=https://fb.cw.yield.ro/detect.php?id='.$row['id'].'" /></html>';
}