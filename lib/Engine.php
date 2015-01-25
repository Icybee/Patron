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

use ICanBoogie\Debug;
use ICanBoogie\Render;
use ICanBoogie\Render\TemplateName;

define('WDPATRON_DELIMIT_MACROS', false);

class Engine
{
	const PREFIX = 'p:';

	protected $trace_templates = false;

	/**
	 * Expression evaluator.
	 *
	 * @var Evaluator
	 */
	private $evaluator;

	/**
	 * Initializes the {@link $evaluator} property, and a bunch of functions.
	 */
	public function __construct()
	{
		#
		# create context
		#

		$this->contextInit();
		$this->evaluator = new Evaluator($this);
		$this->template_resolver = Render\get_template_resolver();

		#
		# add functions
		#

		$this->functions['to_s'] = function($a)
		{
			if (is_array($a) || (is_object($a) && !method_exists($a, '__toString')))
			{
				return \ICanBoogie\dump($a);
			}

			return (string) $a;
		};

		$this->functions['add'] = function($a,$b)
		{
			return ($a + $b);
		};

		$this->addFunction('try', [ $this, '_get_try' ]);

		#
		# some operations
		#

		$this->addFunction('if', function($a, $b, $c=null) { return $a ? $b : $c; });
		$this->addFunction('or', function($a, $b) { return $a ? $a : $b; });
		$this->addFunction('not', function($a) { return !$a; });
		$this->addFunction('mod', function($a, $b) { return $a % $b; });
		$this->addFunction('bit', function($a, $b) { return (int) $a & (1 << $b); });

		$this->addFunction('greater', function($a, $b) { return ($a > $b); });
		$this->addFunction('smaller', function($a, $b) { return ($a < $b); });
		$this->addFunction('equals', function($a, $b) { return ($a == $b); });
		$this->addFunction('different', function($a, $b) { return ($a != $b); });

		#
		# maths
		#

		$this->addFunction('minus', function($a, $b) { return $a - $b; });
		$this->addFunction('plus', function($a, $b) { return $a + $b; });
		$this->addFunction('times', function($a, $b) { return $a * $b; });
		$this->addFunction('by', function($a, $b) { return $a / $b; });

		#
		#
		#

		$this->addFunction('split', function($a, $b=",") { return explode($b,$a); });
		$this->addFunction('join', function($a, $b=",") { return implode($b,$a); });
		$this->addFunction('index', function() { $a = func_get_args(); $i = array_shift($a); return $a[$i]; });
		$this->addFunction('replace', function($a, $b, $c="") { return str_replace($b, $c, $a); });

		#
		# array (mostly from ruby)
		#

		/**
		 * Returns the first element, or the first n elements, of the array. If the array is empty,
		 * the first form returns nil, and the second form returns an empty array.
		 *
		 * a = [ "q", "r", "s", "t" ]
		 * a.first    // "q"
		 * a.first(1) // ["q"]
		 * a.first(3) // ["q", "r", "s"]
		 *
		 */

		$this->addFunction('first', function($a, $n=null) { $rc = array_slice($a, 0, $n ? $n : 1); return $n === null ? array_shift($rc) : $rc; });
		// TODO-20100507: add the 'last' method

		#
		#
		#

		$this->addFunction('markdown', function($txt) { require_once __DIR__ . '/textmark/textmark.php'; return Markdown($txt); });
	}

	/**
	 * Evaluate an expression relative to a context.
	 *
	 * @param mixed $context
	 * @param string $expression
	 * @param bool $silent
	 */
	public function evaluate($expression, $silent=false, $context=null)
	{
		$evaluator = $this->evaluator;

		return $evaluator($context ?: $this->context, $expression, $silent);
	}







	protected $functions = [];

	public function addFunction($name, $callback)
	{
		#
		# FIXME-20080203: should check overrides
		#

		$this->functions[$name] = $callback;
	}

