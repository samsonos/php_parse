<?php
namespace samson\parse;

class SamsonCMS
{
    /**
     * Generic SamsonCMS database entity finder
     * @param string    $entity             Database entity full name
     * @param mixed     $searchParameter    Database parameter value for search
     * @param \samson\activerecord\dbRecord $returnValue On success found database record
     *
     * @return bool True if database record is found
     */
    public static function find($entity, & $searchParameter, & $returnValue = null)
    {
        $entity = '\samson\cms\cmsfield';
        switch(gettype($searchParameter)) {
            case 'null': return false;
            case 'string':  return dbQuery($entity)->cond('Name', $searchParameter )->first($returnValue);
            case 'integer': return dbQuery($entity)->cond('FieldID', $searchParameter )->first($returnValue);
            case 'object':  $returnValue = & $searchParameter; return true;
            default: return e('Cannot find ## record using ##', E_SAMSON_ACTIVERECORD_ERROR, array($entity, $searchParameter));
        }
    }

    /**
     * Find field record
     * @param string $name  Search parameter value
     * @param string $field Search parameter name
     *
     * @return \samson\activerecord\field Structure record
     */
	public static function field_find( $name, $field = 'Name' )
	{
		return dbQuery('\samson\cms\cmsfield')->cond( $field, $name )->first();
	}

    /**
     * Find structure record
     * @param string $name  Search parameter value
     * @param string $field Search parameter name
     *
     * @return \samson\activerecord\structure Structure record
     */
    public static function structure_find( $name, $field = 'Name' )
    {
        return dbQuery('\samson\cms\cmsnav')->cond( $field, $name )->first();
    }

    /**
     * Find structurefield record
     * @param string $name  Search parameter value
     * @param string $field Search parameter name
     *
     * @return \samson\activerecord\structurefield StructureField record
     */
    public static function structurefield_find( $name, $field = 'Name' )
    {
        return dbQuery('\samson\cms\cmsnavfield')->cond( $field, $name )->first();
    }

    /**
     * Create structurefield database record
     * @param \samson\activerecord\structure $structure Pointer to structure record
     * @param \samson\activerecord\field     $field     Pointer to field record
     *
     * @return \samson\activerecord\structurefield Create StructureField record
     */
    public static function structurefield_create(\samson\activerecord\structure $structure, \samson\activerecord\field & $field)
    {
        // Connect material field to structure
        $sf = new \samson\activerecord\structurefield(false);
        $sf->StructureID = $structure->StructureID;
        $sf->FieldID = $field->id;
        $sf->Active = 1;
        $sf->save();

        return $sf;
    }


	/**
	 * Create field table object
	 * @param string $name Field name
	 * @return \samson\activerecord\field Field table object
	 */
	public static function field_create( $name )
	{
		$f = new \samson\activerecord\Field(false);		
		$f->Name = $name;		
		$f->Active = 1;
		$f->save();
	
		return $f;
	}
	
	/**
	 * Get all nested structure id's for structure
	 * @param \samson\cms\CMSNav $obj that contains all nested structures
	 * @param array $struct_ids contain all structures ID
	 */
	public static function structure_ids( \samson\cms\CMSNav $obj, & $struct_ids = array() )
	{
		foreach ($obj->children() as $child )
		{
			$struct_ids[] = $child->StructureID;
	
			self::structure_ids( $child, $struct_ids );
		}
	}

    /**
     * Recursively create CMSNavigation tree and relations with materials
     *
     * @param string $nested_a CMSNav tree
     * @param array  $parents  Array of CMSNavs parents chain
     * @param null   $user
     */
	public static function structure_create( $nested_a, array $parents = array(), & $user = null )
	{
		// Iterate structures array
		foreach ( $nested_a as $k => $v )
		{
			// create structure
			if( is_array($v))
			{
                /** @var \samson\activerecord\Structure $s */
                $s = null;
                $url = utf8_translit($k);

                // Try to find structure by url
                if (!dbQuery('structure')->Active(1)->Url($url)->first($s)){

                    // Create new structure
                    $s = new \samson\activerecord\Structure(false);
                    $s->Name = $k;
                    $s->Active = 1;
                    $s->Url = $url;
                    $s->UserID = $user->id;

                    // Save structure to db
                    $s->save();
                }

                // If parents chain is specified and has data
                if( sizeof( $parents ) )
                {
                    // Get last element from parents chain
                    $parent = end($parents);

                    /** @var \samson\activerecord\structure_relation $str_related */
                    if ($parent->id != $s->id) {
                        $str_related = new \samson\activerecord\structure_relation(false);
                        $str_related->parent_id = $parent->id;
                        $str_related->child_id = $s->id;
                        $str_related->save();
                    }
                }

                // Add new created structure object to parents chain
                array_push( $parents, $s );

                // Recursion
                self::structure_create( $v, $parents, $user );

                // Remove added element from parents chain
                array_pop( $parents );

			} else {
				// save structure material
				foreach ( $parents as $parent )
				{
					$sm = new \samson\activerecord\StructureMaterial (false);
					$sm->MaterialID = $v->MaterialID;
					$sm->StructureID = $parent['StructureID'];
					$sm->Active = 1;
					$sm->save();
				}
            }
		}
	
	}
	
	/**
	 * Clear all SamsonCMS data by CMSNav: CMSMaterial, CMSNavMaterial, CMSMaterialField
	 * @param \samson\cms\CMSNav $structure must contain structure of witch you wanna delete material
	 */
	public static function structure_clear(\samson\cms\CMSNav $structure)
	{		
		$struct_ids = array( $structure->id );

		// get array that contain structure ids
		self::structure_ids( $structure, $struct_ids );

        // If we have found nested structure ids
		if( isset($struct_ids) )
		{
			// convert array to string, for prepare it to SQL query
			$struct_ids = implode (', ', $struct_ids);		
	
			// get array that contain material ids
			$material_ids =	dbQuery('samson\activerecord\structurematerial')
				->StructureID($struct_ids)
				->group_by('MaterialID')
			->fields('MaterialID');

            // Clear all structures
            db()->simple_query('DELETE FROM structure WHERE StructureID IN ('.$struct_ids.') AND StructureID != '.$structure->id);
            // Clear all structure relations
            db()->simple_query('DELETE FROM structure_relation WHERE parent_id IN ('.$struct_ids.')');
            // Clear all structure field relations
            db()->simple_query('DELETE FROM structurefield WHERE StructureID = '.$structure->id);

            // If we have found materials
            if(sizeof($material_ids)) {

                // convert array to string, for prepare it to SQL query
                $material_ids = implode (', ', $material_ids);

                // delete all old material
                db()->simple_query('DELETE FROM material WHERE MaterialID IN ('.$material_ids.')');
                db()->simple_query('DELETE FROM gallery WHERE MaterialID IN ('.$material_ids.')');
                db()->simple_query('DELETE FROM materialfield WHERE MaterialID IN ('.$material_ids.')');
                db()->simple_query('DELETE FROM structurematerial WHERE MaterialID IN ('.$material_ids.')');
                db()->simple_query('DELETE FROM related_materials WHERE first_material IN ('.$material_ids.')');
            }
		}		
	}
}