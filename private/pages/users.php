<?php
// ported from principia-web by chaziz -4/20/2023
namespace OpenSB;

// TODO: do not include fake "users" generated from activitypub profiles. -chaziz 6/7/2024

global $twig, $database;

use SquareBracket\UserData;

$page_number = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] : 1);
$limit = sprintf("%s,%s", (($page_number - 1) * 20), 20);

$queryData = $database->fetchArray(
    $database->query(
        "SELECT u.id, u.about, u.title, 
       (SELECT COUNT(*) FROM videos WHERE author = u.id) AS s_num, 
       (SELECT COUNT(*) FROM journals WHERE author = u.id) AS j_num 
        FROM users u 
        WHERE u.id NOT IN (SELECT userid FROM bans)
        ORDER BY u.lastview DESC LIMIT $limit"));

$countData = $database->result("SELECT COUNT(*) FROM users u WHERE u.id NOT IN (SELECT userid FROM bans)");

$usersData = [];
foreach ($queryData as $user)
{
    //$user_banned = $database->fetch("SELECT * FROM bans WHERE userid = ?", [$user["id"]]);
    //if (!$user_banned) {
    $userData = new UserData($database, $user["id"]);
    $usersData[] =
        [
            "id" => $user["id"],
            "info" => $userData->getUserArray(),
            "submissions" => $user["s_num"],
            "journals" => $user["j_num"],
            "about" => $user["about"],
        ];
    //}
}

$data = [
    'users' => $usersData,
    'count' => $countData,
];

echo $twig->render('users.twig', [
	'users' => $data,
    'page' => $page_number,
]);
