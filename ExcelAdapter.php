<?php
/**
 * Created by Pavlo Onysko <onysko@samsonos.com>
 * on 29.09.2014 at 11:37
 */
 namespace samson\parse;

/**
 *
 * @author Pavlo Onysko <onysko@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 
 */
class ExcelAdapter implements iAdapter
{
    protected $mergedCellsRange;

    protected $objWorksheet;

    public function __construct($filename)
    {
        // Convert extension of file to extension that need for parser
        $extension = $this->get_extension($filename);

        $objReader = \PHPExcel_IOFactory::createReader($extension);
        $objReader->setReadDataOnly(false);

        $objPHPExcel = $objReader->load($filename);

        $this->objWorksheet = $objPHPExcel->getActiveSheet();

        // Get all merged cells
        $this->mergedCellsRange = $this->objWorksheet->getMergeCells();
    }

    /**
     * Convert extension of file to extension that need for parser
     * @param $file_name string name of file that you wanna parse
     * @return string extension that need function parse_excel
     */
    private function get_extension($file_name){

        // get extension of file, by php built-in function
        $extension = pathinfo($file_name);
        $extension = $extension['extension'];

        switch ($extension) {
            case 'xlsx': $extension = 'Excel2007'; break;
            case 'xls': $extension = 'Excel5'; break;
            case 'ods': $extension = 'OOCalc'; break;
            case 'slk': $extension = 'SYLK'; break;
            case 'xml': $extension = 'Excel2003XML'; break;
            case 'gnumeric': $extension = 'Gnumeric'; break;
            default: echo 'This parser read file with extention: xlsx, xls, ods, slk, xml, gnumeric';
        }
        return $extension;
    }

    /**
     * @return mixed Count of rows in excel document
     */
    public function getRowsCount()
    {
        return $this->objWorksheet->getHighestDataRow();
    }

    /**
     * @return int
     * @throws \PHPExcel_Exception
     */
    public function getColumnsCount()
    {
        $highestColumn = $this->objWorksheet->getHighestColumn();
        return \PHPExcel_Cell::columnIndexFromString($highestColumn);
    }

    /**
     * @param $column
     * @param $row
     *
     * @return mixed
     */
    public function getValue($column, $row)
    {
        $cell = $this->objWorksheet->getCellByColumnAndRow($column, $row);
        // Find if this is cell is merged with others

        foreach ($this->mergedCellsRange as $currMergedRange) {
            if ($cell->isInRange($currMergedRange)) {
                $currMergedCellsArray = \PHPExcel_Cell::splitRange($currMergedRange);
                $cell = $this->objWorksheet->getCell($currMergedCellsArray[0][0]);
                break;
            }
        }

        return $cell->getValue();
    }
}
