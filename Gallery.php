<?php
namespace samson\parse;

/**
 * Parser for integrating SamsonCMS gallery table object creation
 * @author Iegorov Vitaly <egorov@samsonos.com>
 */
class Gallery extends ColumnParser
{
    /**
     * Pointer to parent material
     * @var Material
     */
	protected $material;

    /** @var  Base upload path */
    protected $uploadPath;

    /** @var  Column image splitter token */
    protected $token;

    /**
     * Constructor
     *
     * @param integer  $idx         Column index for parsing
     * @param Material $material    Pointer to parent material parser
     * @param callable $parser      External parser routine
     * @param string   $token       Token for getting multiple images from column
     * @param string   $uploadPath  Path to current image location and storing
     */
	public function __construct($idx, Material & $material, $parser = null, $token = ',', $uploadPath = 'cms/upload/')
	{
		// Save connection to material
		$this->material = & $material;
        $this->token = $token;
        $this->uploadPath = $uploadPath;
		
		// Call parent 
		parent::__construct($idx, $parser);
    }

    /**
     * Create gallery record
     *
     * @param string $value Gallery image name
     * @return \samson\activerecord\gallery Gallery record
     */
	public function & parser($value)
	{
        elapsed('Parsing gallery '.$value);
        // Try to split value using passed token
        foreach (explode($this->token, $value) as $photo) {
            // Rewrite common mistakes, trim photo name
            $photo = trim(str_replace(array('. png', ' png'), '.png', $photo));
            $photo = str_replace(array('. jpg', ' jpg'), '.jpg', $photo);
            $photo = str_replace(array('. gif', ' gif'), '.gif', $photo);

            // Build full path to photo file
            $path = $this->uploadPath.$photo;

            // Prepare data for case insensitive file search
            $directoryName = dirname($path);
            $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
            $fileNameLowerCase = strtolower($path);
            $found = false;

            // Iterate all location files
            foreach($fileArray as $file) {
                // If lowercase variant matches
                if(strtolower($file) == $fileNameLowerCase) {

                    // Normalize photo name
                    $normalizedPhoto = str_replace(' ', '_', strtolower($photo));

                    // If scale module is configured
                    if(isset(s()->module_stack['scale'])) {
                        // Resize image
                        m('scale')->resize($file, $normalizedPhoto, $this->uploadPath);
                    }

                    $gallery = new \samson\activerecord\gallery(false);
                    $gallery->Path 	        = $photo;
                    $gallery->Name 	        = $photo;
                    $gallery->Src 	        = $file;
                    $gallery->Loaded 	    = date('Y-m-d h:i:s');
                    $gallery->MaterialID 	= $this->material->result->id;
                    $gallery->Active 		= 1;
                    $gallery->save();

                    $found = true;
                    break;
                }
            }

            // Signal if file was not found
            if(!$found) {
                e('Image file: "##" - not found', D_SAMSON_DEBUG, $path);
            }
        }

		return $gallery;
	}
}
