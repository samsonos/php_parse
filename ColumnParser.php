<?php
namespace samson\parse;

/**
 * Generic column parser
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
abstract class ColumnParser
{
	/** Unique objects collection */
	protected $uniques = array();
	
	/** Main column index  */
	protected $idx;	
	
	/** Last parsed object */
	public $object;
	
	/** External parser */
	protected $parser;
	
	/**
	 * Constuctor 
	 * @param integer 	$name_column 	Index of main column to parse
	 * @param callable 	$parser			External parser function	
	 */
	public function __construct( $idx, $parser = null )
	{		
		// Set main column index
		$this->idx = $idx;		
		
		// Check parser routine
		if( isset($parser))
		{
			if( is_callable($parser) ) $this->parser = $parser; 
			else e('Parser function not callable!', E_SAMSON_FATAL_ERROR );
		}		
	}	
	
	/**
	 * Parse column and create dbRecord object
	 * @param mixed $value Column value
	 * @return \samson\activerecord\dbRecord New object
	 */
	protected abstract function parser( $value );
	
	/**
	 * Generic object creation unique test
	 * @param mixed $value Main object column value
	 * @return boolean True if value is unique
	 */
	public function isUnique( $value )
	{
		// If value is unique 
		if( !isset( $this->uniques[ $value ]) ) 
		{
			$this->uniques[ $value ] = true;
			
			return true;
		}
		// Return true
		else false;
	}

	/**
	 * Perform column parsing from data
	 * @param array 	$data 		Array of column values
	 * @param integer	$row_idx	Current row index
	 * @return \samson\activerecord\material Created and saved object
	 */
	public function parse( array $data, $row_idx  )
	{		
		// If main columns exists
		if( isset($data[ $this->idx ]))
		{		
			// Get column value
			$value = $data[ $this->idx ];
			
			// Perform external value parsing
			if( isset($this->parser)) $value = call_user_func( $this->parser, $value );
			
			// If this values passes unique test
			if( $this->isUnique( $value ))
			{
				// Main parsing - create object 
				$this->object = $this->parser( $value );
				
				// If table object succesfully created
				if( $this->object instanceof \samson\activerecord\dbRecord )
				{							
					return $this->success( $data, $row_idx );
				}
				else return e('Cannot parse row ##, Object has not been created!',E_SAMSON_FATAL_ERROR, $row_idx );
			}
		}
		else
		{
			trace($data);
			return e('Cannot parse row ##, Main column ## does not exists', E_SAMSON_FATAL_ERROR, array($row_idx,$this->idx));
		}
	}	
	
	/**
	 * Handler for success column parsing
	 * @param array 	$data 		Array of column values
	 * @param integer	$row_idx	Current row index
	 * @return boolean
	 */
	public function success( array $data, $row_idx ){ return $this->object; }
}