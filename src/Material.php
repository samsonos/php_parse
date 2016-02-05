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


	/** @var MaterialField[] Collection of materialfield table object parsers */
	protected $fields = array();

    /** @var int $fieldsCounter Counter to enable multiple same number columns usage */
    private $fieldsCounter;

	/** string[] Structure tree array */
    public $structures = array();

    public $uniqueArray = array();

    public $findByField = array();

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

    public function __construct ($idx, $parser = null, $successHandler = null, $allowEmptyValues = false, array $uniqueArray = array(), array $findByField = array())
    {
        parent::__construct($idx, $parser, $successHandler, $allowEmptyValues);
        if (empty($uniqueArray)) {
            $this->uniqueArray = dbQuery('material')->fields('Name');
        } else {
            $this->uniqueArray = $uniqueArray;
        }

        $this->findByField = $findByField;
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
     * @param string   $value       Default value
     *
     * @return \samson\parse\Material Chaining
     */
	public function field( $idx, $field, $parser = null, $structure = null, $description = '', $type = 0, $value = null )
	{
        // If array is passed, not index
        if (is_array($idx)) {
            // Iterate localized columns
            foreach ($idx as $locale => $column) {
                // Create materialfield table object parser
                $this->fields[$this->fieldsCounter] = new MaterialField($column, $field, $this, $parser, $structure, $description, $type, $value, $locale);
                // Increase fields count
                $this->fieldsCounter++;
            }
        } else { // Not localized material
            $this->fields[$this->fieldsCounter] = new MaterialField($idx, $field, $this, $parser, $structure, $description, $type, $value);
            // Increase fields count
            $this->fieldsCounter++;
        }

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
        return $this->field($idx, $field, $parser, $structure, $description);
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
        return $this->field($idx, $field, $parser, $structure, '', 8);
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
        return $this->field($idx, $field, $parser, $structure, $description, 1);
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
        return $this->field($idx, $field, $parser, $structure, $description, 7);
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
        return $this->field($idx, $field, $parser, $structure, $description, 3);
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
        return $this->field($idx, $field, $parser, $structure, $description, 4, $value);
    }

    /**
     * Create gallery material parser for material parser
     * @param integer   $idx    Column index to parse
     * @param string    $path   Path to find images
     * @param callable  $parser External column parser
     * @param string    $token  Column image splitter
     *
     * @return \samson\parse\Material Chaining
     */
    public function gallery($idx, $path = 'cms/upload/', $parser = null, $token = ',')
    {
        // Create new gallery parser
        $this->fields[$this->fieldsCounter] = new Gallery($idx, $this, $parser, $token, $path );
        // Increase fields count
        $this->fieldsCounter++;

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
     * @param string $name Material unique identifier
     * @param null   $url
     * @param int    $published
     * @param int    $active
     * @param int    $user_id
     *
     * @see \samson\parse\ColumnParser::parser()
     * @return \samson\activerecord\material Material table object
     */
	public function & parser($name, $data, $url = null, $published = 1, $active = 1, $user_id = null)
	{
        $m = null;

        if ($this->allowEmptyValues || !isset($this->uniques[$name])) {
            $this->uniques[$name] = '';
            $inArrayValue = isset($this->findByField['index']) ? $data[$this->findByField['index']] : $name;

            if (!in_array($inArrayValue, $this->uniqueArray)) {
                $m = new \samson\activerecord\material(false);
                $m->Name = $name;
                $m->Url  = $this->url_prefix.utf8_translit($name);
                $m->Published 	= $published;
                $m->Active 		= $active;
                $m->UserID 		= !isset($user_id) ? Excel2::$user->id : $user_id;
                $m->save();
            } elseif (!empty($this->findByField)) {
                $m = dbQuery('material')->cond($this->findByField['value'], $data[$this->findByField['index']])->first();
                if (!$m) {
                    $m = new \samson\activerecord\material(false);
                }
                $m->Name = $name;
                $m->Url  = $this->url_prefix.utf8_translit($name);
                $m->Published 	= $published;
                $m->Active 		= $active;
                $m->UserID 		= !isset($user_id) ? Excel2::$user->id : $user_id;
                $m->save();
            }
        } else { // Trigger duplicate warning
            //e('Found duplicate material by ## at ##', D_SAMSON_DEBUG, array($value, $row_idx));
        }

        return $m;
	}

	/** @see \samson\parse\ColumnParser::success() */
	public function success(array $data, $row_idx, $value)
	{
        // Check if we have received material object
        if ($this->result instanceof \samson\activerecord\dbRecord ) {

            // Iterate material field parsers
            foreach ($this->fields as $f) {
                if (!$f->parse($data, $row_idx)) {
                    // Error handling
                }
            }

        } else { // Trigger error
            //return e('Cannot parse row ##, Material has not been created! ##',E_SAMSON_FATAL_ERROR, array($row_idx, $this->result));
        }

		return $this->result;
	}
}