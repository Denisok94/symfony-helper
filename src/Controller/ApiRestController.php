<?php

namespace Denisok94\SymfonyHelper\Controller;

use Denisok94\SymfonyHelper\Components\Helper as H;
use Denisok94\SymfonyHelper\Service\ApiRestService;
use Denisok94\SymfonyHelper\Service\JsonConverter;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ApiRestController
 * 
 * - Данные пользователя в `$this->user`;
 * - Все POST/PUT и тд данные в `$this->data`, `$this->getData(имя_параметра)`;
 * - Получить GET параметры `$this->getQuery(имя_параметра)`;
 * - Переводчик `$this->trans($message, $params)`, файл: `api.*.yaml`;
 * - Логи `$this->info($message)`,`$this->error($message)`,`$this->critical(Exception $e))`;
 * - Формат логов: "api.{controllerName}.{actionName}: {message}".
 * 
 * Готовые шаблоны ответов:
 * - `$this->buildResponse($mes, $code=200)`
 * - `$this->buildResponseConverter($object, $groups, $code=200)` - object to json converter
 * - `$this->buildErrorResponse($mes, $code=400)` + add error in log 
 * - `$this->buildBadRequest($mes)` - code 400 + add error in log 
 * - `$this->buildUnauthorized($mes)` - code 401 + add error in log 
 * - `$this->buildForbidden($mes)` - code 403 + add error in log 
 * - `$this->buildNotFound($mes)` - code 404 + add error in log 
 * - `$this->buildInternalServerError(Exception $e)` - code 500 + add critical in log 
 *  
 * Настроить предварительные ограничения:
 * ```php
 * // add in Controller `public function accessControl() {}`
 * // in accessControl:
 *  return [[
 *      'actions' => ['image'], // список Actions, без `Action`
 *      'roles' => ['ROLE_PARTICIPANT'], // Ограничения по ролям
 *      'data' => true // наличие данных в запросе (POST/PUT и тд)
 *  ], [
 *      'actions' => ['getResult'], // список Actions, без `Action`
 *      'roles' => ['ROLE_EXPERT'], // Ограничения по ролям
 *  ]];
 * // если пользователь проверку не проходит, готовый ответ в `$this->error`
 * function getResultAction(Request $request): JsonResponse
 * {
 *   if ($this->error != false) return $this->error;
 *  //....
 * }
 * ```
 *
 * @package Denisok94\SymfonyHelper\Controller
 */
abstract class ApiRestController extends AbstractController
{
    /** @var JsonResponse */
    protected $error = false;
    /** @var array */
    protected $data = [];
    /** @var UserInterface */
    protected $user;
    /** @var JsonConverter */
    protected $jsonConverter;
    /** @var Request */
    protected $requestStack;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var LoggerInterface|null */
    protected $logger;
    /** @var ContainerInterface */
    protected $container;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    protected $actionName;
    protected $controllerName;
    protected $defaultLocale;
    protected $currentLocale;

    /**
     * ApiRestController constructor.
     * @param ApiRestService $apiRest
     */
    public function setApiRestService(ApiRestService $apiRest)
    {
        $this->requestStack = $apiRest->getRequest();
        $this->logger = $apiRest->getLogger();
        $this->container = $apiRest->getContainer();
        $this->translator = $apiRest->getTranslator();
        $this->jsonConverter = $apiRest->getJsonConverter();
        $this->eventDispatcher = $apiRest->getEventDispatcher();
        //
        $this->getApiName();
        $this->defaultLocale = 'ru';
        $currentLocale = null;
        if ($this->requestStack instanceof Request) {
            $currentLocale = $this->requestStack->getLocale();
        }
        if (null === $currentLocale) {
            $currentLocale = $this->defaultLocale;
        }
        $this->setLocale($currentLocale);
        //
        // $this->info('init');
        $this->error = $this->beforeAction();
    }

