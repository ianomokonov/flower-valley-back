<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization");

require_once 'vendor/autoload.php';
require_once './utils/database.php';
require_once './utils/token.php';
require_once './models/user.php';
require_once './models/block.php';
require_once './models/script.php';

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as ResponseClass;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

$dataBase = new DataBase();
$token = new Token();
$block = new Block($dataBase);
$script = new Script($dataBase);
$user = new User($dataBase);
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

$app->post('/sign-up', function (Request $request, Response $response) use ($dataBase) {
    $user = new User($dataBase);
    try {
        $response->getBody()->write(json_encode($user->create((object) $request->getParsedBody())));
        return $response;
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("message" => "Пользователь уже существует")));
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
        $response->getBody()->write(json_encode(array("error" => $e, "message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->post('/delete-token', function (Request $request, Response $response) use ($dataBase, $token) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->removeRefreshToken($token->decode($request->getParsedBody()['token'], true)->data->id)));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(401);
    }
});

$app->post('/update-password', function (Request $request, Response $response) use ($dataBase) {
    try {
        $user = new User($dataBase);
        $response->getBody()->write(json_encode($user->getUpdateLink($request->getParsedBody()['email'])));
        return $response;
    } catch (Exception $e) {
        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
        return $response->withStatus(500);
    }
});

$app->group('/', function (RouteCollectorProxy $group) use ($block, $script, $user) {
    $group->get('folders', function (Request $request, Response $response) use ($script) {
        $userId = $request->getAttribute('userId');
        $isAdmin = $request->getAttribute('isAdmin');
        $response->getBody()->write(json_encode($script->getFolders($userId, $isAdmin)));
        return $response;
    });
    $group->group('scripts',  function (RouteCollectorProxy $scriptGroup) use ($script) {
        $scriptGroup->get('', function (Request $request, Response $response) use ($script) {
            $query = $request->getQueryParams();
            $userId = $request->getAttribute('userId');
            $isAdmin = $request->getAttribute('isAdmin');
            $response->getBody()->write(json_encode($script->getFolder($isAdmin, $userId, null, isset($query['searchString']) ? $query['searchString'] : '')));
            return $response;
        });

        $scriptGroup->get('/search', function (Request $request, Response $response) use ($script) {
            $query = $request->getQueryParams();
            $userId = $request->getAttribute('userId');
            $isAdmin = $request->getAttribute('isAdmin');
            $response->getBody()->write(json_encode($script->searchScripts($isAdmin, $userId, isset($query['searchString']) ? $query['searchString'] : '')));
            return $response;
        });

        $scriptGroup->get('/{folderId}', function (Request $request, Response $response) use ($script) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $folderId = $route->getArgument('folderId');
            $query = $request->getQueryParams();
            $userId = $request->getAttribute('userId');
            $isAdmin = $request->getAttribute('isAdmin');
            $response->getBody()->write(json_encode($script->getFolder($isAdmin, $userId, $folderId, isset($query['searchString']) ? $query['searchString'] : '')));
            return $response;
        });
    });


    $group->group('script',  function (RouteCollectorProxy $scriptGroup) use ($script, $block) {
        $scriptGroup->get('/{scriptId}', function (Request $request, Response $response) use ($script, $block) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $scriptId = $route->getArgument('scriptId');
            $userId = $request->getAttribute('userId');
            $isAdmin = $request->getAttribute('isAdmin');
            $response->getBody()->write(json_encode($script->read($isAdmin, $userId, $scriptId, $block)));
            return $response;
        });

        $scriptGroup->get('/{scriptId}/operator', function (Request $request, Response $response) use ($script, $block) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $scriptId = $route->getArgument('scriptId');
            $userId = $request->getAttribute('userId');
            $isAdmin = $request->getAttribute('isAdmin');
            $response->getBody()->write(json_encode($script->read($isAdmin, $userId, $scriptId, $block, true)));
            return $response;
        });

        $scriptGroup->get('/{scriptId}/variables', function (Request $request, Response $response) use ($script, $block) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $response->getBody()->write(json_encode($script->getScriptVariables($route->getArgument('scriptId'))));
            return $response;
        });

        $scriptGroup->get('/{scriptId}/blocks', function (Request $request, Response $response) use ($script) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $scriptId = $route->getArgument('scriptId');
            $response->getBody()->write(json_encode($script->getBlocks($scriptId)));
            return $response;
        });

        $scriptGroup->get('/{scriptId}/is-opened', function (Request $request, Response $response) use ($script) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $scriptId = $route->getArgument('scriptId');
            $response->getBody()->write(json_encode($script->isOpened($request->getAttribute('isAdmin'), $request->getAttribute('userId'), $scriptId)));
            return $response;
        });
        $scriptGroup->post('/{scriptId}/variable', function (Request $request, Response $response) use ($script) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($script->createScriptVariable($route->getArgument('scriptId'), $request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response = new ResponseClass();
                $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
                return $response->withStatus(500);
            }
        });
        $scriptGroup->put('/{scriptId}/variable', function (Request $request, Response $response) use ($script) {
            try {
                $response->getBody()->write(json_encode($script->updateScriptVariable($request->getParsedBody())));
                return $response;
            } catch (Exception $e) {
                $response = new ResponseClass();
                $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
                return $response->withStatus(500);
            }
        });

        $scriptGroup->delete('/{scriptId}/variable/{variableId}', function (Request $request, Response $response) use ($script) {
            try {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $response->getBody()->write(json_encode($script->deleteScriptVariable($route->getArgument('variableId'))));
                return $response;
            } catch (Exception $e) {
                $response = new ResponseClass();
                $response->getBody()->write(json_encode(array("message" => $e->getMessage())));
                return $response->withStatus(500);
            }
        });
    });

    $group->group('block',  function (RouteCollectorProxy $scriptGroup) use ($block) {
        $scriptGroup->put('/{blockId}/mark', function (Request $request, Response $response) use ($block) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $blockId = $route->getArgument('blockId');
            $response->getBody()->write(json_encode($block->markBlock($blockId, $request->getAttribute('userId'), $request->getParsedBody())));
            return $response;
        });
        $scriptGroup->get('/{blockId}', function (Request $request, Response $response) use ($block) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $blockId = $route->getArgument('blockId');
            $response->getBody()->write(json_encode($block->read($blockId)));
            return $response;
        });
    });

    $group->group('user',  function (RouteCollectorProxy $userGroup) use ($user) {
        $userGroup->get('', function (Request $request, Response $response) use ($user) {
            $userId = $request->getAttribute('userId');
            $response->getBody()->write(json_encode($user->read($userId)));
            return $response;
        });

        $userGroup->get('/check-admin', function (Request $request, Response $response) use ($user) {
            $userId = $request->getAttribute('userId');
            $response->getBody()->write(json_encode($user->checkAdmin($userId)));
            return $response;
        });

        $userGroup->get('/tasks', function (Request $request, Response $response) use ($user) {
            $userId = $request->getAttribute('userId');
            $response->getBody()->write(json_encode($user->getUserTasks($userId)));
            return $response;
        });

        $userGroup->post('/task', function (Request $request, Response $response) use ($user) {
            $userId = $request->getAttribute('userId');
            $response->getBody()->write(json_encode($user->addUserTask($userId, $request->getParsedBody())));
            return $response;
        });

        $userGroup->delete('/task/{taskId}', function (Request $request, Response $response) use ($user) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $response->getBody()->write(json_encode($user->removeUserTask($route->getArgument('taskId'))));
            return $response;
        });
    });

    $group->group('admin', function (RouteCollectorProxy $adminGroup) use ($script, $block, $user) {
        $adminGroup->get('/users', function (Request $request, Response $response) use ($user) {
            $response->getBody()->write(json_encode($user->getUsers()));
            return $response;
        });

        $adminGroup->put('/user-scripts', function (Request $request, Response $response) use ($user) {
            $response->getBody()->write(json_encode($user->setUserScripts($request->getParsedBody())));
            return $response;
        });

        $adminGroup->group('/script',  function (RouteCollectorProxy $scriptGroup) use ($script) {
            $scriptGroup->post('', function (Request $request, Response $response) use ($script) {
                $response->getBody()->write(json_encode($script->create($request->getAttribute('userId'), $request->getParsedBody())));
                return $response;
            });

            $scriptGroup->put('/{scriptId}', function (Request $request, Response $response) use ($script) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $scriptId = $route->getArgument('scriptId');
                $response->getBody()->write(json_encode($script->update($request->getAttribute('userId'), $scriptId, $request->getParsedBody())));
                return $response;
            });

            $scriptGroup->delete('/{scriptId}', function (Request $request, Response $response) use ($script) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $scriptId = $route->getArgument('scriptId');
                $response->getBody()->write(json_encode($script->delete($scriptId)));
                return $response;
            });

            $scriptGroup->put('/{scriptId}/reorder-blocks', function (Request $request, Response $response) use ($script) {
                $response->getBody()->write(json_encode($script->sortBlocks($request->getParsedBody()['blocks'])));
                return $response;
            });
        });

        $adminGroup->group('/block',  function (RouteCollectorProxy $scriptGroup) use ($block) {
            $scriptGroup->delete('/{blockId}', function (Request $request, Response $response) use ($block) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $blockId = $route->getArgument('blockId');
                $response->getBody()->write(json_encode($block->delete($blockId)));
                return $response;
            });

            $scriptGroup->post('/{blockId}/transition', function (Request $request, Response $response) use ($block) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $blockId = $route->getArgument('blockId');
                $response->getBody()->write(json_encode($block->createTransition($request->getAttribute('userId'), $blockId, $request->getParsedBody())));
                return $response;
            });

            $scriptGroup->put('/{blockId}', function (Request $request, Response $response) use ($block) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $blockId = $route->getArgument('blockId');
                $response->getBody()->write(json_encode($block->update($request->getAttribute('userId'), $blockId, $request->getParsedBody())));
                return $response;
            });

            $scriptGroup->post('', function (Request $request, Response $response) use ($block) {
                $response->getBody()->write(json_encode($block->create($request->getAttribute('userId'), $request->getParsedBody())));
                return $response;
            });
        });

        $adminGroup->group('/transition',  function (RouteCollectorProxy $transitionGroup) use ($block) {
            $transitionGroup->delete('/{transitionId}', function (Request $request, Response $response) use ($block) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $transitionId = $route->getArgument('transitionId');
                $response->getBody()->write(json_encode($block->deleteTransition($transitionId)));
                return $response;
            });

            $transitionGroup->put('/{transitionId}', function (Request $request, Response $response) use ($block) {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();
                $transitionId = $route->getArgument('transitionId');
                $response->getBody()->write(json_encode($block->updateTransition($request->getAttribute('userId'), $transitionId, $request->getParsedBody())));
                return $response;
            });
        });
    })->add(function (Request $request, RequestHandler $handler) use ($user) {
        $userId = $request->getAttribute('userId');

        if ($user->checkAdmin($userId)) {
            return $handler->handle($request);
        }

        $response = new ResponseClass();
        $response->getBody()->write(json_encode(array("message" => "Отказано в доступе к функционалу администратора")));
        return $response->withStatus(403);
    });
})->add(function (Request $request, RequestHandler $handler) use ($token) {
    try {
        $jwt = explode(' ', $request->getHeader('Authorization')[0])[1];
        $data = $token->decode($jwt)->data;
        $userId = $data->id;
        $isAdmin = isset($data->isAdmin) ? $data->isAdmin : false;
        $request = $request->withAttribute('userId', $userId);
        $request = $request->withAttribute('isAdmin', $isAdmin);
        $response = $handler->handle($request);

        return $response;
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
