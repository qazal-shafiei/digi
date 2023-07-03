<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$app = new \Slim\App;


$host = '127.0.0.1';
$db   = 'partners';
$user = 'qazal';
$pass = '123456';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

// Create a partner
$app->post('/partner', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $sql = "INSERT INTO partners (id, tradingName, ownerName, document, coverageArea, address) VALUES ('$data')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id'], $data['tradingName'], $data['ownerName'], $data['document'], json_encode($data['coverageArea']), json_encode($data['address'])]);
    return $response->withJson(['status' => 'success']);
});

// Load partner by id
$app->get('/partner/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $id = $args['id'];
    $sql = "SELECT * FROM partners WHERE id = '{$id}'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $partner = $stmt->fetch();
    $partner['coverageArea'] = json_decode($partner['coverageArea'], true);
    $partner['address'] = json_decode($partner['address'], true);
    return $response->withJson($partner);
});

// Search partner
$app->get('/partner/search', function (Request $request, Response $response) use ($pdo) {
    $queryParams = $request->getQueryParams();
    $point = "POINT({$queryParams['lat']} {$queryParams['long']})";
    $sql = "SELECT *, ST_AsGeoJSON(coverageArea) as coverageArea, ST_AsGeoJSON(address) as address FROM partners WHERE ST_Contains(coverageArea, GeomFromText(?)) ORDER BY ST_Distance(coverageArea, GeomFromText(?)) ASC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$point, $point]);
    $partner = $stmt->fetch();
    $partner['coverageArea'] = json_decode($partner['coverageArea'], true);
    $partner['address'] = json_decode($partner['address'], true);
    return $response->withJson($partner);
});


$app->run();
?>
