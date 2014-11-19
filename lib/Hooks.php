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
		$patron->addTemplate($args['name'], $template);
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
		return $patron->callTemplate($args['name'], $args);
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
			return;
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

			return;
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
	 * @throws Exception when both `test` and `select` are defined.
	 */
	static public function markup_if(array $args, Engine $patron, $template)
	{
		if (isset($args['test']) && isset($args['select']))
		{
			throw new Exception("Ambiguous test. Both <q>test</q> and <q>select</q> are defined.");
		}

		$true = false;

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

		if ($true)
		{
			return $patron($template);
		}
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
				return $patron->error('Unexpected child: :node', [ ':node' => $node ]);
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
			return;
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
	 */
	static public function markup_variable(array $args, Engine $patron, $template)
	{
		$select = $args['select'];

		if ($select && $template)
		{
			return $patron->error('Ambiguous selection');
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
	 */
	static public function markup_with(array $args, Engine $patron, $template)
	{
		if ($template === null)
		{
			return $patron->error('A template is required.');
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
	 * interpolated e.g. if "page" is specified the templates "@page.html" or "partials/@page.html"
	 * are used, which ever comes first.
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

		return $engine->callTemplate('@' . $template_name, $args);
	}
}
