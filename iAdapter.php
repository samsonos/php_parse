<?php
/**
 * Created by Pavlo Onysko <onysko@samsonos.com>
 * on 29.09.2014 at 11:21
 */
 

namespace samson\parse;


interface iAdapter
{
    public function getRowsCount();

    public function getColumnsCount();

    /**
     * @param $column
     * @param $row
     *
     * @return mixed
     */
    public function getValue($column, $row);
}
