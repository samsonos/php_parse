<?php
/**
 * Created by Pavlo Onysko <onysko@samsonos.com>
 * on 29.09.2014 at 11:37
 */
 namespace samson\parse;

 /*use SimpleExcel\SimpleExcel;
 use SimpleExcel\Spreadsheet\Worksheet;*/

/**
 * SamsonPHP parse SimpleExcel adapter
 * @author Egorov Vitaly <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 
 */
class SimpleExcelAdapter implements iAdapter
{
    /** @var Current document worksheet */
    protected $objWorksheet;

    /** @var  integer Rows count */
    protected $recordsCount;

    /** @var  integer Columns count */
    protected $columnsCount;

    public function __construct($filename)
    {
        /*// instantiate new object (will automatically construct the parser & writer type as XML)
        $excel = new SimpleExcel();

        // load an XML file from server to be parsed
        $excel->loadFile($filename, pathinfo($filename, PATHINFO_EXTENSION));

        // Get current worksheet
        $this->objWorksheet = $excel->getWorksheet(1);

        // Get all records
        $records = $this->objWorksheet->getRecords();

        // Get dimensions
        $this->recordsCount = sizeof($records);
        $this->columnsCount = sizeof($records[0]);*/
    }

    /**
     * Get current worksheet rows count
     * @return mixed Count of rows in excel document
     */
    public function getRowsCount()
    {
        return $this->recordsCount;
    }

    /**
     * Get current worksheet columns count
     * @return int
     * @throws \PHPExcel_Exception
     */
    public function getColumnsCount()
    {
        return $this->columnsCount;
    }

    /**
     * Retrieve cell value
     * @param integer $column
     * @param integer $row
     *
     * @return mixed Cell value
     */
    public function getValue($column, $row)
    {
        return $this->objWorksheet->getCell($row, $column);
    }
}
