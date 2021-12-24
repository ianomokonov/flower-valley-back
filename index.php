<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization");

require_once 'vendor/autoload.php';
require_once './utils/database.php';
require_once './utils/token.php';
require_once './models/user.php';
require_once './models/product.php';

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as ResponseClass;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

$dataBase = new DataBase();
$user = new User($dataBase);
$product = new Product($dataBase);
$token = new Token();
$app = AppFactory::create();
$app->setBasePath(rtrim($_SERVER['PHP_SELF'], '/index.php'));

// Add error middleware
$app->addErrorMiddleware(true, true, true);
// Add routess
$app->post('/login', function (Request $request, Response $response) use ($dataBase) {

    $user = new User($dataBase);
    $requestData = $request->getParsedBody();
    try {
        $response->getBody()->write(json_encode($user->login($requestData['email'], $requestData['password'])));
        return $response;
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("message" => "Пользователь не найден")));
        return $response->withStatus(401);
    }
});

$app->post('/refresh-token', function (Request $request, Response $response) use ($dataBase) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->refreshToken($request->getParsedBody()['token'])));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->post('/delete-token', function (Request $request, Response $response) use ($dataBase) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->removeRefreshToken($request->getParsedBody()['token'])));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->get('/product/{id}', function (Request $request, Response $response) use ($product) {
    try {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $response->getBody()->write(json_encode($product->read($route->getArgument('id'))));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(404);
    }
});

$app->group('/', function (RouteCollectorProxy $group) use ($product) {

    $group->group('product', function (RouteCollectorProxy $productGroup) use ($product) {
        $productGroup->post('', function (Request $request, Response $response) use ($product) {
            try {
                $response->getBody()->write(json_encode($product->create($request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка создания продукта")));
                return $response->withStatus(401);
            }
        });

        $productGroup->post('/{id}', function (Request $request, Response $response) use ($product) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($product->update($route->getArgument('id'), $request->getParsedBody(), $_FILES)));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка редактирования продукта")));
                return $response->withStatus(401);
            }
        });

        $productGroup->delete('/{id}', function (Request $request, Response $response) use ($product) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($product->delete($route->getArgument('id'))));
                return $response;
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("e" => $e, "message" => "Ошибка удаления продукта")));
                return $response->withStatus(401);
            }
        });
    });
})->add(function (Request $request, RequestHandler $handler) use ($token, $user) {
    try {
        $jwt = explode(' ', $request->getHeader('Authorization')[0])[1];
        $userId = $token->decode($jwt)->data->id;
        $request = $request->withAttribute('userId', $userId);
        if ($user->checkAdmin($userId)) {
            return $handler->handle($request);
        }

        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => "Отказано в доступе к функционалу администратора")));
        return $response->withStatus(403);
    } catch (Exception $e) {
        $response = new ResponseClass();
        echo json_encode($e);
        $response->getBody()->write(json_encode($e));
        if ($e->getCode() && $e->getCode() != 0) {
            return $response->withStatus($e->getCode());
        }
        return $response->withStatus(500);
    }
});

$app->run();
