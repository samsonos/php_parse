<?php
namespace samson\parse;

class SamsonCMS
{
	/**
	 * 
	 * @param unknown $name
	 */
	public static function field_find( $name, $field = 'Name' )
	{
		return dbQuery('\samson\cms\cmsfield')->cond( $field, $name )->first();
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
		foreach ($obj->children as $child )
		{
			$struct_ids[] = $child->StructureID;
	
			self::structure_ids( $child, $struct_ids );
		}
	}
	
	/**
	 * Recursively create CMSNavigation tree and relations with materials
	 * @param string $nested_a 	CMSNav tree
	 * @param array $parents	Array of CMSNavs parents chain
	 */
	public static function structure_create( $nested_a, array $parents = array() )
	{
		// Iterate structures array
		foreach ( $nested_a as $k => $v )
		{
			// create structure
			if( is_array($v) )
			{	
				//if( !ifcmsnav($k, $s, 'Name') )					
				$s = new \samson\activerecord\Structure(false);					
				$s->Name = $k;
				$s->Active = 1;
				$s->Url = utf8_translit($k);				
				
				// If parents chain is specified and has data
				if( sizeof( $parents ) )
				{
					// Get last element from parents chain
					$parent = end( $parents  );
	
					// Set parent id
					$s->ParentID = $parent->id;
				}
					
				// Save structure to db
				$s->save();
				
					
				// Add new created structure object to parents chain
				array_push( $parents, $s );
					
				// Recursion
				self::structure_create( $v, $parents );
					
				// Remove added element from parents chain
				array_pop( $parents );
			}
			else
			{
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
	
			// convert array to string, for prepare it to SQL query
			$material_ids = implode (', ', $material_ids);
	
			// delete all old material			
			db()->simple_query('DELETE FROM material WHERE MaterialID IN ('.$material_ids.')');
			db()->simple_query('DELETE FROM materialfield WHERE MaterialID IN ('.$material_ids.')');
			db()->simple_query('DELETE FROM structure WHERE StructureID IN ('.$struct_ids.') AND StructureID != '.$structure->id);
			db()->simple_query('DELETE FROM structurematerial WHERE MaterialID IN ('.$material_ids.')');
		}		
	}
}