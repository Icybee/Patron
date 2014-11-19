# Patron [![Build Status](https://travis-ci.org/Icybee/Patron.svg?branch=1.3)](https://travis-ci.org/Icybee/Patron)

__Patron__ is a template engine for PHP5.4+. It facilitates a mangeable way to seperate application
logic and content from its presentation. Templates are usually written in HTML and include keywords
that are replaced as the template is parsed, and special markups that control the logic of the
template or fetch data.

A typical example:

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

## Features

* The markup set is easily extensible.
* Blocks have a scope, similar to `this` in javascript.
* Easy to translate using the `#{t:String to translate}` notation.




## Variables

Variables are outputted with the `#{<expression>}` notation, where `<expression>` is an
expression. They are escaped unless the `=` modifier is used:

```html
#{@title}
#{@title.shuffle()}   <!-- `title` is passed to str_shuffle() -->
#{@title=}            <!-- `title` is not escaped -->
```





## Markups

The package defines the following markups, that can be used within templates.





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

#### Example

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
    <!-- Content: p:with-param*, template -->
</p:foreach>
```

At each turn the following variables are updated in `self`:

- `count`: The number of entries.
- `position`: The position of the current entry.
- `left`: The number of entries left.
- `even`: "even" if the position is even, an empty string otherwise.
- `key`: The key of the entry.





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

#### Example

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

#### Example

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





### Cloning the repository

The package is [available on GitHub](https://github.com/Icybee/Patron), its repository can
be cloned with the following command line:

	$ git clone https://github.com/Icybee/Patron.git





## Documentation

You can generate the documentation for the package and its dependencies with the `make doc`
command. The documentation is generated in the `docs` directory. [ApiGen](http://apigen.org/) is
required. The directory can later be cleaned with the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all dependencies required to run the suite. You can later
clean the directory with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://travis-ci.org/Icybee/Patron.svg?branch=1.3)](https://travis-ci.org/Icybee/Patron)





## License

This package is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.

[brickrouge/brickrouge]: https://github.com/Brickrouge/Brickrouge
