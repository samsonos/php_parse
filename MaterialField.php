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

    /** @var \samson\activerecord\structure Pointer to parent structure */
    protected $parentStructure;

    /** @var string Field name */
    protected $name;

    /** @var  Default field value */
    protected $defaultValue;

    /** @var  Field type */
    protected $type;

    /** @var string Field description */
    protected $description;

    /** @var string MaterialField locale */
    protected $locale;

    /**
     * Constructor
     *
     * @param integer  $idx         Column index for parsing
     * @param mixed    $name        Field Name or object
     * @param Material $material    Pointer to parent material parser
     * @param callable $parser      External parser routine
     * @param null     $structure   Structure name or object
     * @param string   $description Field description
     * @param int      $type        Field type
     * @param string   $value       Field default value
     * @param string   $locale      MaterialField locale, if null materialfield is not localized
     */
	public function __construct( $idx, $name, Material & $material, $parser = null, $structure = null, $description = '', $type = 0, $value = null, $locale = null)
	{
		// Save connection to material
		$this->material = $material;

        // Save all passed data
        $this->name = $name;
        $this->description = $description;
        $this->parentStructure = $structure;
        $this->type = $type;
        $this->defaultValue = $value;
        $this->locale = $locale;
		
		// Call parent 
		parent::__construct($idx, $parser);
    }

    /** Override generic column parser initialization */
    public function init()
    {
        // This is very important
        if (!isset($this->name{0})) {
           return e('Cannot create MaterialField - no name is passed', E_SAMSON_FATAL_ERROR);
        }

        // Try to find field record
        if (!SamsonCMS::find('field', $this->name, $this->db_field)) {
            // Create new field record
            $this->db_field = new \samson\activerecord\field(false);
            $this->db_field->Name = $this->name;
            $this->db_field->Active = 1;
            $this->db_field->Description = $this->description;
            $this->db_field->Value = $this->defaultValue;
            $this->db_field->Type = $this->type;

            // Set field localization if necessary
            if (isset($this->locale)) {
                $this->db_field->local = 1;
            }

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
     * Create materialfield record
     *
     * @param string $value Field value for materialfield table
     *
     * @internal param \samson\parse\strind $field_id Field identifier in field table
     * @internal param string $material_id Material identifier in material table
     * @return \samson\activerecord\materialfield MaterialField table object
     */
    public function & parser($value)
    {
        $mf = new \samson\activerecord\MaterialField(false);
        $mf->FieldID 		= $this->db_field->id;
        $mf->MaterialID 	= $this->material->result->id;

        // If this is numeric field
        if ($this->type == 7) {
            $mf->numeric_value = $value;
        } else { // Other fields
            // If this is wysiwyg field
            if ($this->type == 8) {
                if (is_object($value)) {
                    //trace('ogject!!!!!!!!');
                    //trace($value);
                    $tempValue = '';
                    $elements = $value->getRichTextElements();

                    foreach ($elements as $item) {
                        $excelFont = $item->getFont();
                        $bold = false;
                        if (isset($excelFont)) {
                            if ($excelFont->getBold()) {
                                $bold = true;
                            }
                        }
                        if (!$bold){
                            $tempValue .= $item->getText();
                        } else{
                            $tempValue .= '<b>'.$item->getText().'</b>';
                        }

                    }
                    $value = $tempValue;
                }
                $mf->Value = nl2br($value);
            } else {
                $mf->Value 			= $value;
            }
        }

        $mf->Active 		= 1;
        $mf->locale 		= isset($this->locale) ? $this->locale : '';
        $mf->save();

        return $mf;
    }
}
