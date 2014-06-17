<?php
namespace samson\parse;

/**
 * Parser for integrating SamsonCMS material table object creation
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
class Material extends ColumnParser
{	
	/** Special url prefix */		
	protected $url_prefix = '';
	
	/** Collection of materialfield table object parsers */
	protected $fields = array();

	/** Structure tree array */
	public $structures = array();


    /** Pass column index as arguments for material structure creation */
	public function structure( $s_1, $s_2 = null, $s_3 = null, $s_4=null ){ $this->structures[] = func_get_args(); return $this; }

    /** Initialize column parser */
    public function init()
    {
        // Iterate material fields
        foreach ( $this->fields as $f )
        {
            // Initialize parser
            $f->init();
        }
    }

    /**
     * Generic add material field parser to material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param callable $parser      External column parser
     * @param mixed    $structure   Structure object or Name or identifier
     * @param string   $description Field description
     * @param int      $type        Field type
     *
     * @return \samson\parse\Material Chaining
     */
	public function field( $idx, $field, $parser = null, $structure = null, $description = '', $type = 0 )
	{		
		// Create materialfield table object parser 
		$this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, $type);
				
		return $this;
	}

    /**
     * Create STRING material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     * @param string   $description Field description
     *
     * @return \samson\parse\Material Chaining
     */
    public function string($idx, $field, $structure = null, $parser = null, $description = '')
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, 0);

        return $this;
    }

    /**
     * Create WYSIWYG material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     *
     * @return \samson\parse\Material Chaining
     */
    public function wysiwyg($idx, $field, $structure = null, $parser = null)
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, '', 8);

        return $this;
    }

    /**
     * Create FILE material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     * @param string   $description Field description
     *
     * @return \samson\parse\Material Chaining
     */
    public function file($idx, $field, $structure = null, $parser = null, $description = '')
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, 1);

        return $this;
    }

    /**
     * Create enumerable material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     * @param string   $description Field description
     *
     * @return \samson\parse\Material Chaining
     */
    public function numeric($idx, $field, $structure = null, $parser = null,  $description = '')
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, 7);

        return $this;
    }

    /**
     * Create DATE material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     * @param string   $description Field description
     *
     * @return \samson\parse\Material Chaining
     */
    public function date($idx, $field, $structure = null, $parser = null,  $description = '')
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, 3);

        return $this;
    }

    /**
     * Create selectable material field parser for material parser
     *
     * @param integer  $idx         Column index to parse
     * @param mixed    $field       Field table object Name or identifier
     * @param string   $value       Field select options with desctriptions
     * @param mixed    $structure   Structure object or Name or identifier
     * @param callable $parser      External column parser
     * @param string   $description Field description
     *
     * @return \samson\parse\Material Chaining
     */
    public function select($idx, $field, $value, $structure = null, $parser = null, $description = '')
    {
        // Create materialfield table object parser
        $this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure, $description, 4, $value );

        return $this;
    }
	
	/**
	 * Set generic url prefix
	 * @param string $url_prefix
	 * @return \samson\parse\Material
	 */
	public function prefix( $url_prefix ){ $this->url_prefix = $url_prefix; return $this; }

    /**
     * Generic material table object parser
     *
     * @param string $name Material name
     * @param null   $url
     * @param int    $published
     * @param int    $active
     * @param int    $user_id
     *
     * @see \samson\parse\ColumnParser::parser()
     * @return \samson\activerecord\material Material table object
     */
	public function parser($name, $url = null, $published = 1, $active = 1, $user_id = null)
	{
        $m 				= new \samson\activerecord\material(false);
        $m->Name 		= $name;
        $m->Url 		= $this->url_prefix.utf8_translit($name);
        $m->Published 	= $published;
        $m->Active 		= $active;
        $m->UserID 		= !isset($user_id) ? Excel2::$user->id : $user_id;
        $m->save();

		return $m;			
	}

	/** @see \samson\parse\ColumnParser::success() */
	public function success(array $data, $row_idx)
	{
		// Iterate material field parsers
		foreach ( $this->fields as $f ) {
			if (!$f->parse($data, $row_idx)) {
				// Error handling
			}
		}		
		
		return $this->result;
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
                    $value = call_user_func($this->parser, $value, );
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

            } else { // Empty column error
                return e('Row # ##, Cannot parse column ##, Column value is empty', D_SAMSON_ACTIVERECORD_DEBUG, array($row_idx, $this->idx));
            }

        } else {
            return e('Cannot parse row ##, Main column ## does not exists', E_SAMSON_FATAL_ERROR, array($row_idx,$this->idx));
        }
    }
}