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

    /** @var bool Flag if this field is an image */
    protected $isimg;

    /**
     * Constructor
     *
     * @param integer  $idx      Column index for parsing
     * @param mixed    $field    Field table object Name or Identifier
     * @param Material $material Pointer to parent material parser
     * @param callable $parser   External parser routine
     * @param null     $structure
     */
	public function __construct( $idx, $field, Material & $material, $parser = null, $structure = null)
	{
        $this->isimg = false;

		// Save connection to material
		$this->material = $material;
		
		// Get field table object
        if (SamsonCMS::field_find( $field ) || SamsonCMS::field_find( $field, 'FieldID' )) {
            if( is_string($field) ) $this->db_field = SamsonCMS::field_find( $field );
            else $this->db_field = SamsonCMS::field_find( $field, 'FieldID' );
        } else {
            $this->db_field = SamsonCMS::field_create($field);
        }

        // If parent structure is passed
        if (isset($structure)) {
            /** @var \samson\cms\CMSNav $newstructure */
            $newStructure = null;
            // Try to find structure by name
            if (is_string($structure)) {
                $newStructure = SamsonCMS::structure_find($structure);
            } elseif (is_int($structure)) { // Try to find by id
                $newStructure = SamsonCMS::structure_find($structure, 'StructureID');
            } elseif (is_object($structure)) {

            }

            // Connect material field to structure
            $sf = new \samson\activerecord\structurefield(false);
            $sf->StructureID = $newStructure->StructureID;
            $sf->FieldID = $this->db_field->id;
            $sf->Active = 1;
            $sf->save();
        }
		
		// Call parent 
		parent::__construct( $idx, $parser );
	}
	
	/**
	 * Override standard uniqueness test as materialfield objects can duplicate
	 * @see \samson\parse\ColumnParser::isUnique()
	 */
	public function isUnique( $value ){ return true; }

    /**
     * Create materialfield record
     *
     * @param string $value Field value for materialfield table
     *
     * @internal param \samson\parse\strind $field_id Field identifier in field table
     * @internal param string $material_id Material identifier in material table
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
