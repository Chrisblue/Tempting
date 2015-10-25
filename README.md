![Tempting Logo](http://puu.sh/kTNyC/1d297f0c9e.png) Tempting
===============
>Tempting is a fast and lightweight template system for PHP. It's main purpose is to provide superb performance while keeping templates as simple as possible. It is heavily inspired by [Mustache](https://github.com/bobthecow/mustache.php/blob/master/) but beats it in terms of performance:

Tempting compared to Mustache
---------------
  - Around 2x - 3x times faster (Wall Time)
  - 50x - 80x times less cpu-load
  - lower memory and IO usage

Tempting achieves that by relinquishing RegEx wherever it is possible and using less complex functions like [strtr](http://php.net/manual/de/function.strtr.php). Depending on the complexity of your templates you can process **hundreds of thousands templates per second** on PHP7 or HHVM. It's the ideal choice for targeting a mobile audience.

- - -

Quick Example
---------------

Here is a quick example using only one variable.

```php
<?php
$temp = new Tempting_Engine(array(__DIR__));
echo $temp->Display('page',array('title' => 'Hello World!'));
```

Contents of 'page'
---------------
```html
<!Doctype html>
<html>
    <head>
        <title>{{title}}</title>
    </head>
    <body>
        <h1>{{title}}</h1>
        <p>This is a quick example.</p>
    </body>
</html>
```

This will generate a html page with title and heading set to the contents of the 'title' variable.

A documentation? Count me in:
---------------

Of course this covers only a small part of what's possible. If you want to learn more about Tempting you can just read the documentation on Github.

**The documentation is currently work in progress. The readme will be updated as soon as the documentation is ready! Please pardon the inconvenience.**