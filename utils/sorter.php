<?php

function sortItems($items, $table, $orderColName)
{
    foreach ($items as $item) {
        $query = "update $table set $orderColName=? where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($item[$orderColName], $item['id']));
    }
    return true;
}