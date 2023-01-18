<h1 align = "center"> Symfony Helper </h1>

# Installation

Run:

```bash
composer require --prefer-dist denisok94/symfony-helper
# or
php composer.phar require --prefer-dist denisok94/symfony-helper
```

or add to the `require` section of your `composer.json` file:

```json
"denisok94/symfony-helper": "*"
```

```bash
composer update
# or
php composer.phar update
```
# Setting

```php
// ~config/bundles.php
return [
    //..
    Denisok94\SymfonyHelper\Denisok94SymfonyHelperBundle::class => ['all' => true],
];
```
Унаследовать настройки родительского контроллера
```yaml
# ~config/services.yaml
    # global if all controller extends ApiRestController or ApiListController
    App\Controller\:
        parent: 'denisok94.controller.api_rest_controller'
    # or individual 
    App\Controller\MyController:
        parent: 'denisok94.controller.api_rest_controller'
```
Возвращать ошибки в формате json
```yaml
# ~config/packages/framework.yaml
framework:
    error_controller: Denisok94\SymfonyHelper\Controller\JsonErrorController::show
```
Возвращать ошибки доступа в формате json
```yaml
# ~config/packages/security.yaml
security:
    firewalls:
        main: # or other name use
            access_denied_handler: Denisok94\SymfonyHelper\Security\AccessDeniedHandler

```

# Use
