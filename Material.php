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

    /**
     * Add material field
     *
     * @param integer  $idx    Column index to parse
     * @param mixed    $field  Field table object Name or identifier
     * @param callable $parser External column parser
     * @param null     $structure
     *
     * @return \samson\parse\Material Chaining
     */
	public function field( $idx, $field, $parser = null, $structure = null )
	{		
		// Create materialfield table object parser 
		$this->fields[ $idx ] = new MaterialField( $idx, $field, $this, $parser, $structure);
				
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
	public function parser( $name, $url = null, $published = 1, $active = 1, $user_id = 1 )
	{
        $m 				= new \samson\activerecord\material(false);
        $m->Name 		= $name;
        $m->Url 		= $this->url_prefix.utf8_translit( $name );
        $m->Published 	= $published;
        $m->Active 		= $active;
        $m->UserID 		= $user_id;
        $m->save();

		return $m;			
	}

	/** @see \samson\parse\ColumnParser::success() */
	public function success( array $data, $row_idx )
	{
		// Iterate material fields
		foreach ( $this->fields as $f ) 
		{
			if( !$f->parse( $data, $row_idx ) )
			{
				// Error handling
			}
		}		
		
		return $this->object;
	}	
}