	public function findFunction($name)
	{
		/*
		// TODO: move to Engine

		$hook = null;

		try
		{
			$hook = Hook::find('patron.functions', $name);
		}
		catch (\Exception $e) { }

		if ($hook)
		{
			return $hook;
		}
		*/

		// /

		#
		#
		#

		if (isset($this->functions[$name]))
		{
			return $this->functions[$name];
		}

		$try = 'ICanBoogie\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}

		$try = 'ICanBoogie\I18n\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}

		$try = 'Patron\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}
	}

	private static $singleton;

	static public function get_singleton()
	{
		if (self::$singleton)
		{
			return self::$singleton;
		}

		$class = get_called_class();

		self::$singleton = $singleton = new $class();

		return $singleton;
	}

	public function _get_try($from, $which, $default=null)
	{
		$form = (array) $from;

		return isset($from[$which]) ? $from[$which] : $default;
	}

	/*
	**

	SYSTEM

	**
	*/

	protected $trace = [];
	protected $errors = [];

	public function trace_enter($a)
	{
		array_unshift($this->trace, $a);
	}

	public function trace_exit()
	{
		array_shift($this->trace);
	}

	public function error($alert, array $args=[])
	{
		if ($alert instanceof \ICanBoogie\Exception\Config)
		{
			$this->errors[] = '<div class="alert alert-error alert-danger">' . $alert->getMessage() . '</div>';

			return;
		}
		else if ($alert instanceof \Exception)
		{
			$alert = class_exists('ICanBoogie\Debug') ? Debug::format_alert($alert) : (string) $alert;
		}
		else
		{
			$alert = \ICanBoogie\format($alert, $args);
		}

		#
		#
		#

		$trace_html = null;

		if ($this->trace)
		{
			$i = count($this->trace);
			$root = $_SERVER['DOCUMENT_ROOT'];
			$root_length = strlen($root);

			foreach ($this->trace as $trace)
			{
				list($which, $message) = $trace;

				if ($which == 'file')
				{
					if (strpos($message, $root_length) === 0)
					{
						$message = substr($message, $root_length);
					}
				}

				$trace_html .= sprintf('#%02d: in %s "%s"', $i--, $which, $message) . '<br />';
			}

			if ($trace_html)
			{
				$trace_html = '<pre>' . $trace_html . '</pre>';
			}
		}

		#
		#
		#

		$this->errors[] = '<div class="alert alert-error alert-danger">' . $alert . $trace_html . '</div>';
	}

	public function handle_exception(\Exception $e)
	{
		if ($e instanceof \ICanBoogie\HTTP\Exception)
		{
			throw $e;
		}
		else if ($e instanceof \ICanBoogie\ActiveRecord\Exception)
		{
			throw $e;
		}

		$this->error($e);
	}

	public function fetchErrors()
	{
		$rc = implode(PHP_EOL, $this->errors);

		$this->errors = [];

		return $rc;
	}

	public function get_file()
	{
		foreach ($this->trace as $trace)
		{
			list($which, $data) = $trace;

			if ($which == 'file')
			{
				return $data;
			}
		}
	}

	public function get_template_dir()
	{
		return dirname($this->get_file());
	}

	/*
	**

	TEMPLATES

	**
	*/

	protected $templates = [];

	public function addTemplate($name, $template)
	{
		if (isset($this->templates[$name]))
		{
			$this->error('The template %name is already defined ! !template', [

				'%name' => $name, '!template' => $template

			]);

			return;
		}

		$this->templates[$name] = $template;
	}

	protected function resolve_template($name)
	{
		$template_resolver = $this->template_resolver;
		$file = $this->get_file();

		if ($file)
		{
			$template_resolver = clone $this->template_resolver;
			$template_resolver->add_path(dirname($file));
		}

		$tries = [];
		$template_pathname = $template_resolver->resolve($name, [ '.patron', '.html' ], $tries);

		if ($template_pathname)
		{
			return $this->create_template_from_file($template_pathname);
		}

		$template_name = TemplateName::from($name);
		$template_pathname = $template_resolver->resolve($template_name->as_partial, [ '.patron', '.html' ], $tries);

		if ($template_pathname)
		{
			return $this->create_template_from_file($template_pathname);
		}

		$template_pathname = $template_resolver->resolve($template_name->as_template, [ '.patron', '.html' ], $tries);

		if ($template_pathname)
		{
			return $this->create_template_from_file($template_pathname);
		}

		throw new TemplateNotFound("Template not found: $name.", $tries);
	}

