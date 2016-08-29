# NBOAjaxBundle

Ce bundle permet de facilité l'utilisation des requête ajax dans une application Symfony 2. 
Il permet de définir des paramètres pour chaque route, ainsi que d'autres propriétés.

Ce bundle permet pour chaque paramètre d'une route de définir :
 * Si il est __requis__
 * Si il peut être __vide__
 * Son __type__ : Chaîne de caractères, entier, flottant, booléen, tableau ou date/heure
 * Sa ou ses valeurs par __défaut__
 * Sa ou ses __valeurs interdites__
 * Sa ou ses __valeurs permises__
 * Sa valeur __miminum__ si c'est un entier ou un flottant
 * Sa valeur __maximum__ si c'est un entier ou un flottant
 * Une __expression régulière__ si c'est une chaîne de caractères
 * Le __format date/heure__ si c'est un type date/heure

Il permet pour chaque paramètre fichier d'une route de définir :
 * Si il est __requis__
 * Si il peut être __vide__
 * Le ou les types de fichiers requis
 * La taille maximum du fichier

Pour chaque route :
 * La méthode requise : POST ou GET
 * Le ou les rôles utilisateurs requis pour accèder à la route

## Installation

Avec [composer](https://packagist.org/), ajouter :
```json
{
    "require": {
        "nbo/ajaxbundle": "1.0"
    }
}
```

En suite ajouter le bundle au kernel de l'application :

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        //...
        new NBO\Bundle\AjaxBundle\NBOAjaxBundle(),
        //...
```

## Usage

Ce bundle fournie deux façon simple de gérer les requêtes ajax :
 * Le contrôleur `AjaxController`
 * Le service `ajax_request`

### Le contrôleur `AjaxController`

```php
use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class MyCustomAjaxController extends AjaxController {
    // ...
    public function methodAjaxAction($parameter_0, $parameter_1, $file_0){
        // ...
    }
}
```
Ce contrôleur permet de configurer la route entièrement dans le fichier `routing.yml`.
L'utilisation de ce contrôleur est identique à un contrôleur normal, les paramètres des routes sont accessibles en arguments.

Par défaut le contrôleur vérifie :
 * Si la requête est du type XML HTTP,
 * Si la méthode d'accès requise est validée,
 * Si le ou les rôles utilisateurs requis sont validés
 * Si toutes les configurations de paramètres sont validées

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    path: /ajax/test
    defaults: { _controller: AppAppBundle:MyCustomAjax:methodAjax }
    options:
        expose: true
```

 > Attention : une route ajax est identifiée par l'option `expose` à TRUE. Cette option est la même que celle utilisée par `FOSJsRoutingBundle` dans un souci de simplicité.

#### Créer une route

__YML (recommandé)__

```yml
# UserBundle\Ressource\config\routing.yml
app_test_get_user_ajax:
    path: /ajax/getUser
    defaults: { _controller: AppUserBundle:UserAjax:getUserAjax }
    requirements:
        _method: GET
    options:
        expose: true
        roles: [ ROLE_USER ]
        parameters: { id : { type : 0, min : 1 } }
```

Attention : les expressions régulières ne peuvent être définies dans une configuration YML.

__PHP__

```php
$collection->add(
    'app_test_update_user_ajax',
        new Route('/ajax/updateUser', array(
        '_controller' => 'AppUserBundle:UserAjax:updateUserAjax',
    ), array(
        '_method' => 'POST',
    ), array(
        'expose' => true,
        'parameters => array(
            'id' => array( 'type' => 0, 'min' => 1 )
        )
    )
);
```

#### Autres configurations

__Changer la méthode d'accès__

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    # ...
    requirements:
        _method: POST
```
Par défaut la méthode d'accès est GET.

__Auto validation__

Par défaut, AjaxController vérifie de lui même si les conditions requises sont valides, sinon déclenche une exception.

Si vous ne souhaitez pas que l'exception soit déclenchée, vous pouvez désactiver cette auto validation comme suit :

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    # ...
    options:
        expose: true
        # ...
        autoValid: false
```

Attention : si vous désactivez cette fonctionnalité, les arguments de la méthodes correspondantes à la route dans le contrôelur seront NULL.

Vous devrez effectuer la validation des conditions de la requête par vous même pour récupérer les valeurs des paramètres :

```php
use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class MyCustomAjaxController extends AjaxController {
    // ...
    public function methodAjaxAction(){

        // do something before validation
        // Warning: if you have defined parameter(s), get them after call isValid() method

        if($this->isValid()){

            // All config conditions are valides

            // Get parameters here if necessary
            // ...
        }
        // One of the config conditions is not valid
        // throw exception, return error or do what you want
    }
}
```

__Ajouter des paramètres à une route__

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    # ...
    options:
        expose: true
        # ...
        parameters: [parameter_0, ...]
```

__Ajouter des paramètres fichier à une route__

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    # ...
    options:
        expose: true
        # ...
        files: [file_0, ...]
```

Attention : tous les paramètres, fichier ou non, doivent avoir un nom unique.

__Plus de configuration...__

Le bundle permet d'ajouter toutes les configurations possibles d'un paramètre :

```yml
# AppBundle\Ressource\config\routing.yml
app_test_ajax:
    # ...
    options:
        expose: true
        parameters: { param_0: { require: true, type: 0 }, param_1: { require: false, empty: true, type: 1 } }
        #files: { file_0: {require: false, mimeType: text/csv }, file_1: {require: false}}
```
Dans cette exemple, la route à trois paramètres : "param_0", "param_1" and "param_2".
Le premier est une châine de caractère, le second est un entier non requis pouvant être vide et le troisième un paramètre avec la configuration par défaut.

__Récupérer un paramètre__

Par défaut, avec le contrôleur `AjaxControlleur` vous pouvez passer les paramètres directement en arguments de la méthode de la route.

```php
use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class MyCustomAjaxController extends AjaxController {
    // ...
    public function methodAjaxAction($param_0, $param_1, $file_0){
        // ...
    }
}
```

Mais il est possible de les récupérer via le contrôleur lui même :

```php
use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class MyCustomAjaxController extends AjaxController {
    // ...
    public function methodAjaxAction(){
        // get all parameters and files parameters
        $all_params = $this->getParameters();
        // get "parameter_0" value, use this function for files parameters too
        $param_0 = $this->getParameter("parameter_0");
    }
}
```

__Retourner une réponse__

```php
use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class MyCustomAjaxController extends AjaxController {
    // ...
    public function methodAjaxAction(){
        // ...
        // return JsonResponse with array() parameters
        return $this->createJsonResponse(array());
        // return JsonResponse error with code 400
        // return $this->createErrorJsonResponse();
    }
}
```

Il est également possible de retourner une réponse HTML en passant une vue Twig en argument.

__Exemple complet__

Méthode pour mettre à jour un utilisateur.

Le paramètre `id` est un entier requis ayant pour valeur minimum 1. 
Le paramètre `name` est une chaîne de caractères requise. 
Le paramètre `premium` est un booléen non requis pouvant être vide et ayant pour valeur par défaut FALSE. 
Le paramètre `profile_picture` est un fichier de type requis png ou jpg.

Regarder la documentation des paramètres pour plus d'informations.

La méthode d'accès est POST et seuls les utilisateurs ayant le rôle ROLE_USER peuvent utiliser cette route.

```yml
# UserBundle\Ressource\config\routing.yml
app_test_ajax:
    path: /ajax/test
    defaults: { _controller: AppUserBundle:UserAjax:updateUserAjax }
    requirements:
        _method: POST
    options:
        expose: true
        roles : [ROLE_USER]
        parameters: { id: { type: 0, min: 1}, name, premium: { type: 2, require: false, empty: true, defaultValue: false} }
        files: { profile_picture: { mimeType: [img/png, imh/jpg] } }
```

```php
// App\Bundle\UserBundle\Controller\UserAjaxController.php
namespace App\Bundle\UserBundle\Controller;

use NBO\Bundle\AjaxBundle\Controller\AjaxController;

class UserAjaxController extends AjaxController {

    /**
     * @param int $id
     * @param string $name
     * @param boolean $premium
     * @param UploadedFile $profile_picture
     * @return JsonResponse
     */
    public function updateUserAjaxAction($id, $name, $premium, $profile_picture) {
        $em = $this->getDoctrine()->getManager();

        // find your entity
        // $id is a integer with min value 1
        $user = $em->getRepository('AppAppBundle:User')->find($id);

        // return JsonResponse error, code 400, if User doesn't exist
        if(is_null($user)){
            return $this->createErrorJsonResponse(array("message"=>"User with id ".$id." not found."));
        }
        // update your entity

        // $name is a string
        $user->setName($name);
        // $premium is a boolean with default value false if not define or empty
        $user->setPremium($premium);
        // $profile_picture is a UploadedFile, png or jpg file
        $user->setProfil_picture($profile_picture);

        $em->persist($user);
        $em->flush();
        
        // return new JsonResponse(data)
        return $this->createJsonResponse(array("object"=>$object->toArray()));
    }
}
```


### Le service `ajax_request`

```php
// In a controller method
// get Ajax request service
$ajaxRequest = $this->get('ajax_request');
$ajaxRequest->addParameter("param_0");
```

Regarder la documentation API de la classe AjaxRequest pour plus d'informations.

__Exemple complet__

```php
public function testAjaxMethodAction() {
    // get Ajax request service
    $ajaxRequest = $this->get('ajax_request');

    // add request parameter
    // The parameter "param_test" is an Integer, must be between 0-20, 
    // is required and can not be empty
    $ajaxRequest->addParameter("param_test", ParameterType::INT_TYPE)
            ->min(0)->max(20);
    // The parameter "param_test_2" is a String, must be equal to "ok" or "ko", 
    // is not required and can not be empty
    // If not define, the default value is "ok"
    $ajaxRequest->addParameter("param_test_2")
            ->required(false)
            // not require, 
            // the default value take the restricted value or the first restricted values if is an Array
            ->defaultValue("ok")
            ->restrictedBy(array("ok","ko"));

    // check if Ajax request is valid
    if($ajaxRequest->isValid()){

        // get all parameters data
        $param_test_value = $ajaxRequest->getValue("param_test");

        // return new JsonResponse(data)
        return $ajaxRequest->createJsonResponse(array("param_test"=>$param_test_value));
    }
    // $error = $ajaxRequest->getError();
    return $ajaxRequest->createErrorJsonResponse();
}
```

### Request config parameter properties

__Parameter__

Each parameter has an unique name.
 * YML : `parameters: { parameter_name: {config},... }` or `parameters: [parameter_name,...]`
 * PHP : `addParameter(string parameter_name)` or `addParameter(string parameter_name, array config)`

| yml / php(array) | function | default | description |
|---|---|---|-----|
| `type` | `setType(int)` | 1 | define the parameter type. |
| `require` | `required(boolean)` | true | define if the parameter is required. |
| `empty` | `canBeEmpty(boolean)` | false | define if the parameter can be empty. |
| `defaultValue` | `defaultValue(mixed)` | `null` | define the default value(s) if the parameter is empty. Can be an Array of disabled values. It should be the same type as the parameter if is not an array. |
| `disabledValue` | `disabledValue(mixed)` | `null` | define the disabled value(s). Can be an Array of disabled values. It should be the same type as the parameter if is not an array. |
| `restrictedValue` | `restrictedBy(mixed)` | `null` | define the restricted value(s). Can be an Array of restricted values. It should be the same type as the parameter if is not an array. |
| impossible | `regex(string)` | `null` | define the regular expression to match, only use if the parameter is a String. |
| `min` | `min(int)` | `null` | define the minimum value, only use if the parameter is a Integer or a Float. |
| `max` | `max(int)` | `null` | define the maximum value, only use if the parameter is a Integer or a Float. |
| `datetimeFormat` | `datetimeFormat(string)` | `null` | define the datetime format, only use if the parameter is a Datetime. Look [DateTime format](http://php.net/manual/fr/datetime.createfromformat.php) for more informations about available datetime format. |

__File__

Each file has an unique name.
 * YML : `files: { file_name: {config},... }` or `files: [file_name,...]`
 * PHP : `addFileParameter(string file_name)` or `addFileParameter(string file_name, array config)`

| yml / php(array) | function | default | description |
|---|---|---|-----|
| `require` | `required(boolean)` | true | define if the parameter is required. |
| `empty` | `canBeEmpty(boolean)` | false | define if the parameter can be empty. |
| `mimeType` | `mimeType(mixed)` | `null` | define the require mime type file, can be a string or an array of string. All file is accepted if null. |
| `maxSize` | `maxSize(int)` | `null` | define the max file size in octet. No limit if null. |

### Available type

The bundle provide 5 ajax parameter types:
 * `ParameterType::INT_TYPE = 0` : the parameter must be a Integer
 * `ParameterType::STRING_TYPE = 1` : the parameter must be a String __(default)__
 * `ParameterType::BOOL_TYPE = 2` : the parameter must be a Boolean
    > Available values for `true`: ["on", "On", "ON", "true", "True", "TRUE", "y", "yes", "Y", "Yes", "YES", "1", true, 1]
    > Available values for `false`: ["off", "Off", "OFF", "false", "False", "FALSE", "n", "no", "N", "No", "NO", "0", false, 0, null]
 * `ParameterType::ARRAY_TYPE = 3` : the parameter must be a Array
 * `ParameterType::FLOAT_TYPE = 4` : the parameter must be a Float
    > Available values can be like: 1.2, "1.2", 1.23456789, 1 or 1e2
 * `ParameterType::DATETIME_TYPE = 5` : the parameter must be a Datetime string

### Restricted access by roles

You can restricted the access route by a role or an Array of roles
```yml
# Only the users with the role ROLE_USER can access to the route
app_default_test_ajax:
    path: /testAjax
    defaults: { _controller: OSMOKDefaultBundle:Default:testAjax }
    options:
        expose: true
        roles: [ROLE_USER, ...]
```
By convention, a Ajax route is identify by the "expose" option, set to TRUE.

Documentation API
-------------------

You can find the documentation API here : [documentation API](http://todo_documentation_api.com)

Original Credits
----------------

* Nicolas BONNEAU as main author.

License
-------

This bundle is released under the MIT license. See the complete license in the
bundle:

    Resources/meta/LICENSE
