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

        // Try to find field record
        if (!SamsonCMS::find('field', $field, $this->db_field)) {
            // Create new field record
            $this->db_field = new \samson\activerecord\field(false);
            $this->db_field->Name = $field;
            $this->db_field->Active = 1;
            $this->db_field->save();
        }

        // Try to find structure
        if (SamsonCMS::find('structure', $structure, $structure)) {
            // Is this field is not connect with this structure already
            $fields = null;
            if(!dbQuery('structurefield')->StructureID($structure->StructureID)->FieldID($this->db_field->id)->exec($fields)){
                // Connect material field to structure
                $sf = new \samson\activerecord\structurefield(false);
                $sf->StructureID = $structure->StructureID;
                $sf->FieldID = $this->db_field->id;
                $sf->Active = 1;
                $sf->save();
            }
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
