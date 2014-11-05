<?php
namespace samson\parse;
use samson\cms\CMSMaterial;

/**
 * Generic column parser
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
abstract class ColumnParser
{
	/** @var integer Main column index  */
	protected $idx;

    /** @var mixed Column parsing result */
    public $result;
	
	/** @var callable External parser */
	public $parser;

    /** @var  callable External success handler */
    public $successHandler;

    /** @var bool Flag for allowing to create materials with empty name */
    public $allowEmptyValues;


    /**
     * Constuctor
     *
     * @param          $idx
     * @param callable $parser External parser function
     */
    public function __construct ($idx, $parser = null, $successHandler = null, $allowEmptyValues = false)
    {
        // Set main column index
        $this->idx = $idx;

        $this->allowEmptyValues = $allowEmptyValues;

        // Check parser routine
        if (isset($parser)) {
            if (is_callable($parser)) {
                $this->parser = $parser;
            } else { // Trigger error
            //e('Parser function not callable!', E_SAMSON_FATAL_ERROR );
            }
        }

        // Check parser routine
        if (isset($successHandler)) {
            if (is_callable($successHandler)) {
                $this->successHandler = $successHandler;
            } else { // Trigger error
                //e('Success function not callable!', E_SAMSON_FATAL_ERROR );
            }
        }
    }

    /**
	 * Internal column parser callable
     * Must store $this->result field
	 * @param mixed $value Incoming column value
	 * @return mixed Parsing result
	 */
    protected abstract function & parser($value);

    /**
     * Initialize column parser
     */
    public function init()
    {

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

        $file = fopen('parser_log.txt', "a");
            // If this isn't wysiwyg field
            if(!is_object($value)){
                // Remove unnecessary spaces
                $value = trim($value);
            }

        // If main columns exists
        if (isset($value) || $this->allowEmptyValues) {

            // Remove unnecessary spaces
            $value = trim($value);

            // If value is not empty
            if ($value != '' || $this->allowEmptyValues) {

                // Store input
                $this->result = $value;

                // If external parser is set
                if (isset($this->parser)) {

                    // Call it and save parsed value
                    $this->result = call_user_func($this->parser, $data, $this);
                } else {

                    // Call generic parser
                    $this->result = $this->parser($this->result);
                }
                // Return success handler function
                return $this->success($data, $row_idx, $value);

            } else { // Empty column error
                $file_string = date("Y-m-d H:i:s").' --- Row '.$row_idx.', Cannot parse column '
                    .$this->idx.', value is empty'.PHP_EOL;
                fwrite($file, $file_string);
                // return e('Row # ##, Cannot parse column ##, Column value is empty', D_SAMSON_ACTIVERECORD_DEBUG, array($row_idx, $this->idx));
            }
        } else {
            $file_string = date("Y-m-d H:i:s").' --- Cannot parse row '.$row_idx.', Main column  '
                .$this->idx.', does not exists'.PHP_EOL;
            fwrite($file, $file_string);
            // return e('Cannot parse row ##, Main column ## does not exists', E_SAMSON_FATAL_ERROR, array($row_idx,$this->idx));
		}

        fclose($file);
	}

    /**
     * Handler for success column parsing
     *
     * @param array   $data     Array of column values
     * @param integer $row_idx  Current row index
     * @param string  $value    Column value
     *
     * @return mixed Parsed result
     */
	public function success(array $data, $row_idx, $value ){
        return $this->result;
    }
}