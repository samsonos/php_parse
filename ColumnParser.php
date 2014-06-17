<?php
namespace samson\parse;
use samson\cms\CMSMaterial;

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
            }
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
     * Initialize column parser
     */
    public function init()
    {

    }
	
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
            if( $value != '') {

                // If external parser is set
                if (isset($this->parser)) {
                    // Call it and save parsed value
                    $value = call_user_func($this->parser, $value);
                }

                // If we have not parsed this value earlier
                if ($this->isUnique($value)) {

                    $this->object = $this->parser($value);

                    if( $this->object instanceof \samson\activerecord\dbRecord ) {

                        return $this->success( $data, $row_idx );

                    } else {
                        return e('Cannot parse row ##, Object has not been created!',E_SAMSON_FATAL_ERROR, $row_idx );
                    }
                }

               /* // If this values passes unique test
                if ($this->hasParent) {
                    if ($this->isUnique($value) && $value != '') {
                        $this->object = $this->parser( $value );
                        if( $this->object instanceof \samson\activerecord\dbRecord )
                        {
                            return $this->success( $data, $row_idx );
                        }
                        else return e('Cannot parse row ##, Object has not been created!',E_SAMSON_FATAL_ERROR, $row_idx );
                    }
                } else if( $value != '') {
                    $material = null;
                    if (dbQuery('\samson\cms\cmsmaterial')->Name($value)->first($material)) {
                        $this->object = $this->parser( $value );
                        $rm = new \samson\activerecord\related_materials(false);
                        $rm->first_material = $material->MaterialID;
                        $rm->second_material = $this->object->id;
                        $rm->save();

                        if (dbQuery('\samson\cms\cmsmaterial')->MaterialID($this->object->id)->first($child)) {
                            $child->Url = $child->Url.'-'.generate_password(4);
                            $child->save();
                        }
                    } else {
                        // Main parsing - create object
                        $this->object = $this->parser( $value );
                    }
                }*/

                // If table object successfully created
                if ($this->object instanceof \samson\activerecord\dbRecord ) {
                    // Call success handler
                    return $this->success($data, $row_idx);
                } else {
                    return e('Cannot parse row ##, Object has not been created!', E_SAMSON_FATAL_ERROR, $row_idx );
                }

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
        return $this->object;
    }
}