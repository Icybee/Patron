<?php

namespace Patron;

use ICanBoogie\I18n;

class TranslateNode extends ExpressionNode
{
	protected function render($expression)
	{
		return I18n\t($expression);
	}
}
