<?php
namespace samson\parse;

//[PHPCOMPRESSOR(remove,start)]
require( PHP_P.'PHPExcel/PHPExcel.php');
//[PHPCOMPRESSOR(remove,end)]

use PHPExcel_Cell;
use PHPExcel_IOFactory;

class Excel2
{	
	/** Default timelimit for parser execution */
	const TIME_LIMIT = 30;	
	
	/** Collection of external generic handler for row parsing */
	public $row_parser = array();
	
	/** Number fo row to start parsing from */
	public $from_row;
	
	/** File for parsing */
	public $file_name;
	
	/** Columns parsers map */
	protected $parsers_by_column = array();
	
	/** External generic handlers collection for column parsing */
	protected $column_parsers = array();		
	
	/** External handlers for columns validation*/
	protected $column_validators = array();
	
	/** Set parent structure to work with */
	protected $parent_structure;
	
	/** Parsing materials structure tree */
	protected $structure_tree = array();
	
	/** Structure catalog */
	public $catalog = array();
	
	/** Array for material unuiqueness */
	public $uniques = array();
	
	/** Array of material parser objects */
	protected $material_parsers = array();
	
	/**
	 * 
	 * @param Material $m
	 * @return \samson\parse\Excel2
	 */
	public function material( Material & $m )
	{
		$this->material_parsers[] = $m;		
		return $this;
	}


    public function createStructureField ($field)
    {
        $fieldID = dbQuery('\samson\cms\cmsfield')->Name($field)->first();
        $sf = new \samson\activerecord\structurefield(false);
        $sf->FieldID = $fieldID->FieldID;
        $sf->StructureID = $this->parent_structure->StructureID;
        $sf->Active = 1;
        $sf->save();
        return $this;
    }
    /**
     * Set parent structure element to work when building catalog tree
     * @param $name
     * @return \samson\parse\Excel2 Chaining
     */
	public function setStructure( $name )
	{
		// If passed parent structure does not exists
		if( !ifcmsnav( $name, $cmsnav, 'Name') )
		{
			// Create structure
			$cmsnav = new \samson\cms\cmsnav(false);
			$cmsnav->Name = $name;
			$cmsnav->Url = utf8_translit($name);
			$cmsnav->save();
		}
		
		$this->parent_structure = $cmsnav;	
		
		return $this;
	}
	
	public function setRowParser( $parser )
	{
		// If existing parser is passed
		if( is_callable($parser) )
		{			
			// Add generic column parser to parsers collection
			$this->row_parser[] = $parser;
		}
		else elapsed('Cannot set row parser: '.$parser);
		return $this;
	}
	
	/**
	 * Set array of structure tree logic specefying columns numbers
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
     * @param integer $number Column nubmer
     * @return $this
     */
	public function setColumnParser( $parser, $number = null )
	{
		// If existing parser is passed
		if( is_callable($parser) )
		{
			// If column number is specified 
			if( isset($number)) 
			{
				// Add specific column parser to column parsers array
				if( !isset($this->parsers_by_column[ $number ]))$this->parsers_by_column[ $number ] = array( $parser ); 
				else $this->parsers_by_column[ $number ][] = $parser;
			}
			// Add generic column parser to parsers collection
			else $this->column_parsers[] = $parser;
		}
		else elapsed('Cannot set column parser: '.$parser);
		
		return $this;
	}

    /**
     * Set specific external column validator
     * @param integer $number Column number
     * @param mixed $validator Column validator function
     * @return $this
     */
	public function setColumnValidator( $number, $validator )
	{
		// If existing parser is passed
		if( is_callable($validator) )
		{
			// Add specific column parser to column parsers array
			if( !isset($this->column_validators[ $number ])) $this->column_validators[ $number ] = array( $validator );
			else $this->column_validators[ $number ][] = $validator;			
		}
		else elapsed('Cannot set column validator: '.$validator);
		
		return $this;
	}
			
	/** Constructor */
	public function __construct( $file_name, $from_row = 0 )
	{
		$this->file_name = $file_name;
		$this->from_row = $from_row;
	}	
	
	/**
	 * Convert extension of file to extension that need for parser
	 * @param $file_name string name of file that you wanna parse
	 * @return string extension that need function parse_excel
	 */
	private function get_extension($file_name){
	
		// get extention of file, by php built-in function
		$extention = pathinfo($file_name);
		$extention = $extention['extension'];
	
		switch ($extention) {
			case 'xlsx': $extention = 'Excel2007'; break;
			case 'xls': $extention = 'Excel5'; break;
			case 'ods': $extention = 'OOCalc'; break;
			case 'slk': $extention = 'SYLK'; break;
			case 'xml': $extention = 'Excel2003XML'; break;
			case 'gnumeric': $extention = 'Gnumeric'; break;
			default: echo 'This parser read file with extention: xlsx, xls, ods, slk, xml, gnumeric';
		}
		return $extention;
	}

    /**
     * Parse excel file and save each row in array
     * @return array that contains arrays which contain one row
     */
	public function parse($clear = true)
	{		
		set_time_limit( Parse::TIME_LIMIT );
				
		// Clear old parent structure entities
        if ($clear) {
            if( isset($this->parent_structure) ) SamsonCMS::structure_clear( $this->parent_structure );
        }
		//return;
		// Convert extention of file to extension that need for parser
		$expention = $this->get_extension( $this->file_name );
		
		$objReader = PHPExcel_IOFactory::createReader($expention);
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
			// array that conteins all entry of row
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
				/*if ($column_data != '') {

                }*/
				// If external column parser is specified
				foreach ($this->column_parsers as $parser)
				{
					$column_data = call_user_func( $parser, $col, $column_data );
				}

				// If specific column external parser is set
				if( isset($this->column_validators[ $col ]) ) foreach ($this->column_validators[ $col ] as $parser)
				{
					// If validator returns false - step to next row
					if( call_user_func( $parser, $column_data, $i ) === false ) continue 3;
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
			foreach ($all_rows as $row ) 
			{
				$material = $mp->parse( $row, $i );
                if($material instanceof \samson\activerecord\Material)
                {
                    if (is_callable($mp->new_parser)) {
                        call_user_func($mp->new_parser, $material, $row);
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
		SamsonCMS::structure_create( $this->catalog, array( $this->parent_structure ) );

		return $all_rows;
	}	
}