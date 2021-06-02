<?php


namespace App;


class ColumnList
{
    const itemSortingBy = ['id' => 'i.id',
        'title' => 'i.title %order%, i.id DESC',
        'comment' => 'i.comment %order%, i.id DESC',
        'count' => 'cast(substring(i.count, \'\d+\') AS Integer) %order%, i.id DESC',
        'created_at' => 'i.createdAt %order%, i.id DESC',
        'updated_at' => 'i.updatedAt %order%, i.id DESC',
        'profile' => 'i.profile %order%, i.id DESC',
        'number' => 'i.number %order%, i.id DESC',
        'price' => 'i.price %order%, i.id DESC',
        'category' => 'i.category %order%, i.id DESC'];
}