	protected function create_template_from_file($pathname)
	{
		$content = file_get_contents($pathname);
		$nodes = $this->get_compiled($content);

		return new Template($nodes, [ 'file' => $pathname ]);
	}

	protected function get_template($name)
	{
		if (isset($this->templates[$name]))
		{
			return $this->templates[$name];
		}

		return $this->resolve_template($name);
	}

	public function callTemplate($name, array $args=[])
	{
		$template = $this->get_template($name);

		if (!$template)
		{
			$er = 'Unknown template %name';
			$params = [ '%name' => $name ];

			if ($this->templates)
			{
				$er .= ', available templates: :list';
				$params[':list'] = implode(', ', array_keys($this->templates));
			}

			$this->error($er, $params);

			return;
		}

		$this->trace_enter([ 'template', $name, $template ]);

		if (version_compare(PHP_VERSION, '5.3.4', '>='))
		{
			$this->context['self']['arguments'] = $args;
		}
		else // COMPAT
		{
			$self = $this->context['self'];
			$self['arguments'] = $args;
			$this->context['self'] = $self;
		}

		$rc = $this($template);

		array_shift($this->trace);

		return $rc;
	}

	/*
	**

	CONTEXT

	**
	*/

	public $context;

	protected function contextInit()
	{
		$this->context = new \BlueTihi\Context([ 'self' => null, 'this' => null ]);
	}

	/*
	**

	PUBLISH

	**
	*/

	protected function get_compiled($template)
	{
		static $compiler;

		if ($compiler === null)
		{
			$compiler = new Compiler();
		}

		return $compiler($template);
	}

	public function __invoke($template, $bind=null, array $options=[])
	{
		if (!$template)
		{
			return;
		}

		if ($bind !== null)
		{
			$this->context['this'] = $bind;
		}

		$file = null;

		foreach ($options as $option => $value)
		{
			switch ((string) $option)
			{
				case 'variables':
				{
					foreach ($value as $k => $v)
					{
						$this->context[$k] = $v;
					}
				}
				break;

				case 'file':
				{
					$file = $value;
				}
				break;

				default:
				{
					trigger_error(\ICanBoogie\format('Suspicious option: %option :value', [

						'%option' => $option,
						':value' => $value

					]));
				}
				break;
			}
		}

		if (!($template instanceof Template))
		{
			if (is_array($template) && isset($template['file']))
			{
				$file = $template['file'];

				unset($template['file']);
			}

			if (!is_array($template))
			{
				$template = $this->get_compiled($template);
			}

			$template = new Template($template, [ 'file' => $file ]);
		}

		if ($template->file)
		{
			$this->trace_enter([ 'file', $template->file ]);
		}

		$rc = '';

		foreach ($template as $node)
		{
			if (!($node instanceof Node))
			{
				continue;
			}

			try
			{
				$rc .= $node($this, $this->context);
			}
			catch (\Exception $e)
			{
				if (class_exists('ICanBoogie\Debug'))
				{
					$rc .= Debug::format_alert($e);
				}
				else
				{
					$rc .= $e;
				}
			}

			$rc .= $this->fetchErrors();
		}

		$rc .= $this->fetchErrors();

		#
		#
		#

		if ($file)
		{
			array_shift($this->trace);
		}

		return $rc;
	}

	/*

	#
	# $context_markup is used to keep track of two variables associated with each markup :
	# self and this.
	#
	# 'self' is a reference to the markup itsef, holding its name and the arguments with which
	# it was called, it is also used to store special markup data as for the foreach markup
	#
	# 'this' is a reference to the object of the markup, that being an array, an object or a value
	#
	#

	<p:articles>

		self.range.start
		self.range.limit
		self.range.count

		this = array of Articles

		<p:foreach>

			self.name = foreach
			self.arguments = []
			self.position
			self.key
			self.left

			this = an Article object

		</p:foreach>
	</p:articles>

	*/

	public $context_markup = []; // should be protected
}
