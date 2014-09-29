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
        if (file_exists($filename)) {
            $this->file = file($filename);
        }

        $this->delimiter = $delimiter;
    }

    public function getRowsCount()
    {
        return sizeof($this->file);
    }

    public function getColumnsCount()
    {
        return sizeof(str_getcsv($this->file[2], ';'));
    }

    /**
     * @param $column
     * @param $row
     *
     * @return mixed
     */
    public function getValue($column, $row)
    {
        $currentRow = $this->file[$row];
        $currentColumn = str_getcsv($currentRow, ';');
        return trim($currentColumn[$column]);
    }
}
