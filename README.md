# Patron

__Patron__ is a template engine for PHP5.3+. It facilitates a mangeable way to seperate application
logic and content from its presentation. Templates are usually written in HTML and include keywords
that are replaced as the template is parsed, and special markups that control the logic of the
template or fetch data.

A typical example:

```html
<p:articles limit="10">
	<p:foreach>
		<h1>#{@title}</h1>

		<div class="article-body">#{@=}</div>

		<p:if test="@comments">
			<h2>User comments</h2>

			<p:foreach in="@comments">
				<h3>Comment №#{self.position} by #{@author}</h3>

				<div class="comment-body">#{@=}</div>
			</p:foreach>
		</p:if>
	</p:foreach>

	<p:pager range="self.range" />
</p:articles>
```

## Features

* The markup set is easily extensible.

* Blocks have a scope, similar to `this` in javascript.

* Easy to translate using the `#{t:String to translate}` notation.




## Markups




### `p:decorate`

Decorates a content with a template.

The content of the markup is rendered to create the component to decorate, it is then passed
to the decorating template as the `component` variable.

The name of the decorating template is specified with the `with` attribute, and is
interpolated e.g. if "page" is specified the templates "@page.html" or "partials/@page.html"
are used, which ever comes first.

The parameters specified using `with-param`, as well as the attribute of the markup (except `with`)
are made available as variables in the decorating template.

#### Signature

```html
<p:decorate
    with = string>
    <!-- Content: p:with-param*, template -->
</p:decorate>
```

#### Example

```html
<p:decorate with="page">
     <p:page:content id="body" />
</p:decorate>
```

The `@page.html` template:

```html
<!DOCTYPE html>
<head>
</head>
<body>
	#{component=}
</body>
```






----------




## Requirements

The package requires PHP 5.3 or later.  
The package [icanboogie/common](https://packagist.org/packages/icanboogie/common) is required.





## Installation

The recommended way to install this package is through [composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
	"minimum-stability": "dev",
	"require": {
		"icybee/patron": "*"
	}
}
```





### Cloning the repository

The package is [available on GitHub](https://github.com/Icybee/Patron), its repository can
be cloned with the following command line:

	$ git clone git://github.com/Icybee/Patron.git





## Documentation

You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





## License

Patron is licensed under the New BSD License - See the LICENSE file for details.