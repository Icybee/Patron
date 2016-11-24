# Patron

[![Release](https://img.shields.io/packagist/v/icybee/patron.svg)](https://packagist.org/packages/icybee/patron)
[![Build Status](https://img.shields.io/travis/Icybee/Patron.svg)](http://travis-ci.org/Icybee/Patron)
[![HHVM](https://img.shields.io/hhvm/Icybee/Patron.svg)](http://hhvm.h4cc.de/package/Icybee/Patron)
[![Code Quality](https://img.shields.io/scrutinizer/g/Icybee/Patron.svg)](https://scrutinizer-ci.com/g/Icybee/Patron)
[![Code Coverage](https://img.shields.io/coveralls/Icybee/Patron.svg)](https://coveralls.io/r/Icybee/Patron)
[![Packagist](https://img.shields.io/packagist/dt/Icybee/Patron.svg)](https://packagist.org/packages/Icybee/Patron)


__Patron__ is a template engine for PHP5.4+. It facilitates the separation of the application logic and content from its presentation. Templates are written in HTML and include expressions that are replaced as the template is parsed, and special markups that control the logic of the template.





### A typical example

```html
<p:articles limit="10">
	<p:foreach>
		<article class="#{@css_class}">
			<h1>#{@title}</h1>

			<div class="article-body">#{@=}</div>

			<p:if test="@comments">
				<section class="article-comments">
					<h2>Comments</h2>

					<p:foreach in="@comments">
						<article class="#{@css_class}">
							<header>
							<h3>Comment №#{self.position} by #{@author}</h3>
							</header>

							<div class="comment-body">#{@=}</div>
						</article>
					</p:foreach>
				</section>
			</p:if>
		</article>
	</p:foreach>

	<p:pager />
</p:articles>
```





### Features

* The markup set and the function set are easily extensible.
* Blocks have a _subject_, similar to how `this` works in javascript.
* Easy to translate using the `#{t:String to translate}` notation.





### Acknowledgment

This template engine was developed because around 2007 [textpattern](http://textpattern.com/)
didn't support nested markups, and I though it would be a good exercise. Some part of its code
have slept for a long time, so don't be surprise if you see some camel casing although snake
casing is used nearly everywhere, at least you'll recognize the _old_ parts :-)





## Expressions

Expressions, using the `#{<expression>}` notation, are used to output data. The data is always
escaped unless the `=` modifier is used, just before the closing `}`. The `@` sign is used
to access the properties of the _subject_ (although you can also use `this`).

```html
#{@title}
#{@title.shuffle()}   <!-- `title` is passed to str_shuffle() -->
#{@title=}            <!-- `title` is not escaped -->

#{pagination=}        <!-- another variable that is not escaped -->
```





## Markup collection

The markups that can be used by a _Patron_ engine instance are defined in a [MarkupCollection][]
instance, which is used to create the [Engine][] instance. The `get_markups()` helper
function can be used to obtain a shared markup collection. When it is first created, the
`MarkupCollection::alter` event of class [MarkupCollection\AlterEvent][] is fired. Event hooks
may use this event to alter the collection, adding and removing markups definitions.

The following example demonstrates how an event hook may be used to add a `hello` markup, that
supports a `name` argument which defaults to "world":

```php
<?php

use Patron\Engine;
use Patron\MarkupCollection;

$app->events->attach(function(MarkupCollection\AlterEvent $event, MarkupCollection $collection) {

	$collection['hello'] = [ function(array $args, Engine $engine, $template) {

		return "Hello {$args['name']}!";

	}, [ 'name' => "world" ] ];

});
```

The following markups are defined.





### The `p:if` markup

Provides a simple if-then conditionality.

```html
<p:if
	test = expression
	select = expression
	equals = value>
	<!-- Content: p:with-param*, template -->
</p:if>
```

Either `test` or `select` and an operator (e.g. `equals`) should be defined. The test is silent,
and should not generate notices.

```html
<p:if test="@has_title">This article has a title</p:if>
<p:if test="@has_title.not()">This article has no title</p:if>
<p:if select="@comments_count" equals="10">This article has 10 comments</p:if>
```





### The `p:choose` markup

Selects one among a number of possible alternatives.

```html
<!-- Category: instruction -->
<p:choose>
	<!-- Content: (p:when+, p:otherwise?) -->
</p:choose>

<p:when
	test = boolean-expression>
	<!-- Content: template -->
</p:when>

<p:otherwise>
	<!-- Content: template -->
</p:otherwise>
```

It consists of a sequence of `p:when` elements followed by an optional `p:otherwise`
element. Each `p:when` element has a single attribute, test, which specifies an expression.
The content of the `p:when` and `p:otherwise` elements is a template. When an `p:choose`
element is processed, each of the `p:when` elements is tested in turn, by evaluating the
expression and converting the resulting object to a boolean as if by a call to the boolean
function. The content of the first, and only the first, `p:when` element whose test is true
is instantiated. If no `p:when` is true, the content of the `p:otherwise` element is
instantiated. If no `p:when` element is true, and no `p:otherwise` element is present,
nothing is created.





### The `p:foreach` markup

Applies a template to each entries of the provided array.

```html
<p:foreach
	in = expression | this
	as = qname | this>
	<!-- Content: p:with-param*, p:empty?, p:wrap?, template -->
</p:foreach>
```

At each turn the following variables are updated in `self`:

- `count`: The number of entries.
- `position`: The position of the current entry.
- `left`: The number of entries left.
- `even`: "even" if the position is even, an empty string otherwise.
- `key`: The key of the entry.

```html
<p:foreach in="articles">
	<p:empty>There is no article yet.</p:empty>
	<p:wrap><ul>#{@=}</ul></p:wrap>
	
	<li>#{self.position}/#{self.count} <a href="#{@url}">#{@title}</a></li>
</p:foreach>
```





### The `p:variable` markup

Binds a name to a value.

```html
<!-- Category: top-level-element -->
<!-- Category: instruction -->
<p:variable
	name = qname
	select = expression>
	<!-- Content: p:with-param*, template? -->
</p:variable>

<!-- Category: top-level-element -->
<p:param
	name = qname
	select = expression>
	<!-- Content: template? -->
</p:param>
```

The value to which a variable is bound (the value of the variable) can be an object of any
of the types that can be returned by expressions. There are two elements that can be used
to bind variables: `p:variable` and `p:with-param`. The difference is that the value specified
on the `p:with-param` variable is only a default value for the binding; when the template within
which the `p:with-param` element occurs is invoked, parameters may be passed that are used in
place of the default values.

Both `p:variable` and `p:with-param` have a required name attribute, which specifies the
name of the variable. The value of the name attribute is a qualified name.

```html
<p:variable name="count" select="@comments_count" />
<p:variable name="count">There are #{@comments_count} comments</p:variable>
```





### The `p:with` markup

Parses a template with a bounded value.

```html
<p:with
	select = expression>
	<!-- Content: p:with-param*, template -->
</p:with>
```

```html
<p:with select="articles.first().comments.last()">
Last comment: <a href="#{@url}">#{@title}</a>
</p:with>
```





### The `p:decorate` markup

Decorates a content with a template.

```html
<p:decorate
	with = string>
	<!-- Content: p:with-param*, template -->
</p:decorate>
```

The content of the markup is rendered to create the component to decorate, it is then passed
to the decorating template as the `component` variable.

The name of the decorating template is specified with the `with` attribute, and is
interpolated e.g. if "page" is specified the templates "@page.html" or "partials/@page.html"
are used, which ever comes first.

The parameters specified using `with-param`, as well as the attribute of the markup (except `with`)
are made available as variables in the decorating template.

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





### The `p:template` markup

Adds a template.

```html
<p:template
	name = qname>
	<!-- Content: p:with-param*, template -->
</p:template>
```

The `name` attribute defines the name of the template. The content of the markup defines
the template.





### The `p:call-template` markup

Calls a template.

```html
<p:call-template
	name = qname>
	<!-- Content: p:with-param* -->
</p:call-template>
```





### The `p:translate` markup

Translates and interpolates a string.

```html
<p:translate
	native = string>
	<!-- Content: p:with-param* -->
</p:translate>
```

The arguments for the interpolation are provided using the attributes of the markup, or the
 `with-param` construction.

Example:

```html
<p:translate native="Posted on :date by !name">
	<p:with-param name="date"><time datetime="#{@date}" pubdate="pubdate">#{@date.format_date()}</time></p:with-param>
	<p:with-param name="name" select="@user.name" />
</p:translate>
```





### The `p:document:css` markup

CSS assets can be collected and rendered into `LINK` elements with the `p:document:css`
element. The `href` attribute is used to add an asset to the collection. The `weight`
attribute specifies the weight of that asset. If the `weight` attribute is not specified,
the weight of the asset is defaulted to 100. If the `href` attribute is not specified,
the assets are rendered. If a template is specified the collection is passed as `this`,
otherwise the collection is rendered into an HTML string of `LINK` elements.

**Note:** This markup requires the [brickrouge/brickrouge][] package.

```html
<p:document:css
	href = string
	weight = int>
	<!-- Content: p:with-params, template? -->
</p:document:css>
```

Example:

```html
<p:document:css href="/public/page.css" />
<p:document:css href="/public/reset.css" weight="-100" />
<p:document:css />
```

will produce:

```html
<link href="/public/reset.css" type="text/css" rel="stylesheet" />
<link href="/public/page.css" type="text/css" rel="stylesheet" />
```





### The `p:document:js` markup

JavaScript assets can be collected and rendered into `SCRIPT` elements with the `p:document:js`
element. The `href` attribute is used to add an asset to the collection. The `weight`
attribute specifies the weight of that asset. If the `weight` attribute is not specified,
the weight of the asset is defaulted to 100. If the `href` attribute is not specified,
the assets are rendered. If a template is specified the collection is passed as `this`,
otherwise the collection is rendered into an HTML string of `SCRIPT` elements.

**Note:** This markup requires the [brickrouge/brickrouge][] package.

```html
<p:document:js
	href = string
	weight = int>
	<!-- Content: p:with-params, template? -->
</p:document:js>
```

Example:

```html
<p:document:js href="/public/page.js" />
<p:document:js href="/public/reset.js" weight="-100" />
<p:document:js />
```

will produce:

```html
<script src="/public/reset.css" type="text/javascript"></script>
<script src="/public/page.css" type="text/javascript"></script>
```





### The `p:pager` markup

Renders a page element.

**Note:** This markup requires the [brickrouge/brickrouge][] package.

```html
<p:pager
	count = int
	page = int
	limit = int
	with = string
	range = expression
	noarrows = boolean>
	<!-- Content: p:with-param*, template? -->
</p:pager>
```





## Function collection

The functions that can be used by a _Patron_ engine instance are defined in a
[FunctionCollection][] instance, which is used to create the [Engine][] instance.
The `get_functions()` helper function can be used to obtain a shared markup collection. When it
is first created, the `FunctionCollection::alter` event of class [FunctionCollection\AlterEvent][]
is fired. Event hooks may use this event to alter the collection, adding and removing functions.

The following example demonstrates how an event hook may be used to add a `hello` function:

```php
<?php

use Patron\Engine;
use Patron\FunctionCollection;

$app->events->attach(function(FunctionCollection\AlterEvent $event, FunctionCollection $collection) {

	$collection['hello'] = function($name="world") {

		return "Hello $name!";

	};

});
```

The following functions are defined by default:

- `if`: Returns _b_ if _a_ is truthy, _c_ otherwise.
- `or`: Returns _a_ if truthy, _b_ otherwise.
- `not`: Returns negate value.
- `mod`: A mod of two values.
- `bit`: Check is a bit is defined.
- `greater`: Checks that _a_ is greater than _b_.
- `smaller`: Checks that _a_ is smaller than _b_.
- `equals`: Checks that _a_ equals _b_.
- `different`: Checks that _a_ is different than _b_.
- `add`: Adds two value together.
- `minus`: Subtracts a value from another.
- `plus`: Adds two value together.
- `times`: Multiplies a value.
- `by`: Divide a value.
- `split`: Splits a string into an array.
- `joint`: Joins an array into a string.
- `index`: Returns the value at a specified index.
- `first`: Returns the first element, or the first n elements, of an array.
- `to_s`: Converts a value into a string.
- `replace`: Replace a string.
- `markdown`: Transforms a string into HTML using _Markdown_.





### Finding a function

The `find()` method is used to find a function in the collection, it may also check functions
that are defined outside of the collection, such as PHP functions.

```php
<?php

echo $functions->find('boot'); // ICanBoogie\boot
```





### Executing a function

You can used the `find()` method to find a function than use the returned value to call the
function, or you can directly call the function like it is a method of [FunctionCollection][].

```php
<?php

use Patron\FunctionCollection;

$functions = new FunctionCollection([

	'hello' => function($name = "world") {
	 
	    return "Hello $name!";
	 
	 }

]);

echo $functions->hello("Olivier"); // Hello Olivier!
```

The [FunctionNotDefined][] exception is thrown if the function called is not defined.





## Event hooks

- `ICanBoogie\Application::boot`: This event is used to attaches event hooks to
`MarkupCollection::alter` and `FunctionCollection::alter` in order to add the markups and
functions defined in the `patron.markups` and `patron.function` configs.





----------





## Requirements

The package requires PHP 5.4 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/):

```
$ composer require icybee/patron
```

The following packages are required, you might want to check them out:

* [icanboogie/common](https://packagist.org/packages/icanboogie/common)
* [icanboogie/render](https://packagist.org/packages/icanboogie/render)





### Cloning the repository

The package is [available on GitHub](https://github.com/Icybee/Patron), its repository can
be cloned with the following command line:

	$ git clone https://github.com/Icybee/Patron.git





## Documentation

The package is documented as part of the [ICanBoogie][] framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package and its dependencies with the `make doc` command. The documentation is generated in the `build/docs` directory. [ApiGen](http://apigen.org/) is required. The directory can later be cleaned with the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [PHPUnit](https://phpunit.de/) and [Composer](http://getcomposer.org/) need to be globally available to run the suite. The command installs dependencies as required. The `make test-coverage` command runs test suite and also creates an HTML coverage report in "build/coverage". The directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://img.shields.io/travis/Icybee/Patron.svg)](https://travis-ci.org/Icybee/Patron)
[![Code Coverage](https://img.shields.io/coveralls/Icybee/Patron.svg)](https://coveralls.io/r/Icybee/Patron)





## License

This package is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.

[brickrouge/brickrouge]: https://github.com/Brickrouge/Brickrouge

[Engine]: lib/Engine.php
[FunctionCollection]: lib/FunctionCollection.php
[FunctionCollection\AlterEvent]: lib/FunctionCollection/AlterEvent.php
[FunctionNotDefined]: lib/FunctionNotDefined.php
[MarkupCollection]: lib/MarkupCollection.php
[MarkupCollection\AlterEvent]: lib/MarkupCollection/AlterEvent.php
[MarkupNotDefined]: lib/MarkupNotDefined.php
