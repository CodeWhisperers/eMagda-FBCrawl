<?php
die;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/fb/src/Facebook/autoload.php';

$db = new PDO('mysql:host=localhost;dbname=fb;charset=utf8mb4', 'fb', 'emagdafb');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


$resp        = file_get_contents('https://graph.facebook.com/oauth/access_token?client_id=1061294253965900&client_secret=******&%20grant_type=client_credentials');
$accessToken = str_replace('access_token=', '', $resp);
$fb          = new Facebook\Facebook([
    'app_id'                => '1061294253965900',
    'app_secret'            => '******',
    'default_graph_version' => 'v2.5',
]);

if(!$_GET['id']) {
    $id = 1;
} else {
    $id = (int)$_GET['id'];
}

echo 'ID: '.$id;

// get post id
$stmt = $db->query('SELECT * FROM fb_posts WHERE id='.$id);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(is_array($rows) && !empty($rows)) {
    foreach ($rows as $row) {

        try {
            $response = $fb->get($row['fbid'] . '/comments?limit=100', $accessToken);
            $res      = $response->getDecodedBody();
            if (is_array($res['data'])) {
                foreach ($res['data'] as $com) {
                    $fbpost    = $row['fbid'];
                    $fbid      = $com['id'];
                    $from_name = $com['from']['name'];
                    $from_id   = $com['from']['id'];
                    $message   = $com['message'];
                    $stmt      = $db->prepare("INSERT IGNORE INTO fb_comments(fbpost,fbid,from_name,from_id,message) VALUES(:fbpost,:fbid,:from_name,:from_id,:message)");
                    $stmt->execute(array(':fbpost' => $fbpost, ':fbid' => $fbid, ':from_name' => $from_name, ':from_id' => $from_id, ':message' => $message));
                }
            }
        } catch(Exception $e) {}
    }
    echo '<html><meta http-equiv="refresh" content="0; url=https://fb.cw.yield.ro/comments.php?id='.++$id.'" /></html>';
} else {
    echo 'updated';
}