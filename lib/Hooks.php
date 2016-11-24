<?php

/*
 * This file is part of the Patron package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Patron;

use Brickrouge\Pager;

use ICanBoogie\Application;
use ICanBoogie\Render\TemplateName;

class Hooks
{
	/**
	 * Renders a page element.
	 *
	 * <pre>
	 * <p:pager
	 *     count = int
	 *     page = int
	 *     limit = int
	 *     with = string
	 *     range = expression
	 *     noarrows = boolean>
	 *     <!-- Content: p:with-param*, template? -->
	 * </p:pager>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_pager(array $args, Engine $patron, $template)
	{
		$count = null;
		$limit = null;
		$page =  null;
		$range = null;
		$noarrows = null;
		$with = null;

		extract($args);

		if (!$range)
		{
			if (isset($patron->context['range']))
			{
				$range = $patron->context['range'];
			}
		}

		if ($range)
		{
			$count = $range['count'];
			$limit = $range['limit'];
			$page = isset($range['page']) ? $range['page'] : 0;

			if (isset($range['with']))
			{
				$with = $range['with'];
			}
		}

		$pager = new Pager('div', [

			Pager::T_COUNT => $count,
			Pager::T_LIMIT => $limit,
			Pager::T_POSITION => $page,
			Pager::T_NO_ARROWS => $noarrows,
			Pager::T_WITH => $with

		]);

		return $template ? $patron($template, $pager) : (string) $pager;
	}

	/**
	 * Adds a template.
	 *
	 * <pre>
	 * <p:template
	 *     name = qname>
	 *     <!-- Content: p:with-param*, template -->
	 * </p:template>
	 * </pre>
	 *
	 * The `name` attribute defines the name of the template. The content of the markup defines
	 * the template.
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 */
	static public function markup_template(array $args, Engine $patron, $template)
	{
		$patron->addTemplate(TemplateName::from($args['name'])->as_partial, $template);
	}

	/**
	 * Calls a template.
	 *
	 * <pre>
	 * <p:call-template
	 *     name = qname>
	 *     <!-- Content: p:with-param* -->
	 * </p:call-template>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_call_template(array $args, Engine $patron, $template)
	{
		return $patron->callTemplate(TemplateName::from($args['name'])->as_partial, $args);
	}

	/**
	 * Applies a template to each entries of the provided array.
	 *
	 * <pre>
	 * <p:foreach
	 *     in = expression | this
	 *     as = qname | this>
	 *     <!-- Content: p:with-param*, template -->
	 * </p:foreach>
	 * </pre>
	 *
	 * At each turn the following variables are updated in `self`:
	 *
	 * - `count`: The number of entries.
	 * - `position`: The position of the current entry.
	 * - `left`: The number of entries left.
	 * - `even`: "even" if the position is even, an empty string otherwise.
	 * - `key`: The key of the entry.
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_foreach(array $args, Engine $patron, $template)
	{
		#
		# get entries array from context
		#

		$entries = $args['in'];

		if (!$entries)
		{
			return null;
		}

		if (!is_array($entries) && !is_object($entries))
		{
			$patron->error
			(
				'Invalid source for %param. Source must either be an array or a traversable object. Given: !entries', array
				(
					'%param' => 'in', '!entries' => $entries
				)
			);

			return null;
		}

		#
		# create body from iterations
		#

		$count = count($entries);
		$position = 0;
		$left = $count;
		$even = 'even';
		$key = null;

		$context = [

			'count' => &$count,
			'position' => &$position,
			'left' => &$left,
			'even' => &$even,
			'key' => &$key

		];

		$as = $args['as'];

		$patron->context['self'] = array_merge($patron->context['self'], $context);

		$rc = '';

		foreach ($entries as $key => $entry)
		{
			$position++;
			$left--;
			$even = ($position % 2) ? '' : 'even';

			if ($as)
			{
				$patron->context[$as] = $entry;
			}

			$rc .= $patron($template, $entry);
		}

		return $rc;
	}

	/**
	 * Provides a simple if-then conditionality.
	 *
	 * <pre>
	 * <p:if
	 *     test = expression
	 *     select = expression
	 *     equals = value>
	 *     <!-- Content: p:with-param*, template -->
	 * </p:if>
	 * </pre>
	 *
	 * Either `test` or `select` and an operator (e.g. `equals`) should be defined.
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:if test="@has_title">This article has a title</p:if>
	 * <p:if test="@has_title.not()">This article has no title</p:if>
	 * <p:if select="@comments_count" equals="10">This article has 10 comments</p:if>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 *
	 * @throws \Exception when both `test` and `select` are defined.
	 */
	static public function markup_if(array $args, Engine $patron, $template)
	{
		if (isset($args['test']) && isset($args['select']))
		{
			throw new \Exception("Ambiguous test. Both <q>test</q> and <q>select</q> are defined.");
		}

		if ($args['equals'] !== null)
		{
			$true = $args['select'] == $args['equals'];
		}
		else
		{
			$true = !empty($args['test']);
		}

		#
		# if the evaluation is not empty (0 or ''), we publish the template
		#

		return $true ? $patron($template) : null;
	}

	/**
	 * Selects one among a number of possible alternatives.
	 *
	 * <pre>
	 * <!-- Category: instruction -->
	 * <p:choose>
	 *     <!-- Content: (p:when+, p:otherwise?) -->
	 * </p:choose>
	 *
	 * <p:when
	 *     test = boolean-expression>
	 *     <!-- Content: template -->
	 * </p:when>
	 *
	 * <p:otherwise>
	 *     <!-- Content: template -->
	 * </p:otherwise>
	 * </pre>
	 *
	 * It consists of a sequence of `p:when` elements followed by an optional `p:otherwise`
	 * element. Each `p:when` element has a single attribute, test, which specifies an expression.
	 * The content of the `p:when` and `p:otherwise` elements is a template. When an `p:choose`
	 * element is processed, each of the `p:when` elements is tested in turn, by evaluating the
	 * expression and converting the resulting object to a boolean as if by a call to the boolean
	 * function. The content of the first, and only the first, `p:when` element whose test is true
	 * is instantiated. If no `p:when` is true, the content of the `p:otherwise` element is
	 * instantiated. If no `p:when` element is true, and no `p:otherwise` element is present,
	 * nothing is created.
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_choose(array $args, Engine $patron, $template)
	{
		$otherwise = null;

		#
		# handle 'when' children as they are defined.
		# if we find an 'otherwise' we keep it for later
		#

		foreach ($template as $node)
		{
			$name = $node->name;

			if ($name == 'otherwise')
			{
				$otherwise = $node;

				continue;
			}

			if ($name != 'when')
			{
				$patron->error('Unexpected child: :node', [ ':node' => $node ]);

				return null;
			}

			$value = $patron->evaluate($node->args['test'], true);

			if ($value)
			{
				return $patron($node->nodes);
			}
		}

		#
		# otherwise
		#

		if (!$otherwise)
		{
			return null;
		}

		return $patron($otherwise->nodes);
	}

	/**
	 * Binds a name to a value.
	 *
	 * <pre>
	 * <!-- Category: top-level-element -->
	 * <!-- Category: instruction -->
	 * <p:variable
	 *     name = qname
	 *     select = expression>
	 *     <!-- Content: p:with-param*, template? -->
	 * </p:variable>
	 *
	 * <!-- Category: top-level-element -->
	 * <p:param
	 *     name = qname
	 *     select = expression>
	 *     <!-- Content: template? -->
	 * </p:param>
	 * </pre>
	 *
	 * The value to which a variable is bound (the value of the variable) can be an object of any
	 * of the types that can be returned by expressions. There are two elements that can be used
	 * to bind variables: `p:variable` and `p:with-param`. The difference is that the value
	 * specified on the `p:with-param` variable is only a default value for the binding; when
	 * the template within which the `p:with-param` element occurs is invoked, parameters may
	 * be passed that are used in place of the default values.
	 *
	 * Both `p:variable` and `p:with-param` have a required name attribute, which specifies the
	 * name of the variable. The value of the name attribute is a qualified name.
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:variable name="count" select="@comments_count" />
	 * <p:variable name="count">There are #{@comments_count} comments</p:variable>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_variable(array $args, Engine $patron, $template)
	{
		$select = $args['select'];

		if ($select && $template)
		{
			$patron->error('Ambiguous selection');

			return null;
		}
		else if ($select)
		{
			$value = $select;
		}
		else
		{
			$value = $patron($template);
		}

		$name = $args['name'];

		$patron->context[$name] = $value;
	}

	/**
	 * Parses a template with a bounded value.
	 *
	 * <pre>
	 * <p:with
	 *     select = expression>
	 *     <!-- Content: p:with-param*, template -->
	 * </p:with>
	 * </pre>
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:with select="articles.first().comments.last()">
	 * Last comment: <a href="#{@url}">#{@title}</a>
	 * </p:with>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_with(array $args, Engine $patron, $template)
	{
		if ($template === null)
		{
			$patron->error('A template is required.');

			return null;
		}

		$select = $args['select'];

		return $patron($template, $select);
	}

	/**
	 * Translates and interpolates a string.
	 *
	 * <pre>
	 * <p:translate
	 *     native = string>
	 *     <!-- Content: p:with-param* -->
	 * </p:translate>
	 * </pre>
	 *
	 * The arguments for the interpolation are provided using the attributes of the markup, or the
	 *  `with-param` construction.
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:translate native="Posted on :date by !name">
	 *     <p:with-param name="date"><time datetime="#{@date}" pubdate="pubdate">#{@date.format_date()}</time></p:with-param>
	 *     <p:with-param name="name" select="@user.name" />
	 * </p:translate>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $patron
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_translate(array $args, Engine $patron, $template)
	{
		$native = $args['native'];

		return call_user_func('ICanBoogie\I18n\t', $native, $args);
	}

	/**
	 * Decorates a content with a template.
	 *
	 * <pre>
	 * <p:decorate
	 *     with = string>
	 *     <!-- Content: p:with-param*, template -->
	 * </p:decorate>
	 * </pre>
	 *
	 * The content of the markup is rendered to create the component to decorate, it is then passed
	 * to the decorating template as the `component` variable.
	 *
	 * The name of the decorating template is specified with the `with` attribute, and is
	 * interpolated e.g. if "page" is specified the template name "@page" is used; if "admin/page"
	 * is specified the template name "admin/@page" is used.
	 *
	 * The parameters specified using `with-param` are all turned into variables.
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:decorate with="page">
	 *     <p:page:content id="body" />
	 * </p:decorate>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $engine
	 * @param mixed $template
	 *
	 * @return string
	 */
	static public function markup_decorate(array $args, Engine $engine, $template)
	{
		$template_name = $args['with'];

		$rendered_template = $engine($template);

		unset($args['with']);

		$engine->context['component'] = $rendered_template;

		foreach ($args as $name => $value)
		{
			$engine->context[$name] = $value;
		}

		return $engine->callTemplate(TemplateName::from($template_name)->as_layout, $args);
	}

	/*
	 * Brickrouge
	 */

	/**
	 * CSS assets can be collected and rendered into `LINK` elements with the `p:document:css`
	 * element. The `href` attribute is used to add an asset to the collection. The `weight`
	 * attribute specifies the weight of that asset. If the `weight` attribute is not specified,
	 * the weight of the asset is defaulted to 100. If the `href` attribute is not specified,
	 * the assets are rendered. If a template is specified the collection is passed as `this`,
	 * otherwise the collection is rendered into an HTML string of `LINK` elements.
	 *
	 * <pre>
	 * <p:document:css
	 *     href = string
	 *     weight = int>
	 *     <!-- Content: p:with-params, template? -->
	 * </p:document:css>
	 * </pre>
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:document:css href="/public/page.css" />
	 * <p:document:css href="/public/reset.css" weight="-100" />
	 * <p:document:css />
	 * </pre>
	 *
	 * will produce:
	 *
	 * <pre>
	 * <link href="/public/reset.css" type="text/css" rel="stylesheet" />
	 * <link href="/public/page.css" type="text/css" rel="stylesheet" />
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $engine
	 * @param mixed $template
	 *
	 * @return void|string
	 */
	static public function markup_document_css(array $args, Engine $engine, $template)
	{
		$document = \Brickrouge\get_document();

		if (isset($args['href']))
		{
			$document->css->add($args['href'], $args['weight'], dirname($engine->get_file()));

			return null;
		}

		return $template ? $engine($template, $document->css) : (string) $document->css;
	}

	/**
	 * JavaScript assets can be collected and rendered into `SCRIPT` elements with the `p:document:js`
	 * element. The `href` attribute is used to add an asset to the collection. The `weight`
	 * attribute specifies the weight of that asset. If the `weight` attribute is not specified,
	 * the weight of the asset is defaulted to 100. If the `href` attribute is not specified,
	 * the assets are rendered. If a template is specified the collection is passed as `this`,
	 * otherwise the collection is rendered into an HTML string of `SCRIPT` elements.
	 *
	 * <pre>
	 * <p:document:js
	 *     href = string
	 *     weight = int>
	 *     <!-- Content: p:with-params, template? -->
	 * </p:document:js>
	 * </pre>
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:document:js href="/public/page.js" />
	 * <p:document:js href="/public/reset.js" weight="-100" />
	 * <p:document:js />
	 * </pre>
	 *
	 * will produce:
	 *
	 * <pre>
	 * <script src="/public/reset.css" type="text/javascript"></script>
	 * <script src="/public/page.css" type="text/javascript"></script>
	 * </pre>
	 *
	 * @param array $args
	 * @param Engine $engine
	 * @param mixed $template
	 *
	 * @return void|string
	 */
	static public function markup_document_js(array $args, Engine $engine, $template)
	{
		$document = \Brickrouge\get_document();

		if (isset($args['href']))
		{
			$document->js->add($args['href'], $args['weight'], dirname($engine->get_file()));

			return null;
		}

		return $template ? $engine($template, $document->js) : (string) $document->js;
	}

	/*
	 * ICanBoogie
	 */

	/**
	 * Synthesizes the "patron.markups" config.
	 *
	 * @param array $fragments
	 *
	 * @return array
	 */
	static public function synthesize_markups_config(array $fragments)
	{
		$markups = [];

		foreach ($fragments as $path => $fragment)
		{
			if (empty($fragment['patron.markups']))
			{
				continue;
			}

			$markups = array_merge($markups, $fragment['patron.markups']);
		}

		return $markups;
	}

	/**
	 * Synthesizes the "patron.functions" config.
	 *
	 * @param array $fragments
	 *
	 * @return array
	 */
	static public function synthesize_functions_config(array $fragments)
	{
		$functions = [];

		foreach ($fragments as $path => $fragment)
		{
			if (empty($fragment['patron.functions']))
			{
				continue;
			}

			$functions = array_merge($functions, $fragment['patron.functions']);
		}

		return $functions;
	}

	/**
	 * Attaches event hooks to `MarkupCollection::alter` and `FunctionCollection::alter` in order
	 * to add the markups and functions defined in the `patron.markups` and `patron.function`
	 * configs.
	 *
	 * @param Application\BootEvent $event
	 * @param Application $app
	 */
	static public function on_core_boot(Application\BootEvent $event, Application $app)
	{
		$app->events->attach(function(MarkupCollection\AlterEvent $event, MarkupCollection $markups) use ($app) {

			foreach((array) $app->configs['patron.markups'] as $name => $definition)
			{
				$markups[$name] = $definition + [ 1 => [ ] ];
			}

		});

		$app->events->attach(function(FunctionCollection\AlterEvent $event, FunctionCollection $markups) use ($app) {

			foreach((array) $app->configs['patron.functions'] as $name => $definition)
			{
				$markups[$name] = $definition;
			}

		});
	}
}
