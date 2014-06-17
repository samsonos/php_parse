<?php
namespace samson\parse;

//[PHPCOMPRESSOR(remove,start)]
require( __SAMSON_VENDOR_PATH.'phpoffice/PHPExcel/Classes/PHPExcel.php');
//[PHPCOMPRESSOR(remove,end)]

use PHPExcel_Cell;
use PHPExcel_IOFactory;
use samson\activerecord\dbRelation;

class Excel2
{	
	/** Default timelimit for parser execution */
	const TIME_LIMIT = 300;
	
	/** @var callable[] Collection of external generic handlers for row parsing */
	public $row_parser = array();

    /** @var callable[] External generic handlers collection for parsers for all columns */
    protected $column_parsers = array();

    /** Number fo row to start parsing from */
	public $from_row;
	
	/** File for parsing */
	public $file_name;
	
	/** Columns parsers map */
	protected $parsers_by_column = array();
	
	/** @var callable[] External handlers for columns validation*/
	protected $column_validators = array();
	
	/** Set parent structure to work with */
	protected $parent_structure;
	
	/** Parsing materials structure tree */
	protected $structure_tree = array();
	
	/** Structure catalog */
	public $catalog = array();
	
	/** Array for material unuiqueness */
	public $uniques = array();

    /** Generic parser user */
    public $user;
	
	/** @var Material[] Array of material parser objects */
	protected $material_parsers = array();
	
	/**
	 * Add external Material parser object to material parsers collection
	 * @param Material $m Pointer to Material parser object
	 * @return \samson\parse\Excel2 Chaining
	 */
	public function material(Material & $m)
	{
		$this->material_parsers[] = $m;

		return $this;
	}

    /**
     * Set parent structure element to work when building catalog tree
     * @param mixed $name Pointer to structure object or its name for searching/creating
     * @return \samson\parse\Excel2 Chaining
     */
	public function setStructure($name)
	{
		// If passed parent structure does not exists
		if (!ifcmsnav($name, $cmsnav, 'Name')) {
			// Create structure
			$cmsnav = new \samson\cms\cmsnav(false);
			$cmsnav->Name = $name;
			$cmsnav->Url = utf8_translit($name);
            $cmsnav->Active = 1;
            $cmsnav->UserID = $this->user->id;
			$cmsnav->save();
		}

        // Store pointer to parent structure
		$this->parent_structure = & $cmsnav;
		
		return $this;
	}

    /**
     * Add row parser to row parsers collection
     * @param callable $parser External row parser
     * @return Excel2 Chaining
     */
    public function setRowParser( $parser )
	{
		// If existing parser is passed
		if (is_callable($parser)) {
			// Add generic column parser to parsers collection
			$this->row_parser[] = $parser;

		} else { // Trigger error
            return e('Cannot set external row parser ## - it is not callable', E_SAMSON_FATAL_ERROR, $parser);
        }

		return $this;
	}
	
	/**
	 * Set array of structure tree logic specifying columns numbers
	 * Function accepts as much arguments as column array definition,
	 * this columns values will be used to build catalog of materials
	 *  
	 * @return \samson\parse\Excel2 Chaining
	 */
	public function setStructureColumns()
	{
		// Iterate passed columns number and gather them into array
		$this->structure_tree[] = func_get_args();	
		
		return $this;
	}

    /**
     * Set specific external column parser, if no number is passed parser will be used for all columns
     * @param mixed $parser Column parser function
     * @param integer $number Column number
     * @return \samson\parse\Excel2 Chaining
     */
	public function setColumnParser( $parser, $number = null )
	{
		// If existing parser is passed
		if( is_callable($parser) )
		{
			// If column number is specified 
			if (isset($number)) {
				// Add specific column parser to column parsers array
				if( !isset($this->parsers_by_column[ $number ]))$this->parsers_by_column[ $number ] = array( $parser ); 
				else $this->parsers_by_column[ $number ][] = $parser;

			} else { // Add generic column parser to parsers collection
                $this->column_parsers[] = $parser;
            }
		}
		else elapsed('Cannot set column parser: '.$parser);
		
		return $this;
	}

    /**
     * Add external column validator to column validators collection
     * @param integer   $number     Column number
     * @param callable  $validator  External column validator callable
     * @return \samson\parse\Excel2 Chaining
     */
	public function setColumnValidator($number, $validator)
	{
		// If existing parser is passed
		if( is_callable($validator) )
		{
            // Pointer to column validators collection
            $_validator = & $this->column_validators[ $number ];

			// If this is first time validator is added to this column number
			if (!isset($_validator)) {
                // Create collection
                $_validator = array();
            }

            // Add validator to column validators collection
            $_validator[] = $validator;

		} else { // Trigger error
            return e('Cannot set external column # ## validator ## - it is not callable', E_SAMSON_FATAL_ERROR, array($number, $validator));
        }
		
		return $this;
	}

    /**
     * Constructor
     * @param string $file_name Path to file
     * @param int    $from_row  Starting row
     * @param string $userName  Default parser user name
     */
    public function __construct( $file_name, $from_row = 0, $userName = 'Parser')
	{
		$this->file_name = $file_name;
		$this->from_row = $from_row;

        // Try to find user for storing data into tables
        if(!dbQuery('user')->FName($userName, dbRelation::LIKE)->first($this->user)) {
            // Create new user object
            $this->user = new \samson\activerecord\User(false);
            $this->user->FName = $userName;
            $this->user->save();
        }
	}	
	
