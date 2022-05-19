<?php

function sortItems($items, $table, $orderColName, $dataBase)
{
    foreach ($items as $item) {
        $query = "update $table set `$orderColName`=? where id=?";
        $stmt = $dataBase->db->prepare($query);
        $stmt->execute(array($item[$orderColName], $item['id']));
    }
    return true;
}
