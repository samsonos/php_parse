<?php
namespace samson\parse;

/**
 * Parser for integrating SamsonCMS materialfield table object creation
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
class MaterialField extends ColumnParser
{	
	/** Pointer to parent material */
	protected $material;
	
	/** Pointer to field table object */
	protected $db_field; 	
	
	/**
	 * Constructor
	 * @param integer 	$idx 		Column index for parsing
	 * @param mixed 	$field		Field table object Name or Identifier	 
	 * @param Material 	$material	Pointer to parent material parser
	 * @param callable 	$parser		External parser routine		
	 */
	public function __construct( $idx, $field, Material & $material, $parser = null )
	{
		// Save connection to material
		$this->material = $material;
		
		// Get field table object
		if( is_string($field) ) $this->db_field = SamsonCMS::field_find( $field );
		else $this->db_field = SamsonCMS::field_find( $field, 'FieldID' );
		
		// Call parent 
		parent::__construct( $idx, $parser );
	}
	
	/**
	 * Ovveride standart uniqueness test as materialfield objects can dublicate
	 * @see \samson\parse\ColumnParser::isUnique()
	 */
	public function isUnique( $value ){ return true; }
	
	/**
	 * Create materialfield record
	 * @param strind $field_id		Field identifier in field table
	 * @param string $material_id	Material identifier in material table
	 * @param string $value			Field value for materialfield table
	 * @return \samson\activerecord\materialfield MaterialField table object
	 */
	public function parser( $value )
	{
		$mf = new \samson\activerecord\MaterialField(false);
		$mf->FieldID 		= $this->db_field->id;
		$mf->MaterialID 	= $this->material->object->id;
		$mf->Value 			= $value;
		$mf->Active 		= 1;
		$mf->save();
	
		return $mf;
	}
}