	/**
	 * Convert extension of file to extension that need for parser
	 * @param $file_name string name of file that you wanna parse
	 * @return string extension that need function parse_excel
	 */
	private function get_extension($file_name){
	
		// get extension of file, by php built-in function
		$extension = pathinfo($file_name);
        $extension = $extension['extension'];
	
		switch ($extension) {
			case 'xlsx': $extension = 'Excel2007'; break;
			case 'xls': $extension = 'Excel5'; break;
			case 'ods': $extension = 'OOCalc'; break;
			case 'slk': $extension = 'SYLK'; break;
			case 'xml': $extension = 'Excel2003XML'; break;
			case 'gnumeric': $extension = 'Gnumeric'; break;
			default: echo 'This parser read file with extention: xlsx, xls, ods, slk, xml, gnumeric';
		}
		return $extension;
	}

    /**
     * Parse excel file and save each row in array
     *
     * @param bool $clear Flag for automatic parent structure clearing
     *
     * @return array that contains arrays which contain one row
     */
	public function parse($clear = false)
	{		
		set_time_limit( Parse::TIME_LIMIT );
				
		// Clear old parent structure entities if necessary
        if ( $clear && isset($this->parent_structure)) {
            SamsonCMS::structure_clear($this->parent_structure);
        }

		// Convert extension of file to extension that need for parser
		$extension = $this->get_extension($this->file_name);
		
		$objReader = PHPExcel_IOFactory::createReader($extension);
		$objReader->setReadDataOnly(false);
		
		$objPHPExcel = $objReader->load($this->file_name);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		
		// Get rows count
		$highestRow = $objWorksheet->getHighestDataRow();
		
		// Get columns count
		$highestColumn = $objWorksheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		
		// display how many rows and column parsed
		//elapsed('Parsing '.$highestRow.' rows, with '.$highestColumnIndex.' columns');	
		
		// array that conteins arrays which contein one row
		$all_rows = array();

        // Get all merged cells
        $mergedCellsRange = $objWorksheet->getMergeCells();
		
		// Iterate rows
		for ($i = $this->from_row; $i <= $highestRow; $i++)
		{
			// array that contains all entry of row
			$row = array();
			
			// Iterate columns
			for ($col = 0; $col < $highestColumnIndex; $col++)
			{
                // Get current cell
                $cell = $objWorksheet->getCellByColumnAndRow($col, $i);

                // Find if this is cell is merged with others
                foreach($mergedCellsRange as $currMergedRange)
                {
                    if($cell->isInRange($currMergedRange))
                    {
                        $currMergedCellsArray = PHPExcel_Cell::splitRange($currMergedRange);
                        $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                        break;
                    }
                }

				// Read column
				$column_data = $cell->getValue();

				// If external column parser is specified
				foreach ($this->column_parsers as $parser) {
					$column_data = call_user_func($parser, $col, $column_data );
				}

				// If external column validators is set
				if (isset($this->column_validators[ $col ])) {
                    // Iterate all colum validators
                    foreach ($this->column_validators[ $col ] as $parser) {
                        // If validator returns false - step to next row
                        if (call_user_func( $parser, $column_data, $i ) === false) {
                            continue 3;
                        }
                    }
				}

				// If specific column external parser is set
				if( isset($this->parsers_by_column[ $col ]) ) foreach ($this->parsers_by_column[ $col ] as $parser)
				{
					$column_data = call_user_func( $parser, $column_data );
				}

				// Add column data to collection
                if ($column_data != null && $column_data != '') {
                    $row[$col] = $column_data;
                } else {
                    $row[$col] = '';
                }
				//$row[ $col ] = $column_data == null ? '' : $column_data;
			}
			
			// If external column parser is specified
			foreach ($this->row_parser as $parser ) if( is_callable( $parser )) call_user_func($parser, $row, $i );			
			
			$all_rows[] = $row;
		}	

		// Perform material parsing
		foreach ($this->material_parsers as $mp )
		{
            // Initialize column parser
            $mp->init();

            // Iterate all gathered valid rows
			foreach ($all_rows as $row ) 
			{
				$material = $mp->parse( $row, $i );
                if($material instanceof \samson\activerecord\Material)
                {
                    if (is_callable($mp->parser)) {
                        call_user_func($mp->parser, $material, $row);
                    }
                }

				// Iterate defined structure trees
				if(isset($material)) foreach ( $mp->structures as $tree )
				{
					// Create correct multidimensional array structure using eval
					$catalog_eval = '$this->catalog';
					foreach ( $tree as $column )
					{
						// If desired column exists - use it's value
						if( isset($row[ $column ]) ) {
                            $column = addslashes($row[ $column ]);
                        }
                        if (!empty($column)) {
                            $catalog_eval.= '["'.$column.'"]';
                        }
                        //trace($column);
					}
					$catalog_eval .= '[] = $material;';
                    //trace($catalog_eval);

                    eval($catalog_eval);
				}
			}
		}
		// Build structure
		SamsonCMS::structure_create( $this->catalog, array( $this->parent_structure ), $this->user );

		return $all_rows;
	}	
}