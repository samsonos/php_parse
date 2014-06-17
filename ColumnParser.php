<?php
namespace samson\parse;
use samson\cms\CMSMaterial;

/**
 * Generic column parser
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
abstract class ColumnParser
{
	/** @var string[] Unique objects collection */
	protected $uniques = array();
	
	/** @var integer Main column index  */
	protected $idx;	
	
	/** @var Last parsed object */
	public $object;

    /** @var mixed Column parsing result */
    public $result;
	
	/** @var callable[] External parser */
	public $parser;

    /**
     * Constuctor
     *
     * @param          $idx
     * @param callable $parser External parser function
     */
	public function __construct( $idx, $parser = null)
	{		
		// Set main column index
		$this->idx = $idx;

		// Check parser routine
		if (isset($parser)) {
			if( is_callable($parser) ) {
                $this->parser = $parser;
            } else { // Trigger error
                e('Parser function not callable!', E_SAMSON_FATAL_ERROR );
            }
		}		
	}	
	
	/**
	 * External column parser callable
     * Must store $this->result field
	 * @param mixed $value Incoming column value
	 * @return mixed Parsing result
	 */
	protected abstract function parser($value);

    /**
     * Initialize column parser
     */
    public function init(){}
	
	/**
	 * Generic object creation unique test
	 * @param mixed $value Main object column value
	 * @return boolean True if value is unique
	 */
	public function isUnique($value)
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
	public function parse(array $data, $row_idx)
	{
        // Get column value
        $value = & $data[ $this->idx ];

		// If main columns exists
		if (isset($value)) {

            // Remove unnecessary spaces
            $value = trim($value);

            // If value is not empty
            if ($value != '') {

                // If external parser is set
                if (isset($this->parser)) {
                    // Call it and save parsed value
                    $value = call_user_func($this->parser, $value);
                }

                // Store parser result
                $this->result = $value;

                // Return success handler function
                return $this->success($data, $row_idx);

            } else { // Empty column error
                return e('Row # ##, Cannot parse column ##, Column value is empty', D_SAMSON_ACTIVERECORD_DEBUG, array($row_idx, $this->idx));
            }

        } else {
			return e('Cannot parse row ##, Main column ## does not exists', E_SAMSON_FATAL_ERROR, array($row_idx,$this->idx));
		}
	}	
	
	/**
	 * Handler for success column parsing
	 * @param array 	$data 		Array of column values
	 * @param integer	$row_idx	Current row index
	 * @return boolean
	 */
	public function success(array $data, $row_idx ){
        return $this->result;
    }
}