    /**
     * @param string $locale
     */
    private function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    /**
     * @return JsonResponse|bool
     */
    private function beforeAction(): JsonResponse|bool
    {
        /** @var UserInterface $user */
        $this->user = $user = $this->getUser();
        $this->data = H::toArray($this->requestStack->getContent());
        // если заданы ограничения
        if ($accessControl = $this->accessControl()) {
            if ($error = $this->preAccessControl($accessControl)) return $error;
            foreach ($accessControl as $rules) {
                if (is_array($rules)) {
                    if ($error = $this->preAccessControlItem($rules)) return $error;
                }
                if (isset($rules['actions'])) {
                    // найти настройки action
                    if (in_array($this->actionName, $rules['actions'])) {
                        // если есть ограничения по ролям
                        if (isset($rules['roles']) && is_array($rules['roles'])) {
                            if (!$user) return $this->buildUnauthorized();
                            $access = false;
                            foreach ($rules['roles'] as $role) {
                                try {
                                    $this->denyAccessUnlessGranted($role);
                                    $access = true;
                                    break;
                                } catch (AccessDeniedException $ade) {
                                    //throw $th;
                                }
                            }
                            if ($access == false) return $this->buildForbidden();
                        }
                        // если параметры обязательны
                        if (isset($rules['data']) && $rules['data'] == true) {
                            if (!$this->data) {
                                return $this->buildBadRequest("api.request.empty");
                            }
                            $jsonResp = H::isJsonRequest($this->requestStack) ?? $this->requestStack->isXmlHttpRequest();
                            if (!$jsonResp) {
                                return $this->buildBadRequest("api.request.ajax");
                            }
                        }
                        break;
                    } // end if in_array
                } // end if isset
            } // end foreach
        }
        return false;
    }

    /**
     * Настроить предварительные ограничения
     * 
     * add in Controller `public function accessControl() {}`
     * ```php
     * return [
     *   ['actions' => [], 'roles' => [], 'data' => true]
     * ];
     * ```
     * ```php
     * // in accessControl:
     *  return [[
     *      'actions' => ['image'], // список Actions, без `Action`
     *      'roles' => ['ROLE_PARTICIPANT'], // Ограничения по ролям
     *      'data' => true // наличие данных в запросе (POST/PUT и тд)
     *  ], [
     *      'actions' => ['getResult'], // список Actions, без `Action`
     *      'roles' => ['ROLE_EXPERT'], // Ограничения по ролям
     *  ]];
     * // если пользователь проверку не проходит, готовый ответ в `$this->error`
     * function getResultAction(Request $request): JsonResponse
     * {
     *   if ($this->error != false) return $this->error;
     *  //....
     * }
     * ```
     * @return array|null
     */
    public function accessControl()
    {
        return null;
    }

    /**
     * Предварительная проверка доступа
     * @param array $accessControl - весть массив из accessControl()
     * @return JsonResponse|bool
     */
    public function preAccessControl(array $accessControl): JsonResponse|bool
    {
        return false;
    }

    /**
     * Индивидуальная проверка доступа
     * @param array $item - элемент массива из accessControl()
     * @return JsonResponse|bool
     */
    public function preAccessControlItem(array $item): JsonResponse|bool
    {
        return false;
    }

    /**
     * Получить параметр данных из запроса (POST/PUT и тд)
     * @param string $path имя параметра
     * @param bool $nullValue иначе, если его нет
     * @return array|string|bool
     */
    public function getData($path, $nullValue = false)
    {
        return H::get($this->data, $path, $nullValue);
    }

    /**
     * Получить GET параметр
     * @param string $name имя параметра
     * @param bool $nullValue иначе, если его нет
     * @return array|string|bool
     */
    public function getQuery($name, $nullValue = false)
    {
        return $this->requestStack->query->get($name, $this->requestStack->get($name, $nullValue));
    }

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public $validator;

