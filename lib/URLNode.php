<?php

namespace Patron;

class URLNode extends ExpressionNode
{
	protected function render($expression)
	{
		$app = \ICanBoogie\app();

		if (isset($app->routes[$expression]))
		{
			$route = $app->routes[$expression];

			return $route->url;
		}

		return $app->site->resolve_view_url($expression);
	}
}
