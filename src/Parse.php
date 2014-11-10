<?php
namespace samson\parse;

class Parse extends \samson\core\CompressableExternalModule
{
	/** Default timelimit for parser execution */
	const TIME_LIMIT = 1500;

	/** Module identifier */
	public $id = 'samsonparser';
	
	/** Dependencies */
	public $requirements = array( 'activerecord', 'cmsapi');
}