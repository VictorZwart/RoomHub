<?php

/* Require composer autoloader */
require __DIR__ . '/vendor/autoload.php';

include 'models.php';

/* Connect to DB */
$db = connect_db('localhost', 'ddwt19_fp', 'ddwt19', 'ddwt19');

/* Create Router instance */
$router = new \Bramus\Router\Router();

// Add routes here

/* GET for getting an overview of all rooms */
$router->get('/rooms', function () use ($db) {
});

/* GET for reading specific rooms */
$router->get('/rooms/(\d+)', function ($id) use ($db) {

});
/* GET to view specific account */
$router->get('/account/(\d+)', function ($id) use ($db) {

});

/* DELETE for removing your own room */
$router->delete('/rooms/(\d+)/delete', function ($id) use ($db) {


});

/* POST for adding room*/
$router->post('/rooms', function () use ($db) {
});

/* PUT for Editing rooms */
$router->put('/rooms/(\d+)', function ($id) use ($db) {
    $_PUT = array();
    parse_str(file_get_contents('php://input'), $_PUT);

});
/* PUT for Editing account */
$router->put('/account/(\d+)', function ($id) use ($db) {
    $_PUT = array();
    parse_str(file_get_contents('php://input'), $_PUT);

});