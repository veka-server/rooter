# rooter
Un rooter minimaliste dérivé de Xesau/Router

## Installation

Via composer
```
comopser require veka-server/rooter
```

## Utilisation

Initialisation
```php
// Creation de l'objet
$router = new \VekaServer\Rooter\Rooter();

// Définir une page 404
$router->set404(function(){
    echo 'ma page 404';
});

```
Exemple 1
```php
$router->get(
    '/connexion/magasin/([a-zA-Z0-9_\-+ ]+)/'
    , function($magasin) {
        $obj = new connexion_controller(true);
        $obj->connexion($magasin);
    }
);
```

Exemple 2
```php
$router->get(
    '/home'
    , ['maClasse', 'maMethode]
);
```

Route disponible
```php
$router->get('/home', ['maClasse', 'maMethode']);
$router->post('/home', ['maClasse', 'maMethode']);
$router->getAndPost('/home', ['maClasse', 'maMethode']);
$router->put('/home', ['maClasse', 'maMethode']);
$router->head('/home', ['maClasse', 'maMethode']);
$router->delete('/home', ['maClasse', 'maMethode']);
$router->head('/home', ['maClasse', 'maMethode']);
$router->option('/home', ['maClasse', 'maMethode']);
$router->trace('/home', ['maClasse', 'maMethode']);
$router->connect('/home', ['maClasse', 'maMethode']);
```

Executer le router a la main
```php
$router->dispatchGlobal();
```

## Utiliser le router comme un middleware PSR-15
```php
// creation du dispatcher
$Dispatcher = new VekaServer\Dispatcher\Dispatcher();

// creer le router
$router = new VekaServer\Rooter\Rooter();

// Définir une page 404
$router->set404(function(){
    echo 'ma page 404';
});

// ajouter les route ici, par exemple
$router->get('/home', ['maClasse', 'maMethode]);

// ajout le middlewares
$Dispatcher->pipe($router);
```