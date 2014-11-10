<?php
namespace samson\parse;

use samson\activerecord\dbRelation;

class Excel2
{	
	/** Default timelimit for parser execution */
	const TIME_LIMIT = 600;

    /** Generic parser user */
    public static $user;
	
	/** @var callable[] Collection of external generic handlers for row parsing */
	public $rowParsers = array();

    /** @var callable[] External generic handlers collection for parsers for all columns */
    protected $columnParsers = array();

    /** @var callable[] External handlers for columns validation*/
    protected $columnValidators = array();

    /** @var Material[] Array of material parser objects */
    protected $materialParsers = array();

    /** Number fo row to start parsing from */
	public $from_row;
	
	/** Set parent structure to work with */
	protected $parent_structure;
	
	/** Parsing materials structure tree */
	protected $structure_tree = array();
	
	/** Structure catalog */
	public $catalog = array();
	
	/** Array for material unuiqueness */
	public $uniques = array();

    /** @var iAdapter adapter  */
    public $adapter;
	/**
	 * Add external Material parser object to material parsers collection
	 * @param Material $m Pointer to Material parser object
	 * @return \samson\parse\Excel2 Chaining
	 */
	public function material(Material & $m)
	{
		$this->materialParsers[] = $m;

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
            $cmsnav->Created = date('Y-m-d h:i:s');
            $cmsnav->UserID = self::$user->id;
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
			$this->rowParsers[] = $parser;

		} else { // Trigger error
            return e('Cannot set external row parser ## - it is not callable', E_SAMSON_FATAL_ERROR, $parser);
        }

		return $this;
	}

    /**
     * Set specific external column parser, if no number is passed parser will be used for all columns
     * @param integer $number Column number
     * @param mixed $parser Column parser function
     * @return \samson\parse\Excel2 Chaining
     */
    public function setColumnParser($number, $parser)
    {
        // If existing parser is passed
        if(is_callable($parser)) {
            // Pointer to column validators collection
            $_parser = & $this->columnParsers[ $number ];

            // If this is first time validator is added to this column number
            if (!isset($_parser)) {
                // Create collection
                $_parser = array();
            }

            // Add validator to column validators collection
            $_parser[] = $parser;

        } else { // Trigger error
            return e('Cannot set external column # ## parser ## - it is not callable', E_SAMSON_FATAL_ERROR, array($number, $parser));
        }

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
        if(is_callable($validator)) {
            // Pointer to column validators collection
            $_validator = & $this->columnValidators[ $number ];

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
     * Constructor
     * @param iAdapter $adapter
     * @param int      $from_row
     * @param string   $userName
     */
    public function __construct(iAdapter $adapter, $from_row = 0, $userName = 'Parser')
	{
		$this->from_row = $from_row;
        $this->adapter = $adapter;

        // Try to find user for storing data into tables
        if(!dbQuery('user')->FName($userName, dbRelation::LIKE)->first(self::$user)) {
            // Create new user object
            self::$user = new \samson\activerecord\User(false);
            self::$user->FName = $userName;
            self::$user->save();
        }
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
            //
            SamsonCMS::structure_clear($this->parent_structure);
        }
		
		// display how many rows and column parsed
		//elapsed('Parsing '.$highestRow.' rows, with '.$highestColumnIndex.' columns');	
		
		// array that conteins arrays which contein one row
		$all_rows = array();

		// Iterate rows
		for ($i = $this->from_row; $i < $this->adapter->getRowsCount(); $i++)
		{
			// array that contains all entry of row
			$row = array();
			
			// Iterate columns
			for ($col = 0; $col < $this->adapter->getColumnsCount(); $col++)
			{

				// Read column
				$column_data = $this->adapter->getValue($col, $i);


                // If column external parsers is set
                if (isset($this->columnParsers[$col])) {
                    // Iterate all this columns defined parsers
                    foreach ($this->columnParsers[$col] as $parser) {
                        // Parse column and store result
                        $column_data = call_user_func( $parser, $column_data, $i);
                    }
                }

				// If external column validators is set
				if (isset($this->columnValidators[$col])) {
                    // Iterate all column validators
                    foreach ($this->columnValidators[$col] as $parser) {
                        // If validator returns false - step to next row
                        if (call_user_func($parser, $column_data, $i) === false) {
                            continue 3;
                        }
                    }
				}

				// Add column data to row columns collection
				$row[$col] = $column_data == null ? '' : $column_data;
			}
			
			// If external row parser is specified
			foreach ($this->rowParsers as $parser) {
                // Call and save parser row result
                $row = call_user_func($parser, $row, $i);
            }

            // Add row to final rows collection
			$all_rows[] = $row;
		}	

		// Perform material parsing
		foreach ($this->materialParsers as $mp )
		{
            // Initialize column parser
            $mp->init();

            // Iterate all gathered valid rows
            $size = sizeof($all_rows);
			for ($i = 0; $i < $size; $i++)
			{
                // Get current row columns array
                $row = & $all_rows[$i];

                //if (!dbQuery('material')->MaterialID($row[0])->first())
                {
                    // Try to parse row to get material instance
                    $material = $mp->parse($row, $i);

                    // If we have successfully parsed row
                    if ($material instanceof \samson\activerecord\Material) {
                        if (is_callable($mp->successHandler)) {
                            call_user_func($mp->successHandler, $material, $row);
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
		}
		// Build structure
		SamsonCMS::structure_create( $this->catalog, array( $this->parent_structure ), self::$user );

		return $all_rows;
	}	
}