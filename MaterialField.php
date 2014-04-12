<?php
namespace samson\parse;

/**
 * Parser for integrating SamsonCMS materialfield table object creation
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
class MaterialField extends ColumnParser
{
    /**
     * Pointer to parent material
     * @var Material
     */
	protected $material;
	
	/** Pointer to field table object */
	protected $db_field; 	

    protected $isimg;
	/**
	 * Constructor
	 * @param integer 	$idx 		Column index for parsing
	 * @param mixed 	$field		Field table object Name or Identifier	 
	 * @param Material 	$material	Pointer to parent material parser
	 * @param callable 	$parser		External parser routine		
	 */
	public function __construct( $idx, $field, Material & $material, $parser = null, $structure = null)
	{
        $this->isimg = false;
        $newstructure = null;
		// Save connection to material
		$this->material = $material;
		
		// Get field table object
        if (SamsonCMS::field_find( $field ) || SamsonCMS::field_find( $field, 'FieldID' )) {
            if( is_string($field) ) $this->db_field = SamsonCMS::field_find( $field );
            else $this->db_field = SamsonCMS::field_find( $field, 'FieldID' );
        } else {
            $this->db_field = SamsonCMS::field_create($field);
        }
        if (isset($structure)) {
            if (is_string($structure)) {
                $newstructure = SamsonCMS::structure_find($structure);
            } else {
                $newstructure = SamsonCMS::structure_find($structure, 'StructureID');
            }
            $sf = new \samson\activerecord\structurefield(false);
            $sf->StructureID = $newstructure->StructureID;
            $sf->FieldID = $this->db_field->id;
            $sf->Active = 1;
            $sf->save();
        }
		
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
