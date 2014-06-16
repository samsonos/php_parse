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

    /** @var \samson\activerecord\structure Pointer to parent structure */
    protected $parentStructure;

    /** @var string Field name */
    protected $name;

    /** @var string Field description */
    protected $description;

    /**
     * Constructor
     *
     * @param integer  $idx         Column index for parsing
     * @param mixed    $name        Field Name or object
     * @param Material $material    Pointer to parent material parser
     * @param callable $parser      External parser routine
     * @param null     $structure   Structure name or object
     * @param string   $description Field description
     */
	public function __construct( $idx, $name, Material & $material, $parser = null, $structure = null, $description = '')
	{
        // WTF?
        $this->isimg = false;

		// Save connection to material
		$this->material = $material;

        // Save all passed data
        $this->entity = $name;
        $this->description = $description;
        $this->parentStructure = $structure;
		
		// Call parent 
		parent::__construct( $idx, $parser );
	}

    /** Override generic column parser initialization */
    public function init()
    {
        // Try to find field record
        if (!SamsonCMS::find('field', $this->name, $this->db_field)) {
            // Create new field record
            $this->db_field = new \samson\activerecord\field(false);
            $this->db_field->Name = $this->name;
            $this->db_field->Active = 1;
            $this->db_field->save();
        }

        // Try to find structure
        if (SamsonCMS::find('structure', $this->parentStructure, $this->parentStructure)) {
            // Is this field is not connect with this structure already
            $fields = null;
            if(!dbQuery('structurefield')->StructureID($this->parentStructure->StructureID)->FieldID($this->db_field->id)->exec($fields)){
                // Connect material field to structure
                $sf = new \samson\activerecord\structurefield(false);
                $sf->StructureID = $this->parentStructure->StructureID;
                $sf->FieldID = $this->db_field->id;
                $sf->Active = 1;
                $sf->save();
            }
        }

        parent::init();
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
