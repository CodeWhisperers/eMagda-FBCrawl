<?php
die;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/fb/src/Facebook/autoload.php';

$db = new PDO('mysql:host=localhost;dbname=fb;charset=utf8mb4', 'fb', 'emagdafb');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


$resp        = file_get_contents('https://graph.facebook.com/oauth/access_token?client_id=1061294253965900&client_secret=*****&%20grant_type=client_credentials');
$accessToken = str_replace('access_token=', '', $resp);
$fb          = new Facebook\Facebook([
    'app_id'                => '1061294253965900',
    'app_secret'            => '******',
    'default_graph_version' => 'v2.5',
]);


try {
    // Returns a `Facebook\FacebookResponse` object -> page
    $response = $fb->get('******/feed?limit=100', $accessToken);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}


$pagesEdge = $response->getGraphEdge();
$maxPages  = 50;
$pageCount = 0;
$ids       = [];

do {
    echo '<h1>Page #' . $pageCount . ':</h1>' . "\n\n";

    foreach ($pagesEdge as $page) {
        $post             = $page->asArray();
        $ids[$post['id']] = @$post['message'];
    }
    $pageCount++;
} while ($pageCount < $maxPages && $pagesEdge = $fb->next($pagesEdge));


foreach ($ids as $id => $message) {
    $stmt = $db->prepare("INSERT IGNORE INTO fb_posts(fbid,title) VALUES(:fbid,:title)");
    $stmt->execute(array(':fbid' => $id, ':title' => $message));
}

echo 'updated';