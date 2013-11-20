# **W***i***ldRest** 
the **Wr***i***te less do rest** php framework
___
WildRest is a php rest framework that lets you focus on your real job.

## hello world

* file `usr/init.php`

```
$routes = array(
  '/' => array(
    'GET' => array(
      'function'=>function(){
        response(array(
          'hello'=>'world'
        ));
      }
    )
  )
);
```


## `/usr`
All your code goes into the folder `/usr`


### `init.php`
Here you must set up your `$routes` config array.

You can connect with your databases too, or do any other start logic.

If any of your routes uses the `auth` option, you must declare the `auth_check` here.

### `auth_check`

```
function auth_check($auth){
  // do your checks
}
```

**warning** if you uses any `$_GET` or `$_POST` parameters, and the `_GET` or `_POST` options, you must either unset these auth parameters to avoid the parser to find an unknown parameter.



### `$routes`

```
$routes = array( 
  '[ROUTE]'=>[METHODS],
  ...
);

```

#### `'/foo/:bar/baz'` 

* will match 
  * `/foo/42/baz`
  * `/foo/qux/baz`
* but it will not match 
  * `/foo`
  * `/foo/qux/baz/`
  * `/foo/42/baz/qux`
  * `/foo/42/qux/baz`

#### `'/foo/bar/:baz/*'` 

* will match 
  * `/foo/bar/42/
  * `/foo/bar/42/qux
  * `/foo/bar/qux/42
  * `/foo/bar/qux/42/tny
* will not match
  * `/foo/bar
  * `/foo/bar/42
  * `/foo/42/qux
  * `/bar/qux/42/tny

#### `'/foo/*/bar/'` 
* will match
  * `/foo/*/bar/
* will not match
  * `/foo/*/bar
  * `/foo/42/qux/bar
  * `/foo/bar

#### the order matters
The first route that match the url will be used

#### [METHODS]

```
  array(
    '[METHOD]'=>[OPTIONS],
    ...
  )
```

The `'GET'=>[OPTIONS]` will configure the options for every `GET` method on this route. 
You can declare a `' default '` method that will be used when the specific one is not declared.
If there is not `' default '` method, any undeclared method will get `405 Method Not Allowed` 

#### [OPTIONS]
```
    array(
      'auth'=>'user' // 'admin' | false | 'guest' | array(...) | ...
      '_GET'=>array(
        'foo'=>true, // foo is required
        'bar'=>false,// bar is optional
        '*'  => true // there can be other $_GET parameters 
      ),
      '_POST'=>array(
        'qux'=>false, // qux is optional
        'tny'=>true,  // tny is required
        '*'  =>false  // there cannot be other $_POST parameters
      ),
      'require'=>'foo/bar.php', //file that will be php-required,
      'function'=>'functionname'// or anonymous function that will be called
    )
```
No option is required, everyone is optional.

##### `'auth'`
if `'auth'` is set, `auth_check` will be called with its value.

##### `_GET` and `_POST`
Here you can set with params are required, optional or if there can be random parameters.
 
##### `require` and `function`
You can set with file or function will be required or called to process the request

**warning** if you use `require` or `function` you must allways exit with `response`, `error` or `exit`. Because if the program do not exit, it will try to do the `/urs/routes/` thing. 

### `/usr/routes/`
You can keep your code neat, placing the code for each route and method in his outn folder.

So if you want to serve `GET /foo/:bar/:id`, you need to create an populate `usr/urls/foo/GET` 

and if you want to serve  `DELETE /some/:stuff` you need to create and populate `usr/urls/some/DELETE`

Inside of these `GET`, `POST`, `PUT`, `DELETE` or `WHATEVER` folder there have to be three files
* control.php
* model.php
* view.php

### api

#### `error([status code],[response]);
`error` 

#### `response`
