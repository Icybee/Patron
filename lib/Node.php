<?php

namespace Patron;

abstract class Node
{
	abstract public function __invoke(Engine $engine, $context);
}
