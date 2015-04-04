<?php
/**
 * Created by Pavlo Onysko <onysko@samsonos.com>
 * on 29.09.2014 at 12:51
 */
 namespace samson\parse;

/**
 *
 * @author Pavlo Onysko <onysko@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 
 */
class CSVAdapter implements iAdapter
{
    public $delimiter;

    public $file;

    public function __construct($filename, $delimiter = ';')
    {
        $this->delimiter = $delimiter;
        if (file_exists($filename)) {

            $this->handle = fopen($filename, 'r');

            while ($data = fgetcsv($this->handle, null, $this->delimiter)) {
                $this->file[] = $data;
            }
        }
    }

    public function getRowsCount()
    {
        return sizeof($this->file);
    }

    public function getColumnsCount()
    {
        return sizeof($this->file[2]);
    }

    /**
     * @param $column
     * @param $row
     *
     * @return mixed
     */
    public function getValue($column, $row)
    {
        return trim($this->file[$row][$column]);
    }

    public function  __destruct()
    {
        fclose($this->handle);
    }
}