    /**
     * - set `$this->validator`: Symfony\Component\Validator\Validator\ValidatorInterface
     * @param mixed $model
     * @param mixed $validates
     * @return array|false
     * 
     * ```php
     * try {
     *  $model = $this->jsonConverter->fromJson($request->getContent(), Model::class);
     *  if ($errors = $this->isErrorValid($model, new ModelValidator())) {
     *      return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
     *  }
     * } catch (\Megacoders\CoreBundle\Exception\ConverterException $e) {
     * } catch (\Exception $e) {
     * } 
     * ```
     */
    public function isErrorValid($model, $validates)
    {
        $errors = $this->validator->validate($model, $validates);
        if ($errors->count() > 0) {
            $er = [];
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $er[$error->getPropertyPath()] = $error->getMessage();
            }
            return $er;
            // return new JsonResponse($er, Response::HTTP_BAD_REQUEST);
            // throw new \RuntimeException(implode(", ", $er));
            // throw new \RuntimeException($errors->get(0)->getMessage());
        }
        return false;
    }

    /**
     * Получить имя Controller и Action для логов
     * @return string
     */
    private function getApiName()
    {
        $api = $this->requestStack->get('_controller');
        $api = explode('::', $api);
        $action = $api[1];
        $controller = explode('\\', $api[0]);
        $controller = isset($controller[4]) ? $controller[4] : (isset($controller[3]) ? $controller[3] : (isset($controller[2]) ? $controller[2] : null));

        $this->actionName = isset($action) ? preg_replace('/Action$/', '', $action) : false;
        $this->controllerName = isset($controller) ? preg_replace('/Controller$/', '', $controller) : false;
    }

    //-------------------------------

    /**
     * Переводчик, файл: `api.*.yaml`.
     * @param string $message
     * @param array $params
     * @return string
     */
    public function trans(string $message, array $params = []): string
    {
        return $this->translator->trans($message, $params, 'api', $this->requestStack->getLocale(), 'yaml', $this->currentLocale);
    }

    /**
     * Запись в лог
     * @param string $message
     */
    public function error(string $message): void
    {
        if ($this->logger) {
            $this->logger->error($this->textLogger($message), $this->paramLogger());
        }
    }

    /**
     * Запись в лог
     * @param string $message
     */
    public function info(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($this->textLogger($message), $this->paramLogger());
        }
    }

    /**
     * Запись в лог
     * @param Throwable $e
     * @param string $message
     */
    public function critical(Throwable $e, string $message = ''): void
    {
        if ($this->logger) {
            $this->logger->critical($this->textLogger(
                sprintf("%s %s(%s:%s)", $message, $e->getMessage(), $e->getFile(), $e->getLine())
            ), $this->paramLogger());
        }
    }

    /**
     * @param string $message
     * @return string
     */
    public function textLogger(string $message): string
    {
        return sprintf("api.%s.%s: %s", $this->controllerName, $this->actionName, $this->trans($message));
    }

    /**
     * @return array
     */
    public function paramLogger(): array
    {
        return ['user' => $this->user, 'query' => $this->requestStack->query->all(), 'data' => $this->data];
    }

    //-------------------------------

    /**
     * @param string|array $message
     * @param string $code
     * @return JsonResponse
     */
    protected function buildResponse($message = '', $code = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message
        ], $code);
    }

    /**
     * @param string|array $message
     * @param string $code 400
     * @return JsonResponse
     */
    protected function buildErrorResponse($message = '', $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $this->error(is_array($message) ? H::toJson($message) : ($message));
        return new JsonResponse(["error" => [
            'code' => $code,
            'message' => $message
        ]], $code);
    }

    /**
     * object to json converter 
     * @param mixed $object
     * @param array $groups
     * @param string $code
     * @return JsonResponse
     */
    protected function buildResponseConverter($object, $groups = [], $code = Response::HTTP_OK): JsonResponse
    {
        try {
            return JsonResponse::fromJsonString($this->jsonConverter->toJson($object, $groups), $code);
        } catch (Throwable $e) {
            return $this->buildInternalServerError($e);
        }
    }

    /**
     * 400
     * @param string $message
     * @return JsonResponse
     */
    protected function buildBadRequest(string $message = 'api.bad_request', array $params = []): JsonResponse
    {
        $this->error($message);
        return new JsonResponse(["error" => [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => $this->trans($message, $params)
        ]], Response::HTTP_BAD_REQUEST);
    }

    /**
     * 401
     * @param string $message
     * @return JsonResponse
     */
    protected function buildUnauthorized(string $message = "api.unauthorized"): JsonResponse
    {
        $this->error($message);
        return new JsonResponse(["error" => [
            'code' => Response::HTTP_UNAUTHORIZED,
            'message' => $this->trans($message)
        ]], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 403
     * @param string $message
     * @return JsonResponse
     */
    protected function buildForbidden(string $message = 'api.forbidden'): JsonResponse
    {
        $this->error($message);
        return new JsonResponse(["error" => [
            'code' => Response::HTTP_FORBIDDEN,
            'message' => $this->trans($message)
        ]], Response::HTTP_FORBIDDEN);
    }

    /**
     * 404
     * @param string $message
     * @return JsonResponse
     */
    protected function buildNotFound(string $message = 'api.not_found'): JsonResponse
    {
        $this->error($message);
        return new JsonResponse(["error" => [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => $this->trans($message)
        ]], Response::HTTP_NOT_FOUND);
    }

    /**
     * 500
     * @param Throwable $e
     * @param string $message
     * @return JsonResponse
     */
    protected function buildInternalServerError(Throwable $e, string $message = 'api.internal_server_error'): JsonResponse
    {
        $this->critical($e, $message);
        return new JsonResponse(["error" => [
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->trans($message)
        ]], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
